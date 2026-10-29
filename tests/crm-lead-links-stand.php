<?php
/**
 * «Полноценная работа в рамках своих сделок» (owner's decision 29.10.2026) on the stand
 * database, rolled back. A service, a bill and a document are tied to a deal (cms_lead_id),
 * a payment through its bill:
 * - "Свои сделки" sees the records of his deals (and the leader — of his subordinates'
 *   deals), not the records of other deals of the client; an administrator sees all;
 * - a new record of a client seen in part needs his own open deal of that client
 *   (CrmAccess::validateLeadLink()); a deal of another employee or of another client is refused;
 *   a record without a client takes the client of its deal;
 * - the new responsible of a deal gets "Свои сделки" to its client when he has no link
 *   (CrmAccess::grantLeadExecutorLevel()), the main one and the other links stay, the
 *   access journal gets the event.
 *
 * CRM_LEAD_LINKS_STAND_TEST=local php tests/crm-lead-links-stand.php
 *   TEST_APP_ROOT — the application root (/app); TEST_DEAL_USER_ID — the employee with "Свои сделки"
 *   (1663); TEST_LEADER_ID — his leader (110); TEST_NEW_USER_ID — an employee without links (237)
 */
if (getenv('CRM_LEAD_LINKS_STAND_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_LEAD_LINKS_STAND_TEST=local.');
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
use skeeks\cms\crm\CrmAccessLog;
use skeeks\cms\crm\LeadTriage;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopDocument;
use skeeks\cms\shop\models\ShopPayment;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
    echo 'ok    '.$message."\n";
};
foreach (['cms_deal', 'shop_bill', 'shop_document'] as $table) {
    $check(CrmAccess::leadLinkReady($table), $table.' has the deal column (m261029_190000)');
}

