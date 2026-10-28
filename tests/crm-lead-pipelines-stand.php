<?php
/**
 * П2б on the stand database, rolled back: pipelines and their stages, the
 * default pipeline of new requests, a stage of another pipeline refused, the
 * history of every transition (create, take, stage, move between pipelines,
 * reject, reopen, success), the kanban of a pipeline with sums, the funnel
 * report by the history, the success suggestion and a site without
 * pipelines working as before. The data move (cms/lead-pipeline/init) must
 * have been applied: its plan is empty.
 *
 * CRM_PIPELINES_STAND_TEST=local php tests/crm-lead-pipelines-stand.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CRM_PIPELINES_STAND_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_PIPELINES_STAND_TEST=local.');
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

use skeeks\cms\crm\LeadPipelineMigration;
use skeeks\cms\crm\LeadPipelines;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\models\CmsLeadStageHistory;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\services\CmsLeadBoardService;
use skeeks\cms\services\CmsLeadFunnelReportService;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};
$check(CmsLead::pipelineReady(), 'the П2б migration is applied');

$transaction = Yii::$app->db->beginTransaction();
try {
    $site = CmsSite::find()->default()->one();
    Yii::$app->skeeks->site = $site;
    $siteId = (int)$site->id;

    $admin = null;
    foreach (CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->all() as $worker) {
        if (Yii::$app->authManager->checkAccess((int)$worker->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            $admin = $worker;
            break;
        }
    }
    $check($admin !== null, 'the stand needs an administrator');
    Yii::$app->user->setIdentity($admin);
    $history = static fn(CmsLead $lead) => CmsLeadStageHistory::find()->andWhere(['cms_lead_id' => (int)$lead->id])->orderBy(['id' => SORT_ASC])->all();
    $last = static fn(CmsLead $lead) => CmsLeadStageHistory::find()->andWhere(['cms_lead_id' => (int)$lead->id])->orderBy(['id' => SORT_DESC])->one();

    // --- the data move is applied and repeatable ---
    $plan = (new LeadPipelineMigration(['siteId' => $siteId]))->plan();
    $check($plan['stagesToInbound'] === 0 && $plan['coldLeads'] === 0 && $plan['otherLeads'] === 0 && $plan['snapshots'] === 0,
        'cms/lead-pipeline/init is applied and a second run changes nothing');
    $inbound = CmsLeadPipeline::find()->andWhere(['cms_site_id' => $siteId, 'code' => CmsLeadPipeline::CODE_INBOUND])->one();
    $outbound = CmsLeadPipeline::find()->andWhere(['cms_site_id' => $siteId, 'code' => CmsLeadPipeline::CODE_OUTBOUND])->one();
    $check($inbound && $outbound && $inbound->isMain && !$outbound->isMain, '«Входящие» is the default pipeline, «Исходящие продажи» is not');
    $check((int)CmsLeadPipeline::defaultFor($siteId)->id === (int)$inbound->id, 'defaultFor() gives «Входящие»');
    $check(!CmsLead::find()->andWhere(['cms_site_id' => $siteId, 'cms_lead_pipeline_id' => null])->exists(), 'every deal of the site has a pipeline');
    $check(!CmsLead::find()->andWhere(['cms_site_id' => $siteId])
        ->andWhere(['not exists', (new yii\db\Query())->from(CmsLeadStageHistory::tableName().' h')->andWhere('h.cms_lead_id = '.CmsLead::tableName().'.id')])->exists(),
        'every deal has at least one history row');

    // Stages of each pipeline (the test adds its own in «Исходящие» when there are none).
    $inStages = $inbound->stages;
    $check(count($inStages) >= 2, '«Входящие» has at least two stages');
    $outStages = $outbound->stages;
    if (!$outStages) {
        foreach (['Тест П2б: звонок', 'Тест П2б: интерес'] as $i => $name) {
            $stage = new CmsLeadStage(['cms_site_id' => $siteId, 'cms_lead_pipeline_id' => (int)$outbound->id, 'name' => $name, 'sort' => 10 + $i]);
            $check($stage->save(), 'a stage of «Исходящие» is created');
        }
        $outbound->refresh();
        $outStages = $outbound->stages;
    }
    $same = new CmsLeadStage(['cms_site_id' => $siteId, 'cms_lead_pipeline_id' => (int)$outbound->id, 'name' => $inStages[0]->name.' П2б-дубль']);
    $check($same->save(), 'a stage name is free within its pipeline');
    $dup = new CmsLeadStage(['cms_site_id' => $siteId, 'cms_lead_pipeline_id' => (int)$outbound->id, 'name' => $same->name]);
    $check(!$dup->validate(['name']), 'the same name twice in one pipeline is refused');

    // --- the rollback of the migration refuses same-named stages before any DDL (MySQL does not roll DDL back) ---
    $twin = new CmsLeadStage(['cms_site_id' => $siteId, 'cms_lead_pipeline_id' => (int)$inbound->id, 'name' => $same->name]);
    $check($twin->save(), 'the same stage name in another pipeline of the site is allowed');
    $check((int)CmsLeadStage::find()->andWhere(['cms_site_id' => $siteId, 'name' => $same->name])->count() === 2, 'the site has two same-named stages');
    require_once dirname(__DIR__).'/src/migrations/m261028_130000__create_table__cms_lead_pipeline.php';
    $migration = new m261028_130000__create_table__cms_lead_pipeline(['db' => Yii::$app->db, 'compact' => true]);
    $refused = null;
    try {
        $migration->safeDown();
    } catch (\yii\base\Exception $e) {
        $refused = $e->getMessage();
    }
    $check($refused !== null && strpos($refused, 'Откат невозможен') !== false && strpos($refused, $same->name) !== false,
        'the rollback names the same-named stages and refuses');
    $check(Yii::$app->db->getTableSchema('{{%cms_lead_stage_history}}', true) !== null
        && Yii::$app->db->getTableSchema('{{%cms_lead}}', true)->getColumn('cms_lead_pipeline_id') !== null,
        'the refused rollback has not touched the schema');

    // --- a new request goes to the default pipeline, unsorted, without a stage ---
    $lead = new CmsLead(['name' => 'Тест П2б: заявка', 'source_type' => CmsLead::SOURCE_MANUAL, 'amount' => '120 000']);
    $check($lead->save(), 'a deal without a pipeline is saved: '.implode(' ', $lead->getFirstErrors()));
    $check((int)$lead->cms_lead_pipeline_id === (int)$inbound->id && $lead->status === CmsLead::STATUS_NEW && !$lead->cms_lead_stage_id,
        'a new request lands in «Неразобранное» of «Входящие»');
    $rows = $history($lead);
    $check(count($rows) === 1 && $rows[0]->kind === CmsLeadStageHistory::KIND_CREATE && $rows[0]->to_status === CmsLead::STATUS_NEW
        && (int)$rows[0]->to_pipeline_id === (int)$inbound->id && (int)$rows[0]->created_by === (int)$admin->id,
        'creation is the first history row, with the author');
    $check((int)$lead->stage_changed_at > 0, 'the time on the stage starts');

    // --- taking into work: the first stage of the pipeline ---
    $lead->executor_id = (int)$admin->id;
    $lead->status = CmsLead::STATUS_IN_WORK;
    $check($lead->save(), 'the deal is taken into work');
    $check((int)$lead->cms_lead_stage_id === (int)$inStages[0]->id, 'taken into the first stage of «Входящие»');
    $row = $last($lead);
    $check($row->kind === CmsLeadStageHistory::KIND_TAKE && $row->from_status === CmsLead::STATUS_NEW && (int)$row->to_stage_id === (int)$inStages[0]->id
        && $row->to_stage_name === $inStages[0]->name, 'taking is written with the stage name');

    // --- a stage of another pipeline is refused ---
    $lead->cms_lead_stage_id = (int)$outStages[0]->id;
    $check(!$lead->validate(['cms_lead_stage_id']), 'a stage of «Исходящие» is refused for a deal of «Входящие»');
    $lead->cms_lead_stage_id = (int)$inStages[1]->id;
    $check($lead->save(), 'a stage of its own pipeline is saved');
    $row = $last($lead);
    $check($row->kind === CmsLeadStageHistory::KIND_STAGE && (int)$row->from_stage_id === (int)$inStages[0]->id && (int)$row->to_stage_id === (int)$inStages[1]->id,
        'the stage change: the previous and the new stage');
    $saved = count($history($lead));
    $lead->description = 'без смены этапа';
    $check($lead->save() && count($history($lead)) === $saved, 'saving other fields writes no history');

    // --- the kanban of a pipeline: columns of its stages, count and «Сумма» ---
    $board = new CmsLeadBoardService($inbound);
    $columns = $board->columns((int)$admin->id, 'Тест П2б: заявка');
    $byKey = array_column($columns, null, 'key');
    $column = $byKey[CmsLeadBoardService::stageColumn((int)$inStages[1]->id)] ?? null;
    $check($column && $column['total'] === 1 && (float)$column['amount'] === 120000.0, 'the stage column counts the deal and sums «Сумма»');
    $check(isset($byKey[CmsLeadBoardService::COLUMN_NEW]), '«Неразобранное» is a column of the default pipeline');
    $outColumns = (new CmsLeadBoardService($outbound))->columns((int)$admin->id, 'Тест П2б: заявка');
    $check(!array_sum(array_column($outColumns, 'total')), 'the deal is not on the board of «Исходящие»');
    $check((new CmsLeadBoardService($inbound, [CmsLeadBoardService::FILTER_NO_TASK => true]))->columns((int)$admin->id, 'Тест П2б: заявка')
        [array_search(CmsLeadBoardService::stageColumn((int)$inStages[1]->id), array_column($columns, 'key'), true)]['total'] === 1,
        '«Без задач» keeps a deal in work without tasks');
    $steps = CmsLeadBoardService::forLead($lead)->steps($lead, (int)$admin->id);
    $check(count(array_filter($steps, static fn($s) => $s['stage'] && (int)$s['stage']->cms_lead_pipeline_id === (int)$outbound->id)) === 0,
        'the stage bar of the card has only the stages of the deal\'s pipeline');

    // --- moving to another pipeline ---
    LeadPipelines::instance()->move($lead, (int)$outbound->id, (int)$outStages[1]->id, (int)$admin->id);
    $lead->refresh();
    $check((int)$lead->cms_lead_pipeline_id === (int)$outbound->id && (int)$lead->cms_lead_stage_id === (int)$outStages[1]->id, 'moved to the chosen stage of «Исходящие»');
    $row = $last($lead);
    $check($row->kind === CmsLeadStageHistory::KIND_PIPELINE && (int)$row->from_pipeline_id === (int)$inbound->id && (int)$row->to_pipeline_id === (int)$outbound->id,
        'the move between pipelines is in the history');
    LeadPipelines::instance()->move($lead, (int)$inbound->id, null, (int)$admin->id);
    $lead->refresh();
    $check((int)$lead->cms_lead_stage_id === (int)$inStages[0]->id, 'moving without a stage takes the first stage');
    try {
        LeadPipelines::instance()->move($lead, 999999, null, (int)$admin->id);
        $check(false, 'an unknown pipeline is refused');
    } catch (DomainException $e) {
        $check(true, 'an unknown pipeline is refused');
    }

    // --- reject, reopen, success: dates and history ---
    $lead->status = CmsLead::STATUS_REJECTED;
    $lead->reject_reason = 'Тест П2б: дорого';
    if (\skeeks\cms\models\CmsLeadRejectReason::siteHasReasons($siteId)) {
        $lead->cms_lead_reject_reason_id = (int)\skeeks\cms\models\CmsLeadRejectReason::forSite()[0]->id;
    }
    $check($lead->save(), 'rejected: '.implode(' ', $lead->getFirstErrors()));
    $check((int)$lead->processed_at > 0 && $last($lead)->kind === CmsLeadStageHistory::KIND_REJECT, 'the rejection has its date and its history row');
    $lead->status = CmsLead::STATUS_IN_WORK;
    $lead->processed_at = null;
    $check($lead->save() && $last($lead)->kind === CmsLeadStageHistory::KIND_REOPEN, 'reopening is in the history');
    $lead->status = CmsLead::STATUS_SUCCESS;
    $lead->result_comment = 'Тест П2б: договор';
    $check($lead->save(), 'success: '.implode(' ', $lead->getFirstErrors()));
    $check($last($lead)->kind === CmsLeadStageHistory::KIND_SUCCESS && (float)$lead->deal_amount === 120000.0, 'success with «Сумма» as the deal amount');

    // --- the funnel report by the history ---
    $from = time() - 3600;
    $to = time() + 3600;
    $funnel = (new CmsLeadFunnelReportService())->build($inbound, $from, $to);
    $check($funnel['entered'] >= 1 && $funnel['success'] >= 1, 'the funnel counts the deal created in the period and its success');
    $check(end($funnel['steps'])['title'] === 'Успешно' && end($funnel['steps'])['reached'] >= 1, 'the last step of the funnel is «Успешно»');
    $stay = array_column($funnel['stageTimes'], 'count', 'title');
    $check(($stay[$inStages[0]->name] ?? 0) >= 1, 'the time on a stage comes from real transitions');
    $outFunnel = (new CmsLeadFunnelReportService())->build($outbound, $from, $to);
    $check($outFunnel['entered'] >= 1, 'a deal moved through «Исходящие» entered it too');
    // A period without entries: the time on stages follows the same cohort and is empty too.
    $future = (new CmsLeadFunnelReportService())->build($inbound, mktime(0, 0, 0, 1, 1, 2035), mktime(23, 59, 59, 12, 31, 2035));
    $check($future['entered'] === 0 && array_sum(array_column($future['stageTimes'], 'count')) === 0,
        'the time on stages counts only deals that entered in the period');
    $sales = (new CmsLeadFunnelReportService())->sales($inbound, $from, $to);
    $check($sales['first'] + $sales['repeat'] + $sales['unknown'] >= 1 && $sales['unknown'] >= 1, 'a sale without a client is counted as unknown, once');

    // --- the success suggestion: manual — nothing; payment — only a first payment after the opening ---
    $lead2 = new CmsLead(['name' => 'Тест П2б: оплата', 'executor_id' => (int)$admin->id, 'status' => CmsLead::STATUS_IN_WORK]);
    $check($lead2->save(), 'a deal in work');
    $check(LeadPipelines::instance()->successSignals($lead2) === [], 'the manual criterion suggests nothing');
    $inbound->success_rule = CmsLeadPipeline::SUCCESS_PAYMENT;
    $check($inbound->save(), 'the criterion «Первая оплата»');
    CmsLeadPipeline::clearCache();
    $payer = (new yii\db\Query())->select(['cms_company_id'])->from('{{%shop_payment}}')->andWhere(['is_debit' => 1])->andWhere(['not', ['cms_company_id' => null]])->scalar();
    if ($payer) {
        $lead2->cms_company_id = (int)$payer;
        $check($lead2->save(), 'a deal of a paying client');
        $check(LeadPipelines::instance()->successSignals($lead2) === [], 'a client who paid before the deal: no first payment, no suggestion');
    }

    // --- a site without pipelines works as before ---
    $other = CmsSite::find()->andWhere(['!=', 'id', $siteId])->one();
    if ($other && !CmsLeadPipeline::forSite((int)$other->id)) {
        $plain = new CmsLead(['cms_site_id' => (int)$other->id, 'name' => 'Тест П2б: простой сайт', 'source_type' => CmsLead::SOURCE_FORM]);
        $check($plain->save(), 'a deal of a site without pipelines is saved');
        $check($plain->cms_lead_pipeline_id === null && $plain->getEffectivePipeline() === null, 'it has no pipeline');
        $check($last($plain)->kind === CmsLeadStageHistory::KIND_CREATE, 'its history is still written');
        $check(count(CmsLeadPipeline::forSite((int)$other->id)) === 0, 'no pipeline appears by itself');
    }

    // --- П2г-1: «Клиент» of the deal card — the contacts of the company or the person, the right to change them ---
    $access = skeeks\cms\crm\CrmAccess::instance();
    $companyLink = skeeks\cms\models\CmsCompany2manager::find()->alias('l')
        ->innerJoin(['u' => CmsUser::tableName()], 'u.id = l.cms_user_id AND u.is_active = 1')
        ->andWhere(['not', ['l.cms_user_id' => (int)$admin->id]])
        // Not the main responsible: the main one is always «Полный».
        ->andWhere(['l.is_main' => 0])->all();
    $worker = null;
    foreach ($companyLink as $link) {
        if (!$access->isAdmin((int)$link->cms_user_id)) {
            $worker = $link;
            break;
        }
    }
    $check($worker !== null, 'the stand needs an employee (not an administrator) linked to a company');
    $workerId = (int)$worker->cms_user_id;
    $companyId = (int)$worker->cms_company_id;
    $record = skeeks\cms\models\CmsCrmAccessRequest::RECORD_COMPANY;
    $check($access->canEditClientData((int)$admin->id, $record, $companyId), 'an administrator changes the data of any company');
    $worker->access_level = skeeks\cms\crm\CrmAccess::LEVEL_DEAL;
    $check($worker->save(false), 'the link is lowered to «Свои сделки»');
    $access->enforceLevels = false;
    $check($access->canEditClientData($workerId, $record, $companyId), 'until П2а+ «Свои сделки» works as «Полный»');
    $access->enforceLevels = true;
    $check(!$access->canEditClientData($workerId, $record, $companyId), 'with the levels enforced «Свои сделки» may not change the company data');
    $worker->access_level = skeeks\cms\crm\CrmAccess::LEVEL_EDIT;
    $check($worker->save(false) && $access->canEditClientData($workerId, $record, $companyId), '«Свои сделки и данные компании» may');
    $access->enforceLevels = false;
    $stranger = skeeks\cms\models\CmsCompany::find()->andWhere(['not in', 'id', skeeks\cms\models\CmsCompany2manager::find()->select('cms_company_id')->andWhere(['cms_user_id' => $workerId])])->one();
    $check($stranger === null || !$access->canEditClientData($workerId, $record, (int)$stranger->id) || $access->canSeeCompany($workerId, (int)$stranger->id),
        'a company the employee does not see is not his to change');

    // Rows: the same as in the company card, a shortened list with «ещё N», no "⋮" without the right.
    $phones = [];
    for ($i = 1; $i <= 7; $i++) {
        $phones[] = new skeeks\cms\models\CmsCompanyPhone(['id' => 900000 + $i, 'value' => '+7 900 000-00-0'.$i]);
    }
    $items = skeeks\cms\widgets\admin\CrmContactBlockWidget::phoneItems($phones, '/cms/admin-cms-company-phone', $lead,
        ['editable' => false, 'call' => ['data-lead-id' => (int)$lead->id]]);
    $check(count($items) === 7 && strpos($items[0]['actions'], 'sx-telephony-btn') !== false && strpos($items[0]['actions'], 'data-lead-id="'.(int)$lead->id.'"') !== false
        && strpos($items[0]['actions'], 'sx-send-sms-trigger') !== false && strpos($items[0]['actions'], 'sx-icon-action') === false,
        'a phone row has «Чат» (SMS) and «Позвонить» with the deal, no menu without the right');
    $block = skeeks\cms\widgets\admin\CrmContactBlockWidget::widget(['title' => 'Телефоны компании', 'icon' => 'phone', 'items' => $items, 'limit' => 5, 'moreUrl' => '/company']);
    $check(substr_count($block, 'class="sx-contact-row"') === 5 && strpos($block, 'ещё 2') !== false && strpos($block, 'href="/company"') !== false,
        'a long list shows the first five and «ещё 2» to the company card');
    $emails = skeeks\cms\widgets\admin\CrmContactBlockWidget::emailItems([new skeeks\cms\models\CmsCompanyEmail(['id' => 900001, 'value' => 'test@skeeks.com'])], '/cms/admin-cms-company-email', $lead, ['editable' => false]);
    $check(strpos($emails[0]['actions'], 'Написать письмо') !== false, 'an email row has «Письмо»');

    // The card: «Клиент» from the company or the person; the request data only while the deal has no client.
    $viewSource = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-lead/view.php');
    $check(strpos($viewSource, "\$this->render('_client'") !== false && strpos($viewSource, '$requestData = !$model->company && !$model->client;') !== false,
        'the deal card renders «Клиент» and hides «…из обращения» once a client is linked');

    // The kanban from the menu opens on «Мои сделки»; «Все сделки» and another employee are explicit.
    $board = [skeeks\cms\controllers\AdminCmsLeadController::class, 'boardExecutor'];
    $managers = [7 => 'Я', 8 => 'Подчинённый'];
    $check($board(null, 7, $managers) === 7 && $board('all', 7, $managers) === 0 && $board('', 7, $managers) === 0
        && $board('8', 7, $managers) === 8 && $board('9', 7, $managers) === 0,
        'the kanban: no executor — mine, «all» or empty — all, a subordinate kept, a foreign employee ignored');
    $kanbanSource = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-lead/kanban.php');
    $check(strpos($kanbanSource, "\$link(['executor' => 'all'])") !== false && strpos($kanbanSource, "\$executorId === \$userId ? null : (\$executorId ?: 'all')") !== false,
        'the kanban links keep «Все сделки» while switching the pipeline or the filters');

    echo 'OK crm-lead-pipelines-stand: '.$checks." checks\n";
} finally {
    $transaction->rollBack();
}
