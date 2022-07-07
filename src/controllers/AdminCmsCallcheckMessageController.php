<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\DefaultActionColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCallcheckMessage;
use skeeks\cms\models\CmsSiteEmail;
use skeeks\cms\models\CmsSitePhone;
use skeeks\cms\models\CmsSiteSocial;
use skeeks\cms\models\CmsSmsMessage;
use skeeks\cms\models\CmsSmsProvider;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\yii2\form\Builder;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsCallcheckMessageController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Дозвоны");
        $this->modelShowAttribute = 'asText';
        $this->modelClassName = CmsCallcheckMessage::class;

        $this->generateAccessActions = false;
        $this->permissionName = 'cms/admin-settings';

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            'create' => new UnsetArrayValue(),
            'update' => new UnsetArrayValue(),

            'index'  => [
                'on beforeRender' => function (Event $e) {
                    $e->content = Alert::widget([
                        'closeButton' => false,
                        'options'     => [
                            'class' => 'alert-default',
                        ],

                        'body' => <<<HTML
<p>
В этом разделе показаны все дозвоны для авторизации на сайте.

</p>
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
                         * @var $query CmsActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $query->cmsSite();
                    },


                    'defaultOrder' => [
                        'created_at' => SORT_DESC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',

                        'phone',
                        'code',
                        'created_at',
                        'status',
                    ],
                    'columns'        => [
                        'phone' => [
                            'headerOptions' => [
                                'style' => [
                                    'width' => '150px;'
                                ]
                            ],
                            'format'    => "raw",
                            'class' => DefaultActionColumn::class
                        ],
                        'created_at' => [
                            'class' => DateTimeColumnData::class
                        ],
                        'status' => [
                            'headerOptions' => [
                                'style' => [
                                    'width' => '150px;'
                                ]
                            ],
                            'value' => function(CmsCallcheckMessage $message) {
                                $data[] = "<div>{$message->statusAsText}</div>";
                                if ($message->cmsSmsProvider) {
                                    $data[] = "<div style='font-size: 10px; color: gray;'>{$message->cmsSmsProvider->name}</div>";
                                }

                                if ($message->isError) {
                                    $data[] = Html::tag("small", $message->error_message, [
                                        'style' => "color: red;"
                                    ]);
                                }
                                return implode("", $data);
                            }
                        ],
                    ],
                ],
            ],

        ]);

        return $actions;
    }


}
