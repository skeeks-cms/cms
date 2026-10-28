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
    $result = (new LeadTriage())->accept($incoming, $form, (int)$admin->id);
    $incoming->refresh();
    $check($incoming->status === CmsLead::STATUS_IN_WORK && (int)$incoming->executor_id === (int)$other->id && (int)$incoming->triaged_by === (int)$admin->id, 'accepted: in work, the chosen responsible, who sorted');
    $check(CmsCompany2user::find()->andWhere(['cms_company_id' => $incoming->cms_company_id, 'cms_user_id' => $incoming->cms_user_id])->exists(), 'the new person is linked to the new company');
    $check(CmsCompanyPhone::find()->andWhere(['cms_company_id' => $incoming->cms_company_id])->count() == 1, 'the chosen contact went to the company card');
    $check(count($incoming->phones) === 1, 'the contact stays with the deal («Из обращения»)');
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
    $check($incoming->getPhones()->count() == 2, 'the new phone of the request is added to the target');
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
    $request = $result['request'];
    $check($request instanceof CmsCrmAccessRequest && $request->isPending && $request->kind === CmsCrmAccessRequest::KIND_WORK
        && (int)$request->owner_id === (int)$owner->id && (int)$request->cms_lead_id === (int)$lead->id, 'a request to the main responsible with the deal');
    // The common format of CRM notifications: the event as text, the client as the record link.
    $asked = CmsWebNotify::find()->andWhere(['cms_user_id' => $owner->id, 'kind' => 'crm.access_request', 'event_key' => 'crm.access_request:'.(int)$request->id])->one();
    $check($asked && $asked->name === $other->shortDisplayName.' работает с клиентом «'.$company->name.'» по своей сделке'
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
    (new \skeeks\cms\models\CmsUser2manager(['client_id' => $hiddenPerson->id, 'worker_id' => $owner->id]))->save();
    $personRequest = (new CrmAccessRequests())->request((int)$other->id, 'client', (int)$hiddenPerson->id, CmsCrmAccessRequest::KIND_ACCESS);
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
    $check($refused && $refused->model_id === null && $refused->name === 'Отклонён запрос: доступ к клиенту, клиент «контакт из базы»',
        'a refusal does not name or link a hidden person');
    try {
        (new CrmAccessRequests())->decide($copy, true, (int)$admin->id);
        $check(false, 'a stale copy cannot decide again');
    } catch (DomainException $e) {
        $check(CmsCrmAccessRequest::findOne($personRequest->id)->status === CmsCrmAccessRequest::STATUS_DECLINED, 'the first decision stays');
    }
    // A cancel by a stale copy does not overwrite a decision.
    $cancelLater = (new CrmAccessRequests())->request((int)$other->id, 'client', (int)$hiddenPerson->id, CmsCrmAccessRequest::KIND_ACCESS);
    $staleCopy = CmsCrmAccessRequest::findOne($cancelLater->id);
    (new CrmAccessRequests())->decide($cancelLater, true, (int)$owner->id);
    try {
        (new CrmAccessRequests())->cancel($staleCopy, (int)$other->id);
        $check(false, 'a decided request cannot be cancelled');
    } catch (DomainException $e) {
        $check(CmsCrmAccessRequest::findOne($cancelLater->id)->status === CmsCrmAccessRequest::STATUS_APPROVED, 'the decision stays after a late cancel');
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
    $check($accessAsked && $accessAsked->name === $other->shortDisplayName.' просит разрешения работать с клиентом «'.$company->name.'»', 'the request notification text');
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
    $check(CrmAccess::instance()->canSeeCompany($other->id, (int)$company->id), 'a level below "full" works as full for now');
    $check(CrmAccess::instance()->defaultExecutorId((int)$company->id, null, (int)$other->id) === (int)$other->id, 'a linked employee takes a new deal himself');
    $check(!in_array((int)$other->id, CrmAccess::instance()->companyResponsibleIds((int)$company->id), true), '"edit" is not a "full" responsible');
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
    $noName = new CmsLead(['cms_site_id' => $siteId]);
    (new LeadTriage())->createLead($noName, new LeadIdentityForm(), (int)$other->id);
    $check($noName->name === 'Сделка №'.$noName->id, 'without a client the deal is named by its number');
    if (CmsLead::amountReady()) {
        $withAmount = CmsLead::findOne($noName->id);
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

    // --- leaders ---
    foreach (CmsDepartment::find()->all() as $department) {
        foreach (CmsDepartment::subordinateIds((int)$department->worker_id) as $subordinateId) {
            $check(in_array((int)$department->worker_id, CmsDepartment::leaderIdsOf($subordinateId), true), 'leaderIdsOf is the inverse of subordinateIds');
        }
    }

    echo 'OK crm-lead-triage-stand: '.$checks." checks\n";
} finally {
    $transaction->rollBack();
}
