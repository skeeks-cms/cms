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
        $this->name = "";
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsCompany2user::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;

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
