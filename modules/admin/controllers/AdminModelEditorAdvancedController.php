<?php
/**
 * AdminModelEditorAdvancedController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
/**
 * Class AdminModelEditorAdvancedController
 * @package skeeks\cms\modules\admin\controllers
 */
abstract class AdminModelEditorAdvancedController extends AdminModelEditorController
{
    /**
     *
     */
    public function init()
    {
        parent::init();

        $this
            ->_registerAction("files", [
                "label" => "Управление файлами",
                "type" => self::ACTION_TYPE_MODEL
            ])
        ;
    }

    /**
     * Действия для управления моделью
     * @return array
     */
    public function getModelActions()
    {
        $result = [];

        $result = parent::getModelActions();

        return $result;
    }


    /**
     * @return string|\yii\web\Response
     */
    public function actionFiles()
    {
        $model = $this->getCurrentModel();

        if ($model->load(\Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['view', 'id' => $model->id]);
        } else
        {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
}