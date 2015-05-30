<?php
/**
 * AdminProfileController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\App;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\Search;
use skeeks\cms\models\UserGroup;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class AdminProfileController
 * @package skeeks\cms\controllers
 */
class AdminProfileController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->name                   = "Личный кабинет";
        $this->modelShowAttribute      = "username";
        $this->modelClassName          = User::className();
        parent::init();
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['delete']);
        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['social']);
        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['system']);
        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['files']);

        $behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['change-password'] = [
            "label"     => "Изменение пароля",
            "icon"     => "glyphicon glyphicon-cog",
            "rules" =>
            [
                [
                    "class" => HasModel::className()
                ]
            ]
        ];

        $behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['file-manager'] = [
            "label"     => "Личные файлы",
            "icon"      => "glyphicon glyphicon-folder-open",
            "rules"     =>
            [
                [
                    "class" => HasModel::className()
                ]
            ]
        ];


        return $behaviors;
    }




    /**
     * @return \common\models\User|null|\skeeks\cms\base\db\ActiveRecord|User|\yii\web\User
     */
    public function getCurrentModel()
    {
        if ($this->_currentModel === null)
        {
            $this->_currentModel = \Yii::$app->cms->getAuthUser();
        }

        return $this->_currentModel;
    }

    /**
     * @return mixed|\yii\web\Response
     */
    public function actionIndex()
    {
        return $this->redirect(UrlHelper::construct("cms/admin-profile/view"));
    }

    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionFileManager()
    {
        $model = $this->getCurrentModel();


        return $this->output(\Yii::$app->cms->moduleCms()->renderFile('admin-user/file-manager.php', [
            'model' => $model
        ]));

    }


    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionChangePassword()
    {
        $model = $this->getCurrentModel();

        $modelForm = new PasswordChangeForm([
            'user' => $model
        ]);

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $modelForm->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return \skeeks\cms\modules\admin\widgets\ActiveForm::validate($modelForm);
        }


        if ($modelForm->load(\Yii::$app->request->post()) && $modelForm->changePassword())
        {
            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
            return $this->redirect(['change-password', 'id' => $model->id]);
        } else
        {
            if (\Yii::$app->request->isPost)
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось изменить пароль');
            }

            return $this->output(\Yii::$app->cms->moduleCms()->renderFile('admin-user/_form-change-password.php', [
                'model' => $modelForm
            ]));

            /*return $this->render('_form-change-password', [
                'model' => $modelForm,
            ]);*/
        }
    }


}
