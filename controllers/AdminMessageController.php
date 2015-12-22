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

use skeeks\cms\App;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\forms\ViewFileEditModel;
use skeeks\cms\models\Search;
use skeeks\cms\models\search\SourceMessageSearch;
use skeeks\cms\models\SourceMessage;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelUpdateAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\modules\admin\widgets\DropdownControllerActions;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\validators\db\IsSame;
use skeeks\cms\validators\HasBehavior;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\Model;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Cookie;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminMessageController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление переводами";
        $this->modelShowAttribute      = "message";
        $this->modelClassName          = SourceMessage::className();

        parent::init();
    }


    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'index' =>
                [
                    'modelSearchClassName' => SourceMessageSearch::className(),
                ],
                
                "update" =>
                [
                    'callback' => [$this, 'update']
                ],

            ]
        );
    }

    public function update()
    {
        /**
         * @var $model SourceMessage
         */
        $model          = $this->model;
        $model->initMessages();

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            Model::loadMultiple($model->messages, \Yii::$app->getRequest()->post());
            return Model::validateMultiple($model->messages);

            //return $rr->ajaxValidateForm($model);
        }

        if ($rr->isRequestPjaxPost())
        {
            if (Model::loadMultiple($model->messages, \Yii::$app->getRequest()->post()) && Model::validateMultiple($model->messages))
            {
                $model->saveMessages();
                //\Yii::$app->getSession()->setFlash('success', \Yii::t('app','Saved'));

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {

                } else
                {
                    return $this->redirect(
                        $this->indexUrl
                    );
                }

            } else
            {
                //\Yii::$app->getSession()->setFlash('error', \Yii::t('app','Could not save'));
            }


        }

        return $this->render('_form', [
            'model' => $model
        ]);
    }
}
