<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.04.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\User;
use Yii;
use skeeks\cms\models\searchs\User as UserSearch;
use \skeeks\cms\App;

/**
 * Class UserController
 * @package skeeks\cms\controllers
 */
class ProfileController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
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
