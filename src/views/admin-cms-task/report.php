<?php
/**
 * @var yii\web\View $this
 * @var skeeks\cms\controllers\AdminCmsTaskController $controller
 */

use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\Select;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$controller = $this->context;
$params = $controller->getTaskReportParams();
$report = $controller->buildTaskReport($params);
$columns = $controller->taskReportColumns();
$selectedColumns = (array)ArrayHelper::getValue($params, 'columns', []);
$display = (string)ArrayHelper::getValue($params, 'display', 'charts_data');
$showCharts = in_array($display, ['charts_data', 'charts']);
$showData = in_array($display, ['charts_data', 'data']);
$displayOptions = [
    'charts_data' => 'Графики + данные',
    'charts'      => 'Графики',
    'data'        => 'Данные',
];
$taskView = (string)ArrayHelper::getValue($params, 'task_view', 'list');
$taskViewOptions = [
    'table' => 'Таблица',
    'list'  => 'Список',
];
$showExecutorBreakdown = in_array('executor', $selectedColumns);
$showStatusBreakdown = in_array('status', $selectedColumns);
$showTime = (bool)array_intersect(['fact_time', 'fact_hours'], $selectedColumns);
$relationMode = 'company';
if (ArrayHelper::getValue($params, 'cms_user_id')) {
    $relationMode = 'client';
} elseif (ArrayHelper::getValue($params, 'cms_project_id') && !ArrayHelper::getValue($params, 'cms_company_id')) {
    $relationMode = 'project';
}
$exportParams = \Yii::$app->request->get();
unset($exportParams['r'], $exportParams['format']);

$makePieOptions = function ($title, array $items, $seriesName) {
    $data = [];
    foreach ($items as $item) {
        $tasks = (int)ArrayHelper::getValue($item, 'tasks');
        if ($tasks <= 0) {
            continue;
        }
        $data[] = [
            (string)ArrayHelper::getValue($item, 'name'),
            $tasks,
        ];
    }

    return [
        'title' => ['text' => $title],
        'chart' => [
            'type' => 'pie',
            'height' => 300,
            'spacing' => [0, 0, 0, 0],
        ],
        'credits' => [
            'enabled' => false,
        ],
        'tooltip' => [
            'pointFormat' => '<b>{point.y}</b> задач ({point.percentage:.1f}%)',
        ],
        'legend' => [
            'enabled' => true,
            'align' => 'center',
            'verticalAlign' => 'bottom',
            'layout' => 'horizontal',
            'itemStyle' => [
                'fontSize' => '12px',
                'fontWeight' => 'normal',
                'textOverflow' => 'ellipsis',
            ],
        ],
        'plotOptions' => [
            'pie' => [
                'allowPointSelect' => true,
                'cursor' => 'pointer',
                'center' => ['50%', '42%'],
                'size' => '68%',
                'showInLegend' => true,
                'dataLabels' => [
                    'enabled' => false,
                ],
            ],
        ],
        'series' => [
            [
                'type' => 'pie',
                'name' => $seriesName,
                'data' => $data,
            ],
        ],
    ];
};

