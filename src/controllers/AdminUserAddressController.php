<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsUserAddress;
use skeeks\cms\models\CmsUserPhone;
use skeeks\cms\ya\map\widgets\YaMapDecodeInput;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use yii\helpers\ArrayHelper;

/**
 * Class AdminUserEmailController
 * @package skeeks\cms\controllers
 */
class AdminUserAddressController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление адресами";
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsUserAddress::className();

        $this->permissionName = 'cms/admin-user';
        $this->generateAccessActions = false;

        parent::init();

    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            "create" => [
                'fields'         => [$this, 'updateFields'],
                'buttons'        => ['save'],
                //'size'           => BackendAction::SIZE_SMALL,
                /*"accessCallback" => function ($model) {

                    $cmsUserEmail = new CmsUserPhone();
                    $cmsUserEmail->load(\Yii::$app->request->get());

                    if ($model) {
                        return \Yii::$app->user->can("cms/admin-user/manage", ['model' => $cmsUserEmail->cmsUser]);
                    }

                    return false;
                },*/
            ],
            "update" => [
                'fields'         => [$this, 'updateFields'],
                'buttons'        => ['save'],
                //'size'           => BackendAction::SIZE_SMALL,
                "accessCallback" => function ($model) {
                    if ($this->model) {
                        return \Yii::$app->user->can("cms/admin-user/manage", ['model' => $this->model->cmsUser]);
                    }
                    return false;
                },
            ],
            "delete" => [
                "accessCallback" => function ($model) {
                    if ($this->model) {
                        return \Yii::$app->user->can("cms/admin-user/manage", ['model' => $this->model->cmsUser]);
                    }
                    return false;
                },
            ],
        ]);

        return $actions;
    }


    public function updateFields($action)
    {
        $result = [];

        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        if (!\Yii::$app->yaMap->api_key) {
            $result[] = [
                'class'   => HtmlBlock::class,
                'content' => Alert::widget([
                    'body'        => 'У вас не настроен компонент для работы с yandex картами, в настройках компонента yandex карты пропишите api ключ.',
                    'options'     => [
                        'class' => 'alert alert-danger',
                    ],
                    'closeButton' => false,
                ]),
            ];
        }

        //cmsuseraddress-value

        $result = ArrayHelper::merge($result, [


            [
                'class'   => HtmlBlock::class,
                'content' => '<div style="display: block;">',
            ],
            'value' => [
                'class'        => WidgetField::class,
                'widgetClass'  => YaMapDecodeInput::class,
                'widgetConfig' => [
                    'modelLatitudeAttr'  => 'latitude',
                    'modelLongitudeAttr' => 'longitude',
                ],
            ],

            [
                'class'   => HtmlBlock::class,
                'content' => '</div>',
            ],

            [
                'class'   => HtmlBlock::class,
                'content' => '<div style="display: none;">',
            ],
            'cms_user_id',
            'latitude',
            'longitude',
            [
                'class'   => HtmlBlock::class,
                'content' => '</div>',
            ],


            [
                'class'   => HtmlBlock::class,
                'content' => '<div class="row"><div class="col-md-4">',
            ],
            'entrance',
            [
                'class'   => HtmlBlock::class,
                'content' => '</div><div class="col-md-4">',
            ],
            'floor',
            [
                'class'   => HtmlBlock::class,
                'content' => '</div><div class="col-md-4">',
            ],
            'apartment_number',
            [
                'class'   => HtmlBlock::class,
                'content' => '</div></div>',
            ],


            'name'         => [
                'class'          => TextField::class,
                'elementOptions' => [
                    'placeholder' => 'Например, домашний адрес или рабочий адрес',
                ],
            ],
            'comment'      => [
                'class'          => TextareaField::class,
                'elementOptions' => [
                    'placeholder' => 'Дополнительные примечания в свободной форме. Например код домофона или прочие особенности.',
                ],
            ],
            'cms_image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],

            /*'priority' => [
                'class' => NumberField::class,
            ],*/


        ]);

        return $result;
    }


}
