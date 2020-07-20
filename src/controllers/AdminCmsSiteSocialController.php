<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsSiteEmail;
use skeeks\cms\models\CmsSitePhone;
use skeeks\cms\models\CmsSiteSocial;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteSocialController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Email сайта");
        $this->modelShowAttribute = "url";
        $this->modelClassName = CmsSiteSocial::class;

        $this->generateAccessActions = false;
        $this->permissionName = 'cms/admin-cms-site-social';

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
<p>Добавьте телефоны на ваш сайт. Они будут отображаться в специально отведенных местах шаблона. Где именно, будет зависеть от шаблона.</p>
<p>Так же телефоны могут использоваться в микроразметке и прочих модулях и компонентах.</p>
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
                            'attribute' => 'url',
                            'format'    => "raw",
                            'value'     => function ($model) {
                                $data[] = Html::a($model->url, "#", [
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
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $result = [
            'social_type' => [
                'class' => SelectField::class,
                'items' => CmsSiteSocial::getSocialTypes()
            ],
            'url',
            'name',
            'priority' => [
                'class' => NumberField::class
            ],
        ];

        return $result;
    }

}
