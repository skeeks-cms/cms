<?php
/**
 * П2а+ leak audit: an employee with the level «Свои сделки» (`deal`) of a client must not see
 * other employees' deals, services (CmsDeal), bills, payments, documents, proposals and tasks
 * that are not tied to his own deals; a protected client without his link is not visible at all;
 * a client of the general pool shows its data and its own feed only, until «Взять в работу».
 *
 * The audit commits a fixture marked «Аудит: …» (the web requests use their own connection),
 * walks the site by real HTTP as the employee (sections, cards, tabs, feeds, autocomplete,
 * printouts) and calls the REST/MCP tools in his name (McpComponent::executeTool()), then
 * removes the fixture. Every place is reported as `ok` or `LEAK`; the script fails while any
 * leak remains.
 *
 * CRM_LEAK_AUDIT=local php tests/crm-leak-audit.php
 *   TEST_APP_ROOT   — the application root, /app by default
 *   TEST_DEAL_USER_ID — the employee (1663 by default), TEST_ADMIN_ID — the main responsible (1)
 *   TEST_HTTP_BASE (http://nginx), TEST_HTTP_HOST (localhost:8080) — the local site
 *   AUDIT_KEEP=1    — keep the fixture for a manual check in the browser (ids are printed);
 *   AUDIT_CLEAN=1   — only remove every «Аудит: » record left by a kept run
 */