$db = Yii::$app->db;
$access = CrmAccess::instance();
$transaction = $db->beginTransaction();
try {
    $site = CmsSite::find()->default()->one();
    Yii::$app->skeeks->site = $site;
    $admin = CmsUser::findOne(1);
    $worker = CmsUser::findOne((int)(getenv('TEST_DEAL_USER_ID') ?: 1663));
    $leader = CmsUser::findOne((int)(getenv('TEST_LEADER_ID') ?: 110));
    $newcomer = CmsUser::findOne((int)(getenv('TEST_NEW_USER_ID') ?: 237));
    $check($admin && $access->isAdmin($admin) && $worker && $leader && $newcomer && !$access->isAdmin($worker) && !$access->isAdmin($leader),
        'the stand has an administrator, an employee, his leader and an employee without links');
    $check(in_array((int)$leader->id, CmsDepartment::leaderIdsOf((int)$worker->id), true), 'the leader heads the employee');

    $as = static function (CmsUser $user) {
        Yii::$app->user->setIdentity($user);
    };
    $as($admin);
    $company = static function (string $name) use ($db, $site) {
        $db->createCommand()->insert('{{%cms_company}}', ['name' => 'Проверка: '.$name, 'created_at' => time()])->execute();
        return CmsCompany::findOne((int)$db->getLastInsertID());
    };
    $A = $company('компания со своими сделками');
    $B = $company('другая компания');
    foreach ([[$A, $admin, CrmAccess::LEVEL_FULL, 1], [$A, $worker, CrmAccess::LEVEL_DEAL, 0], [$B, $admin, CrmAccess::LEVEL_FULL, 1], [$B, $worker, CrmAccess::LEVEL_DEAL, 0]] as [$co, $user, $level, $main]) {
        $db->createCommand()->insert('{{%cms_company2manager}}', ['cms_company_id' => $co->id, 'cms_user_id' => $user->id, 'access_level' => $level, 'is_main' => $main])->execute();
    }
    $lead = static function (string $name, CmsCompany $co, CmsUser $executor) use ($db, $site) {
        $db->createCommand()->insert('{{%cms_lead}}', ['name' => 'Проверка: '.$name, 'status' => CmsLead::STATUS_IN_WORK, 'executor_id' => $executor->id,
            'cms_company_id' => $co->id, 'cms_site_id' => (int)$site->id, 'source_type' => 'manual'])->execute();
        return CmsLead::findOne((int)$db->getLastInsertID());
    };
    $own1 = $lead('первая своя сделка', $A, $worker);
    $own2 = $lead('вторая своя сделка', $A, $worker);
    $other = $lead('сделка администратора', $A, $admin);
    $ownB = $lead('своя сделка другой компании', $B, $worker);

    // --- a new record: only within his own deal of that client ---
    $records = [
        'счёт' => static fn(array $a) => new ShopBill($a + ['amount' => 100, 'currency_code' => 'RUB']),
        'документ' => static fn(array $a) => new ShopDocument($a + ['type' => ShopDocument::TYPE_ACT, 'status' => 'draft', 'amount' => 100, 'currency_code' => 'RUB']),
        'услуга' => static fn(array $a) => new CmsDeal($a + ['name' => 'Проверка: услуга', 'cms_deal_type_id' => 13, 'currency_code' => 'RUB', 'amount' => 100]),
    ];
    $leadError = static function ($model): ?string {
        $model->validate(['cms_lead_id']);
        return $model->getFirstError('cms_lead_id');
    };
    foreach ($records as $kind => $make) {
        $as($worker);
        $check($leadError($make(['cms_company_id' => $A->id])) === CrmAccess::OWN_LEAD_REQUIRED, '«Свои сделки»: '.$kind.' клиента без своей сделки — отказ');
        $check($leadError($make(['cms_company_id' => $A->id, 'cms_lead_id' => $own1->id])) === null, '«Свои сделки»: '.$kind.' по первой своей сделке');
        $check($leadError($make(['cms_company_id' => $A->id, 'cms_lead_id' => $own2->id])) === null, '«Свои сделки»: '.$kind.' по второй своей сделке');
        $check($leadError($make(['cms_company_id' => $A->id, 'cms_lead_id' => $other->id])) !== null, '«Свои сделки»: '.$kind.' по чужой сделке — отказ');
        $check($leadError($make(['cms_company_id' => $A->id, 'cms_lead_id' => $ownB->id])) === 'Сделка относится к другому клиенту.',
            '«Свои сделки»: '.$kind.' со сделкой другого клиента — отказ');
        $fromLead = $make(['cms_lead_id' => $own1->id]);
        $check($leadError($fromLead) === null && (int)$fromLead->cms_company_id === (int)$A->id, '«Свои сделки»: '.$kind.' из сделки получает её клиента');
        $as($leader);
        $check($leadError($make(['cms_company_id' => $A->id, 'cms_lead_id' => $own1->id])) === null, 'руководитель: '.$kind.' по сделке подчинённого');
        $check($leadError($make(['cms_company_id' => $A->id])) === CrmAccess::OWN_LEAD_REQUIRED, 'руководитель (уровень подчинённого «Свои сделки»): '.$kind.' без сделки — отказ');
        $as($admin);
        $check($leadError($make(['cms_company_id' => $A->id])) === null, 'администратор: '.$kind.' без сделки');
        $check($leadError($make(['cms_company_id' => $A->id, 'cms_lead_id' => $other->id])) === null, 'администратор: '.$kind.' по любой сделке клиента');
    }

    // --- «своё»: what is tied to his deals (another employee made it) ---
    $as($admin);
    $mark = mt_rand(100000, 999999);
    $saved = [];
    foreach (['own' => $own1, 'other' => $other] as $whose => $l) {
        $bill = new ShopBill(['cms_company_id' => $A->id, 'cms_lead_id' => $l->id, 'amount' => 100, 'currency_code' => 'RUB', 'shop_pay_system_id' => 8,
            'code' => 'check-'.$whose.'-'.$mark, 'description' => 'Проверка: счёт '.$whose]);
        $bill->save(false);
        $payment = new ShopPayment(['cms_company_id' => $A->id, 'amount' => 100, 'currency_code' => 'RUB', 'is_debit' => 1, 'cms_site_id' => (int)$site->id,
            'shop_pay_system_id' => 8, 'comment' => 'Проверка: платёж '.$whose, 'operation_at' => time()]);
        $payment->save(false);
        $db->createCommand()->insert('shop_bill2payment', ['shop_bill_id' => $bill->id, 'shop_payment_id' => $payment->id, 'created_at' => time()])->execute();
        $document = new ShopDocument(['cms_company_id' => $A->id, 'cms_lead_id' => $l->id, 'type' => ShopDocument::TYPE_ACT, 'status' => 'draft', 'amount' => 100,
            'currency_code' => 'RUB', 'issued_at' => time(), 'code' => 'check-'.$whose.'-'.$mark, 'description' => 'Проверка: документ '.$whose]);
        $document->save(false);
        $service = new CmsDeal(['name' => 'Проверка: услуга '.$whose, 'description' => '', 'cms_company_id' => $A->id, 'cms_lead_id' => $l->id, 'cms_deal_type_id' => 13,
            'start_at' => time(), 'amount' => 100, 'currency_code' => 'RUB', 'is_periodic' => 0, 'is_active' => 1]);
        $service->save(false);
        foreach ([$bill, $payment, $document, $service] as $record) {
            // Made by the administrator: «своё» only through the deal.
            $record->updateAttributes(['created_by' => (int)$admin->id]);
        }
        $saved[$whose] = ['счёт' => $bill, 'платёж' => $payment, 'документ' => $document, 'услуга' => $service];
    }
    $sees = static function (CmsUser $user, $record): bool {
        return $record::find()->forManager($user)->andWhere([$record::tableName().'.id' => (int)$record->id])->exists();
    };
    foreach ($saved['own'] as $kind => $record) {
        $check($sees($worker, $record), '«Свои сделки» видит: '.$kind.' своей сделки');
        $check(!$sees($worker, $saved['other'][$kind]), '«Свои сделки» не видит: '.$kind.' чужой сделки');
        $check($sees($leader, $record), 'руководитель видит: '.$kind.' сделки подчинённого');
        $check(!$sees($leader, $saved['other'][$kind]), 'руководитель не видит: '.$kind.' чужой сделки');
        $check($sees($admin, $record) && $sees($admin, $saved['other'][$kind]), 'администратор видит: '.$kind.' обеих сделок');
    }

    // --- the new responsible gets "Свои сделки" to the client of the deal ---
    $as($admin);
    $check($access->linkLevel($newcomer, 'company', (int)$A->id) === null, 'у нового ответственного нет связи с компанией');
    $journalBefore = (int)(new \yii\db\Query())->from('{{%cms_log}}')->andWhere(['log_type' => CrmAccessLog::LOG_TYPE])->count();
    (new LeadTriage())->assign($other, (int)$newcomer->id, (int)$admin->id, 'Проверка: смена ответственного');
    $check($access->linkLevel($newcomer, 'company', (int)$A->id) === CrmAccess::LEVEL_DEAL, 'новый ответственный получил «Свои сделки»');
    $check($access->mainResponsibleId('company', (int)$A->id) === (int)$admin->id, 'основной ответственный не изменился');
    $check($access->linkLevel($worker, 'company', (int)$A->id) === CrmAccess::LEVEL_DEAL, 'уровни других сотрудников не изменились');
    $check((int)(new \yii\db\Query())->from('{{%cms_log}}')->andWhere(['log_type' => CrmAccessLog::LOG_TYPE])->count() === $journalBefore + 1,
        'в ленту клиента записано одно событие доступа');
    // The journal «Доступы»: who changed the responsible, to whom and which level («было → стало»), the reason and the deal.
    $event = \skeeks\cms\models\CmsCrmAccessLog::find()->andWhere(['record_code' => 'company', 'record_id' => (int)$A->id, 'user_id' => (int)$newcomer->id])
        ->orderBy(['id' => SORT_DESC])->one();
    $check($event && $event->event === CrmAccessLog::GRANT && (int)$event->actor_id === (int)$admin->id && (int)$event->cms_lead_id === (int)$other->id
        && $event->level_to === CrmAccess::LEVEL_DEAL && $event->comment === 'Смена ответственного сделки №'.(int)$other->id,
        'журнал «Доступы»: кто сменил, кому, уровень, причина и сделка');
    $check(CrmAccessLog::levelChange($event) === 'нет доступа → Свои сделки', 'журнал «Доступы»: уровень «нет доступа → Свои сделки»');
    $feedEntry = \skeeks\cms\models\CmsLog::find()->andWhere(['log_type' => CrmAccessLog::LOG_TYPE, 'model_id' => (int)$A->id])->orderBy(['id' => SORT_DESC])->one();
    $feedData = $feedEntry && is_array($feedEntry->data) ? $feedEntry->data : [];
    $check($feedEntry && (int)($feedData['cms_lead_id'] ?? 0) === (int)$other->id
        && mb_strpos((string)$feedEntry->comment, 'Смена ответственного сделки №'.(int)$other->id) !== false,
        'лента клиента: та же запись со ссылкой на сделку');
    $journalCount = static fn() => (int)\skeeks\cms\models\CmsCrmAccessLog::find()->andWhere(['record_code' => 'company', 'record_id' => (int)$A->id])->count();
    $journal = $journalCount();
    (new LeadTriage())->assign($other, (int)$worker->id, (int)$admin->id);
    $check($journalCount() === $journal, 'ответственный, у которого уже есть уровень: событие только у сделки, журнал без записи');
    // N6: the same level again — a success without a change and without a journal event; no link — an error.
    $check($access->setLevel('company', (int)$A->id, (int)$worker->id, CrmAccess::LEVEL_DEAL) === false && $journalCount() === $journal,
        'тот же уровень повторно: успех без изменений и без записи в журнал');
    $refused = null;
    try {
        $access->setLevel('company', (int)$A->id, (int)$leader->id, CrmAccess::LEVEL_DEAL);
    } catch (\DomainException $e) {
        $refused = $e->getMessage();
    }
    $check($refused === 'Сотрудник не работает с этим клиентом.', 'уровень сотруднику без связи: ошибка');
    $db->createCommand()->update('{{%cms_company2manager}}', ['access_level' => CrmAccess::LEVEL_EDIT], ['cms_company_id' => $A->id, 'cms_user_id' => $newcomer->id])->execute();
    $journal = $journalCount();
    (new LeadTriage())->assign($other, (int)$newcomer->id, (int)$admin->id);
    $check($access->linkLevel($newcomer, 'company', (int)$A->id) === CrmAccess::LEVEL_EDIT && $journalCount() === $journal,
        'уровень выше «Свои сделки» не понижается, журнал без записи');
    (new LeadTriage())->assign($other, (int)$admin->id, (int)$admin->id);
    $check(CmsCompany2manager::find()->andWhere(['cms_company_id' => $A->id, 'cms_user_id' => $admin->id])->count() === 1
        && $access->linkLevel($admin, 'company', (int)$A->id) === CrmAccess::LEVEL_FULL, 'администратору связь не добавляется');

    echo 'OK crm-lead-links-stand: '.$checks." checks\n";
} catch (\Throwable $e) {
    // The checks print as they go: Yii's fatal handler would hide the reason behind "headers already sent".
    echo get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
    $transaction->rollBack();
    exit(1);
} finally {
    $transaction->rollBack();
}