$this->registerCss(<<<CSS
.sx-task-report .sx-report-filter-row {
    display: grid;
    grid-template-columns: repeat(5, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}
.sx-task-report .sx-report-card {
    background: #fff;
    border: 1px solid #e7e7e7;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 16px;
}
.sx-task-report .sx-report-label {
    display: block;
    color: #777;
    font-size: 12px;
    margin-bottom: 5px;
}
.sx-task-report .sx-report-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(160px, 1fr));
    gap: 12px;
}
.sx-task-report .sx-report-summary-item {
    border-left: 3px solid #2b7de9;
    padding-left: 12px;
}
.sx-task-report .sx-report-summary-value {
    font-size: 24px;
    line-height: 1.2;
    font-weight: 600;
}
.sx-task-report .sx-report-checks label {
    margin-right: 18px;
    white-space: nowrap;
    font-weight: normal;
}
.sx-task-report .sx-report-columns-field {
    grid-column: 1 / -1;
}
.sx-task-report .sx-report-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    min-height: 44px;
}
.sx-task-report .sx-report-submit.is-loading {
    pointer-events: none;
    opacity: .75;
}
.sx-task-report .sx-task-report-relation {
    grid-column: 1 / -1;
}
.sx-task-report .sx-task-report-relation .btn-group {
    display: flex;
    width: 100%;
}
.sx-task-report .sx-task-report-relation .btn {
    flex: 1 1 33.333%;
}
.sx-task-report .sx-task-report-relation .btn.sx-active {
    background: #6c757d !important;
    color: #fff;
}
.sx-task-report .sx-task-report-relation-field {
    display: none;
    margin-top: 12px;
}
.sx-task-report .sx-task-report-relation-field.is-active {
    display: block;
}
.sx-task-report .sx-task-report-company-project-field {
    margin-top: 12px;
}
.sx-task-report .sx-task-report-company-project-field [data-sx-quick-access-picker="projects"] {
    display: none !important;
}
.sx-task-report .sx-report-table td {
    vertical-align: top;
}
.sx-task-report .sx-report-chart {
    height: 320px;
    margin-top: 12px;
    overflow: hidden;
}
.sx-task-report .sx-report-chart .highcharts-container,
.sx-task-report .sx-report-chart svg {
    max-width: 100% !important;
}
.sx-task-report .sx-report-result {
    white-space: pre-line;
}
.sx-task-report .sx-report-task-list {
    display: grid;
    gap: 12px;
}
.sx-task-report .sx-report-task-item {
    border-bottom: 1px solid #e5e9ef;
    padding: 0 0 14px;
}
.sx-task-report .sx-report-task-item:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}
.sx-task-report .sx-report-task-title {
    font-size: 20px;
    line-height: 1.3;
    font-weight: 600;
    margin: 0 0 8px;
}
.sx-task-report .sx-report-task-meta {
    color: #687685;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 10px;
}
.sx-task-report .sx-report-task-meta span {
    display: inline-block;
    margin-right: 16px;
}
.sx-task-report .sx-report-task-result {
    border-left: 3px solid #d7dee8;
    background: #f8fafc;
    margin: 0;
    padding: 10px 12px;
    white-space: pre-line;
}
@media (max-width: 1100px) {
    .sx-task-report .sx-report-filter-row,
    .sx-task-report .sx-report-summary {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 700px) {
    .sx-task-report .sx-report-filter-row,
    .sx-task-report .sx-report-summary {
        grid-template-columns: 1fr;
    }
}
CSS);

