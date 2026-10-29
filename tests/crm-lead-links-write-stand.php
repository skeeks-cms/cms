<?php
/**
 * Who a client record belongs to, when an employee writes it (audit F1/F2/F6, 29.10.2026):
 * CrmAccess::validateLeadLink() for services (CmsDeal), bills, documents and payments, through the
 * shared REST/MCP executor (cmsMcp->executeTool) and through an HTTP form with a valid CSRF token.
 *
 * The matrix: the client — a company, a person, moves company → company, company → person,
 * person → company, to a hidden client; the level — none, deal, edit, full, leader, administrator;
 * the deal — none, own, other's, closed (and reopened). After a refusal: no row in the database,
 * no event in the access journal and no name of the hidden client in the answer.
 *
 * The fixture is committed (the HTTP requests use their own connection), marked «Проверка:», and
 * always removed at the end.
 *
 * CRM_LEAD_LINKS_WRITE_TEST=local php tests/crm-lead-links-write-stand.php
 *   TEST_APP_ROOT (/app); TEST_DEAL_USER_ID (1663), TEST_LEADER_ID (110), TEST_ADMIN_ID (1);
 *   TEST_HTTP_BASE (http://nginx), TEST_HTTP_HOST (localhost:8080)
 */
if (getenv('CRM_LEAD_LINKS_WRITE_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_LEAD_LINKS_WRITE_TEST=local.');
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
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsUser;

$fail = 0;
$checks = 0;
$expect = static function (bool $ok, string $what) use (&$fail, &$checks) {
    $checks++;
    $fail += $ok ? 0 : 1;
    echo ($ok ? 'ok    ' : 'FAIL  ').$what."\n";
};

$db = Yii::$app->db;
$access = CrmAccess::instance();
$site = CmsSite::find()->default()->one();
Yii::$app->skeeks->site = $site;
$admin = CmsUser::findOne((int)(getenv('TEST_ADMIN_ID') ?: 1));
$worker = CmsUser::findOne((int)(getenv('TEST_DEAL_USER_ID') ?: 1663));
$leader = CmsUser::findOne((int)(getenv('TEST_LEADER_ID') ?: 110));
$uniq = (string)mt_rand(100000, 999999);
$mark = 'Проверка: ';
$ids = ['companies' => [], 'people' => [], 'leads' => []];

/** Removes the fixture and everything written on its clients. */
$clean = static function () use ($db, &$ids) {
    $companies = $ids['companies'] ?: [0];
    $people = $ids['people'] ?: [0];
    $leads = $ids['leads'] ?: [0];
    $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
    $client = ['or', ['cms_company_id' => $companies], ['cms_user_id' => $people], ['cms_lead_id' => $leads]];
    $bills = (new \yii\db\Query())->select('id')->from('shop_bill')->where($client)->column();
    $payments = (new \yii\db\Query())->select('id')->from('shop_payment')->where(['or', ['cms_company_id' => $companies], ['cms_user_id' => $people]])->column();
    $documents = (new \yii\db\Query())->select('id')->from('shop_document')->where($client)->column();
    $deals = (new \yii\db\Query())->select('id')->from('cms_deal')->where($client)->column();
    // The journal, its files and the notifications of the run: exact pairs model_code + model_id of the
    // fixture's records (ids of different tables coincide — never by the number alone), and the entries
    // written on its companies.
    $pairs = ['or'];
    foreach ([[\skeeks\cms\models\CmsCompany::class, $ids['companies']], [CmsUser::class, $ids['people']], [CmsLead::class, $ids['leads']],
                 [\skeeks\cms\models\CmsDeal::class, $deals], [\skeeks\cms\shop\models\ShopBill::class, $bills],
                 [\skeeks\cms\shop\models\ShopPayment::class, $payments], [\skeeks\cms\shop\models\ShopDocument::class, $documents]] as [$class, $modelIds]) {
        if ($modelIds) {
            $pairs[] = ['and', ['model_code' => (new $class())->skeeksModelCode], ['model_id' => $modelIds]];
        }
    }
    $logs = (new \yii\db\Query())->select('id')->from('cms_log')->where(['or', ['cms_company_id' => $companies], $pairs])->column();
    if ($db->getTableSchema('{{%cms_log_file}}') !== null) {
        $db->createCommand()->delete('{{%cms_log_file}}', ['cms_log_id' => $logs ?: [0]])->execute();
    }
    if ($db->getTableSchema('{{%cms_web_notify}}') !== null && count($pairs) > 1) {
        $db->createCommand()->delete('{{%cms_web_notify}}', $pairs)->execute();
    }
    foreach (['shop_bill2payment' => ['or', ['shop_bill_id' => $bills ?: [0]], ['shop_payment_id' => $payments ?: [0]]],
                 'cms_deal2bill' => ['shop_bill_id' => $bills ?: [0]], 'cms_deal2payment' => ['shop_payment_id' => $payments ?: [0]],
                 'shop_document2bill' => ['shop_document_id' => $documents ?: [0]], 'shop_document2deal' => ['shop_document_id' => $documents ?: [0]],
                 'shop_bill_item' => ['shop_bill_id' => $bills ?: [0]], 'shop_document_item' => ['shop_document_id' => $documents ?: [0]],
                 'shop_bill' => ['id' => $bills ?: [0]], 'shop_payment' => ['id' => $payments ?: [0]], 'shop_document' => ['id' => $documents ?: [0]],
                 'cms_deal' => ['id' => $deals ?: [0]],
                 'cms_crm_access_log' => ['or', ['and', ['record_code' => 'company'], ['record_id' => $companies]], ['and', ['record_code' => 'client'], ['record_id' => $people]]],
                 'cms_log' => ['id' => $logs ?: [0]],
                 'cms_lead_stage_history' => ['cms_lead_id' => $leads], 'cms_lead' => ['id' => $leads],
                 'cms_company2manager' => ['cms_company_id' => $companies], 'cms_user2manager' => ['client_id' => $people],
                 'cms_company' => ['id' => $companies], 'cms_user' => ['id' => $people]] as $table => $where) {
        if ($db->getTableSchema('{{%'.$table.'}}') !== null) {
            $db->createCommand()->delete('{{%'.$table.'}}', $where)->execute();
        }
    }
    $db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
};

try {
    // --- the fixture: clients with the levels of the employee, his deals and others' ---
    $company = static function (string $name, array $links) use ($db, $mark, $uniq, &$ids) {
        $db->createCommand()->insert('{{%cms_company}}', ['name' => $mark.$name.' '.$uniq, 'created_at' => time()])->execute();
        $id = (int)$db->getLastInsertID();
        $ids['companies'][] = $id;
        foreach ($links as [$userId, $level, $main]) {
            $db->createCommand()->insert('{{%cms_company2manager}}', ['cms_company_id' => $id, 'cms_user_id' => $userId, 'access_level' => $level, 'is_main' => $main])->execute();
        }
        return $id;
    };
    $A = $company('компания «Свои сделки»', [[(int)$admin->id, 'full', 1], [(int)$worker->id, 'deal', 0]]);
    $B = $company('скрытая компания', [[(int)$admin->id, 'full', 1]]);
    $E = $company('компания «edit»', [[(int)$admin->id, 'full', 1], [(int)$worker->id, 'edit', 0]]);
    $F = $company('компания «full»', [[(int)$admin->id, 'full', 1], [(int)$worker->id, 'full', 0]]);
    $hiddenName = $mark.'скрытая компания '.$uniq;
    $db->createCommand()->insert('{{%cms_user}}', ['username' => 'check-'.$uniq, 'first_name' => $mark.'клиент '.$uniq, 'is_worker' => 0,
        'auth_key' => 'check-'.$uniq, 'password_hash' => '', 'created_at' => time(), 'updated_at' => time(), 'cms_site_id' => (int)$site->id])->execute();
    $P = (int)$db->getLastInsertID();
    $ids['people'][] = $P;
    $db->createCommand()->insert('{{%cms_user2manager}}', ['client_id' => $P, 'worker_id' => (int)$admin->id, 'access_level' => 'full', 'is_main' => 1])->execute();
    $db->createCommand()->insert('{{%cms_user2manager}}', ['client_id' => $P, 'worker_id' => (int)$worker->id, 'access_level' => 'deal', 'is_main' => 0])->execute();
    $lead = static function (string $name, array $client, int $executorId, string $status = CmsLead::STATUS_IN_WORK) use ($db, $site, $mark, $uniq, &$ids) {
        $db->createCommand()->insert('{{%cms_lead}}', $client + ['name' => $mark.$name.' '.$uniq, 'status' => $status, 'executor_id' => $executorId,
            'cms_site_id' => (int)$site->id, 'source_type' => 'manual'])->execute();
        $id = (int)$db->getLastInsertID();
        $ids['leads'][] = $id;
        return $id;
    };
    $ownA = $lead('своя сделка А', ['cms_company_id' => $A], (int)$worker->id);
    $foreignA = $lead('чужая сделка А', ['cms_company_id' => $A], (int)$admin->id);
    $closedA = $lead('закрытая своя сделка А', ['cms_company_id' => $A], (int)$worker->id, CmsLead::STATUS_SUCCESS);
    $ownE = $lead('своя сделка E', ['cms_company_id' => $E], (int)$worker->id);
    $ownP = $lead('своя сделка клиента-человека', ['cms_user_id' => $P], (int)$worker->id);
    // A control entry of another record with the same number (another model_code): the cleanup must keep it.
    $db->createCommand()->insert('{{%cms_log}}', ['model_code' => (new \skeeks\cms\models\CmsTask())->skeeksModelCode, 'model_id' => $A,
        'log_type' => \skeeks\cms\models\CmsLog::LOG_TYPE_COMMENT, 'comment' => $mark.'чужая запись с тем же номером '.$uniq,
        'created_at' => time(), 'updated_at' => time()])->execute();
    $controlLogId = (int)$db->getLastInsertID();
    $paySystem = (int)(new \yii\db\Query())->select('id')->from('shop_pay_system')->orderBy(['id' => SORT_ASC])->scalar();

    $scopes = array_values(Yii::$app->oauth2Server->resources['cms-rest-api']['scopes']);
    $tool = static function (CmsUser $as, string $name, array $args) use ($scopes) {
        Yii::$app->user->setIdentity($as);
        try {
            $out = Yii::$app->cmsMcp->executeTool($name, $args, new \skeeks\cms\oauth2\models\CmsOauthAccessToken(['scopes' => json_encode($scopes)]), ['transport' => 'test']);
            return ['ok', json_encode($out, JSON_UNESCAPED_UNICODE)];
        } catch (\Throwable $e) {
            return ['error', $e->getMessage()];
        }
    };
    $kinds = [
        'счёт' => ['shop_bill', 'shop_bill_create', 'shop_bill_update',
            static fn(array $a) => $a + ['amount' => 100, 'currency_code' => 'RUB', 'shop_pay_system_id' => $paySystem, 'description' => 'Проверка: счёт']],
        'документ' => ['shop_document', 'shop_document_create', 'shop_document_update',
            static fn(array $a) => $a + ['type' => 'act', 'status' => 'draft', 'amount' => 100, 'currency_code' => 'RUB', 'description' => 'Проверка: документ']],
        'услуга' => ['cms_deal', 'cms_deal_create', 'cms_deal_update',
            static fn(array $a) => $a + ['name' => 'Проверка: услуга', 'description' => '', 'cms_deal_type_id' => 13, 'currency_code' => 'RUB', 'amount' => 100]],
    ];
    $rows = static fn(string $table, array $where) => (int)(new \yii\db\Query())->from($table)->where($where)->count();
    // The events of the fixture's clients (other sessions may write the journal of other clients meanwhile).
    $journal = static fn() => (int)(new \yii\db\Query())->from('cms_crm_access_log')->where(['or',
        ['and', ['record_code' => 'company'], ['record_id' => $ids['companies']]], ['and', ['record_code' => 'client'], ['record_id' => $ids['people']]]])->count();

    /** A create through the executor: allowed — a row appears; refused — the error, no row, no journal event, no hidden name. */
    $create = static function (string $title, CmsUser $as, string $kind, array $attrs, ?string $refusal) use ($kinds, $tool, $rows, $journal, $expect, $hiddenName) {
        [$table, $createTool, , $make] = $kinds[$kind];
        $where = array_intersect_key($attrs, array_flip(['cms_company_id', 'cms_user_id', 'cms_lead_id']));
        $before = $rows($table, $where);
        $journalBefore = $journal();
        [$status, $out] = $tool($as, $createTool, ['attributes' => $make($attrs)]);
        if ($refusal === null) {
            $expect($status === 'ok' && $rows($table, $where) === $before + 1, $title.': '.$kind.' создан'.($status === 'ok' ? '' : ' — '.mb_substr($out, 0, 160)));
            return;
        }
        $expect($status === 'error' && mb_strpos($out, $refusal) !== false, $title.': '.$kind.' — отказ «'.$refusal.'»'.($status === 'error' ? '' : ' — создан'));
        $expect($rows($table, $where) === $before && $journal() === $journalBefore && mb_strpos($out, $hiddenName) === false,
            $title.': '.$kind.' — нет записи, нет события журнала, нет чужого имени в ответе');
    };

    // --- creating: levels × deals ---
    foreach (array_keys($kinds) as $kind) {
        $create('нет доступа (скрытая компания)', $worker, $kind, ['cms_company_id' => $B], CrmAccess::CLIENT_NOT_AVAILABLE);
        $create('нет доступа, своя сделка другого клиента', $worker, $kind, ['cms_company_id' => $B, 'cms_lead_id' => $ownA], CrmAccess::LEAD_OTHER_CLIENT);
        $create('«Свои сделки», без сделки', $worker, $kind, ['cms_company_id' => $A], CrmAccess::OWN_LEAD_REQUIRED);
        $create('«Свои сделки», своя сделка', $worker, $kind, ['cms_company_id' => $A, 'cms_lead_id' => $ownA], null);
        $create('«Свои сделки», чужая сделка', $worker, $kind, ['cms_company_id' => $A, 'cms_lead_id' => $foreignA], CrmAccess::LEAD_NOT_AVAILABLE);
        $create('«Свои сделки», закрытая своя сделка', $worker, $kind, ['cms_company_id' => $A, 'cms_lead_id' => $closedA], CrmAccess::LEAD_CLOSED);
        $create('«edit», без сделки', $worker, $kind, ['cms_company_id' => $E], CrmAccess::OWN_LEAD_REQUIRED);
        $create('«edit», своя сделка', $worker, $kind, ['cms_company_id' => $E, 'cms_lead_id' => $ownE], null);
        $create('«Полный», без сделки', $worker, $kind, ['cms_company_id' => $F], null);
        $create('клиент-человек «Свои сделки», без сделки', $worker, $kind, ['cms_user_id' => $P], CrmAccess::OWN_LEAD_REQUIRED);
        $create('клиент-человек «Свои сделки», своя сделка', $worker, $kind, ['cms_user_id' => $P, 'cms_lead_id' => $ownP], null);
        $create('руководитель, сделка подчинённого', $leader, $kind, ['cms_company_id' => $A, 'cms_lead_id' => $ownA], null);
        $create('руководитель, без сделки', $leader, $kind, ['cms_company_id' => $A], CrmAccess::OWN_LEAD_REQUIRED);
        $create('руководитель, скрытая компания', $leader, $kind, ['cms_company_id' => $B], CrmAccess::CLIENT_NOT_AVAILABLE);
        $create('администратор, без сделки', $admin, $kind, ['cms_company_id' => $B], null);
        $create('администратор, чужая сделка', $admin, $kind, ['cms_company_id' => $A, 'cms_lead_id' => $foreignA], null);
        $create('администратор, закрытая сделка', $admin, $kind, ['cms_company_id' => $A, 'cms_lead_id' => $closedA], CrmAccess::LEAD_CLOSED);
        $create('администратор, сделка другого клиента', $admin, $kind, ['cms_company_id' => $E, 'cms_lead_id' => $ownA], CrmAccess::LEAD_OTHER_CLIENT);
    }
    // A reopened deal takes new records again.
    $db->createCommand()->update('{{%cms_lead}}', ['status' => CmsLead::STATUS_IN_WORK], ['id' => $closedA])->execute();
    foreach (array_keys($kinds) as $kind) {
        $create('сделка возвращена в работу', $worker, $kind, ['cms_company_id' => $A, 'cms_lead_id' => $closedA], null);
    }

    // --- moving an existing record (made by the administrator on his own deal) ---
    foreach ($kinds as $kind => [$table, $createTool, $updateTool, $make]) {
        [$status, $out] = $tool($admin, $createTool, ['attributes' => $make(['cms_company_id' => $A, 'cms_lead_id' => $ownA])]);
        $id = $status === 'ok' ? (int)(json_decode($out, true)['id'] ?? 0) : 0;
        $expect($id > 0, 'перенос: '.$kind.' для проверок создан');
        $belonging = static fn() => (new \yii\db\Query())->select(['cms_company_id', 'cms_user_id', 'cms_lead_id'])->from($table)->where(['id' => $id])->one();
        $move = static function (string $title, array $attrs, ?string $refusal) use ($tool, $worker, $updateTool, $id, $belonging, $journal, $expect, $kind, $hiddenName) {
            $before = $belonging();
            $journalBefore = $journal();
            [$status, $out] = $tool($worker, $updateTool, ['id' => $id, 'attributes' => $attrs]);
            if ($refusal === null) {
                $after = $belonging();
                $expect($status === 'ok' && (int)$after['cms_company_id'] === (int)(array_key_exists('cms_company_id', $attrs) ? $attrs['cms_company_id'] : $before['cms_company_id'])
                    && (int)$after['cms_lead_id'] === (int)(array_key_exists('cms_lead_id', $attrs) ? $attrs['cms_lead_id'] : $before['cms_lead_id']),
                    'перенос «'.$title.'»: '.$kind.' перенесён'.($status === 'ok' ? '' : ' — '.mb_substr($out, 0, 160)));
                return;
            }
            $expect($status === 'error' && mb_strpos($out, $refusal) !== false && $belonging() == $before && $journal() === $journalBefore
                && mb_strpos($out, $hiddenName) === false, 'перенос «'.$title.'»: '.$kind.' — отказ «'.$refusal.'», принадлежность и журнал прежние');
        };
        $move('только компания → скрытая, сделка прежняя', ['cms_company_id' => $B], CrmAccess::LEAD_OTHER_CLIENT);
        $move('в скрытую компанию без сделки', ['cms_company_id' => $B, 'cms_lead_id' => null], CrmAccess::CLIENT_NOT_AVAILABLE);
        $move('только компания → «edit», сделка прежняя', ['cms_company_id' => $E], CrmAccess::LEAD_OTHER_CLIENT);
        $move('компания → компания со своей сделкой', ['cms_company_id' => $E, 'cms_lead_id' => $ownE], null);
        $move('компания → человек со своей сделкой', ['cms_company_id' => null, 'cms_user_id' => $P, 'cms_lead_id' => $ownP], null);
        $move('человек → компания со своей сделкой', ['cms_company_id' => $A, 'cms_user_id' => null, 'cms_lead_id' => $ownA], null);
        $move('сделку убрать', ['cms_lead_id' => null], CrmAccess::OWN_LEAD_REQUIRED);
        $move('чужая сделка той же компании', ['cms_lead_id' => $foreignA], CrmAccess::LEAD_NOT_AVAILABLE);
    }

    // --- payments: only an available client; tied to a deal through the bill ---
    $payment = static fn(array $a) => ['attributes' => $a + ['amount' => 100, 'currency_code' => 'RUB', 'is_debit' => 1, 'shop_pay_system_id' => $paySystem,
        'comment' => 'Проверка: платёж', 'operation_at' => time()]];
    $before = $rows('shop_payment', ['cms_company_id' => $B]);
    [$status, $out] = $tool($worker, 'shop_payment_create', $payment(['cms_company_id' => $B]));
    $expect($status === 'error' && mb_strpos($out, CrmAccess::CLIENT_NOT_AVAILABLE) !== false && $rows('shop_payment', ['cms_company_id' => $B]) === $before
        && mb_strpos($out, $hiddenName) === false, 'платёж скрытой компании: отказ, нет записи, нет чужого имени');
    [$status, $out] = $tool($worker, 'shop_payment_create', $payment(['cms_company_id' => $A]));
    $expect($status === 'ok', 'платёж доступной компании: создан'.($status === 'ok' ? '' : ' — '.mb_substr($out, 0, 160)));
    [$status, $out] = $tool($admin, 'shop_payment_create', $payment(['cms_company_id' => $A]));
    $paymentId = $status === 'ok' ? (int)(json_decode($out, true)['id'] ?? 0) : 0;
    $billOwn = (int)(new \yii\db\Query())->select('id')->from('shop_bill')->where(['cms_lead_id' => $ownA])->scalar();
    $billForeign = (int)(new \yii\db\Query())->select('id')->from('shop_bill')->where(['cms_lead_id' => $foreignA])->scalar();
    $db->createCommand()->insert('shop_bill2payment', ['shop_bill_id' => $billOwn, 'shop_payment_id' => $paymentId, 'created_at' => time()])->execute();
    $db->createCommand()->update('shop_payment', ['created_by' => (int)$admin->id], ['id' => $paymentId])->execute();
    [$status] = $tool($worker, 'shop_payment_get', ['id' => $paymentId]);
    $expect($status === 'ok', 'платёж администратора по счёту своей сделки — виден');
    [$status, $out] = $tool($admin, 'shop_payment_create', $payment(['cms_company_id' => $A]));
    $foreignPayment = $status === 'ok' ? (int)(json_decode($out, true)['id'] ?? 0) : 0;
    $db->createCommand()->insert('shop_bill2payment', ['shop_bill_id' => $billForeign, 'shop_payment_id' => $foreignPayment, 'created_at' => time()])->execute();
    [$status] = $tool($worker, 'shop_payment_get', ['id' => $foreignPayment]);
    $expect($status === 'error', 'платёж администратора по счёту чужой сделки — не виден');

    // --- HTTP: the form of a service with a valid CSRF token ---
    $base = rtrim(getenv('TEST_HTTP_BASE') ?: 'http://nginx', '/');
    $jar = tempnam(sys_get_temp_dir(), 'lead-links');
    $http = static function (string $path, array $post = null) use ($base, $jar) {
        $curl = curl_init($base.$path);
        // A form is sent as the browser does it: a pjax POST (RequestResponse::isRequestPjaxPost()).
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => array_merge(['Host: '.(getenv('TEST_HTTP_HOST') ?: 'localhost:8080')],
                $post !== null ? ['X-Requested-With: XMLHttpRequest', 'X-PJAX: true', 'X-PJAX-Container: #sx-pjax-form'] : [])]);
        if ($post !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $body = (string)curl_exec($curl);
        $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [$code, html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
    };
    $http('/site?id='.(int)$worker->id);
    $postService = static function (array $attrs) use ($http) {
        [, $form] = $http('/~sxx/cms/admin-cms-deal/create');
        preg_match('~<meta name="csrf-param" content="([^"]+)">\s*<meta name="csrf-token" content="([^"]+)">~', $form, $m);
        $result = $http('/~sxx/cms/admin-cms-deal/create', [($m[1] ?? '_csrf') => ($m[2] ?? ''), 'CmsDeal' => $attrs + ['name' => 'Проверка: услуга формы',
            'cms_deal_type_id' => 13, 'currency_code' => 'RUB', 'amount' => 100, 'is_periodic' => 0, 'description' => '']]);
        if (getenv('DEBUG_HTTP')) { file_put_contents('/tmp/lead-links-http-'.md5(json_encode($attrs)).'.html', $result[1]); }
        return $result;
    };
    $before = $rows('cms_deal', ['cms_company_id' => $B]);
    [$code, $body] = $postService(['cms_company_id' => $B]);
    $expect($rows('cms_deal', ['cms_company_id' => $B]) === $before && mb_strpos($body, CrmAccess::CLIENT_NOT_AVAILABLE) !== false
        && mb_strpos($body, $hiddenName) === false, 'HTTP (CSRF): услуга скрытой компании — отказ в форме, нет записи, нет чужого имени ('.$code.')');
    $before = $rows('cms_deal', ['cms_lead_id' => $ownA]);
    [$code] = $postService(['cms_company_id' => $A, 'cms_lead_id' => $ownA]);
    $expect($rows('cms_deal', ['cms_lead_id' => $ownA]) === $before + 1, 'HTTP (CSRF): услуга по своей сделке — создана ('.$code.')');
    $before = $rows('cms_deal', ['cms_lead_id' => $foreignA]);
    [$code, $body] = $postService(['cms_company_id' => $A, 'cms_lead_id' => $foreignA]);
    $expect($rows('cms_deal', ['cms_lead_id' => $foreignA]) === $before && mb_strpos($body, CrmAccess::LEAD_NOT_AVAILABLE) !== false
        && mb_strpos($body, $mark.'чужая сделка А '.$uniq) === false, 'HTTP (CSRF): услуга по чужой сделке — отказ, нет имени чужой сделки ('.$code.')');
    // A hidden company with his own deal of another client: no record, the hidden name is not in the answer.
    $before = $rows('cms_deal', ['cms_company_id' => $B]);
    [$code, $body] = $postService(['cms_company_id' => $B, 'cms_lead_id' => $ownA]);
    $expect($rows('cms_deal', ['cms_company_id' => $B]) === $before && mb_strpos($body, $hiddenName) === false,
        'HTTP (CSRF): скрытая компания и своя сделка другого клиента — отказ, нет записи, нет скрытого имени ('.$code.')');
    @unlink($jar);
} catch (\Throwable $e) {
    $expect(false, get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().')');
} finally {
    $clean();
    $left = (int)(new \yii\db\Query())->from('cms_company')->where(['like', 'name', $mark.'%'.$uniq, false])->count();
    $expect($left === 0, 'данные «Проверка:» удалены');
    if (!empty($controlLogId)) {
        $expect((new \yii\db\Query())->from('cms_log')->where(['id' => $controlLogId])->exists(),
            'очистка не тронула чужую запись журнала с тем же номером и другим model_code');
        $db->createCommand()->delete('{{%cms_log}}', ['id' => $controlLogId])->execute();
    }
}
echo $fail ? "FAIL crm-lead-links-write-stand: $fail of $checks\n" : "OK crm-lead-links-write-stand: $checks checks\n";
exit($fail ? 1 : 0);
