<?php
/**
 * AdminTreeController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\forms\ViewFileEditModel;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\admin\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\UserLastActivityWidget;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\formInputs\selectTree\SelectTree;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\JsExpression;
use yii\web\Response;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminToolsController extends AdminController
{
    /**
     * Проверка доступа к админке
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(),
            [
                //Проверка доступа к админ панели
                'adminViewEditAccess' =>
                    [
                        'class' => AdminAccessControl::className(),
                        'only' => ['view-file-edit'],
                        'rules' =>
                            [
                                [
                                    'allow' => true,
                                    'roles' =>
                                        [
                                            CmsManager::PERMISSION_EDIT_VIEW_FILES
                                        ],
                                ],
                            ]
                    ],
            ]);
    }

    public function init()
    {
        $this->name = "Управление шаблоном";
        parent::init();
    }

    /**
     * The name of the privilege of access to this controller
     * @return string
     */
    public function getPermissionName()
    {
        return '';
    }

    public function actionViewFileEdit()
    {
        $rootViewFile = \Yii::$app->request->get('root-file');

        $model = new ViewFileEditModel([
            'rootViewFile' => $rootViewFile
        ]);

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            if ($model->load(\Yii::$app->request->post())) {
                if (!$model->saveFile()) {
                    $rr->success = false;
                    $rr->message = "Не удалось сохранить файл.";
                }

                $rr->message = "Сохранено";
                $rr->success = true;
            }

            return $rr;
        }

        return $this->render($this->action->id, [
            'model' => $model
        ]);
    }


    /**
     * Выбор файла
     * @return string
     */
    public function actionSelectFile()
    {
        //$this->layout = '@skeeks/cms/modules/admin/views/layouts/main.php';
        \Yii::$app->cmsToolbar->enabled = 0;

        $model = null;
        $className = \Yii::$app->request->get('className');
        $pk = \Yii::$app->request->get('pk');

        if ($className && $pk) {
            if ($model = $className::findOne($pk)) {

            }
        }


        return $this->render($this->action->id, [
            'model' => $model
        ]);
    }


    /**
     * Данные о текущем пользователе
     * @return RequestResponse
     */
    public function actionGetUser()
    {
        $rr = new RequestResponse();

        $rr->data = [
            'identity' => \Yii::$app->user->identity,
            'user' => \Yii::$app->user,
        ];

        return $rr;
    }

    /**
     * Данные о текущем пользователе
     * @return RequestResponse
     */
    public function actionAdminLastActivity()
    {
        $rr = new RequestResponse();

        if (!\Yii::$app->user->isGuest) {
            $rr->data = (new UserLastActivityWidget())->getOptions();
        } else {
            $rr->data = [
                'isGuest' => true
            ];
        }


        return $rr;
    }


    /**
     * @return string
     */
    protected function _getMode()
    {
        if ($mode = \Yii::$app->request->getQueryParam('mode')) {
            return (string)$mode;
        }

        return '';
    }

    /**
     * @param $model
     *
     * @return string
     */
    public function renderNodeControll($model)
    {
        if ($this->_getMode() == SelectTree::MOD_MULTI) {
            $controllElement = Html::checkbox('tree_id', false, [
                'value' => $model->id,
                'class' => 'sx-checkbox',
                'style' => 'float: left; margin-left: 5px; margin-right: 5px;',
                'onclick' => new JsExpression(<<<JS
    sx.Tree.select("{$model->id}");
JS
                )
            ]);


        } else {
            if ($this->_getMode() == SelectTree::MOD_SINGLE) {

                $controllElement = Html::radio('tree_id', false, [
                    'value' => $model->id,
                    'class' => 'sx-readio',
                    'style' => 'float: left; margin-left: 5px; margin-right: 5px;',
                    'onclick' => new JsExpression(<<<JS
    sx.Tree.selectSingle("{$model->id}");
JS
                    )
                ]);

            } else {
                if ($this->_getMode() == SelectTree::MOD_COMBO) {

                    $controllElement = Html::radio('tree_id', false, [
                        'value' => $model->id,
                        'class' => 'sx-readio',
                        'style' => 'float: left; margin-left: 5px; margin-right: 5px;',
                        'onclick' => new JsExpression(<<<JS
                    sx.Tree.selectSingle("{$model->id}");
JS
                        )
                    ]);

                    $controllElement .= Html::checkbox('tree_id', false, [
                        'value' => $model->id,
                        'class' => 'sx-checkbox',
                        'style' => 'float: left; margin-left: 5px; margin-right: 5px;',
                        'onclick' => new JsExpression(<<<JS
    sx.Tree.select("{$model->id}");
JS
                        )
                    ]);

                } else {
                    $controllElement = '';
                }
            }
        }

        return $controllElement;
    }

    public function actionTree()
    {
        return $this->render($this->action->id, [
            'models' => CmsTree::findRoots()->joinWith('cmsSiteRelation')->orderBy([CmsSite::tableName() . ".priority" => SORT_ASC])->all()
        ]);
    }
}
