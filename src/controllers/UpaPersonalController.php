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
use skeeks\yii2\form\fields\PasswordField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\DynamicModel;
use yii\base\Event;
use yii\base\Model;
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
        $this->name = ['skeeks/cms', 'Personal data'];
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
                    'name' => ['skeeks/cms', 'Personal data'],
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
                    'name' => ['skeeks/cms', 'Change password'],
                    'icon' => 'fa fa-key',
                    'defaultView' => 'change-password',
                    'on initFormModels' => function(Event $e) {
                        $model = $e->sender->model;
                        $dm = new DynamicModel(['pass', 'pass2']);
                        $dm->addRule(['pass', 'pass2'], 'string', ['min' => 6]);
                        $dm->addRule(['pass', 'pass2'], 'required');
                        $dm->addRule(['pass', 'pass2'], function($attribute) use ($dm) {
                            if ($dm->pass != $dm->pass2) {
                                $dm->addError($attribute, \Yii::t('skeeks/cms', 'New passwords do not match'));
                                return false;
                            }
                        });
                        $e->sender->formModels['dm'] = $dm;
                    },
                    'fields' => [
                        'dm.pass' => [
                            'class' => PasswordField::class,
                            'label' => ['skeeks/cms', 'New password'],
                        ],
                        'dm.pass2' =>  [
                            'class' => PasswordField::class,
                            'label' => ['skeeks/cms', 'New password (again)'],
                        ],
                    ]
                ],
            ]
        );
    }
}