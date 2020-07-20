<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsSiteAddress;
use skeeks\cms\ya\map\widgets\YaMapInput;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteAddressController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Адреса сайта");
        $this->modelShowAttribute = "url";
        $this->modelClassName = CmsSiteAddress::class;

        $this->generateAccessActions = false;
        $this->permissionName = 'cms/admin-cms-site-address';

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            'index'  => [
                'on beforeRender' => function (Event $e) {
                    $e->content = Alert::widget([
                        'closeButton' => false,
                        'options'     => [
                            'class' => 'alert-default',
                        ],

                        'body' => <<<HTML
<p>Добавьте адреса на ваш сайт. Они будут отображаться в специально отведенных местах шаблона. Где именно, будет зависеть от шаблона.</p>
HTML
                        ,
                    ]);
                },

                "backendShowings" => false,
                "filters"         => false,
                'grid'            => [

                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;

                        $query->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);
                    },


                    'defaultOrder' => [
                        'priority' => SORT_ASC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'priority',
                    ],
                    'columns'        => [
                        'custom' => [
                            'attribute' => 'value',
                            'format'    => "raw",
                            'value'     => function ($model) {
                                $data[] = Html::a($model->value, "#", [
                                    'class' => "sx-trigger-action",
                                    'style' => "font-size: 18px;",
                                ]);

                                if ($model->name) {
                                    $data[] = "<span style='color: gray;'>(".$model->name.")</span>";
                                }

                                return implode(" ", $data);
                            },
                        ],
                    ],
                ],
            ],


            "emails" => [
                'class' => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'            => "Email-ы",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-site-address-email",
                'relation'        => ['cms_site_address_id' => 'id'],
                'priority'        => 600,
                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_site_address_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],

            "phones" => [
                'class' => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'            => "Телефоны",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-site-address-phone",
                'relation'        => ['cms_site_address_id' => 'id'],
                'priority'        => 600,
                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_site_address_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],


            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],
        ]);

        return $actions;
    }

    public function updateFields($action)
    {
        $result = [];

        if (!\Yii::$app->yaMap->api_key) {
            $result[] = [
                'class'   => HtmlBlock::class,
                'content' => Alert::widget([
                    'body'    => 'У вас не настроен компонент для работы с yandex картами, в настройках компонента yandex карты пропишите api ключ.',
                    'options' => [
                        'class' => 'alert alert-danger',
                    ],
                    'closeButton' => false,
                ]),
            ];
        }

        $result = ArrayHelper::merge($result, [

            'coordinates' => [
                'class'        => WidgetField::class,
                'widgetClass'  => YaMapInput::class,
                'widgetConfig' => [
                    'YaMapWidgetOptions' => [
                        'options' => [
                            'style' => 'height: 400px;',
                        ],
                    ],

                    'clientOptions' => [
                        'select' => new \yii\web\JsExpression(<<<JS
        function(e, data)
        {
            var lat = data.coords[0];
            var long = data.coords[1];
            var address = data.address;
            var phone = data.phone;
            var email = data.email;

            $('#cmssiteaddress-value').val(address);
            $('#cmssiteaddress-latitude').val(lat);
            $('#cmssiteaddress-longitude').val(long);
        }
JS
                        ),
                    ],
                ],
            ],

            'name',

            [
                'class'   => HtmlBlock::class,
                'content' => '<div style="display: block;">',
            ],
            'value',
            'latitude',
            'longitude',

            [
                'class'   => HtmlBlock::class,
                'content' => '</div>',
            ],

            'cms_image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'work_time'    => [
                'class'       => WidgetField::class,
                'widgetClass' => \skeeks\yii2\scheduleInputWidget\ScheduleInputWidget::class,
            ],

            'priority' => [
                'class' => NumberField::class,
            ],
        ]);

        return $result;
    }

}
