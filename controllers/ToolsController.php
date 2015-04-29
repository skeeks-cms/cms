<?php
/**
 * Вспомогательные иструменты
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\sx\models\Ref;
use Yii;
use yii\web\Response;


/**
 * Class ToolsController
 * @package skeeks\cms\controllers
 */
class ToolsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }


    /**
     * Выбор файла
     * @return string
     */
    public function actionSelectFile()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/main.php';
        \Yii::$app->cmsToolbar->enabled = 0;

        $model = null;
        if (\Yii::$app->request->get('linked_to_model') && \Yii::$app->request->get('linked_to_value') )
        {
            $className = \Yii::$app->registeredModels->getClassNameByCode(\Yii::$app->request->get('linked_to_model'));
            $ref = new Ref($className, \Yii::$app->request->get('linked_to_value'));
            $model = $ref->findModel();
        }


        return $this->render($this->action->id, [
            'model' => $model
        ]);
    }

}
