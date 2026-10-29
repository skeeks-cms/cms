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
use skeeks\cms\crm\LeadTriage;
use skeeks\cms\crm\OutboundSales;
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
    $schemaIntact = static fn(): bool => Yii::$app->db->getTableSchema('{{%cms_lead_stage_history}}', true) !== null
        && Yii::$app->db->getTableSchema('{{%cms_lead}}', true)->getColumn('cms_lead_pipeline_id') !== null
        && Yii::$app->db->getTableSchema('{{%cms_lead_stage}}', true)->getColumn('cms_lead_pipeline_id') !== null;
    $rollback = static function (m261028_130000__create_table__cms_lead_pipeline $migration): ?string {
        try {
            $migration->safeDown();
        } catch (\yii\base\Exception $e) {
            return $e->getMessage();
        }
        return null;
    };

    // The real migration: pipelines, stages and history hold data, so it refuses first, naming what would be lost.
    $refused = $rollback(new m261028_130000__create_table__cms_lead_pipeline(['db' => Yii::$app->db, 'compact' => true]));
    $check($refused !== null && strpos($refused, 'Откат невозможен') !== false
        && preg_match('/воронки: \d+/u', $refused) && preg_match('/этапы в воронках: \d+/u', $refused) && strpos($refused, 'Схема не изменена') !== false,
        'the rollback with pipeline data refuses and names what would be lost: '.$refused);
    $check($schemaIntact(), 'the rollback refused for data has not touched the schema');

    // Without that data the second guard must still refuse same-named stages before any DDL: the data
    // guard is switched off and every DDL method throws, so reaching DDL fails the test, not the stand.
    $noDdl = new class(['db' => Yii::$app->db, 'compact' => true]) extends m261028_130000__create_table__cms_lead_pipeline {
        public $ddl = [];
        protected function refuseRollbackWithData(array $checks): void {}
        private function stop(string $what) { $this->ddl[] = $what; throw new RuntimeException('FAIL: the rollback reached DDL: '.$what); }
        public function execute($sql, $params = []) { $this->stop('execute'); }
        public function dropTable($table) { $this->stop('dropTable '.$table); }
        public function dropColumn($table, $column) { $this->stop('dropColumn '.$table.'.'.$column); }
        public function dropIndex($name, $table) { $this->stop('dropIndex '.$name); }
        public function dropForeignKey($name, $table) { $this->stop('dropForeignKey '.$name); }
        public function createIndex($name, $table, $columns, $unique = false) { $this->stop('createIndex '.$name); }
    };
    $refused = $rollback($noDdl);
    $check($refused !== null && strpos($refused, 'Откат невозможен') !== false && strpos($refused, $same->name) !== false && $noDdl->ddl === [],
        'the rollback names the same-named stages and refuses before any DDL: '.$refused);
    $check($schemaIntact(), 'the rollback refused for same-named stages has not touched the schema');

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

    // --- П2д: «Исходящие продажи» — default stages, the personal offer moves the deal forward ---
    $fresh = new CmsLeadPipeline(['cms_site_id' => $siteId, 'name' => 'Тест П2д: пустая', 'sort' => 900, 'is_default' => 0, 'success_rule' => CmsLeadPipeline::SUCCESS_MANUAL]);
    $check($fresh->save(), 'an empty pipeline is created');
    $check(OutboundSales::ensureStages($fresh) === OutboundSales::DEFAULT_STAGES
        && array_map(static fn($s) => $s->name, CmsLeadStage::forPipeline($fresh, $siteId)) === OutboundSales::DEFAULT_STAGES,
        'an outbound pipeline without stages gets «Первый контакт», «КП отправлено», «Смотрел», «Ответил», «Думает»');
    $own = new CmsLeadPipeline(['cms_site_id' => $siteId, 'name' => 'Тест П2д: своя', 'sort' => 901, 'is_default' => 0, 'success_rule' => CmsLeadPipeline::SUCCESS_MANUAL]);
    $check($own->save(), 'a pipeline with its own stages is created');
    foreach (['Звонок' => 10, 'Встреча' => 20] as $name => $sort) {
        (new CmsLeadStage(['cms_site_id' => $siteId, 'cms_lead_pipeline_id' => (int)$own->id, 'name' => $name, 'sort' => $sort]))->save();
    }
    $check(OutboundSales::ensureStages($own) === [OutboundSales::STAGE_SENT, OutboundSales::STAGE_VIEWED, OutboundSales::STAGE_REPLIED]
        && array_map(static fn($s) => $s->name, CmsLeadStage::forPipeline($own, $siteId)) === ['Звонок', OutboundSales::STAGE_SENT, OutboundSales::STAGE_VIEWED, OutboundSales::STAGE_REPLIED, 'Встреча']
        && OutboundSales::ensureStages($own) === [],
        'a pipeline with stages keeps them and only gets «КП отправлено», «Смотрел», «Ответил» after the first one, once');
    $outbound->refresh();
    $check(OutboundSales::stage($outbound, OutboundSales::ROLE_SENT) && OutboundSales::stage($outbound, OutboundSales::ROLE_VIEWED)
        && OutboundSales::stage($outbound, OutboundSales::ROLE_REPLIED)
        && (int)OutboundSales::pipeline($siteId)->id === (int)$outbound->id,
        'the stand «Исходящие продажи» has «КП отправлено», «Смотрел» and «Ответил» for campaigns (migrations m261029_100000, m261029_110000)');

    $sentStage = OutboundSales::stage($outbound, OutboundSales::ROLE_SENT);
    $viewedStage = OutboundSales::stage($outbound, OutboundSales::ROLE_VIEWED);
    $feed = static function (CmsLead $lead, string $event, int $sendId = 0) {
        $log = new skeeks\cms\models\CmsLog();
        $log->model_code = (string)$lead->skeeksModelCode;
        $log->model_id = (int)$lead->id;
        $log->model_as_text = (string)$lead->name;
        $log->log_type = OutboundSales::PROPOSAL_LOG_TYPE;
        $log->comment = 'КП №0';
        $log->data = array_filter(['id' => 0, 'event' => $event, 'send' => $sendId]);
        $log->save(false);
        $lead->refresh();
    };
    $sale = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Тест П2д: продажа', 'source_type' => CmsLead::SOURCE_MANUAL, 'cms_lead_pipeline_id' => (int)$outbound->id,
        'executor_id' => (int)$admin->id, 'status' => CmsLead::STATUS_IN_WORK]);
    $inWork = static fn(CmsLead $lead) => $lead->save() && ($lead->executor_id = (int)$admin->id) && ($lead->status = CmsLead::STATUS_IN_WORK) && $lead->save();
    $check($inWork($sale), 'an outbound deal in work is created: '.implode(' ', $sale->getFirstErrors()));
    $check((int)$sale->cms_lead_stage_id !== (int)$sentStage->id, 'it starts before «КП отправлено»');
    $feed($sale, 'sent');
    $check((int)$sale->cms_lead_stage_id === (int)$sentStage->id && $last($sale)->kind === CmsLeadStageHistory::KIND_STAGE,
        'a КП sent from the deal moves it to «КП отправлено», with a history row');
    $feed($sale, 'checked');
    $feed($sale, 'opened');
    $check((int)$sale->cms_lead_stage_id === (int)$viewedStage->id, 'a link check does nothing, the first view moves it to «Смотрел»');
    $feed($sale, 'sent');
    $check((int)$sale->cms_lead_stage_id === (int)$viewedStage->id, 'a second sending does not move it back');
    // The flow: a sending of a campaign (bot, API, mailing) does not move the deal — the campaign decides.
    $db = Yii::$app->db;
    $campaignSend = $db->getTableSchema('{{%cms_proposal_send}}') ? (int)(new yii\db\Query())->select('s.id')->from(['s' => '{{%cms_proposal_send}}'])
        ->innerJoin(['m' => '{{%cms_comm_message}}'], 'm.id = s.cms_comm_message_id')->andWhere(['not', ['m.cms_comm_campaign_id' => null]])->scalar() : 0;
    $manualSend = $db->getTableSchema('{{%cms_proposal_send}}') ? (int)(new yii\db\Query())->select('s.id')->from(['s' => '{{%cms_proposal_send}}'])
        ->innerJoin(['m' => '{{%cms_comm_message}}'], 'm.id = s.cms_comm_message_id')->andWhere(['m.cms_comm_campaign_id' => null])->scalar() : 0;
    if ($campaignSend && $manualSend) {
        $check(OutboundSales::isCampaignSend($campaignSend) && !OutboundSales::isCampaignSend($manualSend) && !OutboundSales::isCampaignSend(0),
            'a sending of a campaign is told from a manual one');
        $flow = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Тест П2д: поток', 'source_type' => CmsLead::SOURCE_MANUAL, 'cms_lead_pipeline_id' => (int)$outbound->id]);
        $inWork($flow);
        $start = (int)$flow->cms_lead_stage_id;
        $feed($flow, 'sent', $campaignSend);
        $feed($flow, 'opened', $campaignSend);
        $check((int)$flow->cms_lead_stage_id === $start, 'a sending and a view of a campaign do not move the deal');
        $feed($flow, 'sent', $manualSend);
        $check((int)$flow->cms_lead_stage_id === (int)$sentStage->id, 'a manual sending from the deal moves it');
    }
    $thinking = null;
    foreach (CmsLeadStage::forPipeline($outbound, $siteId) as $stage) {
        if ($stage->name === OutboundSales::STAGE_THINKING) {
            $thinking = $stage;
        }
    }
    if ($thinking) {
        $sale->cms_lead_stage_id = (int)$thinking->id;
        $sale->save();
        $feed($sale, 'opened');
        $check((int)$sale->cms_lead_stage_id === (int)$thinking->id, 'a deal already at «Думает» stays there');
    }
    $unsorted = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Тест П2д: неразобранная', 'source_type' => CmsLead::SOURCE_MANUAL, 'cms_lead_pipeline_id' => (int)$outbound->id]);
    $unsorted->save();
    $feed($unsorted, 'sent');
    $check($unsorted->status === CmsLead::STATUS_NEW && !$unsorted->cms_lead_stage_id, 'a deal in «Неразобранное» is not moved');
    $inboundSale = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Тест П2д: входящая', 'source_type' => CmsLead::SOURCE_MANUAL, 'executor_id' => (int)$admin->id, 'status' => CmsLead::STATUS_IN_WORK]);
    $inWork($inboundSale);
    $before = (int)$inboundSale->cms_lead_stage_id;
    $feed($inboundSale, 'sent');
    $check((int)$inboundSale->cms_lead_stage_id === $before, 'a deal of «Входящие» is not moved');
    // The settings of the site CRM choose another stage.
    if ($thinking) {
        Yii::$app->crmSettings->proposalViewedStageId = (int)$thinking->id;
        $other = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Тест П2д: настройка', 'source_type' => CmsLead::SOURCE_MANUAL, 'cms_lead_pipeline_id' => (int)$outbound->id,
            'executor_id' => (int)$admin->id, 'status' => CmsLead::STATUS_IN_WORK]);
        $inWork($other);
        $feed($other, 'opened');
        Yii::$app->crmSettings->proposalViewedStageId = null;
        $check((int)$other->cms_lead_stage_id === (int)$thinking->id, '«Этап при просмотре» of the CRM settings is used');
    }
    $fields = Yii::$app->crmSettings->getConfigFormFields()['outgoing']['fields'] ?? [];
    $check(isset($fields['outboundPipelineId'], $fields['proposalSentStageId'], $fields['proposalViewedStageId']),
        'the settings «Исходящие продажи» have the pipeline and the two stages');

    // The form: «Услуга — Компания», the site of a new company.
    $type = skeeks\cms\models\CmsDealType::find()->orderBy(['id' => SORT_ASC])->one();
    $identity = new skeeks\cms\crm\LeadIdentityForm(['company_mode' => skeeks\cms\crm\LeadIdentityForm::MODE_NEW, 'company_name' => 'Тест П2д: Ромашка', 'company_site' => 'romashka-p2d-test.ru']);
    $named = new CmsLead(['cms_site_id' => $siteId, 'cms_deal_type_id' => $type ? (int)$type->id : null]);
    $check(!$type || (new LeadTriage())->suggestName($named, $identity) === $type->name.' — Тест П2д: Ромашка', 'a deal with «Услуга» is named «Услуга — Компания»');
    $check(skeeks\cms\crm\LeadIdentityForm::siteUrl('romashka.ru') === 'https://romashka.ru' && skeeks\cms\crm\LeadIdentityForm::siteUrl('не сайт') === ''
        && $identity->validate(['company_site']), 'a typed site becomes an address, a wrong one is refused');
    $applied = (new LeadTriage())->applyIdentity($named, $identity, (int)$admin->id);
    $check(skeeks\cms\models\CmsCompanyLink::find()->andWhere(['cms_company_id' => (int)$applied['companyId'], 'url' => 'https://romashka-p2d-test.ru'])->exists(),
        'the site of a new company goes to its links');
    // The rollback of «Услуга» refuses before any DDL while deals have it.
    $sale->cms_deal_type_id = $type ? (int)$type->id : null;
    if ($type && $sale->save()) {
        require_once dirname(__DIR__).'/src/migrations/m261029_100000__alter_table__cms_lead_outbound.php';
        $refused = null;
        try {
            (new m261029_100000__alter_table__cms_lead_outbound(['db' => Yii::$app->db, 'compact' => true]))->safeDown();
        } catch (\yii\base\Exception $e) {
            $refused = $e->getMessage();
        }
        $check($refused !== null && strpos($refused, 'Откат невозможен') !== false
            && Yii::$app->db->getTableSchema('{{%cms_lead}}', true)->getColumn('cms_deal_type_id') !== null,
            'the rollback of «Услуга» refuses while deals have it, the schema stays');
    }
    $viewSource = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-lead/_identity.php');
    $check(strpos($viewSource, "'company_site' => 'site'") !== false, 'the form «Новая сделка» has the field «Сайт» of a new company');

    echo 'OK crm-lead-pipelines-stand: '.$checks." checks\n";
} finally {
    $transaction->rollBack();
}
