<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\ckeditor\CKEditorWidget;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;

/**
 * Class AdminUserEmailController
 * @package skeeks\cms\controllers
 */
class AdminCmsLogController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление логами";
        $this->modelShowAttribute = "logTypeAsText";
        $this->modelClassName = CmsLog::class;

        $this->permissionName = CmsManager::PERMISSION_ADMIN_ACCESS;
        $this->generateAccessActions = false;

        parent::init();
    }
    
    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            'index'  => [
                'class' => ViewBackendAction::class,
                'permissionName' => CmsManager::PERMISSION_ROLE_ADMIN_ACCESS,
                'accessCallback' => function() {
                    return \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
                }
            ],

            "create" => new UnsetArrayValue(),

            "update" => [
                'fields' => [$this, 'updateFields'],
                /*'size'           => BackendAction::SIZE_SMALL,*/
                'buttons'        => ['save'],
                "accessCallback" => function ($model) {
                    if ($this->model) {
                        return \Yii::$app->user->can("cms/admin-cms-log/update-delete", ['model' => $this->model]);
                    }
                    return false;
                },
            ],

            "delete" => [
                "accessCallback" => function ($model) {
                    if ($this->model) {
                        return \Yii::$app->user->can("cms/admin-cms-log/update-delete", ['model' => $this->model]);
                    }
                    return false;
                },
            ],

            'add-comment' => [
                'class' => BackendAction::class,
                'isVisible' => false,
                'callback' => [$this, 'addComment'],
                'accessCallback' => function() {
                    return \Yii::$app->user->can(CmsManager::PERMISSION_ADMIN_ACCESS);
                }
            ]
        ]);

        return $actions;
    }
    
    

    public function addComment()
    {
        $rr = new RequestResponse();

        try {

            if ($rr->isRequestAjaxPost()) {

                $log = new CmsLog();

                $log->log_type = CmsLog::LOG_TYPE_COMMENT;
                
                if ($log->load(\Yii::$app->request->post()) && $log->save()) {

                } else {
                    $errors = $log->getFirstErrors();
                    $message = '';
                    if ($errors) {
                        $error = array_shift($errors);
                        $message = $error;
                    }
                    throw new Exception($message);
                }

                $rr->message = "Сохранено";
                $rr->success = true;
            }

        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }


        return $rr;
    }
    
    
    public function updateFields($action)
    {
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $result = [
            'comment' => [
                'class' => WidgetField::class,
                'widgetClass' => CKEditorWidget::class
            ],
            'fileIds' => [
                'class' => WidgetField::class,
                'widgetClass' => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'multiple' => true
                ],
            ]
        ];

        return $result;
    }

}
