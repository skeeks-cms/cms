<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 20.12.2017
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\components\Cms;
use skeeks\cms\models\forms\PasswordChangeFormV2;
use skeeks\sx\helpers\ResponseHelper;
use skeeks\yii2\form\Field;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * Class UpaPersonalController
 * @package skeeks\cms\controllers
 */
class UpaPersonalController extends BackendController
{
    public $defaultAction = 'view';

    public function init()
    {
        $this->name = ['skeeks/cms', 'Personal data'];

        $this->permissionNames = [
            Cms::UPA_PERMISSION => 'Доступ к персональной части',
        ];

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
        $actions = ArrayHelper::merge(parent::actions(), [
                'view' =>
                    [
                        'class'           => BackendAction::class,
                        'name'            => 'Профиль',
                        "icon"            => "fa fa-user",
                        "permissionNames" => [],
                        "callback"        => [$this, 'actionView'],
                        "priority"        => 10,
                        "isVisible"       => false,
                    ],

                'password' =>
                    [
                        'class'           => BackendAction::class,
                        'name'            => 'Смена пароля',
                        "icon"            => "glyphicon glyphicon-warning-sign",
                        "permissionNames" => [],
                        "callback"        => [$this, 'actionPassword'],
                        "priority"        => 10,
                        "isVisible"       => false,
                    ],

                "update" => [
                    'class'     => BackendAction::class,
                    'name'      => ['skeeks/cms', 'Personal data'],
                    "callback"  => [$this, 'actionUpdate'],
                    "isVisible" => false,
                ],
                /*
                "change-password" => [
                    'buttons'           => ['apply'],
                    'class'             => BackendModelUpdateAction::class,
                    'name'              => ['skeeks/cms', 'Change password'],
                    'icon'              => 'fa fa-key',
                    'defaultView'       => 'change-password',
                    'on initFormModels' => function (Event $e) {
                        $model = $e->sender->model;
                        $dm = new DynamicModel(['pass', 'pass2']);
                        $dm->addRule(['pass', 'pass2'], 'string', ['min' => 6]);
                        $dm->addRule(['pass', 'pass2'], 'required');
                        $dm->addRule(['pass', 'pass2'], function ($attribute) use ($dm) {
                            if ($dm->pass != $dm->pass2) {
                                $dm->addError($attribute, \Yii::t('skeeks/cms', 'New passwords do not match'));
                                return false;
                            }
                        });
                        $e->sender->formModels['dm'] = $dm;
                    },

                    'on beforeSave' => function (Event $e) {
                        /**
                         * @var $action BackendModelUpdateAction;
                         * @var $model CmsUser;
                        $action = $e->sender;
                        $model = $action->model;
                        $action->isSaveFormModels = false;
                        $dm = ArrayHelper::getValue($action->formModels, 'dm');

                        $model->setPassword($dm->pass);

                        if ($model->save()) {
                            //$action->afterSaveUrl = Url::to(['update', 'pk' => $newModel->id, 'content_id' => $newModel->content_id]);
                        } else {
                            throw new Exception(print_r($model->errors, true));
                        }

                    },

                    'fields' => [
                        'dm.pass'  => [
                            'class' => PasswordField::class,
                            'label' => ['skeeks/cms', 'New password'],
                        ],
                        'dm.pass2' => [
                            'class' => PasswordField::class,
                            'label' => ['skeeks/cms', 'New password (again)'],
                        ],
                    ],
                ],*/
            ]
        );


        foreach ($actions as $key => $action) {
            $actions[$key]['accessCallback'] = true;
        }

        return $actions;
    }


    public function actionView()
    {
        $user = \Yii::$app->user->identity;
        return $this->render($this->action->id, [
            'model' => $user,
        ]);
    }
    public function actionUpdate()
    {
        $rr = new ResponseHelper();
        $user = \Yii::$app->user->identity;

        /*if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($user);
        }*/

        if ($rr->isRequestAjaxPost) {
            if ($user->load(\Yii::$app->request->post()) && $user->save()) {
                $rr->message = '✓ Сохранено';
                $rr->success = true;
            } else {
                $rr->success = false;
                $rr->data = [
                    'validation' => ArrayHelper::merge(
                        ActiveForm::validate($user), []
                    ),
                ];
            }

            return $rr;
        }

        return $this->render($this->action->id, [
            'model' => $user,
        ]);
    }


    public function actionPassword()
    {
        $rr = new ResponseHelper();
        $model = \Yii::$app->user->identity;
        $formModel = new PasswordChangeFormV2();
        $formModel->user = $model;
        /*if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($formModel);
        }*/

        if ($rr->isRequestAjaxPost) {
            try {
                if ($formModel->load(\Yii::$app->request->post()) && $formModel->changePassword()) {
                    $rr->message = '✓ Пароль изменен';
                    $rr->success = true;
                } else {
                    $rr->success = false;
                    $rr->data = [
                        'validation' => ArrayHelper::merge(
                            ActiveForm::validate($formModel), []
                        ),
                    ];
                }
            } catch (\Exception $exception) {
                $rr->message = 'Пароль не изменен!' . $exception->getMessage();
                $rr->success = false;
            }


            return $rr;
        }

        return $this->render($this->action->id, ['model' => $formModel]);
    }


    public function updateFields()
    {
        return [
            'image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            /*'username',*/
            'last_name',
            'first_name',
            'patronymic',
            'email',
            'phone'    => [
                'elementOptions'  => [
                    'placeholder' => '+7 903 722-28-73',
                ],
                'on beforeRender' => function (Event $e) {
                    /**
                     * @var $field Field
                     */
                    $field = $e->sender;
                    \skeeks\cms\admin\assets\JqueryMaskInputAsset::register(\Yii::$app->view);
                    $id = \yii\helpers\Html::getInputId($field->model, $field->attribute);
                    \Yii::$app->view->registerJs(<<<JS
                        $("#{$id}").mask("+7 999 999-99-99");
JS
                    );
                },
            ],
        ];
    }
}