if (getenv('CRM_LEAK_AUDIT') !== 'local') {
    throw new RuntimeException('Set CRM_LEAK_AUDIT=local.');
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
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopDocument;
use skeeks\cms\shop\models\ShopPayment;

const AUDIT_PREFIX = 'Аудит: ';
$db = Yii::$app->db;

/** Removes every record of the audit: by the prefix and by the ids hanging on the audit companies and people. */
$clean = static function () use ($db) {
    $like = ['like', 'name', AUDIT_PREFIX.'%', false];
    $companies = CmsCompany::find()->select('id')->andWhere($like)->column();
    $people = CmsUser::find()->select('id')->andWhere(['like', 'first_name', AUDIT_PREFIX.'%', false])->column();
    $leads = CmsLead::find()->select('id')->andWhere(['or', $like, ['cms_company_id' => $companies ?: [0]], ['cms_user_id' => $people ?: [0]]])->column();
    $deals = CmsDeal::find()->select('id')->andWhere(['or', $like, ['cms_company_id' => $companies ?: [0]], ['cms_user_id' => $people ?: [0]]])->column();
    $bills = ShopBill::find()->select('id')->andWhere(['or', ['like', 'description', AUDIT_PREFIX.'%', false], ['cms_company_id' => $companies ?: [0]], ['cms_user_id' => $people ?: [0]]])->column();
    $payments = ShopPayment::find()->select('id')->andWhere(['or', ['like', 'comment', AUDIT_PREFIX.'%', false], ['cms_company_id' => $companies ?: [0]], ['cms_user_id' => $people ?: [0]]])->column();
    $documents = ShopDocument::find()->select('id')->andWhere(['or', ['like', 'description', AUDIT_PREFIX.'%', false], ['cms_company_id' => $companies ?: [0]], ['cms_user_id' => $people ?: [0]]])->column();
    $tasks = CmsTask::find()->select('id')->andWhere(['or', $like, ['cms_company_id' => $companies ?: [0]], ['cms_lead_id' => $leads ?: [0]], ['cms_deal_id' => $deals ?: [0]]])->column();
    $proposals = $db->createCommand('SELECT id FROM cms_proposal WHERE name LIKE :p OR cms_company_id IN ('.implode(',', array_map('intval', $companies ?: [0])).')', [':p' => AUDIT_PREFIX.'%'])->queryColumn();
    $calls = $db->createCommand('SELECT id FROM cms_telephony_call WHERE provider_call_id LIKE :p', [':p' => 'audit-%'])->queryColumn();
    $messages = $db->createCommand('SELECT id FROM cms_comm_message WHERE subject LIKE :p', [':p' => AUDIT_PREFIX.'%'])->queryColumn();
    $conversations = $db->createCommand('SELECT id FROM cms_comm_conversation WHERE thread_key LIKE :p', [':p' => 'audit-%'])->queryColumn();
    $codes = [
        (new CmsCompany())->skeeksModelCode => $companies, (new CmsUser())->skeeksModelCode => $people,
        (new CmsLead())->skeeksModelCode => $leads, (new CmsDeal())->skeeksModelCode => $deals,
        (new ShopBill())->skeeksModelCode => $bills, (new ShopPayment())->skeeksModelCode => $payments,
        (new ShopDocument())->skeeksModelCode => $documents, (new CmsTask())->skeeksModelCode => $tasks,
    ];
    foreach ($codes as $code => $ids) {
        if ($ids) {
            CmsLog::deleteAll(['model_code' => $code, 'model_id' => $ids]);
            $db->createCommand()->delete('cms_web_notify', ['model_code' => $code, 'model_id' => $ids])->execute();
        }
    }
    $del = static function (string $table, array $where) use ($db) {
        $db->createCommand()->delete($table, $where)->execute();
    };
    if ($messages) {
        $del('cms_comm_link', ['cms_comm_message_id' => $messages]);
        $del('cms_comm_message', ['id' => $messages]);
    }
    if ($conversations) {
        $del('cms_comm_link', ['model_code' => 'conversation-audit', 'model_id' => $conversations]);
        $del('cms_comm_conversation', ['id' => $conversations]);
    }
    if ($calls) {
        $del('cms_telephony_call', ['id' => $calls]);
    }
    if ($proposals) {
        foreach (['cms_proposal_version', 'cms_proposal_send'] as $table) {
            $del($table, ['cms_proposal_id' => $proposals]);
        }
        $del('cms_proposal', ['id' => $proposals]);
    }
    if ($tasks) {
        CmsTask::deleteAll(['id' => $tasks]);
    }
    if ($documents) {
        $del('shop_document2deal', ['shop_document_id' => $documents]);
        $del('shop_document_item', ['shop_document_id' => $documents]);
        ShopDocument::deleteAll(['id' => $documents]);
    }
    if ($payments) {
        $del('shop_bill2payment', ['shop_payment_id' => $payments]);
        $del('cms_deal2payment', ['shop_payment_id' => $payments]);
        ShopPayment::deleteAll(['id' => $payments]);
    }
    if ($bills) {
        $del('cms_deal2bill', ['shop_bill_id' => $bills]);
        $del('shop_bill_item', ['shop_bill_id' => $bills]);
        ShopBill::deleteAll(['id' => $bills]);
    }
    if ($deals) {
        CmsDeal::deleteAll(['id' => $deals]);
    }
    if ($leads) {
        $del('cms_lead_stage_history', ['cms_lead_id' => $leads]);
        CmsLead::deleteAll(['id' => $leads]);
    }
    if ($companies) {
        CmsCompany2user::deleteAll(['cms_company_id' => $companies]);
        CmsCompany2manager::deleteAll(['cms_company_id' => $companies]);
        CmsCompany::deleteAll(['id' => $companies]);
    }
    if ($people) {
        CmsCompany2user::deleteAll(['cms_user_id' => $people]);
        CmsUser2manager::deleteAll(['client_id' => $people]);
        CmsUser::deleteAll(['id' => $people]);
    }

    return count($companies) + count($people) + count($leads) + count($deals) + count($bills) + count($payments) + count($documents) + count($tasks) + count($proposals);
};

if (getenv('AUDIT_CLEAN')) {
    echo 'removed: '.$clean()." records\n";
    exit(0);
}

$site = \skeeks\cms\models\CmsSite::find()->default()->one();
Yii::$app->skeeks->site = $site;
$siteId = (int)$site->id;
$admin = CmsUser::findOne((int)(getenv('TEST_ADMIN_ID') ?: 1));
$worker = CmsUser::findOne((int)(getenv('TEST_DEAL_USER_ID') ?: 1663));
if (!$admin || !$worker || Yii::$app->authManager->checkAccess((int)$worker->id, \skeeks\cms\rbac\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
    throw new RuntimeException('The stand needs the main responsible TEST_ADMIN_ID and a non-administrator TEST_DEAL_USER_ID.');
}
$uniq = (string)mt_rand(100000, 999999);
$name = static fn(string $what) => AUDIT_PREFIX.$what.' '.$uniq;

// --- the results: a place, how it was checked, ok / LEAK ---
$results = [];
$report = static function (string $place, string $how, bool $leak, string $detail = '') use (&$results) {
    $results[] = [$place, $how, $leak ? 'LEAK' : 'ok', $detail];
};

$fx = [];
try {
    // ============ the fixture ============
    $link = static function (string $class, string $column, int $recordId, int $userId, string $level, int $main) {
        $worker = $class === CmsUser2manager::class ? 'worker_id' : 'cms_user_id';
        $row = new $class([$column => $recordId, $worker => $userId]);
        $row->save(false);
        $class::updateAll(['access_level' => $level, 'is_main' => $main], [$column => $recordId, $worker => $userId]);
    };
    $company = function (string $what, array $links, string $protection = null) use ($name, $link, $db) {
        $model = new CmsCompany(['name' => $name($what)]);
        $model->save(false);
        CmsCompany2manager::deleteAll(['cms_company_id' => $model->id]);
        foreach ($links as [$userId, $level, $main]) {
            $link(CmsCompany2manager::class, 'cms_company_id', (int)$model->id, $userId, $level, $main);
        }
        if ($protection !== null) {
            $db->createCommand()->update('cms_company', ['crm_protection' => $protection], ['id' => $model->id])->execute();
        }
        return $model;
    };
    $lead = static function (string $title, array $client, int $executorId, float $amount = 0) use ($siteId) {
        $model = new CmsLead($client + ['name' => $title, 'cms_site_id' => $siteId, 'executor_id' => $executorId, 'status' => CmsLead::STATUS_IN_WORK]);
        if ($model->hasAttribute('amount')) {
            $model->amount = $amount;
        }
        $model->save(false);
        return $model;
    };
    $A = $company('Компания А', [[(int)$admin->id, CrmAccess::LEVEL_FULL, 1], [(int)$worker->id, CrmAccess::LEVEL_DEAL, 0]]);
    $pool = $company('Компания из пула', []);
    $protected = $company('Защищённая компания', [[(int)$admin->id, CrmAccess::LEVEL_FULL, 1]], 'always');

    // a contact person of A and a client person with his own main responsible
    $person = new CmsUser(['first_name' => $name('Клиент Б'), 'last_name' => 'Аудитов', 'cms_site_id' => $siteId,
        'auth_key' => Yii::$app->security->generateRandomString()]);
    $person->save(false);
    CmsUser2manager::deleteAll(['client_id' => $person->id]);
    $link(CmsUser2manager::class, 'client_id', (int)$person->id, (int)$admin->id, CrmAccess::LEVEL_FULL, 1);
    $link(CmsUser2manager::class, 'client_id', (int)$person->id, (int)$worker->id, CrmAccess::LEVEL_DEAL, 0);

    $own = $lead($name('своя сделка'), ['cms_company_id' => $A->id], (int)$worker->id);
    $foreign = $lead($name('чужая сделка'), ['cms_company_id' => $A->id], (int)$admin->id, 913377);
    $ownP = $lead($name('своя сделка клиента Б'), ['cms_user_id' => $person->id], (int)$worker->id);
    $foreignP = $lead($name('чужая сделка клиента Б'), ['cms_user_id' => $person->id], (int)$admin->id);
    $poolLead = $lead($name('сделка компании из пула'), ['cms_company_id' => $pool->id], (int)$admin->id);
    $protectedLead = $lead($name('сделка защищённой'), ['cms_company_id' => $protected->id], (int)$admin->id);

    $service = new CmsDeal(['name' => $name('услуга'), 'description' => '', 'cms_company_id' => $A->id, 'cms_deal_type_id' => 13,
        'start_at' => time() - 86400, 'end_at' => time() + 86400 * 300, 'amount' => 4321, 'currency_code' => 'RUB', 'is_periodic' => 0, 'is_active' => 1]);
    $service->save(false);
    $serviceP = new CmsDeal(['name' => $name('услуга клиента Б'), 'description' => '', 'cms_user_id' => $person->id, 'cms_deal_type_id' => 13,
        'start_at' => time() - 86400, 'amount' => 4322, 'currency_code' => 'RUB', 'is_periodic' => 0, 'is_active' => 1]);
    $serviceP->save(false);

    $bill = static function (array $client, float $amount, string $what) use ($name) {
        $model = new ShopBill($client + ['amount' => $amount, 'currency_code' => 'RUB', 'shop_pay_system_id' => 8,
            'code' => 'audit-'.Yii::$app->security->generateRandomString(16), 'description' => $name($what)]);
        $model->save(false);
        return $model;
    };
    $billA = $bill(['cms_company_id' => $A->id], 91337.71, 'счёт компании А');
    $db->createCommand()->insert('cms_deal2bill', ['shop_bill_id' => $billA->id, 'cms_deal_id' => $service->id, 'created_at' => time()])->execute();
    $billP = $bill(['cms_user_id' => $person->id], 91338.72, 'счёт клиента Б');
    $billPool = $bill(['cms_company_id' => $pool->id], 91339.73, 'счёт компании из пула');
    $billProt = $bill(['cms_company_id' => $protected->id], 91340.74, 'счёт защищённой');

    $payment = static function (array $client, float $amount, string $what) use ($name, $siteId) {
        $model = new ShopPayment($client + ['amount' => $amount, 'currency_code' => 'RUB', 'is_debit' => 1, 'cms_site_id' => $siteId,
            'shop_pay_system_id' => 8, 'comment' => $name($what), 'operation_at' => time()]);
        $model->save(false);
        return $model;
    };
    $payA = $payment(['cms_company_id' => $A->id], 81337.71, 'платёж компании А');
    $payP = $payment(['cms_user_id' => $person->id], 81338.72, 'платёж клиента Б');
    $payProt = $payment(['cms_company_id' => $protected->id], 81340.74, 'платёж защищённой');

    $document = static function (array $client, string $what) use ($name) {
        $model = new ShopDocument($client + ['type' => ShopDocument::TYPE_ACT, 'status' => 'draft', 'amount' => 71337.71, 'currency_code' => 'RUB',
            'issued_at' => time(), 'code' => 'audit-'.Yii::$app->security->generateRandomString(16), 'description' => $name($what)]);
        $model->save(false);
        return $model;
    };
    $docA = $document(['cms_company_id' => $A->id], 'документ компании А');
    $docP = $document(['cms_user_id' => $person->id], 'документ клиента Б');

    $proposal = static function (array $fields, string $what) use ($name, $siteId, $admin, $db) {
        $number = 900000 + (int)$db->createCommand('SELECT COUNT(*) FROM cms_proposal')->queryScalar() + mt_rand(1, 99999);
        $db->createCommand()->insert('cms_proposal', $fields + ['cms_site_id' => $siteId, 'kind' => 'proposal', 'number' => $number,
            'name' => $name($what), 'status' => 'draft', 'responsible_id' => $admin->id, 'currency_code' => 'RUB',
            'created_at' => time(), 'updated_at' => time(), 'created_by' => $admin->id])->execute();
        return (int)$db->getLastInsertID();
    };
    $kpForeign = $proposal(['cms_lead_id' => $foreign->id, 'cms_company_id' => $A->id], 'КП по чужой сделке');
    $kpCompany = $proposal(['cms_company_id' => $A->id], 'КП компании без сделки');
    $kpService = $proposal(['cms_deal_id' => $service->id, 'cms_company_id' => $A->id, 'cms_lead_id' => $foreign->id], 'КП по услуге и чужой сделке');
    $kpOwn = $proposal(['cms_lead_id' => $own->id, 'cms_company_id' => $A->id, 'responsible_id' => $worker->id], 'КП по своей сделке');

    $task = static function (array $fields, string $what, int $executorId) use ($name) {
        $model = new CmsTask($fields + ['name' => $name($what), 'executor_id' => $executorId, 'status' => CmsTask::STATUS_NEW]);
        $model->save(false);
        return $model;
    };
    $taskForeign = $task(['cms_company_id' => $A->id, 'cms_lead_id' => $foreign->id], 'задача чужой сделки', (int)$admin->id);
    $taskCompany = $task(['cms_company_id' => $A->id], 'задача компании без сделки', (int)$admin->id);
    $taskService = $task(['cms_deal_id' => $service->id], 'задача услуги', (int)$admin->id);
    $taskOwn = $task(['cms_company_id' => $A->id, 'cms_lead_id' => $own->id], 'задача своей сделки', (int)$worker->id);
    $taskProt = $task(['cms_company_id' => $protected->id], 'задача защищённой', (int)$admin->id);

    // an outgoing call of the main responsible on the other deal (no notifier: not incoming)
    $db->createCommand()->insert('cms_telephony_call', ['created_at' => time(), 'updated_at' => time(), 'cms_company_id' => $A->id,
        'cms_lead_id' => $foreign->id, 'cms_worker_user_id' => $admin->id, 'provider_call_id' => 'audit-'.$uniq, 'direction' => 'out',
        'status' => 'answered', 'duration' => 61, 'client_phone' => '+79990'.$uniq])->execute();
    $callId = (int)$db->getLastInsertID();
    $db->createCommand()->insert('cms_telephony_call', ['created_at' => time(), 'updated_at' => time(), 'cms_company_id' => $protected->id,
        'cms_worker_user_id' => $admin->id, 'provider_call_id' => 'audit-p'.$uniq, 'direction' => 'out',
        'status' => 'answered', 'duration' => 62, 'client_phone' => '+79991'.$uniq])->execute();
    $callProt = (int)$db->getLastInsertID();

    // a letter linked only to the other deal and to the service, and one linked to the company (the client's own)
    $db->createCommand()->insert('cms_comm_conversation', ['cms_site_id' => $siteId, 'channel' => 'email', 'thread_key' => 'audit-'.$uniq,
        'subject' => $name('переписка'), 'created_at' => time(), 'updated_at' => time(), 'last_message_at' => time()])->execute();
    $conversation = (int)$db->getLastInsertID();
    $letter = static function (string $what, array $links) use ($db, $siteId, $conversation, $name, $uniq) {
        $db->createCommand()->insert('cms_comm_message', ['cms_site_id' => $siteId, 'cms_comm_conversation_id' => $conversation, 'channel' => 'email',
            'direction' => 'in', 'status' => 'received', 'external_id' => 'audit-'.$what.'-'.$uniq, 'from_address' => 'audit-'.$uniq.'@example.test',
            'subject' => $name($what), 'body_text' => $name($what), 'created_at' => time(), 'updated_at' => time(), 'message_at' => time()])->execute();
        $id = (int)$db->getLastInsertID();
        foreach ($links as [$code, $modelId]) {
            $db->createCommand()->insert('cms_comm_link', ['cms_comm_message_id' => $id, 'model_code' => $code, 'model_id' => $modelId, 'link_type' => 'manual', 'created_at' => time()])->execute();
        }
        return $id;
    };
    $letterForeign = $letter('письмо по чужой сделке', [[(new CmsLead())->skeeksModelCode, (int)$foreign->id]]);
    $letterService = $letter('письмо по услуге', [[(new CmsDeal())->skeeksModelCode, (int)$service->id]]);
    $letterCompany = $letter('письмо компании', [[(new CmsCompany())->skeeksModelCode, (int)$A->id]]);
    $letterProt = $letter('письмо защищённой', [[(new CmsCompany())->skeeksModelCode, (int)$protected->id]]);

    // the feed: comments on the other deal, the bill, the service, the company task, the pool's deal; the company's own comment
    $comment = static function ($model, string $what, array $client) use ($name, $admin) {
        $log = new CmsLog($client + ['model_code' => $model->skeeksModelCode, 'model_id' => $model->id, 'log_type' => CmsLog::LOG_TYPE_COMMENT,
            'comment' => $name($what), 'created_by' => $admin->id]);
        $log->save(false);
        return $log;
    };
    $comment($foreign, 'комментарий чужой сделки', ['cms_company_id' => $A->id]);
    $comment($billA, 'комментарий счёта', ['cms_company_id' => $A->id]);
    $comment($payA, 'комментарий платежа', ['cms_company_id' => $A->id]);
    $comment($docA, 'комментарий документа', ['cms_company_id' => $A->id]);
    $comment($service, 'комментарий услуги', ['cms_company_id' => $A->id]);
    $comment($taskCompany, 'комментарий задачи компании', ['cms_company_id' => $A->id]);
    $comment($foreignP, 'комментарий чужой сделки клиента Б', ['cms_user_id' => $person->id]);
    $comment($billP, 'комментарий счёта клиента Б', ['cms_user_id' => $person->id]);
    $comment($poolLead, 'комментарий сделки пула', ['cms_company_id' => $pool->id]);
    $comment($A, 'комментарий компании', ['cms_company_id' => $A->id]);
    $comment($own, 'комментарий своей сделки', ['cms_company_id' => $A->id]);

    echo "fixture: company A {$A->id}, pool {$pool->id}, protected {$protected->id}, person {$person->id}; leads own {$own->id} foreign {$foreign->id};"
        ." service {$service->id}; bill {$billA->id}; payment {$payA->id}; document {$docA->id}; proposals $kpForeign/$kpCompany/$kpService/$kpOwn;"
        ." tasks {$taskForeign->id}/{$taskCompany->id}/{$taskService->id}/{$taskOwn->id}; call $callId; letters $letterForeign/$letterService/$letterCompany\n";

    // ============ HTTP as the employee ============
    $base = rtrim(getenv('TEST_HTTP_BASE') ?: 'http://nginx', '/');
    $jar = tempnam(sys_get_temp_dir(), 'crm-leak');
    $http = static function (string $path, array $post = null, bool $ajax = false) use ($base, $jar) {
        $curl = curl_init($base.$path);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => array_merge(['Host: '.(getenv('TEST_HTTP_HOST') ?: 'localhost:8080')], ($post !== null || $ajax) ? ['X-Requested-With: XMLHttpRequest'] : []),
        ]);
        if ($post !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $body = (string)curl_exec($curl);
        $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        // one space for &nbsp; and thin spaces of the amounts
        $body = str_replace(["\xC2\xA0", "\xE2\x80\xAF", "\xE2\x80\x89", '&nbsp;', '&#160;'], ' ', html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return [$code, $body];
    };
    $http('/site?id='.(int)$worker->id);
    [$code, $home] = $http('/~sxx/cms/admin-cms-company/view?pk='.(int)$A->id);
    if ($code !== 200 || strpos($home, (string)$worker->shortDisplayName) === false) {
        throw new RuntimeException('The HTTP session is not the employee\'s ('.$code.'): check SKEEKS_LOCAL_STAND and /site?id=.');
    }

    // what of the foreign things a page shows
    $foreignMarkers = [
        'чужая сделка' => $foreign->name, 'чужая сделка клиента Б' => $foreignP->name, 'сделка защищённой' => $protectedLead->name,
        'сделка пула' => $poolLead->name, 'услуга' => $service->name, 'услуга клиента Б' => $serviceP->name,
        'счёт А' => '91 337', 'счёт Б' => '91 338', 'счёт пула' => '91 339', 'счёт защищённой' => '91 340',
        'платёж А' => '81 337', 'платёж Б' => '81 338', 'платёж защищённой' => '81 340',
        'документ А' => $docA->description, 'документ Б' => $docP->description,
        'КП чужой сделки' => AUDIT_PREFIX.'КП по чужой сделке '.$uniq, 'КП без сделки' => AUDIT_PREFIX.'КП компании без сделки '.$uniq,
        'КП услуги' => AUDIT_PREFIX.'КП по услуге и чужой сделке '.$uniq,
        'задача чужой сделки' => $taskForeign->name, 'задача без сделки' => $taskCompany->name, 'задача услуги' => $taskService->name,
        'задача защищённой' => $taskProt->name,
        'коммент. чужой сделки' => AUDIT_PREFIX.'комментарий чужой сделки '.$uniq, 'коммент. счёта' => AUDIT_PREFIX.'комментарий счёта '.$uniq,
        'коммент. платежа' => AUDIT_PREFIX.'комментарий платежа '.$uniq, 'коммент. документа' => AUDIT_PREFIX.'комментарий документа '.$uniq,
        'коммент. услуги' => AUDIT_PREFIX.'комментарий услуги '.$uniq, 'коммент. задачи' => AUDIT_PREFIX.'комментарий задачи компании '.$uniq,
        'коммент. чужой сделки Б' => AUDIT_PREFIX.'комментарий чужой сделки клиента Б '.$uniq, 'коммент. счёта Б' => AUDIT_PREFIX.'комментарий счёта клиента Б '.$uniq,
        'коммент. сделки пула' => AUDIT_PREFIX.'комментарий сделки пула '.$uniq,
        'письмо по чужой сделке' => AUDIT_PREFIX.'письмо по чужой сделке '.$uniq, 'письмо по услуге' => AUDIT_PREFIX.'письмо по услуге '.$uniq,
        'письмо защищённой' => AUDIT_PREFIX.'письмо защищённой '.$uniq,
        'защищённая компания' => $protected->name, 'сумма чужой сделки' => '913 377',
    ];
    $routes = [
        'чужая сделка' => ['admin-cms-lead', $foreign->id], 'чужая сделка клиента Б' => ['admin-cms-lead', $foreignP->id],
        'услуга' => ['admin-cms-deal', $service->id], 'счёт А' => ['admin-cms-bill', $billA->id], 'счёт Б' => ['admin-cms-bill', $billP->id],
        'платёж А' => ['admin-payment', $payA->id], 'платёж Б' => ['admin-payment', $payP->id], 'документ А' => ['admin-cms-document', $docA->id],
        'КП чужой сделки' => ['admin-cms-proposal', $kpForeign], 'КП без сделки' => ['admin-cms-proposal', $kpCompany], 'КП услуги' => ['admin-cms-proposal', $kpService],
        'задача чужой сделки' => ['admin-cms-task', $taskForeign->id], 'задача без сделки' => ['admin-cms-task', $taskCompany->id],
        'защищённая компания' => ['admin-cms-company', $protected->id],
    ];
    $found = static function (string $body, array $keys = null) use ($foreignMarkers, $routes): array {
        $hits = [];
        foreach ($foreignMarkers as $key => $marker) {
            if ($keys !== null && !in_array($key, $keys, true)) {
                continue;
            }
            if (preg_match('~^\d+ \d+$~', $marker)) {
                // an amount: 91 337,71 on a page, 91337.71 in the CSV export — not a part of another number
                $hit = (bool)preg_match('~(?<![\d ])(?:'.$marker.'|'.str_replace(' ', '', $marker).')(?:[.,]\d\d|(?!\s?\d))~u', $body);
            } else {
                $hit = $marker !== '' && mb_strpos($body, $marker) !== false;
            }
            if (!$hit && isset($routes[$key])) {
                [$route, $id] = $routes[$key];
                $hit = (bool)preg_match('~'.preg_quote($route, '~').'/(?:view|update|index)[^"\'\s<]*?pk(?:=|%3D)'.(int)$id.'(?!\d)~', $body);
            }
            if ($hit) {
                $hits[] = $key;
            }
        }
        return $hits;
    };
    $page = static function (string $place, string $path, array $keys = null, string $how = null) use ($http, $found, $report) {
        [$code, $body] = $http($path);
        $hits = $code === 200 ? $found($body, $keys) : [];
        $report($place, ($how ?: 'GET '.$path).' → '.$code, (bool)$hits, $hits ? 'видно: '.implode(', ', $hits) : '');
        // the CSV export of the grid (GridView::$exportParam): the same query, every page, windows-1251
        if ($code === 200 && preg_match('~_sx-export=([\w-]+)~', $body, $m)) {
            [$c2, $csv] = $http($path.(strpos($path, '?') === false ? '?' : '&').'_sx-export='.$m[1]);
            $csv = str_replace("\xC2\xA0", ' ', (string)@iconv('windows-1251', 'UTF-8//IGNORE', $csv));
            $hits = $c2 === 200 ? $found($csv, $keys) : [];
            $report($place.': выгрузка CSV', 'GET …&_sx-export='.$m[1].' → '.$c2, (bool)$hits, $hits ? 'видно: '.implode(', ', $hits) : '');
        }
        return [$code, $body];
    };
    $closed = static function (string $place, string $path, string $marker) use ($http, $report) {
        [$code, $body] = $http($path);
        $open = $code === 200 && ($marker === '' || mb_strpos($body, $marker) !== false);
        $report($place, 'GET '.$path.' → '.$code, $open, $open ? 'открывается' : '');
        return [$code, $body];
    };
    $mustSee = static function (string $place, string $path, string $marker) use ($http, $report) {
        [$code, $body] = $http($path);
        $report($place.' (должно быть видно)', 'GET '.$path.' → '.$code, false, $code === 200 && mb_strpos($body, $marker) !== false ? '' : 'НЕ ВИДНО своего — проверить');
    };

    // --- sections of the menu ---
    $sections = [
        'Сделки: канбан' => '/~sxx/cms/admin-cms-lead/kanban',
        'Сделки: канбан, поиск' => '/~sxx/cms/admin-cms-lead/kanban?q='.urlencode('Аудит'),
        'Сделки: таблица' => '/~sxx/cms/admin-cms-lead/index',
        'Сделки: отчёт' => '/~sxx/cms/admin-cms-lead/report',
        'Компании' => '/~sxx/cms/admin-cms-company/index',
        'Клиенты' => '/~sxx/cms/admin-user/index',
        'Счета' => '/~sxx/cms/admin-cms-bill/index',
        'Платежи' => '/~sxx/shop/admin-payment/index',
        'Документы' => '/~sxx/cms/admin-cms-document/index',
        'Реквизиты' => '/~sxx/cms/admin-cms-contractor/index',
        'Предложения' => '/~sxx/cmsProposal/admin-cms-proposal/index',
        'Чеки' => '/~sxx/shop/admin-shop-check/index',
        'Запросы доступа' => '/~sxx/cms/admin-cms-crm-access-request/index?scope=all',
        'Услуги' => '/~sxx/cms/admin-cms-deal/index',
        'Задачи' => '/~sxx/cms/admin-cms-task/index',
        'Задачи: календарь' => '/~sxx/cms/admin-cms-task/calendar',
        'Звонки' => '/~sxx/cms/admin-cms-telephony-call/index',
        'Проекты' => '/~sxx/cms/admin-cms-project/index',
        'Почта' => '/~sxx/cmsCommunication/admin-cms-comm-message/mail',
        'Почта: поиск' => '/~sxx/cmsCommunication/admin-cms-comm-message/mail?q='.urlencode(AUDIT_PREFIX.'письмо'),
        'Чаты' => '/~sxx/cmsCommunication/admin-cms-comm-chat',
        'Рабочий стол' => '/~sxx/admin/admin-index/dashboard?pk=1',
    ];
    foreach ($sections as $place => $path) {
        $page('Раздел «'.$place.'»', $path);
    }

    // --- cards of the foreign records by a direct address ---
    $cards = [
        'Карточка чужой сделки' => ['/~sxx/cms/admin-cms-lead/view?pk='.$foreign->id, $foreign->name],
        'Чужая сделка: задачи' => ['/~sxx/cms/admin-cms-lead/tasks?pk='.$foreign->id, $foreign->name],
        'Чужая сделка: журнал' => ['/~sxx/cms/admin-cms-lead/log?pk='.$foreign->id, ''],
        'Карточка услуги' => ['/~sxx/cms/admin-cms-deal/view?pk='.$service->id, $service->name],
        'Услуга: счета' => ['/~sxx/cms/admin-cms-deal/bills?pk='.$service->id, ''],
        'Услуга: платежи' => ['/~sxx/cms/admin-cms-deal/payments?pk='.$service->id, ''],
        'Услуга: документы' => ['/~sxx/cms/admin-cms-deal/documents?pk='.$service->id, ''],
        'Услуга: задачи' => ['/~sxx/cms/admin-cms-deal/tasks?pk='.$service->id, ''],
        'Карточка счёта' => ['/~sxx/cms/admin-cms-bill/view?pk='.$billA->id, '91 337'],
        'Счёт: изменить' => ['/~sxx/cms/admin-cms-bill/update?pk='.$billA->id, ''],
        'Счёт: платежи' => ['/~sxx/cms/admin-cms-bill/payments?pk='.$billA->id, ''],
        'Счёт: документы' => ['/~sxx/cms/admin-cms-bill/documents?pk='.$billA->id, ''],
        'Карточка платежа' => ['/~sxx/shop/admin-payment/view?pk='.$payA->id, '81 337'],
        'Платёж: изменить' => ['/~sxx/shop/admin-payment/update?pk='.$payA->id, ''],
        'Документ: изменить' => ['/~sxx/cms/admin-cms-document/update?pk='.$docA->id, ''],
        'Услуга: изменить' => ['/~sxx/cms/admin-cms-deal/update?pk='.$service->id, ''],
        'Задача чужой сделки: изменить' => ['/~sxx/cms/admin-cms-task/update?pk='.$taskForeign->id, ''],
        'Карточка документа' => ['/~sxx/cms/admin-cms-document/view?pk='.$docA->id, ''],
        'Документ: XML' => ['/~sxx/cms/admin-cms-document/xml?pk='.$docA->id, ''],
        'КП чужой сделки' => ['/~sxx/cmsProposal/admin-cms-proposal/view?pk='.$kpForeign, ''],
        'КП чужой сделки: PDF' => ['/~sxx/cmsProposal/admin-cms-proposal/pdf?pk='.$kpForeign, ''],
        'КП компании без сделки' => ['/~sxx/cmsProposal/admin-cms-proposal/view?pk='.$kpCompany, ''],
        'КП по услуге' => ['/~sxx/cmsProposal/admin-cms-proposal/view?pk='.$kpService, ''],
        'Задача чужой сделки' => ['/~sxx/cms/admin-cms-task/view?pk='.$taskForeign->id, ''],
        'Задача компании без сделки' => ['/~sxx/cms/admin-cms-task/view?pk='.$taskCompany->id, ''],
        'Задача услуги' => ['/~sxx/cms/admin-cms-task/view?pk='.$taskService->id, ''],
        'Звонок по чужой сделке' => ['/~sxx/cms/admin-cms-telephony-call/view?pk='.$callId, ''],
        'Письмо по чужой сделке' => ['/~sxx/cmsCommunication/admin-cms-comm-message/view?pk='.$letterForeign, ''],
        'Письмо по услуге' => ['/~sxx/cmsCommunication/admin-cms-comm-message/view?pk='.$letterService, ''],
        'Чужая сделка клиента Б' => ['/~sxx/cms/admin-cms-lead/view?pk='.$foreignP->id, $foreignP->name],
        'Счёт клиента Б' => ['/~sxx/cms/admin-cms-bill/view?pk='.$billP->id, ''],
        'Платёж клиента Б' => ['/~sxx/shop/admin-payment/view?pk='.$payP->id, ''],
        'Документ клиента Б' => ['/~sxx/cms/admin-cms-document/view?pk='.$docP->id, ''],
    ];
    foreach ($cards as $place => [$path, $marker]) {
        $closed($place, $path, $marker);
    }

    // --- the card of company A and its tabs (the company itself is allowed) ---
    $tabs = ['view' => 'Профиль и лента', 'leads' => 'Сделки', 'deals' => 'Услуги', 'bills' => 'Счета', 'documents' => 'Документы',
        'payments' => 'Платежи', 'tasks' => 'Задачи', 'projects' => 'Проекты', 'telephony' => 'Звонки', 'log' => 'Журнал', 'crm-access' => 'Доступ'];
    foreach ($tabs as $tab => $label) {
        $page('Компания А: вкладка «'.$label.'»', '/~sxx/cms/admin-cms-company/'.$tab.'?pk='.$A->id);
    }
    $mustSee('Компания А: своя сделка', '/~sxx/cms/admin-cms-company/leads?pk='.$A->id, $own->name);
    $mustSee('Компания А: лента, свой комментарий', '/~sxx/cms/admin-cms-company/view?pk='.$A->id, AUDIT_PREFIX.'комментарий своей сделки '.$uniq);
    $mustSee('Своя сделка: карточка', '/~sxx/cms/admin-cms-lead/view?pk='.$own->id, $own->name);
    // the person B and its tabs
    foreach (['view' => 'Профиль и лента', 'leads' => 'Сделки', 'bills' => 'Счета', 'payments' => 'Платежи', 'tasks' => 'Задачи',
                 'orders' => 'Заказы', 'checks' => 'Чеки', 'log' => 'Журнал'] as $tab => $label) {
        $page('Клиент Б: вкладка «'.$label.'»', '/~sxx/cms/admin-user/'.$tab.'?pk='.$person->id);
    }
    // the own deal: its card must not pull the other deals or the company's finance
    $page('Своя сделка: карточка и лента', '/~sxx/cms/admin-cms-lead/view?pk='.$own->id);

    // --- the pool company before «Взять в работу» ---
    [$code, $poolCard] = $http('/~sxx/cms/admin-cms-company/view?pk='.$pool->id);
    $report('Компания из пула: карточка', 'GET view → '.$code, $code === 200 && (bool)$found($poolCard, ['сделка пула', 'коммент. сделки пула', 'счёт пула']),
        implode(', ', $found($poolCard, ['сделка пула', 'коммент. сделки пула', 'счёт пула'])));
    foreach (['leads', 'deals', 'bills', 'documents', 'payments', 'tasks', 'telephony', 'log'] as $tab) {
        $closed('Компания из пула: вкладка '.$tab, '/~sxx/cms/admin-cms-company/'.$tab.'?pk='.$pool->id, '');
    }
    $closed('Компания из пула: сделка по ссылке', '/~sxx/cms/admin-cms-lead/view?pk='.$poolLead->id, $poolLead->name);
    $closed('Компания из пула: счёт по ссылке', '/~sxx/cms/admin-cms-bill/view?pk='.$billPool->id, '');

    // --- the protected company without a link ---
    foreach (['view', 'leads', 'deals', 'bills', 'documents', 'payments', 'tasks', 'telephony', 'log', 'crm-access'] as $tab) {
        $closed('Защищённая компания: '.$tab, '/~sxx/cms/admin-cms-company/'.$tab.'?pk='.$protected->id, '');
    }
    $closed('Защищённая: сделка по ссылке', '/~sxx/cms/admin-cms-lead/view?pk='.$protectedLead->id, '');
    $closed('Защищённая: счёт по ссылке', '/~sxx/cms/admin-cms-bill/view?pk='.$billProt->id, '');
    $closed('Защищённая: платёж по ссылке', '/~sxx/shop/admin-payment/view?pk='.$payProt->id, '');
    $closed('Защищённая: изменить счёт', '/~sxx/cms/admin-cms-bill/update?pk='.$billProt->id, '');
    $closed('Защищённая: изменить платёж', '/~sxx/shop/admin-payment/update?pk='.$payProt->id, '');
    $closed('Защищённая: задача по ссылке', '/~sxx/cms/admin-cms-task/view?pk='.$taskProt->id, '');
    $closed('Защищённая: звонок по ссылке', '/~sxx/cms/admin-cms-telephony-call/view?pk='.$callProt, '');
    $closed('Защищённая: письмо по ссылке', '/~sxx/cmsCommunication/admin-cms-comm-message/view?pk='.$letterProt, '');
    // the duplicate search names the client but must not open it
    [$code, $dup] = $http('/~sxx/cms/admin-cms-company/duplicate-check?name='.urlencode($protected->name), null, true);
    $report('Поиск повторов: защищённая компания', 'GET duplicate-check?name= → '.$code,
        (bool)preg_match('~admin-cms-company/view[^"\']*pk(?:=|%3D)'.$protected->id.'(?!\d)~', $dup), 'по договору — только название и ответственный');

    // --- autocomplete of the forms: every AjaxSelect of the create forms, asked for the audit records ---
    $forms = [
        'Счёт: новый' => '/~sxx/cms/admin-cms-bill/create',
        'Документ: новый' => '/~sxx/cms/admin-cms-document/create',
        'Платёж: новый' => '/~sxx/shop/admin-payment/create',
        'Задача: новая' => '/~sxx/cms/admin-cms-task/create',
        'КП: новое' => '/~sxx/cmsProposal/admin-cms-proposal/create',
        'Сделка: новая' => '/~sxx/cms/admin-cms-lead/create',
        'Услуга: новая' => '/~sxx/cms/admin-cms-deal/create',
    ];
    foreach ($forms as $place => $path) {
        [$code, $form] = $http($path);
        if ($code !== 200) {
            $report('Автодополнение: '.$place, 'GET '.$path.' → '.$code, false, 'форма недоступна');
            continue;
        }
        preg_match_all('~ajaxid(?:=|%3D)([\w-]+)~', $form, $m);
        $ids = array_unique($m[1]);
        $hits = [];
        foreach ($ids as $ajaxId) {
            foreach ([$uniq, 'Аудит', '91337', '81337'] as $q) {
                $sep = strpos($path, '?') === false ? '?' : '&';
                [$c2, $json] = $http($path.$sep.'ajaxid='.$ajaxId.'&q='.urlencode($q), null, true);
                $hits = array_merge($hits, array_map(static fn($h) => $h.' ('.$ajaxId.')', $found($json)));
            }
        }
        $report('Автодополнение: '.$place, count($ids).' полей выбора, q='.$uniq.'/Аудит', (bool)$hits, implode(', ', array_unique($hits)));
    }
    // a value of a hidden record put into a form shows its name (AjaxSelectModel::valueCallback)
    [$code, $form] = $http('/~sxx/cms/admin-cms-bill/create?ShopBill%5Bcms_company_id%5D='.$protected->id);
    $report('Форма счёта с id защищённой компании', 'GET bill/create?ShopBill[cms_company_id]= → '.$code,
        $code === 200 && mb_strpos($form, $protected->name) !== false, 'название скрытой компании в поле выбора');
    [$code, $form] = $http('/~sxx/cms/admin-cms-task/create?CmsTask%5Bcms_lead_id%5D='.$foreign->id);
    $report('Форма задачи с id чужой сделки', 'GET task/create?CmsTask[cms_lead_id]= → '.$code,
        $code === 200 && mb_strpos($form, $foreign->name) !== false, 'название чужой сделки в поле выбора');

    // --- notifications: the bell of the employee shows only his own ---
    $page('Уведомления (колокольчик)', '/~sxx/cms/admin-profile/notifications');

    @unlink($jar);

    // ============ REST / MCP in the employee's name ============
    Yii::$app->user->setIdentity($worker);
    $token = new \skeeks\cms\oauth2\models\CmsOauthAccessToken(['scopes' => json_encode(array_values(Yii::$app->oauth2Server->resources['cms-rest-api']['scopes']))]);
    $tool = static function (string $name, array $arguments) use ($token) {
        try {
            return ['ok', Yii::$app->cmsMcp->executeTool($name, $arguments, $token, ['transport' => 'test'])];
        } catch (\Throwable $e) {
            return ['error', $e->getMessage()];
        }
    };
    $json = static fn($v) => str_replace(["\xC2\xA0"], ' ', json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $byId = [
        'cms_lead_get' => ['id' => $foreign->id], 'cms_deal_get' => ['id' => $service->id], 'shop_bill_get' => ['id' => $billA->id],
        'shop_payment_get' => ['id' => $payA->id], 'shop_document_get' => ['id' => $docA->id], 'cms_proposal_get' => ['id' => $kpForeign],
        'cms_task_get' => ['id' => $taskForeign->id], 'cms_telephony_call_get' => ['id' => $callId], 'cms_comm_message_get' => ['id' => $letterForeign],
        'cms_company_get' => ['id' => $protected->id],
    ];
    $byIdProtected = ['shop_bill_get' => ['id' => $billProt->id], 'shop_payment_get' => ['id' => $payProt->id], 'cms_task_get' => ['id' => $taskProt->id]];
    foreach ($byIdProtected as $name => $args) {
        [$status] = $tool($name, $args);
        $report('MCP '.$name.' (защищённая компания без доступа)', 'executeTool id='.$args['id'], $status === 'ok', $status === 'ok' ? 'вернул запись' : '');
    }
    $names = array_keys(Yii::$app->cmsMcp->getTools());
    foreach ($byId as $name => $args) {
        if (!in_array($name, $names, true)) {
            continue;
        }
        [$status, $out] = $tool($name, $args);
        $report('MCP '.$name.' (чужая запись)', 'executeTool id='.$args['id'], $status === 'ok', $status === 'ok' ? 'вернул запись' : '');
    }
    $lists = [
        'cms_lead_list' => ['q' => $uniq], 'cms_deal_list' => ['cms_company_id' => $A->id], 'shop_bill_list' => ['cms_company_id' => $A->id],
        'shop_payment_list' => ['cms_company_id' => $A->id], 'shop_document_list' => ['cms_company_id' => $A->id],
        'cms_proposal_list' => ['cms_company_id' => $A->id], 'cms_task_list' => ['cms_company_id' => $A->id],
        'cms_telephony_call_list' => ['cms_company_id' => $A->id], 'cms_comm_message_list' => ['q' => $uniq],
        'cms_log_list' => ['model_code' => 'company', 'model_id' => $A->id],
        'cms_company_list' => ['q' => $uniq],
        'cms_company_get' => ['id' => $A->id],
        'cms_lead_get' => ['id' => $own->id],
        'cms_company_timeline_get' => ['id' => $A->id],
        'cms_crm_history_get' => ['record_type' => 'company', 'record_id' => $A->id, 'limit' => 100],
        'cms_crm_client_overview_get' => ['record_type' => 'company', 'record_id' => $A->id],
        'cms_crm_client_overview_list' => ['q' => $uniq],
    ];
    foreach ($lists as $name => $args) {
        if (!in_array($name, $names, true)) {
            $report('MCP '.$name, 'нет такого инструмента', false, 'пропущен');
            continue;
        }
        [$status, $out] = $tool($name, $args);
        $hits = $status === 'ok' ? $found($json($out)) : [];
        $report('MCP '.$name, 'executeTool '.http_build_query($args).($status === 'ok' ? '' : ' → ошибка: '.mb_substr($out, 0, 80)), (bool)$hits, $hits ? 'видно: '.implode(', ', $hits) : '');
    }
} finally {
    if (getenv('AUDIT_KEEP')) {
        echo "AUDIT_KEEP: the fixture is kept; remove it with AUDIT_CLEAN=1\n";
    } else {
        $clean();
    }
}

$leaks = array_filter($results, static fn($r) => $r[2] === 'LEAK');
foreach ($results as [$place, $how, $status, $detail]) {
    echo str_pad($status, 5).' | '.$place.' | '.$how.($detail !== '' ? ' | '.$detail : '')."\n";
}
echo count($results).' places, '.count($leaks)." leaks\n";
if ($leaks) {
    fwrite(STDERR, 'FAIL: '.count($leaks)." leaks of «Свои сделки»\n");
    exit(1);
}
echo "OK crm-leak-audit\n";
