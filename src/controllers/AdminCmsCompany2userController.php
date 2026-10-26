<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyLink;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\widgets\Select;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\TextField;
use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;

/**
 * Class AdminUserEmailController
 * @package skeeks\cms\controllers
 */
class AdminCmsCompany2userController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Контакт компании";
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsCompany2user::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;
        //A small window: the person is named in the form; removing is "Убрать из компании" in the card menu.
        $this->modelHeader = '';

        parent::init();
    }
    
    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            'index' => new UnsetArrayValue(),
            "create" => new UnsetArrayValue(),

            "update" => [
                'fields' => [$this, 'updateFields'],
                'size'           => BackendModelUpdateAction::SIZE_SMALL,
                'buttons'        => ['save'],
                /*"accessCallback" => function ($model) {
                    if ($this->model) {
                        return \Yii::$app->user->can("cms/admin-user/update", ['model' => $this->model]);
                    }
                    return false;
                },*/
            ],
            "delete" => [
                'name' => 'Убрать из компании'
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
                'content' => \yii\helpers\Html::tag('div',
                    \yii\helpers\Html::tag('label', 'Человек')
                    .\yii\helpers\Html::tag('div', \yii\helpers\Html::encode($model->cmsUser ? $model->cmsUser->shortDisplayName : '#'.$model->cms_user_id)),
                    ['class' => 'col-12 form-group']
                ),
            ],
            'comment',
            'is_root' => [
                'class' => BoolField::class,
                'allowNull' => false,
            ],
            'is_notify' => [
                'class' => BoolField::class,
                'allowNull' => false,
            ],
            'sort' => [
                'class' => NumberField::class,
            ],
        ];

        return $result;
    }

}
