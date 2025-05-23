<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsUserEmail;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextField;
use yii\helpers\ArrayHelper;

/**
 * Class AdminUserEmailController
 * @package skeeks\cms\controllers
 */
class AdminUserEmailController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление email адресами";
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsUserEmail::className();

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
                'size'           => BackendAction::SIZE_SMALL,
                'buttons'        => ['save'],
                /*"accessCallback" => function ($model) {

                    $cmsUserEmail = new CmsUserEmail();
                    $cmsUserEmail->load(\Yii::$app->request->get());

                    if ($model) {
                        return \Yii::$app->user->can("cms/admin-user/manage", ['model' => $cmsUserEmail->cmsUser]);
                    }

                    return false;
                },*/
            ],
            "update" => [
                'fields'         => [$this, 'updateFields'],
                'size'           => BackendAction::SIZE_SMALL,
                'buttons'        => ['save'],
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
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $result = [
            [
                'class'   => HtmlBlock::class,
                'content' => '<div class="row no-gutters"><div class="col-12" style="max-width: 500px;">',
            ],
            'value' => [
                'class'          => TextField::class,
                'elementOptions' => [
                    'placeholder'  => 'Email',
                    'autocomplete' => 'off',
                ],
            ],
            [
                'class'   => HtmlBlock::class,
                'content' => '</div><div class="col-12" style="max-width: 500px;">',
            ],
            'name'  => [
                'class'          => TextField::class,
                'elementOptions' => [
                    'placeholder' => 'Например рабочий email',
                ],
            ],
            [
                'class'   => HtmlBlock::class,
                'content' => '</div></div>',
            ],
            [
                'class'   => HtmlBlock::class,
                'content' => '<div style="display: none;">',
            ],
            'cms_user_id',
            [
                'class'   => HtmlBlock::class,
                'content' => '</div>',
            ],

        ];

        return $result;
    }

}
