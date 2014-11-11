<?php
/**
 * AdminUserController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\widgets\ActiveForm;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminUserController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление пользователями";

        $this->_modelShowAttribute      = "username";

        $this->_modelClassName          = User::className();
        $this->_modelSearchClassName    = UserSearch::className();

        parent::init();

    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            self::BEHAVIOR_ACTION_MANAGER =>
            [
                "actions" =>
                [
                    'change-password' =>
                    [
                        "label" => "Изменение пароля",
                        "rules" =>
                        [
                            [
                                "class" => HasModel::className()
                            ]
                        ]
                    ],
                ]
            ]
        ]);
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

            return $this->render('_form-change-password', [
                'model' => $modelForm,
            ]);
        }
    }
}
