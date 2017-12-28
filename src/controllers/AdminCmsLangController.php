<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsLang;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsLangController
 * @package skeeks\cms\controllers
 */
class AdminCmsLangController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Management of languages');
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsLang::class;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $fields = [
            'image_id' => [
                'class' => WidgetField::class,
                'widgetClass' => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept' => 'image/*',
                    'multiple' => false
                ]
            ],
            'code',
            'active' => [
                'class' => BoolField::class,
                'trueValue' => "Y",
                'falseValue' => "N",
            ],
            'name',
            'description',
            'priority',
        ];
        $actions = ArrayHelper::merge(parent::actions(), [
                "activate-multi" => [
                    'class' => AdminMultiModelEditAction::className(),
                    "name" => \Yii::t('skeeks/cms', 'Activate'),
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" => [
                    'class' => AdminMultiModelEditAction::className(),
                    "name" => \Yii::t('skeeks/cms', 'Deactivate'),
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiInActivate'],
                ],
                'update' => [
                    'fields' => $fields
                ],
                'create' => [
                    'fields' => $fields
                ]
            ]
        );

        return $actions;
    }
}
