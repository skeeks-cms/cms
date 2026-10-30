<?php
/**
 * П2а on the stand database, rolled back: the duplicate check CrmMatcher
 * (all evidence, strong and weak, public mail domains, hidden records
 * masked), sorting of «Неразобранное» (accept with a new company and person
 * linked to each other, add to an open deal and a repeated source event that
 * lands there, support task, spam out of the report), a deal for a client of
 * another responsible (the deal goes to him, the actor stays the initiator,
 * a request to the responsible and his leaders, approval gives the deal),
 * "Это другая компания" and the department leaders rule.
 *
 * CRM_TRIAGE_STAND_TEST=local php tests/crm-lead-triage-stand.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CRM_TRIAGE_STAND_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_TRIAGE_STAND_TEST=local.');
}
define('ROOT_DIR', getenv('TEST_APP_ROOT') ?: '/app');
define('YII_ENV', 'dev');
define('YII_DEBUG', true);
require ROOT_DIR.'/vendor/skeeks/cms/bootstrap.php';
$config = new Yiisoft\Config\Config(new Yiisoft\Config\ConfigPaths(ROOT_DIR, 'config'), null, [Yiisoft\Config\Modifier\RecursiveMerge::groups('web', 'web-'.ENV, 'params', 'params-web-'.ENV)], 'params-web-'.ENV);
$c = $config->get($config->has('web-'.ENV) ? 'web-'.ENV : 'web');
$c['components']['request']['scriptFile'] = ROOT_DIR.'/frontend/web/index.php';
$c['components']['request']['scriptUrl'] = '/index.php';
$c['components']['request']['hostInfo'] = 'http://localhost';
$c['components']['user']['enableSession'] = false;
new yii\web\Application($c);
Yii::$app->errorHandler->unregister();

use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmAccessRequests;
use skeeks\cms\crm\CrmMatcher;
use skeeks\cms\crm\LeadIdentityForm;
use skeeks\cms\crm\LeadTriage;
use skeeks\cms\crm\TriageEvent;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsCrmDuplicateMark;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsWebNotify;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\services\CmsLeadService;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};

$transaction = Yii::$app->db->beginTransaction();
try {
    $site = \skeeks\cms\models\CmsSite::find()->default()->one();
    Yii::$app->skeeks->site = $site;
    $siteId = (int)$site->id;

    $workers = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->all();
    $admin = null;
    $plain = [];
    foreach ($workers as $worker) {
        if (Yii::$app->authManager->checkAccess((int)$worker->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            $admin = $admin ?? $worker;
        } elseif (Yii::$app->authManager->checkAccess((int)$worker->id, 'cms/admin-lead')) {
            $plain[] = $worker;
        }
    }
    $check($admin !== null && count($plain) >= 2, 'the stand needs an administrator and two employees with the deals right');
    [$owner, $other] = [$plain[0], $plain[1]];
    $as = static function (CmsUser $user) {
        Yii::$app->user->setIdentity($user);
    };
    $uniq = (string)mt_rand(100000, 999999);
    $phone = '+7 999 '.substr($uniq, 0, 3).'-'.substr($uniq, 3, 2).'-'.substr($uniq, 5, 1).'7';
    $email = 'p2a-'.$uniq.'@romashka-'.$uniq.'.test';

    // A company of the owner with a phone, an email and a site.
    $company = new CmsCompany(['name' => 'ООО «Ромашка '.$uniq.'»']);
    $check($company->save(), 'company saved');
    // Its creator became the main one (п. 13а); the fixture belongs to the owner only.
    CmsCompany2manager::deleteAll(['cms_company_id' => $company->id]);
    (new CmsCompany2manager(['cms_company_id' => $company->id, 'cms_user_id' => $owner->id]))->save(false);
    (new CmsCompanyPhone(['cms_company_id' => $company->id, 'value' => $phone]))->save(false);
    (new \skeeks\cms\models\CmsCompanyEmail(['cms_company_id' => $company->id, 'value' => 'info@romashka-'.$uniq.'.test']))->save(false);

    // --- CrmMatcher ---
    $matcher = CrmMatcher::instance();
    $raw = $matcher->rawFind(['siteId' => $siteId, 'phones' => ['8'.substr(preg_replace('/\D/', '', $phone), 1)], 'emails' => [$email, 'someone@gmail.com'], 'name' => 'Ромашка '.$uniq]);
    $hit = $raw['company:'.$company->id] ?? null;
    $check($hit !== null && $hit['strong'], 'phone in another notation finds the company');
    $by = array_column($hit['reasons'], 'by');
    $check(in_array('phone', $by, true) && in_array('domain', $by, true) && in_array('name', $by, true), 'all evidence: phone, domain of the email, name without the legal form');
    $check(!array_filter($raw, static fn($c) => in_array('gmail.com', array_column($c['reasons'], 'value'), true)), 'a public mail domain is not evidence');
    $weak = $matcher->rawFind(['siteId' => $siteId, 'name' => 'ромашка '.$uniq]);
    $check(isset($weak['company:'.$company->id]) && !$weak['company:'.$company->id]['strong'], 'a name alone is weak evidence');

    $presented = $matcher->present([$hit], $other, $siteId)[0];
    $check(!$presented['visible'] && $presented['id'] === null && $presented['title'] === $company->name, 'hidden company: name, no id');
    $check(array_column($presented['responsible'], 'id') === [(int)$owner->id], 'hidden company: its responsible');
    $check(CrmAccess::instance()->parseCandidateRef($presented['ref']) === ['company', (int)$company->id], 'signed reference parses back');
    $check(CrmAccess::instance()->parseCandidateRef('company:'.$company->id.':0000000000000000') === null, 'forged reference refused');
    $check($matcher->present([$hit], $owner, $siteId)[0]['visible'], 'the owner sees his company');

    // --- the client fields of the deal form, as in Bitrix24: the dropdown and «Есть совпадения» ---
    $render = static function (string $view, string $line, string $for, CmsUser $viewer) use ($matcher, $siteId): string {
        $found = $matcher->searchLine($line, $for, $viewer, $siteId);
        return Yii::$app->view->renderFile('@skeeks/cms/views/admin-cms-lead/'.$view.'.php', [
            'candidates' => $found['candidates'], 'for' => $for, 'parsed' => $found['parsed'], 'query' => $line, 'allowForeign' => true,
        ]);
    };
    $drop = $render('_identity-dropdown', 'Ромашка '.$uniq, 'company', $other);
    $check(strpos($drop, 'Такая компания уже есть') !== false && strpos($drop, 'Работать с этим клиентом') !== false && strpos($drop, 'data-action="work"') !== false
        && strpos($drop, 'Запросить') === false, 'dropdown: a company of another responsible offers «Работать с этим клиентом», no requests');
    $check(preg_match('/data-sx-identity-create="company"[^>]*>\s*<span[^>]*>\s*<i[^>]*><\/i>\s*Создать новую компанию «Ромашка '.$uniq.'»/u', $drop) === 1
        && strrpos($drop, 'sx-lead-drop--create') > strrpos($drop, 'sx-lead-drop--found'), 'dropdown: «+ создать новую компанию «…»» is always last');
    $check(strpos($render('_identity-dropdown', 'Нет такой '.$uniq, 'company', $other), 'Создать новую компанию «Нет такой '.$uniq.'»') !== false,
        'dropdown: nothing found — creating is offered');
    $hidden = $render('_identity-check', 'Новая '.$uniq.', '.$phone, 'company', $other);
    $check(strpos($hidden, 'Есть совпадения: 1') !== false && strpos($hidden, '1 совпадение по телефону') !== false, 'check: the count and the group by phone');
    $check(strpos($hidden, '<b>'.$phone.'</b>') !== false && strpos($hidden, 'info@romashka-'.$uniq) === false && strpos($hidden, 'Использовать') === false,
        'check: a hidden company shows only the matched phone, no «Использовать»');
    $check(strpos($hidden, 'data-sx-identity-duplicate') !== false && strpos($hidden, 'Работать с этим клиентом') !== false, 'check: «Работать с этим клиентом» and «Это другая компания»');
    $own = $render('_identity-check', 'Новая '.$uniq.', '.$phone, 'company', $owner);
    $check(strpos($own, 'Использовать') !== false && strpos($own, '<b>'.$phone.'</b>') !== false && strpos($own, 'info@romashka-'.$uniq.'.test') !== false
        && strpos($own, '<b>info@romashka') === false, 'check: an own company — its contacts, the matched one in bold, «Использовать»');
    $check($render('_identity-check', 'Пусто '.$uniq.', +7 900 '.$uniq, 'company', $other) === '', 'check: no matches — no popover');

    // After «Новая сделка» is saved its card opens (a window goes on to it, a page follows it), never the filled form again.
    [$leadController] = Yii::$app->createController('cms/admin-cms-lead');
    $createAction = $leadController->triageActions()['create'];
    $callback = (string)($createAction['activeFormConfig']['clientCallback'] ?? '');
    $check(strpos($callback, "'model-create'") !== false && strpos($callback, "isCard ? 'apply' : 'save'") !== false
        && strpos($callback, 'window.location.href') !== false && strpos(stripslashes($callback), 'admin-cms-lead/view') !== false,
        'the create form opens the card of the new deal instead of keeping the form');

    // --- an unsorted deal from a form, accepted with a new company and person ---
    $service = new CmsLeadService();
    $incoming = $service->createFromSource(['cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM, 'source_ref' => 'p2a-'.$uniq.'-1',
        'name' => 'Заявка '.$uniq, 'phones' => ['+7 912 '.$uniq.'1'], 'emails' => ['new-'.$uniq.'@client-'.$uniq.'.test'], 'status' => CmsLead::STATUS_NEW]);
    $check($incoming->status === CmsLead::STATUS_NEW && !$incoming->executor_id, 'a form deal lands in «Неразобранное»');
    $check(!CrmAccess::instance()->canTriage($other->id, $incoming), 'a deal without evidence is not sorted by an ordinary employee');
    $check(CrmAccess::instance()->canTriage($admin->id, $incoming), 'an administrator sorts it');

    $triaged = [];
    \yii\base\Event::on(CmsLead::class, CmsLead::EVENT_TRIAGED, static function (TriageEvent $event) use (&$triaged) {
        $triaged[] = $event->result;
    });
    $form = new LeadIdentityForm(['company_mode' => 'new', 'company_name' => 'Новая '.$uniq, 'client_mode' => 'new', 'client_first_name' => 'Ирина',
        'executor_id' => $other->id]);
    $phoneId = (int)$incoming->phones[0]->id;
    $form->contacts = ['phone:'.$phoneId => 'company'];
    // П2г: addresses, links and requisites of the request go to the company the deal gets.
    (new \skeeks\cms\models\CmsLeadAddress(['cms_lead_id' => $incoming->id, 'value' => 'Проверка: адрес '.$uniq, 'latitude' => 55.75, 'longitude' => 37.61]))->save(false);
    (new \skeeks\cms\models\CmsLeadLink(['cms_lead_id' => $incoming->id, 'url' => 'https://p2g-'.$uniq.'.test', 'link_type' => \skeeks\cms\models\CmsCompanyLink::TYPE_OTHER]))->save(false);
    $requisite = new \skeeks\cms\models\CmsContractor(['cms_site_id' => $siteId, 'contractor_type' => 'legal', 'name' => 'Проверка '.$uniq, 'inn' => '77'.substr(str_pad($uniq, 8, '0'), 0, 8)]);
    $requisiteSaved = $requisite->save(false);
    if ($requisiteSaved) {
        (new \skeeks\cms\models\CmsLead2contractor(['cms_lead_id' => $incoming->id, 'cms_contractor_id' => $requisite->id]))->save(false);
    }
    $incoming = CmsLead::findOne($incoming->id);
    $result = (new LeadTriage())->accept($incoming, $form, (int)$admin->id);
    $incoming->refresh();
    $check($incoming->status === CmsLead::STATUS_IN_WORK && (int)$incoming->executor_id === (int)$other->id && (int)$incoming->triaged_by === (int)$admin->id, 'accepted: in work, the chosen responsible, who sorted');
    $check(CmsCompany2user::find()->andWhere(['cms_company_id' => $incoming->cms_company_id, 'cms_user_id' => $incoming->cms_user_id])->exists(), 'the new person is linked to the new company');
    $check(CmsCompanyPhone::find()->andWhere(['cms_company_id' => $incoming->cms_company_id])->count() == 1, 'the chosen contact went to the company card');
    $check(count($incoming->phones) === 1, 'the contact stays with the deal («Из обращения»)');
    $check(\skeeks\cms\models\CmsCompanyAddress::find()->andWhere(['cms_company_id' => $incoming->cms_company_id, 'value' => 'Проверка: адрес '.$uniq])->exists()
        && \skeeks\cms\models\CmsCompanyLink::find()->andWhere(['cms_company_id' => $incoming->cms_company_id, 'url' => 'https://p2g-'.$uniq.'.test'])->exists(), 'the address and the link of the request went to the new company');
    $check(!$requisiteSaved || \skeeks\cms\models\CmsCompany2contractor::find()->andWhere(['cms_company_id' => $incoming->cms_company_id, 'cms_contractor_id' => $requisite->id])->exists(), 'the requisites of the request are linked to the new company');
    $newClient = CmsUser::findOne($incoming->cms_user_id);
    $check($newClient && !$newClient->is_worker && !$newClient->password_hash, 'the person gets no password');
    $check($triaged === ['accepted'], 'EVENT_TRIAGED after commit');

    // --- add to an open deal; the repeated event lands there ---
    $repeat = $service->createFromSource(['cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM, 'source_ref' => 'p2a-'.$uniq.'-2',
        'name' => 'Повтор '.$uniq, 'phones' => ['+7 912 '.$uniq.'1', '+7 913 '.$uniq.'1'], 'status' => CmsLead::STATUS_NEW]);
    $raw = $matcher->rawFind($matcher->inputOfLead($repeat));
    $check(in_array((int)$incoming->id, $matcher->repeatsOf($raw, $siteId, [(int)$repeat->id]), true), 'the open deal of the client is a repeat candidate');
    (new LeadTriage())->merge($repeat, $incoming, (int)$admin->id);
    $repeat->refresh();
    $check($repeat->status === CmsLead::STATUS_REJECTED && $repeat->triage_result === CmsLead::TRIAGE_MERGED && (int)$repeat->merged_into_id === (int)$incoming->id, 'merged: closed, points to the target');
    // П2г: the target has a client — the contacts of the request go to its person card, not to the deal.
    $check($incoming->getPhones()->count() == 1, 'the target deal gets no new phone of its own');
    $check(\skeeks\cms\models\CmsUserPhone::find()->andWhere(['cms_user_id' => (int)$incoming->cms_user_id])->count() == 2, 'the phones of the request go to the person of the target');
    $again = $service->createFromSource(['cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM, 'source_ref' => 'p2a-'.$uniq.'-2', 'name' => 'Повтор '.$uniq]);
    $check((int)$again->id === (int)$incoming->id, 'the repeated source event goes to the target deal');
    $partnerLead = $service->createFromSource(['cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM, 'source_ref' => 'p2a-'.$uniq.'-p',
        'name' => 'Партнёр '.$uniq, 'partner_id' => $owner->id, 'status' => CmsLead::STATUS_NEW]);
    try {
        (new LeadTriage())->merge($partnerLead, $incoming, (int)$admin->id);
        $check(false, 'a partner request is not added to a deal of another partner');
    } catch (DomainException $e) {
        $check(true, 'partner request refused');
    }

    // --- support and spam ---
    $support = $service->createFromSource(['cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM, 'source_ref' => 'p2a-'.$uniq.'-3', 'name' => 'Не работает почта', 'status' => CmsLead::STATUS_NEW]);
    $result = (new LeadTriage())->support($support, null, (int)$admin->id);
    $support->refresh();
    $check($support->triage_result === CmsLead::TRIAGE_SUPPORT && $result['task'] instanceof CmsTask && (int)$result['task']->cms_lead_id === (int)$support->id, 'support: a task, the deal closed');
    $spam = $service->createFromSource(['cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM, 'source_ref' => 'p2a-'.$uniq.'-4', 'name' => 'Купите SEO', 'status' => CmsLead::STATUS_NEW]);
    (new LeadTriage())->spam($spam, (int)$admin->id);
    $spam->refresh();
    $check($spam->triage_result === CmsLead::TRIAGE_SPAM && $spam->staffStatusName === 'Спам' && $spam->statusName === 'Отклонён', 'spam: employees see «Спам», partners «Отклонён»');
    $as($admin);
    $report = (new \skeeks\cms\services\CmsLeadReportService())->build(time() - 3600, time() + 3600);
    $reportIds = array_map('intval', CmsLead::find()->andWhere(['triage_result' => null, 'id' => [$spam->id, $support->id, $repeat->id]])->select('id')->column());
    $check($reportIds === [], 'sorted deals carry the result the report excludes');
    $spam->status = CmsLead::STATUS_IN_WORK;
    $check($spam->save() && $spam->triage_result === null, 'a reopened sorted deal is an ordinary deal again');

    // --- «Работать с этим клиентом»: the deal is the employee's at once, level "Свои сделки" ---
    $check(CrmAccess::instance()->mainResponsibleId('company', (int)$company->id) === (int)$owner->id, 'the earliest link is the main responsible');
    $as($other);
    $foreign = new LeadIdentityForm(['company_mode' => 'foreign', 'company_ref' => $presented['ref'], 'foreign_comment' => 'Знаю директора']);
    $lead = new CmsLead(['name' => 'Допродажа '.$uniq, 'cms_site_id' => $siteId]);
    $result = (new LeadTriage())->createLead($lead, $foreign, (int)$other->id);
    $check((int)$lead->executor_id === (int)$other->id && (int)$lead->cms_company_id === (int)$company->id, 'the deal is the employee\'s and linked to the company');
    $check(CrmAccess::instance()->linkLevel($other->id, 'company', (int)$company->id) === CrmAccess::LEVEL_DEAL, 'the employee gets "Свои сделки" at once');
    $check(CrmAccess::instance()->mainResponsibleId('company', (int)$company->id) === (int)$owner->id, 'the main responsible stays');
    $check(empty($result['request']) && !CmsCrmAccessRequest::find()->andWhere(['requester_id' => $other->id, 'record_code' => 'company', 'record_id' => $company->id])->exists(),
        '«Работать с этим клиентом» sends no request: the deal is created at once');
    $told = CmsWebNotify::find()->andWhere(['cms_user_id' => $owner->id, 'event_key' => 'lead.delegated:'.(int)$lead->id.':'.(int)$other->id])->one();
    $check($told && $told->name === $other->shortDisplayName.': работа с клиентом «'.$company->name.'» по своей сделке'
        && $told->model instanceof CmsLead && (int)$told->model_id === (int)$lead->id, 'the main responsible is notified: the client in the text, the deal as the link');
    // The request mechanics (an incoming request of a client «по согласованию» uses the same service).
    $request = (new CrmAccessRequests())->request((int)$other->id, 'company', (int)$company->id, CmsCrmAccessRequest::KIND_WORK, (int)$lead->id);
    $check($request->isPending && (int)$request->owner_id === (int)$owner->id && (int)$request->cms_lead_id === (int)$lead->id, 'a request to the main responsible with the deal');
    // The common format of CRM notifications: the event as text, the client as the record link.
    $asked = CmsWebNotify::find()->andWhere(['cms_user_id' => $owner->id, 'kind' => 'crm.access_request', 'event_key' => 'crm.access_request:'.(int)$request->id])->one();
    $check($asked && $asked->name === $other->shortDisplayName.': работа с клиентом «'.$company->name.'» по своей сделке, запрос уровня доступа'
        && $asked->model instanceof CmsCrmAccessRequest && (int)$asked->model_id === (int)$request->id, 'request notification: the client in the text, the request as the link line');
    $check(strpos($asked->getOpenUrl(), '/admin-cms-crm-access-request/view') !== false && strpos($asked->getOpenUrl(), 'pk='.(int)$request->id) !== false,
        'the short link of the notification (mail, MAX) opens the request card');
    // «Запросы доступа»: a section of its own; seen by the deciders and the sender only.
    $access0 = CrmAccess::instance();
    $check($access0->canSeeRequest($owner->id, $request) && $access0->canSeeRequest($other->id, $request), 'the main responsible and the sender see the request');
    $stranger = null;
    foreach ($plain as $candidate) {
        if (!in_array((int)$candidate->id, [(int)$owner->id, (int)$other->id], true) && !$access0->isRequestDecider($candidate, $request)) {
            $stranger = $candidate;
            break;
        }
    }
    if ($stranger) {
        $check(!$access0->canSeeRequest($stranger->id, $request), 'another employee does not see the request (403)');
    }
    $listed = static fn($user, string $scope) => CmsCrmAccessRequest::find()->andWhere($access0->requestsCondition($user, $scope))->andWhere([CmsCrmAccessRequest::tableName().'.id' => $request->id])->exists();
    $check($listed($owner, CrmAccess::REQUESTS_TO_ME) && !$listed($other, CrmAccess::REQUESTS_TO_ME), '«Ждут моего решения»: the main responsible, not the sender');
    $check($listed($other, CrmAccess::REQUESTS_MINE) && !$listed($owner, CrmAccess::REQUESTS_MINE), '«Мои запросы»: the sender');
    $check($access0->pendingRequestsCount($owner) >= 1 && $access0->canUseRequests($owner), 'the menu item and its counter for the main responsible');
    [$requestsController] = Yii::$app->createController('cms/admin-cms-crm-access-request');
    $requestActions = $requestsController->actions();
    $check(isset($requestActions['view'], $requestActions['decide']) && !isset($requestActions['create']) && !isset($requestActions['delete']),
        'the section has the card and the decision, requests are not created by a form');
    $same = (new CrmAccessRequests())->request((int)$other->id, 'company', (int)$company->id, CmsCrmAccessRequest::KIND_WORK, (int)$lead->id);
    $check((int)$same->id === (int)$request->id, 'a repeated request joins the pending one');
    $check(!CrmAccess::instance()->canDecideRequest($other->id, $request), 'the requester does not decide');
    $check(CrmAccess::instance()->canDecideRequest($owner->id, $request), 'the main responsible decides');
    (new CrmAccessRequests())->decide($request, false, (int)$owner->id, 'Клиент мой');
    $lead->refresh();
    $check((int)$lead->executor_id === (int)$other->id && CrmAccess::instance()->linkLevel($other->id, 'company', (int)$company->id) === CrmAccess::LEVEL_DEAL,
        'declined: the deal stays with the employee, level "Свои сделки"');
    $given = CmsWebNotify::find()->andWhere(['cms_user_id' => $other->id, 'event_key' => 'crm.access_decided:'.(int)$request->id])->one();
    $check($given && $given->name === 'Отклонён запрос: работа с клиентом' && $given->model instanceof CmsLead && (int)$given->model_id === (int)$lead->id,
        'decision notification: the deal as the link line');
    $check(CrmAccess::instance()->canAssignLead($owner->id, $lead), 'the company responsible may give the deal to someone else');
    // The link is removed on the access page: the company is hidden from him again (the next checks).
    CrmAccess::instance()->removeLink('company', (int)$company->id, (int)$other->id);
    $check(!CrmAccess::instance()->canSeeCompany($other->id, (int)$company->id), 'without a link the company is hidden');
    try {
        CrmAccess::instance()->removeLink('company', (int)$company->id, (int)$owner->id);
        $check(false, 'the main responsible cannot be removed');
    } catch (DomainException $e) {
        $check(CrmAccess::instance()->mainResponsibleId('company', (int)$company->id) === (int)$owner->id, 'the main responsible cannot be removed');
    }
    $brief = CrmAccess::instance()->companyBrief($other, $company);
    $check(!$brief['visible'] && $brief['name'] === $company->name && $brief['responsible'][0]['id'] === (int)$owner->id, 'brief: name and responsible');

    // --- review fixes ---
    // 1. Sorting fields are not loaded by forms.
    $probe = CmsLead::findOne($lead->id);
    $probe->load(['CmsLead' => ['triage_result' => 'merged', 'merged_into_id' => (int)$incoming->id, 'triaged_by' => (int)$other->id]]);
    $check($probe->triage_result === null && $probe->merged_into_id === null && $probe->triaged_by === null, 'a form does not set sorting fields');
    // 2. «Клиент сделки» does not copy a contact into a hidden company.
    $as($other);
    (new \skeeks\cms\models\CmsLeadPhone(['cms_lead_id' => $lead->id, 'value' => '+7 914 '.$uniq.'1']))->save();
    $phoneRow = \skeeks\cms\models\CmsLeadPhone::find()->andWhere(['cms_lead_id' => $lead->id])->one();
    (new LeadTriage())->linkIdentity(CmsLead::findOne($lead->id), new LeadIdentityForm(['contacts' => ['phone:'.$phoneRow->id => 'company']]), (int)$other->id);
    $check(!CmsCompanyPhone::find()->andWhere(['cms_company_id' => $company->id, 'value' => $phoneRow->value])->exists(), 'no contact goes to a company the employee does not see');
    // 3. A hidden private person is not named in requests.
    $hiddenPerson = new CmsUser(['first_name' => 'Скрытый'.$uniq, 'is_worker' => 0]);
    $hiddenPerson->save();
    \skeeks\cms\models\CmsUser2manager::deleteAll(['client_id' => $hiddenPerson->id]);
    (new \skeeks\cms\models\CmsUser2manager(['client_id' => $hiddenPerson->id, 'worker_id' => $owner->id]))->save();
    $personRequest = (new CrmAccessRequests())->request((int)$other->id, 'client', (int)$hiddenPerson->id, CmsCrmAccessRequest::KIND_WORK);
    $check((new CrmAccessRequests())->recordName($personRequest, (int)$other->id) === 'контакт из базы', 'the requester does not learn the name');
    $check((new CrmAccessRequests())->recordName($personRequest, (int)$owner->id) === $hiddenPerson->shortDisplayName, 'the responsible sees the name');
    // 4. A bad phone of a new person is refused before anything is saved.
    try {
        (new LeadTriage())->checkIdentity(new LeadIdentityForm(['client_mode' => 'new', 'client_first_name' => 'Петя', 'client_phone' => 'bad-phone']), (int)$other->id);
        $check(false, 'a bad phone is refused');
    } catch (DomainException $e) {
        $check(strpos($e->getMessage(), 'Телефон') === 0, 'a bad phone is refused before the save');
    }
    // 6. Two decisions of one request: the second is refused.
    $copy = CmsCrmAccessRequest::findOne($personRequest->id);
    (new CrmAccessRequests())->decide($personRequest, false, (int)$owner->id, 'нет');
    $refused = CmsWebNotify::find()->andWhere(['cms_user_id' => $other->id, 'event_key' => 'crm.access_decided:'.(int)$personRequest->id])->one();
    $check($refused && $refused->model_id === null && $refused->name === 'Отклонён запрос: работа с клиентом, клиент «контакт из базы»',
        'a refusal does not name or link a hidden person');
    try {
        (new CrmAccessRequests())->decide($copy, true, (int)$admin->id);
        $check(false, 'a stale copy cannot decide again');
    } catch (DomainException $e) {
        $check(CmsCrmAccessRequest::findOne($personRequest->id)->status === CmsCrmAccessRequest::STATUS_DECLINED, 'the first decision stays');
    }
    // A cancel by a stale copy does not overwrite a decision.
    $cancelLater = (new CrmAccessRequests())->request((int)$other->id, 'client', (int)$hiddenPerson->id, CmsCrmAccessRequest::KIND_WORK);
    $staleCopy = CmsCrmAccessRequest::findOne($cancelLater->id);
    (new CrmAccessRequests())->decide($cancelLater, true, (int)$owner->id);
    try {
        (new CrmAccessRequests())->cancel($staleCopy, (int)$other->id);
        $check(false, 'a decided request cannot be cancelled');
    } catch (DomainException $e) {
        $check(CmsCrmAccessRequest::findOne($cancelLater->id)->status === CmsCrmAccessRequest::STATUS_APPROVED, 'the decision stays after a late cancel');
    }
    // Old kinds («Сделка себе», «Доступ к клиенту»): not sent any more; a pending one is closed as «Устарел».
    try {
        (new CrmAccessRequests())->request((int)$other->id, 'company', (int)$company->id, CmsCrmAccessRequest::KIND_DELEGATE, (int)$lead->id);
        $check(false, 'a request of an old kind is not sent');
    } catch (DomainException $e) {
        $check(strpos($e->getMessage(), 'Работать с этим клиентом') !== false, 'a request of an old kind is refused with the new way');
    }
    $countBefore = CrmAccess::instance()->pendingRequestsCount($owner);
    $legacy = new CmsCrmAccessRequest([
        'requester_id' => $other->id, 'owner_id' => $owner->id, 'record_code' => 'company', 'record_id' => $company->id,
        'kind' => CmsCrmAccessRequest::KIND_DELEGATE, 'cms_lead_id' => $lead->id, 'level' => CrmAccess::LEVEL_DEAL,
    ]);
    $legacy->save(false);
    Yii::$app->notifications->notify((int)$owner->id, 'crm.access_request', [
        'name' => 'старый запрос', 'model' => $legacy, 'eventKey' => 'crm.access_request:'.(int)$legacy->id,
    ]);
    $check(CrmAccess::instance()->pendingRequestsCount($owner) === $countBefore
        && !CmsCrmAccessRequest::find()->andWhere(CrmAccess::instance()->requestsCondition($owner, CrmAccess::REQUESTS_TO_ME))->andWhere(['id' => $legacy->id])->exists(),
        'the menu counter and «Ждут моего решения» skip a request of an old kind');
    $check(!CrmAccess::instance()->canDecideRequest($owner, $legacy), 'a request of an old kind cannot be decided');
    $check((new CrmAccessRequests())->closeLegacy() >= 1, 'closeLegacy() closes pending requests of old kinds');
    $legacy->refresh();
    $check($legacy->status === CmsCrmAccessRequest::STATUS_OBSOLETE && $legacy->statusName === 'Устарел', 'a pending request of an old kind became «Устарел»');
    $closedNote = CmsWebNotify::find()->andWhere(['cms_user_id' => $other->id, 'event_key' => 'crm.access_obsolete:'.(int)$legacy->id])->one();
    $check($closedNote && strpos($closedNote->name, 'Запрос №'.(int)$legacy->id.' закрыт') === 0 && strpos($closedNote->name, 'Работать с этим клиентом') !== false,
        'the sender is told the request is closed and how access is given now');
    $check($closedNote && ($closedNote->model instanceof CmsCompany ? (int)$closedNote->model_id === (int)$company->id : strpos($closedNote->name, '«'.$company->name.'»') !== false),
        'the notification leads to the client (or names it when the sender does not see it)');
    $ownerNote = CmsWebNotify::find()->andWhere(['cms_user_id' => $owner->id, 'event_key' => 'crm.access_request:'.(int)$legacy->id])->one();
    $check($ownerNote && (int)$ownerNote->is_read === 1, 'the notification of the main responsible about it is read');
    try {
        (new CrmAccessRequests())->decide($legacy, true, (int)$admin->id);
        $check(false, 'an obsolete request cannot be decided');
    } catch (DomainException $e) {
        $check(CmsCrmAccessRequest::findOne($legacy->id)->status === CmsCrmAccessRequest::STATUS_OBSOLETE, 'an obsolete request stays «Устарел» after a decision try');
    }
    // 7. The reference does not show the id.
    $check(strpos($presented['ref'], 'company:') === false && strpos($presented['ref'], ':'.$company->id) === false, 'the reference is opaque');

    // --- three levels, «Доступ только по согласованию», the main responsible decides ---
    $check(CrmAccess::instance()->linkLevel($owner->id, 'company', (int)$company->id) === CrmAccess::LEVEL_FULL, 'existing links are "full"');
    $check(array_keys(CrmAccess::levels()) === ['deal', 'edit', 'full'], 'three levels: deal, edit, full');
    $third = $plain[2] ?? $admin;
    (new CmsCompany2manager(['cms_company_id' => $company->id, 'cms_user_id' => $third->id]))->save(false);
    $check(CrmAccess::instance()->mainResponsibleId('company', (int)$company->id) === (int)$owner->id, 'a new link does not change the main responsible');
    $check(CrmAccess::instance()->companyResponsibleIds((int)$company->id, null)[0] === (int)$owner->id, 'the main responsible is listed first');
    CrmAccess::instance()->setApprovalRequired('company', (int)$company->id, true);
    $check(CrmAccess::instance()->approvalRequired('company', (int)$company->id), '«Доступ только по согласованию» is on');
    $check($matcher->present([$hit], $other, $siteId)[0]['byApproval'], 'a match shows that the client is «по согласованию»');
    $check(CrmAccess::instance()->canManageAccess($owner->id, 'company', (int)$company->id) && !CrmAccess::instance()->canManageAccess($other->id, 'company', (int)$company->id),
        'the main responsible manages the access, an employee does not');
    $as($other);
    try {
        (new LeadTriage())->createLead(new CmsLead(['name' => 'По согласованию '.$uniq, 'cms_site_id' => $siteId]),
            new LeadIdentityForm(['company_mode' => 'foreign', 'company_ref' => $presented['ref']]), (int)$other->id);
        $check(false, 'a deal by hand for a client «по согласованию» waits for the approval');
    } catch (DomainException $e) {
        $check(strpos($e->getMessage(), 'по согласованию') !== false, 'a deal by hand for a client «по согласованию» waits for the approval');
    }
    $work = (new LeadTriage())->workWithClient($presented['ref'], (int)$other->id, 'Веду продажи');
    $access = $work['request'];
    $check($work['lead'] === null && $access->isPending && $access->kind === CmsCrmAccessRequest::KIND_WORK && !$access->cms_lead_id,
        '«Работать с этим клиентом» «по согласованию»: only the request, no deal yet');
    $accessAsked = CmsWebNotify::find()->andWhere(['cms_user_id' => $owner->id, 'event_key' => 'crm.access_request:'.(int)$access->id])->one();
    $check($accessAsked && $accessAsked->name === $other->shortDisplayName.': запрос на работу с клиентом «'.$company->name.'»', 'the request notification text');
    if (!in_array((int)$third->id, CmsDepartment::leaderIdsOf((int)$owner->id), true) && !CrmAccess::instance()->isAdmin($third)) {
        $check(!CrmAccess::instance()->canDecideRequest($third->id, $access), 'another employee of the client does not decide');
    }
    (new CrmAccessRequests())->decide($access, true, (int)$owner->id, null, CrmAccess::LEVEL_EDIT);
    $access->refresh();
    $created = $access->cms_lead_id ? CmsLead::findOne((int)$access->cms_lead_id) : null;
    $check($created && (int)$created->executor_id === (int)$other->id && (int)$created->cms_company_id === (int)$company->id,
        'approved: the deal of the employee is created now');
    $check(CrmAccess::instance()->linkLevel($other->id, 'company', (int)$company->id) === CrmAccess::LEVEL_EDIT, 'approved with the level "Свои сделки и данные компании"');
    $opened = CmsWebNotify::find()->andWhere(['cms_user_id' => $other->id, 'event_key' => 'crm.access_decided:'.(int)$access->id])->one();
    $check($opened && $opened->name === 'Одобрен запрос: работа с клиентом, уровень «Свои сделки и данные компании»'
        && $opened->model instanceof CmsLead && (int)$opened->model_id === (int)$created->id, 'approval notification: the level and the new deal');
    $check(CrmAccess::instance()->canSeeCompany($other->id, (int)$company->id), 'a level below "full" sees the company');
    $check(CrmAccess::instance()->defaultExecutorId((int)$company->id, null, (int)$other->id) === (int)$other->id, 'a linked employee takes a new deal himself');
    $check(!in_array((int)$other->id, CrmAccess::instance()->companyResponsibleIds((int)$company->id), true), '"edit" is not a "full" responsible');

    // --- П2а+: what each level may do with the company data ---
    $levelAccess = CrmAccess::instance();
    $companyPhone = CmsCompanyPhone::find()->andWhere(['cms_company_id' => $company->id])->one();
    $actionAllowed = static function (string $route, $model, string $action) {
        [$controller] = Yii::$app->createController($route);
        $controller->setModel($model);
        $callback = $controller->actions()[$action]['accessCallback'] ?? null;

        return is_callable($callback) ? (bool)$callback() : (bool)$callback;
    };
    $setLevel = static fn(CmsUser $user, string $level) => CmsCompany2manager::updateAll(['access_level' => $level],
        ['cms_company_id' => $company->id, 'cms_user_id' => $user->id]);
    // "edit": the data of the company, not the responsible employees.
    $as($other);
    $check($levelAccess->canEditClientData($other->id, 'company', (int)$company->id) && $levelAccess->canEditClientRecord($other->id, $companyPhone),
        '"edit" changes the company data and its phones');
    $check($actionAllowed('cms/admin-cms-company', $company, 'update') && $actionAllowed('cms/admin-cms-company-phone', $companyPhone, 'delete'),
        '"edit": the company form and deleting a phone are allowed');
    $check(!$levelAccess->canManageAccess($other->id, 'company', (int)$company->id) && !$actionAllowed('cms/admin-cms-company', $company, 'delete'),
        '"edit" does not manage the responsible employees and does not delete the company');
    // "deal": sees and contacts the client, changes nothing of its data.
    $setLevel($other, CrmAccess::LEVEL_DEAL);
    $check($levelAccess->canSeeCompany($other->id, (int)$company->id) && $levelAccess->clientDataLevel($other->id, 'company', (int)$company->id) === CrmAccess::LEVEL_DEAL,
        '"deal" sees the company');
    $check(!$levelAccess->canEditClientData($other->id, 'company', (int)$company->id) && !$levelAccess->canEditClientRecord($other->id, $companyPhone),
        '"deal" does not change the company data');
    $check(!$actionAllowed('cms/admin-cms-company', $company, 'update') && !$actionAllowed('cms/admin-cms-company-phone', $companyPhone, 'update')
        && !$actionAllowed('cms/admin-cms-company-phone', $companyPhone, 'delete') && !$actionAllowed('cms/admin-cms-company', $company, 'add-user'),
        '"deal": the company form, a phone edit and delete, adding a person are refused (403)');
    $check($actionAllowed('cms/admin-cms-company', $company, 'view'), '"deal" opens the company card');
    // "deal" adds a new phone or email (the contact block of his deal), changes no existing one.
    $check(!$levelAccess->canAddClientContact($other->id, ['company', (int)$company->id]), '"deal" does not add a phone from the company card');
    $check($levelAccess->canAddClientContact($other->id, ['company', (int)$company->id], (int)$lead->id), '"deal" adds a new phone or email from his deal');
    $check(!$levelAccess->canEditClientRecord($other->id, $companyPhone), '"deal" does not change an existing phone');
    // Other deals of the company and the proposals of them are hidden; the company and its feed are not.
    $ownersLead = new CmsLead(['name' => 'Чужая '.$uniq, 'cms_site_id' => $siteId, 'cms_company_id' => $company->id, 'executor_id' => $owner->id, 'status' => CmsLead::STATUS_IN_WORK]);
    $check($ownersLead->save(), 'a deal of the main responsible');
    $sees = static fn(CmsUser $user, CmsLead $deal) => CmsLead::find()->forManager($user)->andWhere([CmsLead::tableName().'.id' => $deal->id])->exists();
    $check(!$sees($other, $ownersLead) && $sees($other, $lead), '"deal" sees his own deal of the company, not the deal of another employee');
    $check(!$levelAccess->canSeeLead($other->id, $ownersLead), '"deal": the card of another deal is closed');
    // Requisites of the company: from "edit".
    $requisites = new \skeeks\cms\models\CmsContractor(['cms_site_id' => $siteId, 'contractor_type' => 'legal', 'name' => 'ООО «Реквизиты '.$uniq.'»', 'inn' => '77'.$uniq.'01']);
    $check($requisites->save(false), 'requisites of the company');
    (new \skeeks\cms\models\CmsCompany2contractor(['cms_company_id' => $company->id, 'cms_contractor_id' => $requisites->id]))->save(false);
    $check(!$actionAllowed('cms/admin-cms-contractor', $requisites, 'update') && !$actionAllowed('cms/admin-cms-contractor', $requisites, 'delete'),
        '"deal": the requisites of the company are not changed (403)');
    // A call of the client: the main responsible is notified when another employee took it.
    $call = new \skeeks\cms\models\CmsTelephonyCall(['direction' => 'in', 'status' => 'answered', 'cms_company_id' => $company->id, 'cms_worker_user_id' => $other->id]);
    $call->id = 900000000 + (int)$uniq;
    (new \skeeks\cms\crm\CrmClientCallNotifier())->notify($call);
    $callNote = CmsWebNotify::find()->andWhere(['cms_user_id' => $owner->id, 'event_key' => 'crm.client_call:'.(int)$call->id])->one();
    $check($callNote && strpos($callNote->name, 'Звонок клиента «'.$company->name.'»') === 0 && (int)$callNote->model_id === (int)$company->id,
        'a call of the client notifies the main responsible, the company is the link');
    $call->cms_worker_user_id = $owner->id;
    $call->id++;
    (new \skeeks\cms\crm\CrmClientCallNotifier())->notify($call);
    $check(!CmsWebNotify::find()->andWhere(['event_key' => 'crm.client_call:'.(int)$call->id])->exists(), 'no notification when the main responsible took the call');
    // The client feed for "deal": the client's own records and his deals' records; tasks of other deals are hidden.
    $feedLog = static function ($model, string $text, string $type = 'comment') {
        $log = new \skeeks\cms\models\CmsLog(['model_code' => $model->skeeksModelCode, 'model_id' => $model->id, 'log_type' => $type, 'comment' => $text]);
        $log->save(false);

        return $log;
    };
    $ownTask = new CmsTask(['name' => 'Своя '.$uniq, 'executor_id' => $other->id, 'cms_lead_id' => $lead->id, 'cms_company_id' => $company->id]);
    $ownTask->save(false);
    $otherTask = new CmsTask(['name' => 'Чужая '.$uniq, 'executor_id' => $owner->id, 'cms_lead_id' => $ownersLead->id, 'cms_company_id' => $company->id]);
    $otherTask->save(false);
    CmsTask::updateAll(['cms_company_id' => $company->id], ['id' => [$ownTask->id, $otherTask->id]]);
    // «Свои сделки» works only through its deals (owner's decision 29.10.2026): a letter of the client linked to
    // the company only and a comment of an employee about it are the whole client's; a letter of his deal is his.
    $companyNote = $feedLog($company, 'Клиент написал '.$uniq, 'message');
    $staffNote = $feedLog($company, 'Пароль хостинга '.$uniq);
    $leadLetter = $feedLog($lead, 'Ответ клиента по сделке '.$uniq, 'message');
    $ownTaskNote = $feedLog($ownTask, 'По своей '.$uniq);
    $otherTaskNote = $feedLog($otherTask, 'По чужой '.$uniq);
    $as($other);
    $feedIds = static fn() => array_map('intval', CmsCompany::findOne($company->id)->getLogs()->select(\skeeks\cms\models\CmsLog::tableName().'.id')->column());
    $seen = $feedIds();
    $cardSeen = array_map('intval', CmsCompany::findOne($company->id)->getCardLogs()->select(\skeeks\cms\models\CmsLog::tableName().'.id')->column());
    $check(in_array((int)$ownTaskNote->id, $seen, true) && in_array((int)$leadLetter->id, $cardSeen, true) && !in_array((int)$otherTaskNote->id, $seen, true),
        '"deal": the feed shows the records of his deals and their tasks, not the tasks of another deal');
    $check(!in_array((int)$staffNote->id, $seen, true) && !in_array((int)$companyNote->id, $seen, true)
        && !in_array((int)$companyNote->id, array_map('intval', CmsCompany::findOne($company->id)->getCompanyLogs()->select('id')->column()), true),
        '"deal": nothing of the client itself — no letter of the company, no comment of employees (only through his deals)');
    // The actions of the card: only through his open deal (CrmAccess::canActOnClient()), refused by the server.
    $check($levelAccess->canActOnClient($other->id, $company, (int)$lead->id) && !$levelAccess->canActOnClient($other->id, $company)
        && !$levelAccess->canActOnClient($other->id, $company, (int)$ownersLead->id)
        && in_array((int)$lead->id, array_map('intval', $levelAccess->ownOpenLeads($other->id, $company)->select(CmsLead::tableName().'.id')->column()), true),
        '"deal": an action of the card only by his own open deal (ownOpenLeads())');
    $note = new \skeeks\cms\models\CmsLog(['model_code' => $company->skeeksModelCode, 'model_id' => $company->id, 'comment' => 'В общую ленту '.$uniq]);
    $check(!$note->validate() && $note->getFirstError('comment') === CrmAccess::OWN_LEAD_REQUIRED, '"deal": a note in the feed of the client itself is refused');
    $noLeadTask = new CmsTask(['name' => 'Без сделки '.$uniq, 'executor_id' => $other->id, 'cms_company_id' => $company->id]);
    $noLeadTask->validate();
    $foreignLeadTask = new CmsTask(['name' => 'Чужая сделка '.$uniq, 'executor_id' => $other->id, 'cms_company_id' => $company->id, 'cms_lead_id' => $ownersLead->id]);
    $foreignLeadTask->validate();
    $ownLeadTask = new CmsTask(['name' => 'Своя сделка '.$uniq, 'executor_id' => $other->id, 'cms_company_id' => $company->id, 'cms_lead_id' => $lead->id]);
    $ownLeadTask->validate();
    $check($noLeadTask->getFirstError('cms_lead_id') === CrmAccess::OWN_LEAD_REQUIRED && $foreignLeadTask->getFirstError('cms_lead_id') !== null
        && $ownLeadTask->getFirstError('cms_lead_id') === null, '"deal": a task of the client only on his own deal');
    if (class_exists(\skeeks\cms\proposal\models\CmsProposal::class)) {
        $check(\skeeks\cms\proposal\models\CmsProposal::clientActionError($company->id, null) === CrmAccess::OWN_LEAD_REQUIRED,
            '"deal": a proposal of the company itself is refused (in his deal it is allowed)');
    }
    $as($owner);
    $check(in_array((int)$otherTaskNote->id, $feedIds(), true), '"full" sees the whole feed');
    // Pinning: who works on the record of the comment.
    $check(!$levelAccess->canPinLog($other->id, $companyNote) && $levelAccess->canPinLog($owner->id, $companyNote),
        'a company comment is pinned from the level "edit", not by "deal"');
    $leadNote = $feedLog($ownersLead, 'Комментарий сделки '.$uniq);
    $check(!$levelAccess->canPinLog($other->id, $leadNote) && $levelAccess->canPinLog($owner->id, $leadNote),
        'a deal comment is pinned by who works on the deal');
    // The card feed: the client's records and its deals' records (as in Bitrix24), each deal record once.
    $ownLeadNote = $feedLog($lead, 'В своей сделке '.$uniq);
    $twinData = ['id' => 800000000 + (int)$uniq];
    $twinOnCompany = new \skeeks\cms\models\CmsLog(['model_code' => $company->skeeksModelCode, 'model_id' => $company->id, 'log_type' => 'message', 'data' => $twinData]);
    $twinOnCompany->save(false);
    $twinOnLead = new \skeeks\cms\models\CmsLog(['model_code' => $lead->skeeksModelCode, 'model_id' => $lead->id, 'log_type' => 'message', 'data' => $twinData]);
    $twinOnLead->save(false);
    \skeeks\cms\models\CmsLog::updateAll(['is_pinned' => 1], ['id' => $ownLeadNote->id]);
    $cardIds = static fn() => array_map('intval', CmsCompany::findOne($company->id)->getCardLogs()->notPinnedOf(CmsCompany::findOne($company->id))
        ->select(\skeeks\cms\models\CmsLog::tableName().'.id')->column());
    $as($owner);
    $card = $cardIds();
    $check(in_array((int)$companyNote->id, $card, true), 'the card feed shows the company records');
    $check(in_array((int)$leadNote->id, $card, true) && in_array((int)$ownLeadNote->id, $card, true), 'the card feed shows the records of all its deals');
    $check(in_array((int)$twinOnCompany->id, $card, true) && !in_array((int)$twinOnLead->id, $card, true),
        'a letter written to the company and to its deal is shown once');
    $check(in_array((int)$ownLeadNote->id, $card, true), 'a record pinned in its deal stays in the company feed');
    $as($other);
    $card = $cardIds();
    $check(in_array((int)$ownLeadNote->id, $card, true) && !in_array((int)$leadNote->id, $card, true) && !in_array((int)$companyNote->id, $card, true),
        '"deal": the card feed shows his deal records, not the records of another deal or of the company itself');
    // A letter written to the company and to his deal: the copy of the company is hidden, so the copy of the deal stays.
    $check(in_array((int)$twinOnLead->id, $card, true) && !in_array((int)$twinOnCompany->id, $card, true),
        '"deal": a letter of the company and of his deal is seen in his deal (the twin is not dropped)');
    $as($other);
    // "full" (the main responsible): everything.
    $as($owner);
    $check($levelAccess->canEditClientData($owner->id, 'company', (int)$company->id) && $levelAccess->canManageAccess($owner->id, 'company', (int)$company->id)
        && $actionAllowed('cms/admin-cms-company', $company, 'update') && $actionAllowed('cms/admin-cms-company', $company, 'delete')
        && $actionAllowed('cms/admin-cms-company-phone', $companyPhone, 'delete'), '"full": the data, the accesses and deleting are allowed');
    $check($actionAllowed('cms/admin-cms-contractor', $requisites, 'update'), '"full": the requisites are changed');
    // «Доступ» is one tab; the company form posts no employees, people or requisites and never rewrites them.
    $companyActions = $actionAllowed('cms/admin-cms-company', $company, 'crm-access');
    [$companyController] = Yii::$app->createController('cms/admin-cms-company');
    $companyController->setModel($company);
    $check($companyActions && ($companyController->actions()['transfer']['isVisible'] ?? true) === false,
        '«Передать клиента» is a block of «Доступ», not a tab of its own');
    // «+ Добавить сотрудника» (the same window in «Доступ» and in the card block): who manages the access only.
    $check($actionAllowed('cms/admin-cms-company', $company, 'crm-access-add'), 'the main responsible opens «Добавить сотрудника»');
    $as($other);
    $check(!$actionAllowed('cms/admin-cms-company', $company, 'crm-access-add'), 'an employee without the access management does not');
    $as($owner);
    $rowsOfAccess = array_column($levelAccess->accessRows('company', (int)$company->id), null, 'id');
    $check($rowsOfAccess[(int)$owner->id]['origin'] === 'основной ответственный' && $rowsOfAccess[(int)$owner->id]['main'],
        'the rows of the access: the main responsible first, with its origin');
    $linksBefore = CmsCompany2manager::find()->select(['cms_user_id', 'access_level', 'is_main'])->andWhere(['cms_company_id' => $company->id])->orderBy(['cms_user_id' => SORT_ASC])->asArray()->all();
    $peopleBefore = \skeeks\cms\models\CmsCompany2user::find()->select('cms_user_id')->andWhere(['cms_company_id' => $company->id])->column();
    $formCompany = CmsCompany::findOne($company->id);
    $formCompany->load(['CmsCompany' => ['name' => $company->name.' (форма)', 'description' => 'Сохранено формой']]);
    $check($formCompany->save(), 'the company form saves without the employees field');
    $linksAfter = CmsCompany2manager::find()->select(['cms_user_id', 'access_level', 'is_main'])->andWhere(['cms_company_id' => $company->id])->orderBy(['cms_user_id' => SORT_ASC])->asArray()->all();
    $check($linksAfter == $linksBefore && \skeeks\cms\models\CmsCompany2user::find()->select('cms_user_id')->andWhere(['cms_company_id' => $company->id])->column() == $peopleBefore,
        'saving the form keeps the employees, their levels, the main responsible and the people');
    $setLevel($other, CrmAccess::LEVEL_EDIT);
    // An incoming request of a client «по согласованию» does not wait: it goes to the main responsible.
    if ($third2 = $plain[3] ?? null) {
        $as($third2);
        $incomingForm = new LeadIdentityForm(['company_mode' => 'foreign', 'company_ref' => $matcher->present([$hit], $third2, $siteId)[0]['ref']]);
        $applied = (new LeadTriage())->applyIdentity(new CmsLead(['cms_site_id' => $siteId]), $incomingForm, (int)$third2->id, true);
        $check($applied['executorId'] === (int)$owner->id, 'an incoming request «по согласованию» goes to the main responsible');
    }
    CrmAccess::instance()->setApprovalRequired('company', (int)$company->id, false);

    // --- «Передать клиента» by an administrator ---
    $keep = new CmsLead(['name' => 'Остаётся '.$uniq, 'cms_site_id' => $siteId, 'cms_company_id' => $company->id, 'executor_id' => $owner->id, 'status' => CmsLead::STATUS_IN_WORK]);
    $check($keep->save(), 'a deal to keep');
    $check(!CrmAccess::instance()->canTransferClient($other->id, 'company', (int)$company->id), 'a manager does not transfer a client');
    // 8. A task of an open deal without a company of its own moves too.
    $dealTask = new CmsTask(['name' => 'Задача сделки '.$uniq, 'executor_id' => $owner->id, 'cms_lead_id' => $lead->id]);
    $check($dealTask->save(false), 'a deal task');
    CmsTask::updateAll(['cms_company_id' => null], ['id' => $dealTask->id]);
    $keepTask = new CmsTask(['name' => 'Задача оставленной '.$uniq, 'executor_id' => $owner->id, 'cms_lead_id' => $keep->id]);
    $check($keepTask->save(false), 'a task of the kept deal');
    $message = (new \skeeks\cms\crm\CrmClientTransfer())->transfer('company', (int)$company->id, (int)$other->id, (int)$admin->id, [(int)$keep->id]);
    $check(CrmAccess::instance()->mainResponsibleId('company', (int)$company->id) === (int)$other->id, 'the new one is the main responsible');
    $check(CrmAccess::instance()->linkLevel($other->id, 'company', (int)$company->id) === CrmAccess::LEVEL_FULL, 'the new one is "full"');
    $check(CrmAccess::instance()->linkLevel($owner->id, 'company', (int)$company->id) === CrmAccess::LEVEL_EDIT,
        'the former main keeps "Свои сделки и данные компании"');
    $check(CrmAccess::instance()->linkLevel($third->id, 'company', (int)$company->id) === CrmAccess::LEVEL_FULL, 'other employees keep their links');
    $keep->refresh();
    $check((int)$keep->executor_id === (int)$owner->id, 'a kept deal stays with its executor');
    $check((int)CmsLead::findOne($lead->id)->executor_id === (int)$other->id, 'other open deals go to the new responsible');
    $check((int)CmsTask::findOne($dealTask->id)->executor_id === (int)$other->id, 'a task of a moved deal moves');
    $check((int)CmsTask::findOne($keepTask->id)->executor_id === (int)$owner->id, 'a task of a kept deal stays');

    // --- the managers field of a form rewrites the links: levels and the main responsible are kept ---
    $edited = CmsCompany::findOne($company->id);
    $edited->managers = [(int)$third->id, (int)$owner->id, (int)$other->id];
    $check($edited->save(), 'the company form saves its managers');
    $check(CrmAccess::instance()->mainResponsibleId('company', (int)$company->id) === (int)$other->id
        && CrmAccess::instance()->linkLevel($owner->id, 'company', (int)$company->id) === CrmAccess::LEVEL_EDIT, 'the form keeps the main responsible and the levels');
    // A new company: its author becomes the main responsible (the form puts him among the managers).
    $as($third);
    $authored = new CmsCompany(['name' => 'Автор '.$uniq]);
    $authored->managers = [(int)$owner->id, (int)$third->id];
    $check($authored->save() && CrmAccess::instance()->mainResponsibleId('company', (int)$authored->id) === (int)$third->id, 'the author of a new company is its main responsible');
    $check(CrmAccess::instance()->accessList('company', (int)$authored->id)[0]['main'], 'the access list shows the main responsible first');

    // --- "Это другая компания" ---
    $as($other);
    $different = new LeadIdentityForm(['company_mode' => 'new', 'company_name' => 'Ромашка '.$uniq, 'duplicate_ref' => $presented['ref'], 'duplicate_reason' => 'Другой город']);
    $lead2 = new CmsLead(['name' => 'Другая Ромашка', 'cms_site_id' => $siteId]);
    (new LeadTriage())->createLead($lead2, $different, (int)$other->id);
    $mark = CmsCrmDuplicateMark::find()->andWhere(['record_code' => 'company', 'record_id' => $lead2->cms_company_id])->one();
    $check($mark && (int)$mark->other_id === (int)$company->id && $mark->reason === 'Другой город', 'a possible duplicate is marked with its reason');
    $check((int)$lead2->executor_id === (int)$other->id, 'a new company: the actor is responsible');

    // --- a pasted line (CrmContactParser) and the deal form as in amoCRM / Bitrix24 ---
    $parsed = \skeeks\cms\crm\CrmContactParser::parse("ООО «Ромашка»\t+7 999 123-45-67\tИван Петров\tinfo@romashka.ru, sales@romashka.ru\t7701234567\twww.romashka.ru");
    $check($parsed['name'] === 'ООО «Ромашка»' && $parsed['person'] === 'Иван Петров', 'a line: the name and the person');
    $check($parsed['phones'] === ['+7 999 123-45-67'] && $parsed['emails'] === ['info@romashka.ru', 'sales@romashka.ru'], 'a line: phones and emails');
    $check($parsed['inns'] === ['7701234567'] && $parsed['sites'] === ['www.romashka.ru'], 'a line: INN and site');
    $check(\skeeks\cms\crm\CrmContactParser::parse('9991234567')['phones'] === ['9991234567'], 'a bare mobile number is a phone, not an INN');
    $found = CrmMatcher::instance()->searchLine('Скикс-нет, '.$phone, 'company', $owner, $siteId);
    $check((bool)array_filter($found['candidates'], static fn($c) => $c['id'] === (int)$company->id && $c['strong']), 'a pasted line finds the company by its phone');
    $as($other);
    $lineLead = new CmsLead(['cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_MANUAL]);
    (new LeadTriage())->createLead($lineLead, new LeadIdentityForm([
        'company_mode' => 'new', 'company_name' => 'Строка '.$uniq,
        'company_phones' => '+7 915 '.$uniq.'1', 'company_emails' => 'a-'.$uniq.'@line.test, b-'.$uniq.'@line.test',
        'client_mode' => 'new', 'client_first_name' => 'Ольга',
    ]), (int)$other->id);
    $check($lineLead->name === 'Строка '.$uniq, 'an empty name is the company name (made by hand — no source)');
    $check(CmsCompanyPhone::find()->andWhere(['cms_company_id' => $lineLead->cms_company_id])->count() == 1
        && \skeeks\cms\models\CmsCompanyEmail::find()->andWhere(['cms_company_id' => $lineLead->cms_company_id])->count() == 2, 'phones and emails of the line go to the new company');
    // П2г: a deal by an employee is saved only with its client.
    $noName = new CmsLead(['cms_site_id' => $siteId]);
    try {
        (new LeadTriage())->createLead($noName, new LeadIdentityForm(['client_phone' => '+7 916 '.$uniq.'1']), (int)$other->id);
        $check(false, 'a deal without a client is refused');
    } catch (DomainException $e) {
        $check($e->getMessage() === LeadTriage::CLIENT_REQUIRED && $noName->isNewRecord, 'a deal without a client is refused, nothing saved');
    }
    $check(!\skeeks\cms\models\CmsLeadPhone::find()->andWhere(['like', 'value', $uniq.'1'])->andWhere(['like', 'value', '916'])->exists(), 'the typed phone is not kept anywhere');
    if (CmsLead::amountReady()) {
        $withAmount = CmsLead::findOne($lineLead->id);
        $withAmount->amount = '150 000';
        $check($withAmount->save() && (float)$withAmount->amount === 150000.0, '«Сумма» of an open deal is kept');
        $withAmount->status = CmsLead::STATUS_SUCCESS;
        $withAmount->result_comment = 'Оплачено';
        $check($withAmount->validate() && (float)$withAmount->deal_amount === 150000.0, 'success proposes «Сумма» as the deal amount');
    }

    // --- a strong match is never saved silently (CrmDuplicateGuard) ---
    $twin = new CmsCompany(['name' => 'Двойник '.$uniq]);
    $check($twin->save(), 'a second company');
    $samePhone = new CmsCompanyPhone(['cms_company_id' => $twin->id, 'value' => $phone]);
    $check(!$samePhone->save() && strpos($samePhone->getFirstError('value'), 'уже есть у компании') !== false, 'a phone of another company is refused without a reason');
    $sameEmail = new \skeeks\cms\models\CmsCompanyEmail(['cms_company_id' => $twin->id, 'value' => 'INFO@romashka-'.$uniq.'.test']);
    $check(!$sameEmail->save(), 'an email of another company is refused without a reason (any case)');
    $samePhone->duplicate_reason = 'Другое юрлицо того же владельца';
    $check($samePhone->save(), 'with a reason the phone is saved');
    $check(CmsCrmDuplicateMark::find()->andWhere(['record_code' => 'company', 'record_id' => $twin->id, 'other_id' => $company->id])->exists(), 'the pair is marked as a possible duplicate');
    // Moving a shared phone or email to a third company (an update of cms_company_id) is checked anew.
    $third3 = new CmsCompany(['name' => 'Третья '.$uniq]);
    $check($third3->save(), 'a third company');
    $moved = CmsCompanyPhone::findOne($samePhone->id);
    $moved->cms_company_id = $third3->id;
    $check(!$moved->save() && strpos($moved->getFirstError('value'), 'уже есть у компании') !== false, 'moving a shared phone to another company needs a reason');
    $movedEmail = new \skeeks\cms\models\CmsCompanyEmail(['cms_company_id' => $company->id, 'value' => 'move-'.$uniq.'@romashka.test']);
    $check($movedEmail->save(), 'an own email');
    $movedEmail = \skeeks\cms\models\CmsCompanyEmail::findOne($movedEmail->id);
    $movedEmail->cms_company_id = (string)$third3->id;
    $check($movedEmail->save(), 'moving an email nobody else has is free');
    $copyEmail = new \skeeks\cms\models\CmsCompanyEmail(['cms_company_id' => $twin->id, 'value' => 'move-'.$uniq.'@romashka.test', 'duplicate_reason' => 'Тест']);
    $check($copyEmail->save(), 'a shared email with a reason');
    $copyEmail = \skeeks\cms\models\CmsCompanyEmail::findOne($copyEmail->id);
    $copyEmail->cms_company_id = $company->id;
    $check(!$copyEmail->save(), 'moving a shared email to a third company without a reason is refused');
    $copyEmail->duplicate_reason = 'Перенос по просьбе клиента';
    $check($copyEmail->save() && CmsCrmDuplicateMark::find()->andWhere(['record_code' => 'company', 'record_id' => $company->id, 'other_id' => $third3->id])->exists(),
        'with a reason the move is saved and marked');
    $unchanged = CmsCompanyPhone::findOne($samePhone->id);
    $unchanged->cms_company_id = (string)$unchanged->cms_company_id;
    $check($unchanged->save(), 'saving a row without changes needs no reason');

    $publicMail = new \skeeks\cms\models\CmsCompanyEmail(['cms_company_id' => $twin->id, 'value' => 'twin-'.$uniq.'@gmail.com']);
    $check($publicMail->save(), 'a public mail domain alone is no match');
    try {
        (new LeadTriage())->checkIdentity(new LeadIdentityForm(['company_mode' => 'new', 'company_name' => 'Ещё одна '.$uniq, 'company_phones' => $phone]), (int)$other->id);
        $check(false, 'a new company in the deal form with another company\'s phone needs «Это другая компания»');
    } catch (DomainException $e) {
        $check(strpos($e->getMessage(), 'уже есть у компании') !== false, 'the deal form refuses a phone of another company');
    }

    // --- «Повторы клиентов»: strict (default) and free, one scenario per entry point ---
    $check(\skeeks\cms\crm\CrmDuplicateGuard::mode() === \skeeks\cms\crm\CrmSettings::DUPLICATES_STRICT
        || Yii::$app->crmSettings->duplicateMode === \skeeks\cms\crm\CrmSettings::DUPLICATES_FREE, 'a site without saved CRM settings is strict');
    $check((new \skeeks\cms\crm\CrmSettings())->duplicateMode === \skeeks\cms\crm\CrmSettings::DUPLICATES_STRICT, 'the default mode is strict');
    $companyEmail = 'info@romashka-'.$uniq.'.test';
    try {
        foreach ([\skeeks\cms\crm\CrmSettings::DUPLICATES_STRICT, \skeeks\cms\crm\CrmSettings::DUPLICATES_FREE] as $mode) {
            \skeeks\cms\crm\CrmDuplicateGuard::$mode = $mode;
            $strict = $mode === \skeeks\cms\crm\CrmSettings::DUPLICATES_STRICT;
            $repeat = new CmsCompany(['name' => 'Повтор '.$mode.' '.$uniq]);
            $check($repeat->save(), $mode.': a company');
            $marked = static fn() => CmsCrmDuplicateMark::find()->andWhere(['record_code' => 'company', 'record_id' => $repeat->id, 'other_id' => $company->id])->exists();

            // The tabs «Телефоны» / «Email», the create forms of a company and the API save through these models.
            $phoneRow = new CmsCompanyPhone(['cms_company_id' => $repeat->id, 'value' => $phone]);
            $check($phoneRow->save() === !$strict, $mode.': a phone of another company without a reason is '.($strict ? 'refused' : 'saved'));
            $check($marked() === !$strict, $mode.': '.($strict ? 'nothing is marked' : 'the pair is marked as a possible duplicate anyway'));
            $emailRow = new \skeeks\cms\models\CmsCompanyEmail(['cms_company_id' => $repeat->id, 'value' => $companyEmail]);
            $check($emailRow->save() === !$strict, $mode.': an email of another company without a reason');
            $withReason = new CmsCompanyPhone(['cms_company_id' => $repeat->id, 'value' => $phone, 'duplicate_reason' => 'Другое юрлицо']);
            $check($strict ? $withReason->save() : true, $mode.': with a reason it is always saved');
            // An INN of another company (the requisites link): the same decision.
            $check(\skeeks\cms\crm\CrmDuplicateGuard::refuses('') === $strict && !\skeeks\cms\crm\CrmDuplicateGuard::refuses('Другой город'), $mode.': INN');
            $check((strpos(\skeeks\cms\crm\CrmDuplicateGuard::reasonLabel(), 'необязательно') !== false) === !$strict, $mode.': the reason field stays, optional in the free mode');

            // The deal form: a new company with another company's phone, and «Это другая компания» without a reason.
            $newCompany = new LeadIdentityForm(['company_mode' => 'new', 'company_name' => 'Сделка '.$mode.' '.$uniq, 'company_phones' => $phone]);
            try {
                (new LeadTriage())->checkIdentity($newCompany, (int)$other->id);
                $check(!$strict, $mode.': the deal form accepts a shared phone');
            } catch (DomainException $e) {
                $check($strict, $mode.': the deal form refuses a shared phone without a reason');
            }
            $differentRef = $matcher->present([$hit], $other, $siteId)[0]['ref'];
            $check((new LeadIdentityForm(['company_mode' => 'new', 'company_name' => 'Другая '.$uniq, 'duplicate_ref' => $differentRef]))->validate() === !$strict,
                $mode.': «Это другая компания» '.($strict ? 'needs' : 'does not need').' a reason');
            if (!$strict) {
                $freeLead = new CmsLead(['cms_site_id' => $siteId]);
                (new LeadTriage())->createLead($freeLead, new LeadIdentityForm(['company_mode' => 'new', 'company_name' => 'Свободная '.$uniq, 'company_phones' => $phone]), (int)$other->id);
                $check(CmsCrmDuplicateMark::find()->andWhere(['record_code' => 'company', 'record_id' => $freeLead->cms_company_id, 'other_id' => $company->id])->exists(),
                    'free: a deal with a new company is created and the company is marked');
            }
            // The hidden client stays hidden in both modes.
            $check(!$matcher->present([$hit], $other, $siteId)[0]['visible'] || CrmAccess::instance()->canSeeCompany((int)$other->id, (int)$company->id),
                $mode.': hiding by the access level does not depend on the mode');

            // The API (cms-mcp cms_company_create_full) asks the same guard.
            if (class_exists(\skeeks\cms\mcp\services\CmsCompanyService::class)) {
                $as($admin);
                $api = (new \skeeks\cms\mcp\services\CmsCompanyService())->companyCreateFull([
                    'name' => 'API '.$mode.' '.$uniq, 'allow_duplicate' => true, 'phones' => [['value' => $phone]],
                ]);
                $check(!empty($api['created']) === !$strict, $mode.': API cms_company_create_full without a reason');
            }
        }
    } finally {
        \skeeks\cms\crm\CrmDuplicateGuard::$mode = null;
    }

    // --- П2д: the protection of a client and the general pool ---
    if (\skeeks\cms\crm\CrmProtection::ready() && class_exists(\skeeks\cms\shop\models\ShopPayment::class)) {
        $protection = new \skeeks\cms\crm\CrmProtection();
        $access2d = CrmAccess::instance();
        $as($owner);
        $guarded = new CmsCompany(['name' => 'Защита '.$uniq]);
        $guarded->save(false);
        $access2d->setMain('company', (int)$guarded->id, (int)$owner->id);
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'free' && !$access2d->approvalRequired('company', (int)$guarded->id),
            'a client without payments and services is free');
        $pay = static function (CmsCompany $company, int $at) use ($siteId) {
            $payment = new \skeeks\cms\shop\models\ShopPayment(['cms_site_id' => $siteId, 'cms_company_id' => $company->id, 'is_debit' => 1, 'amount' => 1000, 'currency_code' => 'RUB', 'operation_at' => $at]);
            $payment->save(false);

            return $payment;
        };
        $pay($guarded, time() - 86400 * 400);
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'free', 'a payment older than the protection period does not protect');
        $pay($guarded, time() - 86400);
        $guarded->refresh();
        $check($guarded->crm_protection === 'protected' && (int)$guarded->crm_protected_until > strtotime('+11 months')
            && strpos((string)$guarded->crm_protection_reason, 'Последний платёж') === 0 && $access2d->approvalRequired('company', (int)$guarded->id),
            'a recent payment protects the client (the event of the payment recalculates it)');
        $check(strpos(\skeeks\cms\crm\CrmProtection::describe('company', (int)$guarded->id), 'Под защитой до ') === 0, 'the protection in words');
        \skeeks\cms\shop\models\ShopPayment::updateAll(['operation_at' => time() - 86400 * 800], ['cms_company_id' => $guarded->id]);
        $protection->recalcMany();
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'free', 'the daily recalculation frees an expired protection');
        $service = new \skeeks\cms\models\CmsDeal(['name' => 'Услуга '.$uniq, 'cms_company_id' => $guarded->id, 'is_active' => 1, 'start_at' => time() - 86400, 'end_at' => time() + 86400 * 30,
            'cms_deal_type_id' => (int)\skeeks\cms\models\CmsDealType::find()->select('id')->scalar(), 'amount' => 0, 'currency_code' => 'RUB', 'description' => '', 'is_periodic' => 0]);
        $service->save(false);
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'protected', 'an active service protects (the event of the service)');
        $service->is_active = 0;
        $service->save(false);
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'free', 'a closed service frees the client');
        $access2d->setApprovalRequired('company', (int)$guarded->id, true);
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'always', '«Всегда по согласованию» by hand');
        $protection->recalcMany(true);
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'always', '«Всегда по согласованию» never expires');
        $access2d->setApprovalRequired('company', (int)$guarded->id, false);
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$guarded->id) === 'free', '«Авто» recalculates from payments and services');
        $filtered = CmsCompany::find()->andWhere(['id' => $guarded->id]);
        $access2d->applyResponsibleFilter($filtered, 'company', 'protection_free', CmsCompany::tableName().'.id', (int)$owner->id);
        $check($filtered->exists() && isset(CrmAccess::responsibleFilterItems()['protection_protected']), 'the company list filters by the protection');
        // «Новые компании и клиенты, которые я создаю, — „Всегда по согласованию“»: the setting of the creator.
        if (\skeeks\cms\crm\CrmProtection::newAlwaysReady()) {
            CmsUser::updateAll(['crm_new_always' => 1], ['id' => $owner->id]);
            $mineAlways = new CmsCompany(['name' => 'Своя защита '.$uniq]);
            $mineAlways->save(false);
            $mineAlways->refresh();
            $personAlways = new CmsUser(['first_name' => 'Своя защита'.$uniq, 'is_worker' => 0]);
            $personAlways->save();
            $check($mineAlways->crm_protection === 'always' && (int)$mineAlways->created_by === (int)$owner->id
                && \skeeks\cms\crm\CrmProtection::state('client', (int)$personAlways->id) === 'always',
                'the setting of the creator: his new company and person are «Всегда по согласованию»');
            $check(strpos(\skeeks\cms\crm\CrmProtection::shield($mineAlways), 'sx-crm-shield--always') !== false
                && \skeeks\cms\crm\CrmProtection::shield(CmsCompany::findOne($guarded->id)) === '', 'the shield: a protected client has it, a free one shows nothing');
            CmsUser::updateAll(['crm_new_always' => 0], ['id' => $owner->id]);
            $as($other);
            $notMine = new CmsCompany(['name' => 'Без настройки '.$uniq]);
            $notMine->save(false);
            $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$notMine->id) === 'free', 'without the setting a new company is free');
            $as($owner);
        }

        // There is no general pool (owner's decision 29.10.2026, п. 13а): a CRM client always has a main responsible.
        $as($other);
        $created = new CmsCompany(['name' => 'Создал сотрудник '.$uniq]);
        $created->save(false);
        $check($access2d->mainResponsibleId('company', (int)$created->id) === (int)$other->id && !$access2d->isOrphan('company', (int)$created->id),
            'a company created by an employee: he is its main responsible at once');
        $as($owner);
        // Old data: a company without any employee — seen by an administrator and the heads of departments only.
        $pool = new CmsCompany(['name' => 'Без основного '.$uniq]);
        $pool->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $pool->id]);
        $check($access2d->isOrphan('company', (int)$pool->id) && $access2d->canSeeOrphan($admin, 'company', (int)$pool->id)
            && !isset(CrmAccess::responsibleFilterItems()['pool']), 'old data: a company without employees, no filter «Общий пул»');
        $plainSees = CmsCompany::find()->andWhere([CmsCompany::tableName().'.id' => $pool->id]);
        CrmAccess::applyClientVisibility($plainSees, $other);
        if (!$access2d->isDepartmentHead((int)$other->id)) {
            $as($other);
            $check(!$plainSees->exists() && !$access2d->canSeeOrphan($other, 'company', (int)$pool->id)
                && !$actionAllowed('cms/admin-cms-company', $pool, 'view'), 'an employee does not see a company without the main one');
        }
        $presented = $access2d->presentCandidate(['type' => 'company', 'id' => (int)$pool->id], false, $other);
        $check(!isset($presented['canTake']) && !isset($presented['inBase']) && $presented['title'] !== null, 'a match has no «Взять в работу» and no «Компания в общей базе»');
        $plan = array_column(array_filter($protection->poolMainPlan((int)$admin->id), static fn($row) => $row['code'] === 'company'), 'user_id', 'id');
        $check(isset($plan[(int)$pool->id]) && (int)$plan[(int)$pool->id] === (int)$admin->id, 'the plan of assign-pool-main: by the history, else the default employee');
        // «Работать с этим клиентом» on such a company: the main one by the history (else the default), the deal is his with "deal".
        $as($other);
        $worked = (new LeadTriage())->workWithClient($access2d->candidateRef('company', (int)$pool->id), (int)$other->id);
        $poolMain = $access2d->mainResponsibleId('company', (int)$pool->id);
        $check($worked['lead'] && $poolMain !== null && $poolMain !== (int)$other->id
            && $access2d->linkLevel($other, 'company', (int)$pool->id) === CrmAccess::LEVEL_DEAL,
            '«Работать с этим клиентом» on a company without the main one: the main one is assigned, the employee gets "Свои сделки"');
        $as($owner);
        // The first payment of a company without a main responsible: who registered it becomes the main one.
        $paid = new CmsCompany(['name' => 'Первый платёж '.$uniq]);
        $paid->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $paid->id]);
        $pay($paid, time());
        $check($access2d->mainResponsibleId('company', (int)$paid->id) === (int)$owner->id
            && \skeeks\cms\crm\CrmProtection::state('company', (int)$paid->id) === 'protected', 'the first payment gives a main responsible and protects');
    }

    // A protected client without a main responsible: the request, the approval with "Свои сделки" —
    // the requester gets exactly "deal", the one who approved becomes the main responsible.
    if (\skeeks\cms\crm\CrmProtection::ready()) {
        $orphan = new CmsCompany(['name' => 'Без основного '.$uniq]);
        $orphan->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $orphan->id]);
        CrmAccess::instance()->setApprovalRequired('company', (int)$orphan->id, true);
        $check(CrmAccess::instance()->isOrphan('company', (int)$orphan->id), 'a protected client without a main responsible (old data)');
        $as($other);
        $orphanWork = (new LeadTriage())->workWithClient(CrmAccess::instance()->candidateRef('company', (int)$orphan->id), (int)$other->id);
        $check($orphanWork['lead'] === null && $orphanWork['request'] instanceof CmsCrmAccessRequest, 'it sends a request');
        (new CrmAccessRequests())->decide($orphanWork['request'], true, (int)$admin->id, null, CrmAccess::LEVEL_DEAL);
        $check(CrmAccess::instance()->linkLevel($other->id, 'company', (int)$orphan->id) === CrmAccess::LEVEL_DEAL
            && CrmAccess::instance()->mainResponsibleId('company', (int)$orphan->id) === (int)$admin->id,
            'approved with "Свои сделки": the requester gets exactly "deal", the one who approved is the main responsible');
        // Participants only with the main one (owner's decision 29.10.2026); an automatic choice never raises a level.
        $bare = new CmsCompany(['name' => 'Только сделки '.$uniq]);
        $bare->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $bare->id]);
        CrmAccess::instance()->grantLink('company', (int)$bare->id, (int)$other->id, CrmAccess::LEVEL_DEAL);
        $bareMain = CrmAccess::instance()->mainResponsibleId('company', (int)$bare->id);
        $otherHeads = CmsDepartment::leaderIdsOf((int)$other->id);
        $check($bareMain !== null && $bareMain !== (int)$other->id
            && CrmAccess::instance()->linkLevel($other->id, 'company', (int)$bare->id) === CrmAccess::LEVEL_DEAL
            && ($otherHeads ? $bareMain === (int)$otherHeads[0] : true)
            && !CrmAccess::instance()->isOrphan('company', (int)$bare->id),
            'a participant never stays without the main one: his head (else the default administrator), he keeps "deal"');
        $as($admin);
        $byAdmin = new CmsCompany(['name' => 'Первый участник '.$uniq]);
        $byAdmin->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $byAdmin->id]);
        CrmAccess::instance()->grantLink('company', (int)$byAdmin->id, (int)$other->id, CrmAccess::LEVEL_EDIT);
        $check(CrmAccess::instance()->mainResponsibleId('company', (int)$byAdmin->id) === (int)$admin->id
            && CrmAccess::instance()->linkLevel($other->id, 'company', (int)$byAdmin->id) === CrmAccess::LEVEL_EDIT,
            'the first participant added by an administrator: the administrator is the main one, the employee keeps "edit"');
        CrmAccess::instance()->setMain('company', (int)$byAdmin->id, (int)$other->id, CrmAccess::LEVEL_EDIT);
        $check(CrmAccess::instance()->mainResponsibleId('company', (int)$byAdmin->id) === (int)$other->id,
            '«сделать основным»: the employee is the main one');
        CmsCompany2manager::findOne(['cms_company_id' => $byAdmin->id, 'cms_user_id' => $other->id])->delete();
        $leftMain = CrmAccess::instance()->mainResponsibleId('company', (int)$byAdmin->id);
        $check($leftMain !== null && $leftMain !== (int)$other->id && ($otherHeads ? $leftMain === (int)$otherHeads[0] : true),
            'the main one left (no participant "full"): the head of the former main one, else the default administrator');
        $as($other);

        // The journal of the access: every event is kept, seen by whom it concerns.
        if (\skeeks\cms\crm\CrmAccessLog::ready()) {
            $journal = static fn(CmsCompany $company, string $event) => \skeeks\cms\models\CmsCrmAccessLog::find()
                ->andWhere(['record_code' => 'company', 'record_id' => $company->id, 'event' => $event])->one();
            $check((bool)$journal($created, \skeeks\cms\crm\CrmAccessLog::MAIN_AUTO), 'the journal: the creator is the main one');
            $paidMain = \skeeks\cms\models\CmsCrmAccessLog::find()->andWhere(['record_code' => 'company', 'record_id' => $paid->id,
                'event' => \skeeks\cms\crm\CrmAccessLog::MAIN_AUTO])->andWhere(['not', ['shop_payment_id' => null]])->one();
            $check($paidMain && (int)$paidMain->user_id === (int)$owner->id && $paidMain->shop_payment_id, 'the journal: the main responsible by the first payment');
            $asked = $journal($orphan, \skeeks\cms\crm\CrmAccessLog::REQUEST);
            $approved = $journal($orphan, \skeeks\cms\crm\CrmAccessLog::REQUEST_APPROVED);
            $approverMain = \skeeks\cms\models\CmsCrmAccessLog::find()->andWhere(['record_code' => 'company', 'record_id' => $orphan->id,
                'event' => \skeeks\cms\crm\CrmAccessLog::MAIN_AUTO])->andWhere(['not', ['request_id' => null]])->one();
            $check($asked && $approved && $approved->level_to === CrmAccess::LEVEL_DEAL && (int)$approved->user_id === (int)$other->id
                && $approverMain && (int)$approverMain->user_id === (int)$admin->id, 'the journal: the request, its approval with the level, the approver as the main one');
            $check(\skeeks\cms\models\CmsLog::find()->andWhere(['model_code' => $orphan->skeeksModelCode, 'model_id' => $orphan->id, 'log_type' => \skeeks\cms\crm\CrmAccessLog::LOG_TYPE])->count() >= 2,
                'every event is a record of the client feed');
            $sees = static function ($user, $log) {
                $query = \skeeks\cms\models\CmsCrmAccessLog::find()->andWhere([\skeeks\cms\models\CmsCrmAccessLog::tableName().'.id' => $log->id]);
                if (($condition = \skeeks\cms\crm\CrmAccessLog::visibleCondition($user)) !== null) {
                    $query->andWhere($condition);
                }

                return $query->exists();
            };
            $check($sees($other, $asked) && $sees($admin, $approved), 'the employee sees his events, an administrator — all');
            $strangerLog = null;
            foreach ($plain as $candidate) {
                if (!in_array((int)$candidate->id, [(int)$owner->id, (int)$other->id], true) && !CmsDepartment::subordinateIds((int)$candidate->id)
                    && !CrmAccess::instance()->isAdmin($candidate)) {
                    $strangerLog = $candidate;
                    break;
                }
            }
            if ($strangerLog) {
                $check(!$sees($strangerLog, $asked), 'another employee does not see the events of a client not his');
            }

            // The filters and bulk actions of the company list. $bare as the old data: a participant without the
            // main one (no hooks of the links; assign-orphan-main fixes such clients).
            CmsCompany2manager::deleteAll(['and', ['cms_company_id' => $bare->id], ['<>', 'cms_user_id', $other->id]]);
            CmsCompany2manager::updateAll(['is_main' => 0, 'access_level' => CrmAccess::LEVEL_DEAL], ['cms_company_id' => $bare->id]);
            \skeeks\cms\models\CmsCrmAccessLog::deleteAll(['record_code' => 'company', 'record_id' => $bare->id, 'event' => \skeeks\cms\crm\CrmAccessLog::MAIN_AUTO]);
            $as($admin);
            $listed = static function (string $filter, ?int $userId = null, bool $mainOnly = false) use ($bare) {
                $query = CmsCompany::find()->andWhere([CmsCompany::tableName().'.id' => $bare->id]);
                if ($userId === null) {
                    CrmAccess::instance()->applyResponsibleFilter($query, 'company', $filter, CmsCompany::tableName().'.id', 0);
                } else {
                    CrmAccess::instance()->applyEmployeeFilter($query, 'company', $userId, $mainOnly, CmsCompany::tableName().'.id');
                }

                return $query->exists();
            };
            $check($listed('no_main') && $listed('', (int)$other->id) && !$listed('', (int)$other->id, true),
                'the filters «Без основного ответственного» and «Сотрудник работает»');
            $bulk = static fn(string $operation, array $values) => (new \skeeks\cms\crm\CrmBulkAccessForm(array_merge(['operation' => $operation], $values)))->apply($bare);
            $bulk(\skeeks\cms\crm\CrmBulkAccessForm::MAIN, ['user_id' => $owner->id]);
            $bulkMain = $journal($bare, \skeeks\cms\crm\CrmAccessLog::MAIN_AUTO);
            $check(CrmAccess::instance()->mainResponsibleId('company', (int)$bare->id) === (int)$owner->id && $bulkMain && (int)$bulkMain->actor_id === (int)$admin->id
                && !$listed('no_main') && $listed('', (int)$owner->id, true), 'bulk «Назначить основного ответственного»: the main one, the journal, the filters');
            $bulk(\skeeks\cms\crm\CrmBulkAccessForm::GRANT, ['user_id' => $other->id, 'level' => CrmAccess::LEVEL_EDIT]);
            $bulkLevel = $journal($bare, \skeeks\cms\crm\CrmAccessLog::LEVEL);
            $check(CrmAccess::instance()->linkLevel($other->id, 'company', (int)$bare->id) === CrmAccess::LEVEL_EDIT
                && $bulkLevel && $bulkLevel->level_from === CrmAccess::LEVEL_DEAL && $bulkLevel->level_to === CrmAccess::LEVEL_EDIT,
                'bulk «Добавить сотрудника с уровнем»: an existing link changes its level, written as «Уровень изменён»');
            $bulk(\skeeks\cms\crm\CrmBulkAccessForm::PROTECTION, ['protection' => 'always']);
            $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$bare->id) === 'always' && $journal($bare, \skeeks\cms\crm\CrmAccessLog::PROTECTION),
                'bulk «Сменить защиту»: always by approval, written to the journal');
            // «Доступ» of a hidden client does not open (its name, main responsible and protection stay hidden).
            $hidden = new CmsCompany(['name' => 'Скрытая '.$uniq]);
            $hidden->save(false);
            CmsCompany2manager::deleteAll(['cms_company_id' => $hidden->id]);
            CrmAccess::instance()->setMain('company', (int)$hidden->id, (int)$owner->id, CrmAccess::LEVEL_EDIT);
            CrmAccess::instance()->setApprovalRequired('company', (int)$hidden->id, true);
            $check($actionAllowed('cms/admin-cms-company', $hidden, 'crm-access'), 'an administrator opens «Доступ» of any client');
            $as($other);
            if (!CrmAccess::instance()->canSeeCompany((int)$other->id, (int)$hidden->id)) {
                $check(!$actionAllowed('cms/admin-cms-company', $hidden, 'crm-access'), '«Доступ» of a client the employee does not see is closed');
            }
            $as($admin);
            $bulk(\skeeks\cms\crm\CrmBulkAccessForm::PROTECTION, ['protection' => 'auto']);
            $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$bare->id) !== 'always', 'bulk «Сменить защиту»: back to «Авто»');
            if ($strangerLog) {
                $check(!CrmAccess::instance()->canManageAccess((int)$strangerLog->id, 'company', (int)$bare->id)
                    && CrmAccess::instance()->canManageAccess((int)$admin->id, 'company', (int)$bare->id),
                    'a bulk action refuses a company the employee does not manage, an administrator manages all');
            }
        }
    }

    // A client with a history and without the main one (old data): the main one by the history.
    if (CrmAccess::mainReady() && \skeeks\cms\crm\CrmProtection::ready() && class_exists(\skeeks\cms\shop\models\ShopPayment::class)) {
        $veteran = new CmsCompany(['name' => 'С давним платежом '.$uniq]);
        $veteran->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $veteran->id]);
        $oldPayment = new \skeeks\cms\shop\models\ShopPayment(['cms_site_id' => $siteId, 'cms_company_id' => $veteran->id, 'is_debit' => 1, 'amount' => 1000,
            'currency_code' => 'RUB', 'operation_at' => time() - 86400 * 800]);
        $oldPayment->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $veteran->id]);
        \skeeks\cms\models\CmsCompany::updateAll(['crm_protection' => 'free'], ['id' => $veteran->id]);
        $check(in_array((int)$veteran->id, array_column(array_filter((new \skeeks\cms\crm\CrmProtection())->historyMainPlan((int)$admin->id),
            static fn($row) => $row['code'] === 'company'), 'id'), true), 'the plan gives it a main responsible by its history');
        $as($other);
        $worked = (new LeadTriage())->workWithClient(CrmAccess::instance()->candidateRef('company', (int)$veteran->id), (int)$other->id);
        $check($worked['lead'] && CrmAccess::instance()->mainResponsibleId('company', (int)$veteran->id) !== null
            && CrmAccess::instance()->mainResponsibleId('company', (int)$veteran->id) !== (int)$other->id
            && CrmAccess::instance()->linkLevel($other, 'company', (int)$veteran->id) === CrmAccess::LEVEL_DEAL,
            '«Работать с этим клиентом»: the deal at once, the level "Свои сделки", the main one by the history');
        $check(!\skeeks\cms\shop\models\ShopPayment::find()->forManager($other)->andWhere([\skeeks\cms\shop\models\ShopPayment::tableName().'.id' => $oldPayment->id])->exists()
            && CrmAccess::instance()->feedCondition($other, $veteran) !== null, 'the old payments and the whole history stay hidden from "Свои сделки"');
        $as($admin);
    }

    // A client person is equal to a company: his data by his companies too, his personal orders by the access to him only.
    if (CrmAccess::mainReady() && \skeeks\cms\crm\CrmProtection::ready()) {
        $personCompany = new CmsCompany(['name' => 'Компания человека '.$uniq]);
        $personCompany->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $personCompany->id]);
        $person = new CmsUser(['first_name' => 'Человек'.$uniq, 'is_worker' => 0]);
        $person->save();
        (new \skeeks\cms\models\CmsCompany2user(['cms_company_id' => $personCompany->id, 'cms_user_id' => $person->id]))->save(false);
        CrmAccess::instance()->setMain('company', (int)$personCompany->id, (int)$other->id, CrmAccess::LEVEL_EDIT);
        // A person of a company with employees is its data, not the general pool.
        $check(!CrmAccess::instance()->isOrphan('client', (int)$person->id), 'a person of a company with employees is its data, not a client without the main one');
        if (!CrmAccess::instance()->canSeeCompany($owner, (int)$personCompany->id)) {
            $check(!CmsUser::find()->forManagerOrOrphans($owner)->andWhere([CmsUser::tableName().'.id' => $person->id])->exists(),
                'a person of a foreign company is not in the client list of another employee');
        }
        $serviceType = (int)\skeeks\cms\models\CmsDealType::find()->select('id')->scalar();
        $personalService = new \skeeks\cms\models\CmsDeal(['name' => 'Личная услуга '.$uniq, 'cms_user_id' => $person->id, 'is_active' => 1, 'start_at' => time() - 86400,
            'end_at' => time() + 86400 * 30, 'cms_deal_type_id' => $serviceType, 'amount' => 0, 'currency_code' => 'RUB', 'description' => '', 'is_periodic' => 0]);
        $personalService->save(false);
        $companyService = new \skeeks\cms\models\CmsDeal(['name' => 'Услуга компании '.$uniq, 'cms_company_id' => $personCompany->id, 'cms_user_id' => $person->id, 'is_active' => 1,
            'start_at' => time() - 86400, 'end_at' => time() + 86400 * 30, 'cms_deal_type_id' => $serviceType, 'amount' => 0, 'currency_code' => 'RUB', 'description' => '', 'is_periodic' => 0]);
        $companyService->save(false);
        $personalLead = new CmsLead(['name' => 'Личная сделка '.$uniq, 'cms_site_id' => $siteId, 'cms_user_id' => $person->id,
            'executor_id' => $admin->id, 'status' => CmsLead::STATUS_IN_WORK]);
        $personalLead->save(false);
        $leadSeen = static fn(CmsUser $user) => CmsLead::find()->forManager($user)->andWhere([CmsLead::tableName().'.id' => $personalLead->id])->exists();
        $servicesSeen = static fn(CmsUser $user) => array_map('intval', \skeeks\cms\models\CmsDeal::find()->forManager($user)
            ->andWhere([\skeeks\cms\models\CmsDeal::tableName().'.id' => [$personalService->id, $companyService->id]])->select(\skeeks\cms\models\CmsDeal::tableName().'.id')->column());
        $as($other);
        $check(CrmAccess::instance()->clientDataLevel($other, 'client', (int)$person->id) === CrmAccess::LEVEL_FULL
            && CrmAccess::instance()->canEditClientData($other, 'client', (int)$person->id), 'the data of a person: the level of his company');
        $check(CrmAccess::instance()->personalLevel($other, (int)$person->id) === null && $servicesSeen($other) === [(int)$companyService->id]
            && CrmAccess::instance()->feedCondition($other, $person) !== null && !$leadSeen($other),
            'the personal orders of a person (services, deals) are closed by the access to his company, even "full"');
        // An own link to the person counts with the company: the best of them.
        CrmAccess::instance()->grantLink('client', (int)$person->id, (int)$other->id, CrmAccess::LEVEL_DEAL);
        $check(CrmAccess::instance()->clientDataLevel($other, 'client', (int)$person->id) === CrmAccess::LEVEL_FULL
            && CrmAccess::instance()->personalLevel($other, (int)$person->id) === CrmAccess::LEVEL_DEAL, 'an own "deal" link to a person does not lower the level of his data');
        CrmAccess::instance()->setLevel('client', (int)$person->id, (int)$other->id, CrmAccess::LEVEL_FULL);
        $check(count($servicesSeen($other)) === 2 && CrmAccess::instance()->feedCondition($other, $person) === null && $leadSeen($other),
            'the access "full" to the person opens his personal orders');
        $as($admin);
        // The protection: the personal services and payments protect the person, not the company's.
        $check(\skeeks\cms\crm\CrmProtection::state('client', (int)$person->id) === 'protected', 'a personal service protects the person');
        $personalService->is_active = 0;
        $personalService->save(false);
        $check(\skeeks\cms\crm\CrmProtection::state('client', (int)$person->id) === 'free', 'a service of his company does not protect the person');
        $companyPayment = new \skeeks\cms\shop\models\ShopPayment(['cms_site_id' => $siteId, 'cms_company_id' => $personCompany->id, 'cms_user_id' => $person->id,
            'is_debit' => 1, 'amount' => 1000, 'currency_code' => 'RUB', 'operation_at' => time()]);
        $companyPayment->save(false);
        (new \skeeks\cms\crm\CrmProtection())->recalc('client', (int)$person->id);
        $check(\skeeks\cms\crm\CrmProtection::state('client', (int)$person->id) === 'free', 'a payment of his company does not protect the person');
        // A person without a company created by an employee: he is the main one; the protection — as a company.
        $as($other);
        $single = new CmsUser(['first_name' => 'Физлицо'.$uniq, 'is_worker' => 0]);
        $single->save();
        $check(CrmAccess::instance()->mainResponsibleId('client', (int)$single->id) === (int)$other->id
            && \skeeks\cms\crm\CrmProtection::state('client', (int)$single->id) === 'free', 'a person created by an employee: the employee is the main one');
        // A user who registered on the site himself is not a CRM client (п. 14): nobody is assigned.
        Yii::$app->user->setIdentity(null);
        $siteUser = new CmsUser(['first_name' => 'Сам с сайта'.$uniq, 'is_worker' => 0]);
        $siteUser->save();
        $check(CrmAccess::instance()->mainResponsibleId('client', (int)$siteUser->id) === null, 'a user of the site who registered himself gets no main responsible');
        $as($other);
        $personalPayment = new \skeeks\cms\shop\models\ShopPayment(['cms_site_id' => $siteId, 'cms_user_id' => $single->id,
            'is_debit' => 1, 'amount' => 1000, 'currency_code' => 'RUB', 'operation_at' => time()]);
        $personalPayment->save(false);
        $check(\skeeks\cms\crm\CrmProtection::state('client', (int)$single->id) === 'protected' && CrmAccess::instance()->approvalRequired('client', (int)$single->id),
            'a personal payment protects the person');
        $as($admin);
    }

    // A leader takes the best level of his subordinates on a client, never above it.
    $leader = null;
    $subordinate = null;
    foreach (CmsDepartment::find()->all() as $department) {
        $candidate = CmsUser::findOne((int)$department->worker_id);
        if (!$candidate || CrmAccess::instance()->isAdmin($candidate)) {
            continue;
        }
        foreach (CmsDepartment::subordinateIds((int)$candidate->id) as $subordinateId) {
            $user = CmsUser::findOne((int)$subordinateId);
            if ($user && !CrmAccess::instance()->isAdmin($user) && !CmsDepartment::subordinateIds((int)$user->id)) {
                [$leader, $subordinate] = [$candidate, $user];
                break 2;
            }
        }
    }
    if ($leader && CrmAccess::mainReady()) {
        $foreign = new CmsCompany(['name' => 'Клиент другого отдела '.$uniq]);
        $foreign->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $foreign->id]);
        CrmAccess::instance()->setMain('company', (int)$foreign->id, (int)$admin->id, CrmAccess::LEVEL_EDIT);
        CrmAccess::instance()->grantLink('company', (int)$foreign->id, (int)$subordinate->id, CrmAccess::LEVEL_DEAL);
        $subLead = new CmsLead(['name' => 'Сделка подчинённого '.$uniq, 'cms_site_id' => $siteId, 'cms_company_id' => $foreign->id,
            'executor_id' => $subordinate->id, 'status' => CmsLead::STATUS_IN_WORK]);
        $subLead->save(false);
        $mainLead = new CmsLead(['name' => 'Сделка основного '.$uniq, 'cms_site_id' => $siteId, 'cms_company_id' => $foreign->id,
            'executor_id' => $admin->id, 'status' => CmsLead::STATUS_IN_WORK]);
        $mainLead->save(false);
        $leadsSeen = static fn() => CmsLead::find()->forManager($leader)->andWhere([CmsLead::tableName().'.id' => [$subLead->id, $mainLead->id]])->select(CmsLead::tableName().'.id')->column();
        $check(CrmAccess::instance()->clientDataLevel($leader, 'company', (int)$foreign->id) === CrmAccess::LEVEL_DEAL
            && !CrmAccess::instance()->canManageAccess($leader, 'company', (int)$foreign->id)
            && CrmAccess::instance()->dealOnlyCompanyIds($leader)->andWhere(['dealOnly.cms_company_id' => $foreign->id])->exists(),
            'a leader whose subordinate has "deal" on a foreign client gets "deal", no access management');
        $check(array_map('intval', $leadsSeen()) === [(int)$subLead->id], 'the leader sees the deal of his subordinate, not the other deals of the client');
        $check(CrmAccess::instance()->feedCondition($leader, $foreign) !== null, 'the feed of the client is limited for the leader');
        CrmAccess::instance()->setMain('company', (int)$foreign->id, (int)$subordinate->id, CrmAccess::LEVEL_EDIT);
        $check(CrmAccess::instance()->clientDataLevel($leader, 'company', (int)$foreign->id) === CrmAccess::LEVEL_FULL
            && CrmAccess::instance()->canManageAccess($leader, 'company', (int)$foreign->id) && count($leadsSeen()) === 2,
            'the subordinate is the main responsible: the leader gets "full", manages the access, sees every deal');

        // The bulk actions of the company list under a leader: the selection of the marked rows goes over
        // forManagerOrOrphans() with the join of the managers (an administrator has no join and never met it).
        $bulkSecond = new CmsCompany(['name' => 'Массово '.$uniq]);
        $bulkSecond->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $bulkSecond->id]);
        CrmAccess::instance()->setMain('company', (int)$bulkSecond->id, (int)$subordinate->id);
        $as($leader);
        $bulkController = Yii::$app->createController('/cms/admin-cms-company')[0];
        $applySelection = new \ReflectionMethod(\skeeks\cms\crm\CrmBulkAccessAction::class, 'applySelection');
        $applySelection->setAccessible(true);
        foreach ([
            \skeeks\cms\crm\CrmBulkAccessForm::MAIN => ['user_id' => (int)$subordinate->id],
            \skeeks\cms\crm\CrmBulkAccessForm::GRANT => ['user_id' => (int)$leader->id, 'level' => CrmAccess::LEVEL_EDIT],
            \skeeks\cms\crm\CrmBulkAccessForm::PROTECTION => ['protection' => 'always'],
        ] as $operation => $values) {
            try {
                $updated = $applySelection->invoke($bulkController->createAction('crm-bulk-'.$operation),
                    [(int)$foreign->id, (int)$bulkSecond->id], new \skeeks\cms\crm\CrmBulkAccessForm(['operation' => $operation] + $values));
                $bulkError = $updated === 2 ? null : 'updated '.var_export($updated, true);
            } catch (\Throwable $e) {
                $bulkError = $e->getMessage();
            }
            $check($bulkError === null, 'a bulk action «'.$operation.'» under a leader (the join of the managers): '.($bulkError ?? 'ok'));
        }
        $check(\skeeks\cms\crm\CrmProtection::state('company', (int)$bulkSecond->id) === 'always', 'the bulk protection is applied');
        // A company of the pool (as 579, 568 of the stand): the head gives its first participant and becomes the main one.
        $bulkPool = new CmsCompany(['name' => 'Пул массово '.$uniq]);
        $bulkPool->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $bulkPool->id]);
        $applySelection->invoke($bulkController->createAction('crm-bulk-grant'), [(int)$bulkPool->id],
            new \skeeks\cms\crm\CrmBulkAccessForm(['operation' => \skeeks\cms\crm\CrmBulkAccessForm::GRANT, 'user_id' => (int)$subordinate->id, 'level' => CrmAccess::LEVEL_DEAL]));
        $check(CrmAccess::instance()->mainResponsibleId('company', (int)$bulkPool->id) === (int)$leader->id
            && CrmAccess::instance()->linkLevel($subordinate->id, 'company', (int)$bulkPool->id) === CrmAccess::LEVEL_DEAL,
            'a bulk participant of a pool company by a head: the head is the main one, the employee gets the level chosen');
        // The same bulk actions of the client list (crm\CrmClientGrid): a person of the subordinate, an employee refused.
        $bulkPerson = new CmsUser(['first_name' => 'Массово человек'.$uniq, 'is_worker' => 0]);
        $bulkPerson->save();
        \skeeks\cms\models\CmsUser2manager::deleteAll(['client_id' => $bulkPerson->id]);
        CrmAccess::instance()->setMain('client', (int)$bulkPerson->id, (int)$subordinate->id);
        $clientController = Yii::$app->createController('/cms/admin-user')[0];
        $applySelection->invoke($clientController->createAction('crm-bulk-grant'), [(int)$bulkPerson->id],
            new \skeeks\cms\crm\CrmBulkAccessForm(['operation' => \skeeks\cms\crm\CrmBulkAccessForm::GRANT, 'recordCode' => 'client', 'user_id' => (int)$leader->id, 'level' => CrmAccess::LEVEL_EDIT]));
        $applySelection->invoke($clientController->createAction('crm-bulk-protection'), [(int)$bulkPerson->id],
            new \skeeks\cms\crm\CrmBulkAccessForm(['operation' => \skeeks\cms\crm\CrmBulkAccessForm::PROTECTION, 'recordCode' => 'client', 'protection' => 'always']));
        $applySelection->invoke($clientController->createAction('crm-bulk-main'), [(int)$bulkPerson->id],
            new \skeeks\cms\crm\CrmBulkAccessForm(['operation' => \skeeks\cms\crm\CrmBulkAccessForm::MAIN, 'recordCode' => 'client', 'user_id' => (int)$leader->id]));
        $cell = \skeeks\cms\crm\CrmClientGrid::managersCell('client', $bulkPerson);
        $check(CrmAccess::instance()->mainResponsibleId('client', (int)$bulkPerson->id) === (int)$leader->id
            && CrmAccess::instance()->linkLevel($subordinate->id, 'client', (int)$bulkPerson->id) === CrmAccess::LEVEL_EDIT
            && \skeeks\cms\crm\CrmProtection::state('client', (int)$bulkPerson->id) === 'always'
            && mb_strpos($cell, 'Ответственный') !== false && mb_strpos($cell, (string)$leader->shortDisplayName) < mb_strpos($cell, (string)$subordinate->shortDisplayName),
            'the bulk actions of the client list: the main one, the level, the protection; the column shows the main one first');
        try {
            $applySelection->invoke($clientController->createAction('crm-bulk-protection'), [(int)$subordinate->id],
                new \skeeks\cms\crm\CrmBulkAccessForm(['operation' => \skeeks\cms\crm\CrmBulkAccessForm::PROTECTION, 'recordCode' => 'client', 'protection' => 'always']));
            $workerRefused = false;
        } catch (\Throwable $e) {
            $workerRefused = true;
        }
        $check($workerRefused && \skeeks\cms\crm\CrmClientGrid::managersCell('client', $subordinate) === '', 'an employee in the client list: no bulk access, an empty column');
        // The dismissal (п. 13а): an employee with clients is not deactivated until they are given away.
        $leaving = CmsCompany::findOne($foreign->id);
        $mainOf = CrmAccess::instance()->mainResponsibleId('company', (int)$leaving->id);
        $worker = CmsUser::findOne($mainOf);
        $worker->is_active = 0;
        $check(!$worker->save(false, ['is_active']) && strpos((string)$worker->getFirstError('is_active'), 'Передать всех клиентов') !== false,
            'an employee who is the main one of clients is not deactivated');
        $worker->refresh();
        $openLead = new CmsLead(['name' => 'Открытая уходящего '.$uniq, 'cms_site_id' => $siteId, 'cms_company_id' => $leaving->id,
            'executor_id' => $worker->id, 'status' => CmsLead::STATUS_IN_WORK]);
        $openLead->save(false);
        $work = (new \skeeks\cms\crm\CrmClientTransfer())->workOf((int)$worker->id);
        $check(in_array((int)$leaving->id, array_column($work['clients'], 'id'), true) && in_array((int)$openLead->id, array_map(static fn($l) => (int)$l->id, $work['leads']), true),
            '«Передать всех клиентов» lists his clients and his open deals of them');
        $as($leader);
        $result = (new \skeeks\cms\crm\CrmClientTransfer())->transferAllOf((int)$worker->id, (int)$leader->id, (int)$leader->id, [], [], true);
        $openLead->refresh();
        $check(CrmAccess::instance()->mainResponsibleId('company', (int)$leaving->id) === (int)$leader->id
            && CrmAccess::instance()->linkLevel($worker->id, 'company', (int)$leaving->id) === null && (int)$openLead->executor_id === (int)$leader->id
            && CrmAccess::instance()->mainClientsCount((int)$worker->id) === 0 && strpos($result, 'Клиентов передано') === 0,
            'given to the head on a dismissal: the head is the main one, the former main one leaves, the open deal moves');
        $worker->is_active = 0;
        $check($worker->save(false, ['is_active']), 'without clients the employee is deactivated');
        // Back for the next checks (the transaction rolls everything back anyway).
        CmsUser::updateAll(['is_active' => 1], ['id' => $worker->id]);
        $as($admin);

        $as($admin);

        // A request: decided by the main responsible and his leaders, never by the requester's leader.
        $toMe = static fn(CmsUser $user, CmsCrmAccessRequest $request) => CmsCrmAccessRequest::find()
            ->andWhere(CrmAccess::instance()->requestsCondition($user, CrmAccess::REQUESTS_TO_ME))->andWhere([CmsCrmAccessRequest::tableName().'.id' => $request->id])->exists();
        $stranger = new CmsCompany(['name' => 'Чужой клиент запроса '.$uniq]);
        $stranger->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $stranger->id]);
        CrmAccess::instance()->setMain('company', (int)$stranger->id, (int)$admin->id, CrmAccess::LEVEL_EDIT);
        $subRequest = (new CrmAccessRequests())->request((int)$subordinate->id, 'company', (int)$stranger->id, CmsCrmAccessRequest::KIND_WORK);
        // The main one when it was sent was the requester himself (owner_id): it does not make his leader a decider.
        CmsCrmAccessRequest::updateAll(['owner_id' => $subordinate->id], ['id' => $subRequest->id]);
        $subRequest->refresh();
        $check(!CrmAccess::instance()->canDecideRequest($leader, $subRequest) && !$toMe($leader, $subRequest)
            && !in_array((int)$leader->id, CrmAccess::instance()->requestDeciderIds($subRequest), true),
            'the leader of the requester does not decide the request, nor sees it in «Ждут моего решения»');
        $subLog = \skeeks\cms\models\CmsCrmAccessLog::find()->andWhere(['request_id' => $subRequest->id, 'event' => \skeeks\cms\crm\CrmAccessLog::REQUEST])->one();
        if ($subLog && !CrmAccess::instance()->canSeeCompany($leader, (int)$stranger->id)) {
            $as($leader);
            $check(\skeeks\cms\controllers\AdminCmsCrmAccessLogController::clientLink($subLog) === 'клиент из базы'
                && strpos(\skeeks\cms\controllers\AdminCmsCrmAccessLogController::decisionButtons($subLog), 'data-sx-crm-request-decide') === false,
                'the leader of the requester reads the event without the client name and without decision buttons');
            $as($admin);
        }
        $requester = in_array((int)$other->id, [(int)$leader->id, (int)$subordinate->id], true) ? $owner : $other;
        if (!in_array((int)$requester->id, [(int)$leader->id, (int)$subordinate->id], true)) {
            $own = new CmsCompany(['name' => 'Клиент подчинённого '.$uniq]);
            $own->save(false);
            CmsCompany2manager::deleteAll(['cms_company_id' => $own->id]);
            CrmAccess::instance()->setMain('company', (int)$own->id, (int)$subordinate->id, CrmAccess::LEVEL_EDIT);
            $ownRequest = (new CrmAccessRequests())->request((int)$requester->id, 'company', (int)$own->id, CmsCrmAccessRequest::KIND_WORK);
            $check(CrmAccess::instance()->canDecideRequest($leader, $ownRequest) && $toMe($leader, $ownRequest),
                'the leader of the main responsible decides the request');
        }
    }

    // --- leaders ---
    foreach (CmsDepartment::find()->all() as $department) {
        foreach (CmsDepartment::subordinateIds((int)$department->worker_id) as $subordinateId) {
            $check(in_array((int)$department->worker_id, CmsDepartment::leaderIdsOf($subordinateId), true), 'leaderIdsOf is the inverse of subordinateIds');
        }
    }

} finally {
    $transaction->rollBack();
}

// --- П2а+ by real HTTP requests: the session of an employee with "Свои сделки" (as a live user).
// The fixture is committed (the web requests use their own connection) and removed at the end.
// TEST_HTTP_BASE (default http://nginx) and TEST_HTTP_HOST (default localhost:8080) point to the local site.
$httpUser = CmsUser::findOne((int)(getenv('TEST_DEAL_USER_ID') ?: 1663)) ?: $other;
$fixture = [];
try {
    $httpCompany = new CmsCompany(['name' => 'HTTP-проверка уровней '.$uniq]);
    $httpCompany->save(false);
    $fixture[] = $httpCompany;
    (new CmsCompany2manager(['cms_company_id' => $httpCompany->id, 'cms_user_id' => $admin->id]))->save(false);
    CmsCompany2manager::updateAll(['is_main' => 1, 'access_level' => CrmAccess::LEVEL_FULL], ['cms_company_id' => $httpCompany->id, 'cms_user_id' => $admin->id]);
    (new CmsCompany2manager(['cms_company_id' => $httpCompany->id, 'cms_user_id' => $httpUser->id]))->save(false);
    CmsCompany2manager::updateAll(['is_main' => 0, 'access_level' => CrmAccess::LEVEL_DEAL], ['cms_company_id' => $httpCompany->id, 'cms_user_id' => $httpUser->id]);
    $httpPhone = new CmsCompanyPhone(['cms_company_id' => $httpCompany->id, 'value' => '+7 913 '.substr($uniq, 0, 3).'-'.substr($uniq, 3, 2).'-55']);
    $httpPhone->save(false);
    $httpLead = new CmsLead(['name' => 'HTTP-сделка '.$uniq, 'cms_site_id' => $siteId, 'cms_company_id' => $httpCompany->id, 'executor_id' => $httpUser->id, 'status' => CmsLead::STATUS_IN_WORK]);
    $httpLead->save(false);
    $fixture[] = $httpLead;

    $base = rtrim(getenv('TEST_HTTP_BASE') ?: 'http://nginx', '/');
    $jar = tempnam(sys_get_temp_dir(), 'crm-http');
    $http = static function (string $path, array $post = null) use ($base, $jar) {
        $curl = curl_init($base.$path);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge(['Host: '.(getenv('TEST_HTTP_HOST') ?: 'localhost:8080')], $post !== null ? ['X-Requested-With: XMLHttpRequest'] : []),
        ]);
        if ($post !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $body = (string)curl_exec($curl);
        $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [$code, $body];
    };
    $http('/site?id='.(int)$httpUser->id);
    [$code, $card] = $http('/~sxx/cms/admin-cms-company/view?pk='.(int)$httpCompany->id);
    $check($code === 200 && strpos($card, (string)$httpUser->shortDisplayName) !== false, 'HTTP: the session is the employee\'s, the company card opens');
    $check(strpos($card, 'admin-cms-company-phone/create') === false && strpos($card, 'Изменить телефон') === false
        && strpos($card, 'admin-cms-company-address/create') === false && strpos($card, 'crm-access-add') === false,
        'HTTP: the company card of "deal" has no add, edit or access buttons');
    // «Доступы» — the standard list of the journal; the old addresses lead there.
    [$code, $journalPage] = $http('/~sxx/cms/admin-cms-crm-access-log');
    $check($code === 200 && strpos($journalPage, 'Ждут моего решения') !== false && strpos($journalPage, 'sx-grid-multi-btn') === false,
        'HTTP: «Доступы» opens with the tab «Ждут моего решения», no bulk actions');
    foreach (['/~sxx/cms/admin-cms-company/crm-access-log', '/~sxx/cms/admin-cms-crm-access-request'] as $oldPath) {
        $curl = curl_init($base.$oldPath);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Host: '.(getenv('TEST_HTTP_HOST') ?: 'localhost:8080')]]);
        curl_exec($curl);
        $check(in_array((int)curl_getinfo($curl, CURLINFO_HTTP_CODE), [301, 302], true)
            && strpos((string)curl_getinfo($curl, CURLINFO_REDIRECT_URL), 'admin-cms-crm-access-log') !== false, 'HTTP: '.$oldPath.' leads to «Доступы»');
        curl_close($curl);
    }
    preg_match('/name="csrf-param" content="([^"]+)"/', $card, $param);
    preg_match('/name="csrf-token" content="([^"]+)"/', $card, $token);
    $csrf = $param && $token ? [$param[1] => html_entity_decode($token[1])] : [];
    foreach ([
        'the phone edit form' => '/~sxx/cms/admin-cms-company-phone/update?pk='.(int)$httpPhone->id,
        'a new phone from the card' => '/~sxx/cms/admin-cms-company-phone/create?CmsCompanyPhone%5Bcms_company_id%5D='.(int)$httpCompany->id,
        'a new address' => '/~sxx/cms/admin-cms-company-address/create?CmsCompanyAddress%5Bcms_company_id%5D='.(int)$httpCompany->id,
        'a new link' => '/~sxx/cms/admin-cms-company-link/create?CmsCompanyLink%5Bcms_company_id%5D='.(int)$httpCompany->id,
        'the company form' => '/~sxx/cms/admin-cms-company/update?pk='.(int)$httpCompany->id,
        'adding a person' => '/~sxx/cms/admin-cms-company/add-user?pk='.(int)$httpCompany->id,
        'requisites' => '/~sxx/cms/admin-cms-company-contractor/create?company_id='.(int)$httpCompany->id,
        'adding an employee' => '/~sxx/cms/admin-cms-company/crm-access-add?pk='.(int)$httpCompany->id,
    ] as $what => $path) {
        [$code] = $http($path);
        $check($code === 403, 'HTTP "deal": '.$what.' — 403');
    }
    $postedPhone = '+7 913 '.substr($uniq, 0, 3).'-'.substr($uniq, 3, 2).'-66';
    [$code] = $http('/~sxx/cms/admin-cms-company-phone/create?CmsCompanyPhone%5Bcms_company_id%5D='.(int)$httpCompany->id,
        $csrf + ['CmsCompanyPhone' => ['cms_company_id' => $httpCompany->id, 'value' => $postedPhone]]);
    $check($code === 403 && (int)CmsCompanyPhone::find()->andWhere(['cms_company_id' => $httpCompany->id])->count() === 1,
        'HTTP "deal": a phone posted from the card is refused and not saved');
    [$code] = $http('/~sxx/cms/admin-cms-company-phone/delete?pk='.(int)$httpPhone->id, $csrf);
    $check($code === 403 && CmsCompanyPhone::find()->andWhere(['id' => $httpPhone->id])->exists(), 'HTTP "deal": deleting a phone is refused');
    [$code, $leadCard] = $http('/~sxx/cms/admin-cms-lead/view?pk='.(int)$httpLead->id);
    $check($code === 200 && strpos($leadCard, 'lead_id='.(int)$httpLead->id) !== false && strpos($leadCard, 'Изменить телефон') === false,
        'HTTP "deal": the own deal offers «Добавить телефон» from the deal, no edit of the company phones');
    $check(strpos($leadCard, 'admin-cms-lead-contractor/create') === false && strpos($leadCard, 'admin-cms-lead-link/create') === false
        && strpos($leadCard, 'admin-cms-company-contractor/create') === false && strpos($leadCard, 'admin-cms-company-link/create') === false,
        'HTTP "deal": a deal of a company has no requisites or links of its own, those of the company are read only');
    [$code] = $http('/~sxx/cms/admin-cms-company-phone/create?CmsCompanyPhone%5Bcms_company_id%5D='.(int)$httpCompany->id.'&lead_id='.(int)$httpLead->id);
    $check($code === 200, 'HTTP "deal": a new phone from the own deal opens');
    // There is no general pool (п. 13а): a company without employees (old data) is closed to an employee, no «Взять в работу».
    if (\skeeks\cms\crm\CrmProtection::ready() && !CrmAccess::instance()->isDepartmentHead((int)$httpUser->id)) {
        $httpPool = new CmsCompany(['name' => 'HTTP-без основного '.$uniq]);
        $httpPool->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $httpPool->id]);
        $fixture[] = $httpPool;
        [$code] = $http('/~sxx/cms/admin-cms-company/view?pk='.(int)$httpPool->id);
        $check($code === 403, 'HTTP: a company without the main one is closed to an employee (403)');
        [$code] = $http('/~sxx/cms/admin-cms-company/crm-take?pk='.(int)$httpPool->id, $csrf);
        $check($code !== 200 && CrmAccess::instance()->mainResponsibleId('company', (int)$httpPool->id) === null, 'HTTP: «Взять в работу» is gone');
    }
    @unlink($jar);
} finally {
    foreach (array_reverse($fixture) as $record) {
        if ($record instanceof CmsLead) {
            CmsLead::deleteAll(['id' => $record->id]);
        } else {
            CmsCompanyPhone::deleteAll(['cms_company_id' => $record->id]);
            CmsCompany2manager::deleteAll(['cms_company_id' => $record->id]);
            CmsCompany::deleteAll(['id' => $record->id]);
        }
    }
}

echo 'OK crm-lead-triage-stand: '.$checks." checks\n";
