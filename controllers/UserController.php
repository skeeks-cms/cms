<?php
/**
 * UserController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 23.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\User;
use Yii;
use skeeks\cms\models\searchs\User as UserSearch;
use \skeeks\cms\App;

/**
 * Class UserController
 * @package skeeks\cms\controllers
 */
class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }


    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionProfile()
    {
        if (\Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        return $this->redirect(\Yii::$app->cms->getAuthUser()->getPageUrl());
    }

    /**
     * @param $username
     * @return array
     * @throws \skeeks\cms\components\Exception
     */
    protected function _user($username)
    {
        $model      = null;
        $personal   = false;
        //Если пользователь авторизован
        if (\Yii::$app->cms->getAuthUser())
        {
            //Если это личный профиль
            if (\Yii::$app->cms->getAuthUser()->username == $username)
            {
                $model = \Yii::$app->cms->getAuthUser();
                $personal = true;
            }
        }

        if (!$model)
        {
            $model = \Yii::$app->cms->findUser()->where(["username" => $username])->one(); //(["username" => $username]);
        }


        return [
            'model'         => $model,
            'personal'      => $personal,
        ];
    }

    public function actionPublications($username)
    {
        $data = $this->_user($username);
        return $this->render('publications', $data);
    }

    public function actionEdit($username)
    {
        $data = $this->_user($username);
        return $this->render('edit', $data);
    }

    /**
     * @param $username
     * @return string
     */
    public function actionView($username)
    {
        $data = $this->_user($username);
        return $this->render('view', $data);
    }

    /**
     * @param $username
     * @return string
     */
    public function actionChangePassword($username)
    {
        $data = $this->_user($username);

        $modelForm = new PasswordChangeForm([
            'user' => $data['model']
        ]);

        $rr = new RequestResponse();

        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($modelForm);
        }

        if ($rr->isRequestAjaxPost())
        {
            if ($modelForm->load(\Yii::$app->request->post()) && $modelForm->changePassword())
            {
                $rr->success = true;
                $rr->message = 'Пароль успешно изменен';
            } else
            {
                $rr->message = 'Не удалось изменить пароль';
            }

            return $rr;
        }

        return $this->render($this->action->id, $data);
    }


    /**
     * @param $username
     * @return string
     */
    public function actionEditInfo($username)
    {
        $data = $this->_user($username);
        $model = $data['model'];

        $rr = new RequestResponse();

        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($model);
        }

        if ($rr->isRequestAjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()) && $model->save())
            {
                $rr->success = true;
                $rr->message = 'Данные успешно сохранены';
            } else
            {
                $rr->message = 'Не получилось сохранить данные';
            }

            return $rr;
        }

        return $this->render($this->action->id, $data);
    }

}