$projectAjaxUrl = Json::encode(Url::current(['ajaxid' => 'task-report-cms-project-id']));
$this->registerJs(<<<JS
(function() {
    var root = $(".sx-task-report-relation");
    var company = $("#task-report-cms-company-id");
    var client = $("#task-report-cms-user-id");
    var companyProject = $("#task-report-cms-project-id");
    var project = $("#task-report-cms-standalone-project-id");
    var projectRequest = null;
    var projectAjaxUrl = {$projectAjaxUrl};

    if (!root.length) {
        return;
    }

    function clearProject() {
        companyProject.val("").trigger("change");
        project.val("").trigger("change");
    }

    function activeMode() {
        return root.find(".sx-task-report-relation-btn.sx-active").data("mode") || "company";
    }

    function showMode(mode) {
        root.find(".sx-task-report-relation-btn").removeClass("sx-active")
            .filter('[data-mode="' + mode + '"]').addClass("sx-active");
        root.find(".sx-task-report-relation-field").removeClass("is-active");
        root.find('.sx-task-report-relation-field[data-mode="' + mode + '"]').addClass("is-active");

        if (mode !== "company") {
            root.find(".sx-task-report-company-project-field").removeClass("is-active").slideUp();
        }

        company.prop("disabled", mode !== "company");
        client.prop("disabled", mode !== "client");
        companyProject.prop("disabled", mode !== "company");
        project.prop("disabled", mode !== "project");
    }

    function updateCompanyProjects() {
        var companyId = company.val();
        var field = root.find(".sx-task-report-company-project-field");

        if (projectRequest) {
            projectRequest.abort();
            projectRequest = null;
        }

        if (!companyId || activeMode() !== "company") {
            field.removeClass("is-active").slideUp();
            companyProject.prop("disabled", true);
            return;
        }

        projectRequest = $.getJSON(projectAjaxUrl, {
            cms_company_id: companyId,
            q: ""
        }, function(data) {
            var hasProjects = data && data.results && data.results.length;
            if (hasProjects) {
                companyProject.prop("disabled", false);
                field.addClass("is-active").slideDown();
            } else {
                companyProject.val("").trigger("change");
                companyProject.prop("disabled", true);
                field.removeClass("is-active").slideUp();
            }
        });
    }

    root.on("click", ".sx-task-report-relation-btn", function() {
        var mode = $(this).data("mode");
        var wasCompany = activeMode() === "company";

        showMode(mode);

        if (mode === "company") {
            client.val("").trigger("change");
            updateCompanyProjects();
        } else if (mode === "client") {
            company.val("").trigger("change");
            clearProject();
        } else if (mode === "project") {
            if (wasCompany) {
                clearProject();
            }
            company.val("").trigger("change");
            client.val("").trigger("change");
        }
    });

    company.on("select2:select select2:unselect change", function() {
        if (activeMode() === "company") {
            client.val("").trigger("change");
            updateCompanyProjects();
        }
    });

    client.on("select2:select select2:unselect change", function() {
        if (activeMode() === "client") {
            company.val("").trigger("change");
            clearProject();
        }
    });

    project.on("select2:select select2:unselect change", function() {
        if (activeMode() === "project") {
            company.val("").trigger("change");
            client.val("").trigger("change");
        }
    });

    root.closest("form").on("beforeSubmit submit", function() {
        var mode = activeMode();
        if (mode === "company") {
            client.val("");
            project.val("");
        } else if (mode === "client") {
            company.val("");
            companyProject.val("");
            project.val("");
        } else if (mode === "project") {
            company.val("");
            client.val("");
            companyProject.val("");
        }
    });

    showMode(root.data("mode") || "company");
    updateCompanyProjects();
})();
JS);

$this->registerJs(<<<JS
(function() {
    var form = $(".sx-task-report").find("form").first();
    var submitButton = form.find(".sx-report-submit");
    var exportButtons = form.find(".sx-report-export");
    var baseline = "";
    var ready = false;

    if (!form.length) {
        return;
    }

    exportButtons.tooltip();

    function formState() {
        return form.serialize();
    }

    function setDirty(isDirty) {
        submitButton.stop(true, true);
        exportButtons.stop(true, true);

        if (isDirty) {
            submitButton.fadeIn(160);
            exportButtons.fadeOut(120);
        } else {
            submitButton.fadeOut(120);
            exportButtons.fadeIn(160);
        }
    }

    function refreshState() {
        if (!ready) {
            return;
        }
        setDirty(formState() !== baseline);
    }

    form.on("change input select2:select select2:unselect", "input, select, textarea", refreshState);
    form.on("click", ".sx-task-report-relation-btn", function() {
        setTimeout(refreshState, 0);
    });

    form.on("submit beforeSubmit", function() {
        submitButton.addClass("is-loading").prop("disabled", true);
        submitButton.find("i").removeClass("fa-chart-bar").addClass("fa-spinner fa-spin");
    });

    setTimeout(function() {
        baseline = formState();
        ready = true;
        setDirty(false);
    }, 700);
})();
JS);
?>

