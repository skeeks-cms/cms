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

use skeeks\cms\models\User;
use Yii;
use yii\web\Controller;
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

    /**
     * @param $username
     * @return string
     */
    public function actionView($username)
    {
        $model      = null;
        $personal   = false;
        //Если пользователь авторизован
        if (App::user())
        {
            //Если это личный профиль
            if (App::user()->username == $username)
            {
                $model = App::user();
                $personal = true;
            }
        }

        if (!$model)
        {
            $model = App::findUser()->where(["username" => $username])->one(); //(["username" => $username]);
        }


        return $this->render('view', [
            'model'         => $model,
            'personal'      => $personal,
        ]);
    }

}
