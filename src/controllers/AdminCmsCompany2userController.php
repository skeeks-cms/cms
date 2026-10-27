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
                //The company and the person of the link are not changed here (the check in getModel() is about them).
                'on beforeValidate' => function (\yii\base\Event $event) {
                    $model = $event->sender->model;
                    $model->cms_company_id = $model->getOldAttribute('cms_company_id');
                    $model->cms_user_id = $model->getOldAttribute('cms_user_id');
                },
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

    /**
     * The link gives the person access to the company's services (is_root = full
     * access): it is shown, changed and removed only by who manages the company.
     */
    public function getModel()
    {
        $model = parent::getModel();
        if ($model && !$model->isNewRecord) {
            $company = $model->cmsCompany;
            $visible = $company && \skeeks\cms\models\CmsCompany::find()->forManager()
                ->andWhere([\skeeks\cms\models\CmsCompany::tableName().'.id' => (int)$company->id])->exists();
            if (!$visible || !\Yii::$app->user->can('cms/admin-company/manage', ['model' => $company])) {
                throw new \yii\web\ForbiddenHttpException('Нет доступа к этой компании.');
            }
        }
        return $model;
    }

    public function updateFields($action)
    {
        $model = $action->model;
        $model->load(\Yii::$app->request->get());
        //The form names the person of the saved link, not one from the request.
        if (!$model->isNewRecord) {
            $model->cms_company_id = $model->getOldAttribute('cms_company_id');
            $model->cms_user_id = $model->getOldAttribute('cms_user_id');
        }

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