<div class="sx-task-report">
    <div class="sx-report-card">
        <?php $form = \yii\bootstrap\ActiveForm::begin([
            'method' => 'get',
            'action' => Url::to(['report']),
        ]); ?>

        <div class="sx-report-filter-row">
            <div class="sx-task-report-relation" data-mode="<?php echo Html::encode($relationMode); ?>">
                <div class="btn-group btn-block" role="group" aria-label="Task relation">
                    <button type="button" class="btn btn-default sx-task-report-relation-btn" data-mode="company">Компания</button>
                    <button type="button" class="btn btn-default sx-task-report-relation-btn" data-mode="client">Клиент</button>
                    <button type="button" class="btn btn-default sx-task-report-relation-btn" data-mode="project">Проект</button>
                </div>

                <div class="sx-task-report-relation-field" data-mode="company">
                    <span class="sx-report-label">Компания</span>
                    <?php echo AjaxSelectModel::widget([
                        'id'         => 'task-report-cms-company-id',
                        'name'       => 'cms_company_id',
                        'value'      => ArrayHelper::getValue($params, 'cms_company_id') ?: null,
                        'modelClass' => CmsCompany::class,
                        'searchQuery' => function ($word = '') {
                            $query = CmsCompany::find()->forManager();
                            if ($word) {
                                $query->search($word);
                            }
                            return $query;
                        },
                        'options' => [
                            'class' => 'form-control',
                        ],
                        'placeholder' => 'Любая компания',
                    ]); ?>

                    <div class="sx-task-report-relation-field sx-task-report-company-project-field">
                        <span class="sx-report-label">Проект компании</span>
                        <?php echo AjaxSelectModel::widget([
                            'id'         => 'task-report-cms-project-id',
                            'name'       => 'cms_project_id',
                            'value'      => ArrayHelper::getValue($params, 'cms_project_id') ?: null,
                            'modelClass' => CmsProject::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsProject::find()->forManager();
                                $cmsCompanyId = \Yii::$app->request->get('cms_company_id');
                                if ($cmsCompanyId) {
                                    $query->andWhere(['cms_company_id' => $cmsCompanyId]);
                                }
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                            'pluginOptions' => [
                                'ajax' => [
                                    'data' => new \yii\web\JsExpression('function(params) { return {q: params.term, cms_company_id: $(".sx-task-report-relation-btn[data-mode=company]").hasClass("sx-active") ? $("#task-report-cms-company-id").val() : ""}; }'),
                                ],
                            ],
                            'options' => [
                                'class' => 'form-control',
                            ],
                            'placeholder' => 'Любой проект компании',
                        ]); ?>
                    </div>
                </div>

                <div class="sx-task-report-relation-field" data-mode="client">
                    <span class="sx-report-label">Клиент</span>
                    <?php echo AjaxSelectModel::widget([
                        'id'         => 'task-report-cms-user-id',
                        'name'       => 'cms_user_id',
                        'value'      => ArrayHelper::getValue($params, 'cms_user_id') ?: null,
                        'modelClass' => CmsUser::class,
                        'searchQuery' => function ($word = '') {
                            $query = CmsUser::find()->forManager();
                            if ($word) {
                                $query->search($word);
                            }
                            return $query;
                        },
                        'options' => [
                            'class' => 'form-control',
                        ],
                        'placeholder' => 'Любой клиент',
                    ]); ?>
                </div>

                <div class="sx-task-report-relation-field" data-mode="project">
                    <span class="sx-report-label">Проект</span>
                    <?php echo AjaxSelectModel::widget([
                        'id'         => 'task-report-cms-standalone-project-id',
                        'name'       => 'cms_project_id',
                        'value'      => ArrayHelper::getValue($params, 'cms_project_id') ?: null,
                        'modelClass' => CmsProject::class,
                        'searchQuery' => function ($word = '') {
                            $query = CmsProject::find()->forManager();
                            if ($word) {
                                $query->search($word);
                            }
                            return $query;
                        },
                        'options' => [
                            'class' => 'form-control',
                        ],
                        'placeholder' => 'Любой проект',
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="sx-report-filter-row">
            <div>
                <span class="sx-report-label">Период</span>
                <?php echo DaterangeInputWidget::widget([
                    'name'    => 'period',
                    'value'   => ArrayHelper::getValue($params, 'period'),
                    'options' => [
                        'placeholder' => 'Период отчета',
                        'class'       => 'form-control',
                    ],
                ]); ?>
            </div>
            <div>
                <span class="sx-report-label">Исполнитель</span>
                <?php echo AjaxSelectModel::widget([
                    'name'       => 'executor_id',
                    'value'      => ArrayHelper::getValue($params, 'executor_id') ?: null,
                    'modelClass' => CmsUser::class,
                    'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->isWorker();
                        if ($word) {
                            $query->search($word);
                        }
                        return $query;
                    },
                    'options' => [
                        'class' => 'form-control',
                    ],
                    'placeholder' => 'Любой исполнитель',
                ]); ?>
            </div>
            <div>
                <span class="sx-report-label">Статус</span>
                <?php echo Select::widget([
                    'name'     => 'status',
                    'value'    => ArrayHelper::getValue($params, 'status'),
                    'data'     => CmsTask::statuses(),
                    'multiple' => true,
                    'options'  => [
                        'class' => 'form-control',
                    ],
                    'placeholder' => 'Любые статусы',
                ]); ?>
            </div>
            <div>
                <span class="sx-report-label">Отображение данных</span>
                <?php echo Html::dropDownList('display', $display, $displayOptions, [
                    'class' => 'form-control',
                ]); ?>
            </div>
            <div>
                <span class="sx-report-label">Отображение задач</span>
                <?php echo Html::dropDownList('task_view', $taskView, $taskViewOptions, [
                    'class' => 'form-control',
                ]); ?>
            </div>
        </div>

        <div class="sx-report-filter-row">
            <div class="sx-report-columns-field">
                <span class="sx-report-label">Выгружаемые данные</span>
                <div class="sx-report-checks">
                    <?php echo Html::checkboxList('columns', $selectedColumns, $columns); ?>
                </div>
            </div>
        </div>

        <div class="sx-report-actions">
            <button type="submit" class="btn btn-primary sx-report-submit" style="display: none;">
                <i class="fa fa-chart-bar"></i> Сформировать отчет
            </button>
            <?php echo Html::a('<i class="fa fa-file-csv"></i> CSV', ArrayHelper::merge(['report-export', 'format' => 'csv'], $exportParams), [
                'class' => 'btn btn-default sx-report-export',
                'data-pjax' => '0',
                'data-toggle' => 'tooltip',
                'title' => 'Скачать CSV',
            ]); ?>
            <?php echo Html::a('<i class="fa fa-file-excel"></i> XLSX', ArrayHelper::merge(['report-export', 'format' => 'xlsx'], $exportParams), [
                'class' => 'btn btn-default sx-report-export',
                'data-pjax' => '0',
                'data-toggle' => 'tooltip',
                'title' => 'Скачать XLSX',
            ]); ?>
            <?php echo Html::a('<i class="fa fa-file-pdf"></i> PDF', ArrayHelper::merge(['report-export', 'format' => 'pdf'], $exportParams), [
                'class' => 'btn btn-default sx-report-export',
                'data-pjax' => '0',
                'data-toggle' => 'tooltip',
                'title' => 'Скачать PDF',
            ]); ?>
        </div>

        <?php $form::end(); ?>
    </div>

    <div class="sx-report-card sx-report-summary">
        <div class="sx-report-summary-item">
            <div class="sx-report-label">Задач в отчете</div>
            <div class="sx-report-summary-value"><?php echo (int)ArrayHelper::getValue($report, 'summary.tasks'); ?></div>
        </div>
        <?php if ($showTime) : ?>
        <div class="sx-report-summary-item">
            <div class="sx-report-label">Отработанное время</div>
            <div class="sx-report-summary-value"><?php echo CmsScheduleHelper::durationAsText((int)ArrayHelper::getValue($report, 'summary.duration')); ?></div>
        </div>
        <div class="sx-report-summary-item">
            <div class="sx-report-label">Отработано часов</div>
            <div class="sx-report-summary-value"><?php echo \Yii::$app->formatter->asDecimal((float)ArrayHelper::getValue($report, 'summary.hours'), 1); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (($showData || $showCharts) && ($showExecutorBreakdown || $showStatusBreakdown)) : ?>
    <div class="row">
        <?php if ($showExecutorBreakdown) : ?>
        <div class="col-md-6">
            <div class="sx-report-card">
                <h4>По исполнителям</h4>
                <?php if ($showData) : ?>
                <table class="table table-striped table-hover">
                    <thead>
                    <tr>
                        <th>Исполнитель</th>
                        <th>Задач</th>
                        <?php if ($showTime) : ?>
                        <th>Время</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ((array)ArrayHelper::getValue($report, 'byExecutor') as $item) : ?>
                        <tr>
                            <td><?php echo Html::encode($item['name']); ?></td>
                            <td><?php echo (int)$item['tasks']; ?></td>
                            <?php if ($showTime) : ?>
                            <td><?php echo CmsScheduleHelper::durationAsText((int)$item['duration']); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                <?php if ($showCharts) : ?>
                    <div class="sx-report-chart">
                        <?php echo \skeeks\widget\highcharts\Highcharts::widget([
                            'options' => $makePieOptions(null, (array)ArrayHelper::getValue($report, 'byExecutor'), 'tasks'),
                        ]); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($showStatusBreakdown) : ?>
        <div class="col-md-6">
            <div class="sx-report-card">
                <h4>По статусам</h4>
                <?php if ($showData) : ?>
                <table class="table table-striped table-hover">
                    <thead>
                    <tr>
                        <th>Статус</th>
                        <th>Задач</th>
                        <?php if ($showTime) : ?>
                        <th>Время</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ((array)ArrayHelper::getValue($report, 'byStatus') as $item) : ?>
                        <tr>
                            <td><?php echo Html::encode($item['name']); ?></td>
                            <td><?php echo (int)$item['tasks']; ?></td>
                            <?php if ($showTime) : ?>
                            <td><?php echo CmsScheduleHelper::durationAsText((int)$item['duration']); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                <?php if ($showCharts) : ?>
                    <div class="sx-report-chart">
                        <?php echo \skeeks\widget\highcharts\Highcharts::widget([
                            'options' => $makePieOptions(null, (array)ArrayHelper::getValue($report, 'byStatus'), 'tasks'),
                        ]); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

    <?php if ($showData) : ?>
    <div class="sx-report-card">
        <?php if ($taskView == 'list') : ?>
            <div class="sx-report-task-list">
                <?php foreach ((array)ArrayHelper::getValue($report, 'rows') as $row) : ?>
                    <article class="sx-report-task-item">
                        <h3 class="sx-report-task-title"><?php echo Html::encode(ArrayHelper::getValue($row, 'name')); ?></h3>
                        <div class="sx-report-task-meta">
                            <?php foreach ($selectedColumns as $column) : ?>
                                <?php if (in_array($column, ['name', 'result'])) {
                                    continue;
                                } ?>
                                <?php $value = ArrayHelper::getValue($row, $column); ?>
                                <?php if ($value === null || $value === '') {
                                    continue;
                                } ?>
                                <span><b><?php echo Html::encode(ArrayHelper::getValue($columns, $column, $column)); ?>:</b> <?php echo Html::encode($value); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (in_array('result', $selectedColumns) && ArrayHelper::getValue($row, 'result') !== '') : ?>
                            <blockquote class="sx-report-task-result"><?php echo Html::encode(ArrayHelper::getValue($row, 'result')); ?></blockquote>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$report['rows']) : ?>
                    <div>По выбранным условиям данных нет.</div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <table class="table table-striped table-hover sx-report-table">
                <thead>
                <tr>
                    <?php foreach ($selectedColumns as $column) : ?>
                        <th><?php echo Html::encode(ArrayHelper::getValue($columns, $column, $column)); ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ((array)ArrayHelper::getValue($report, 'rows') as $row) : ?>
                    <tr>
                        <?php foreach ($selectedColumns as $column) : ?>
                            <td class="<?php echo $column == 'result' ? 'sx-report-result' : ''; ?>">
                                <?php echo Html::encode(ArrayHelper::getValue($row, $column)); ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$report['rows']) : ?>
                    <tr>
                        <td colspan="<?php echo max(1, count($selectedColumns)); ?>">По выбранным условиям данных нет.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
