<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use kartik\datecontrol\DateControl;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTaskSchedule;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\admin\CmsTaskStatusWidget;
use skeeks\cms\widgets\admin\CmsTaskViewWidget;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\ckeditor\Ckeditor;
use skeeks\cms\widgets\formInputs\SmartDurationInputWidget;
use skeeks\cms\widgets\formInputs\SmartTimeInputWidget;
use skeeks\cms\widgets\GridView;
use skeeks\crm\helpers\CrmScheduleHelper;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsTaskScheduleController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Лог исполнения задач");
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsTaskSchedule::class;

        $this->permissionName = CmsManager::PERMISSION_ADMIN_ACCESS;
        $this->generateAccessActions = false;

        parent::init();
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [

                'name' => 'Список задач',

                'filters' => false,

                'grid' => [

                    'defaultOrder' => [
                        'id' => SORT_ASC,
                    ],
                    
                    /*'sortAttributes' => [
                        'scheduleTotalTime' => [
                            'asc'  => ['scheduleTotalTime' => SORT_ASC],
                            'desc' => ['scheduleTotalTime' => SORT_DESC],
                            'name' => 'Отработанное время',
                        ],
                    ],*/

                    'visibleColumns' => [
                        /*'checkbox',*/
                        'actions',


                        'created_by',

                        'day',

                        'start_at',
                        'end_at',

                        'duration',

                        /*'cms_task_id',*/
                    ],

                    'columns' => [

                        'day' => [
                            'label'    => "День",
                            'format'    => "raw",
                            'value'    => function(CmsTaskSchedule $cmsTaskSchedule) {
                                //return CmsScheduleHelper::getAsTextBySchedules([$cmsTaskSchedule]) . " " . ;
                                return \Yii::$app->formatter->asDate($cmsTaskSchedule->start_at);
                            },
                        ],

                        'start_at' => [
                            'value'    => function(CmsTaskSchedule $cmsTaskSchedule) {
                                return \Yii::$app->formatter->asDatetime($cmsTaskSchedule->start_at, "php:h:i");
                            },
                        ],

                        'end_at' => [
                            'value'    => function(CmsTaskSchedule $cmsTaskSchedule) {
                                return $cmsTaskSchedule->end_at ? \Yii::$app->formatter->asDatetime($cmsTaskSchedule->end_at, "php:h:i") : "сейчас...";
                            },
                        ],

                        'created_by' => [
                            'value' => function (CmsTaskSchedule $CmsTask) {
                                return CmsWorkerViewWidget::widget(['user' => $CmsTask->createdBy, 'isSmall' => true]);
                            },
                        ],
                        'duration' => [
                            'label'    => "Длительность",
                            'format'    => "raw",
                            'value' => function (CmsTaskSchedule $cmsTaskSchedule) {
                                return CmsScheduleHelper::durationAsTextBySchedules([$cmsTaskSchedule]);
                            },
                        ],
                    ],
                ],
            ],

            "create" => new UnsetArrayValue(),

            "update" => [
                'fields' => [$this, 'updateFields'],
                'accessCallback' => function () {
                    return \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
                }
            ],

            "delete" => [
                'accessCallback' => function () {
                    return \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
                }
            ],

            "delete-multi" => new UnsetArrayValue(),

            /*"update" => [
                'fields' => [$this, 'updateFields'],
            ],*/
        ]);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsTask
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());


        $result = [

            'start_at' => [
                'class'        => WidgetField::class,
                'widgetClass'  => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                ],
            ],

            'end_at' => [
                'class'        => WidgetField::class,
                'widgetClass'  => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                ],
            ],
        ];



        return $result;
    }


}
