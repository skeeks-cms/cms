<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\controllers\BackendModelController;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\SelectModelDialogTreeWidget;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsSite;
use skeeks\cms\shop\models\ShopContent;
use skeeks\cms\shop\models\ShopSite;
use skeeks\cms\widgets\formInputs\ckeditor\Ckeditor;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Exception;
use yii\web\Application;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteInfoController extends BackendModelController
{
    public function init()
    {
        $this->name = "Настройки сайта";
        $this->modelShowAttribute = false;
        $this->modelClassName = CmsSite::class;

        $this->defaultAction = "update";
        $this->generateAccessActions = false;

        /*$this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can($this->uniqueId);
        };*/

        parent::init();
    }
    
    /**
     * @return Model|ActiveRecord
     */
    public function getModel()
    {
        return \Yii::$app->skeeks->site;
    }
    
    public function actions()
    {
        return [
            'update' => [
                'class' => BackendModelUpdateAction::class,
                'fields' => [$this, 'updateFields'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function updateFields()
    {
        return [
            'name',
            'image_id'    => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'favicon_storage_file_id'    => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'work_time'    => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\yii2\scheduleInputWidget\ScheduleInputWidget::class,
            ],
        ];
    }

}
