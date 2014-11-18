<?php
/**
 * AdminStaticBlockController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\App;
use skeeks\cms\models\Infoblock;
use skeeks\cms\models\Search;
use skeeks\cms\models\StaticBlock;
use skeeks\cms\models\UserGroup;
use skeeks\cms\models\WidgetConfig;
use skeeks\cms\models\WidgetSettings;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\widgets\text\Text;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\ArrayHelper;

/**
 *
 * @method Infoblock getCurrentModel()
 *
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminStaticBlockController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление статическим блоками";
        $this->_modelShowAttribute      = "code";
        $this->_modelClassName          = StaticBlock::className();
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

                ]
            ]
        ]);
    }

    /**
     * Creates a new Game model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        /**
         * @var $model StaticBlock
         */
        $modelClass = $this->_modelClassName;
        $model      = new $modelClass();

        if ($model->load(\Yii::$app->request->post()))
        {
            $model->save(false);

            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
            return $this->redirect(['view', 'id' => $model->id]);
        } else
        {
            if (\Yii::$app->request->isPost)
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }

            return $this->render('_form', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionUpdate()
    {
        /**
         * @var $model StaticBlock
         */
        $model = $this->getCurrentModel();
        $oldValue = $model->value;

        if ($model->load(\Yii::$app->request->post()))
        {
            $newValue = $model->value;
            $model->value = ArrayHelper::merge($oldValue, $model->value);

            $model->save(false);

            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
            return $this->redirect(['update', 'id' => $model->id]);
        }
        else
        {
            if (\Yii::$app->request->isPost)
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }

            return $this->render('_form', [
                'model' => $model,
            ]);
        }
    }
}
