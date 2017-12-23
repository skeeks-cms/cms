<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 20.12.2017
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelController;
use skeeks\cms\models\CmsUser;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\DynamicModel;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * Class UpaPersonalController
 * @package skeeks\cms\controllers
 */
class UpaPersonalController extends BackendModelController
{
    public $defaultAction = 'update';

    public function init()
    {
        $this->name = "Личные настройки";
        $this->modelClassName = \Yii::$app->user->identityClass;
        $this->modelShowAttribute = 'displayName';
        parent::init();
    }

    public function getModel()
    {
        return \Yii::$app->user->identity;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
                "update" => [
                    'class' => BackendModelUpdateAction::class,
                    'fields' => [
                        'image_id' => [
                            'class' => WidgetField::class,
                            'widgetClass' => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                            'widgetConfig' => [
                                'accept' => 'image/*',
                                'multiple' => false
                            ]
                        ],
                        'username',
                        'first_name',
                        'last_name',
                        'patronymic',
                        'email',
                        'phone',
                    ]
                ],
                "change-password" => [
                    'class' => BackendModelUpdateAction::class,
                    'name' => \Yii::t('skeeks/cms', 'Change password'),
                    'defaultView' => 'change-password',
                    'on initFormModels' => function(Event $e) {
                        $model = $e->sender->model;
                        $dm = new DynamicModel(['pass', 'pass2']);
                        $dm->addRule(['pass'], 'integer');
                        $e->sender->formModels['dm'] = $dm;
                    },
                    'fields' => [
                        'dm.pass' => [
                            'label' => 'test',
                            'hint' => 'test 1'
                        ],
                        'dm.pass2',
                    ]
                ],
            ]
        );
    }
}