<?php
/**
 * AdminModelEditorSmartController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\Exception;
use skeeks\cms\models\behaviors\HasComments;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMetaData;
use skeeks\cms\models\behaviors\HasSubscribes;
use skeeks\cms\models\behaviors\HasVotes;

use skeeks\cms\modules\admin\controllers\helpers\rules\HasModelBehaviors;
use skeeks\cms\modules\admin\controllers\helpers\rules\ModelHasBehaviors;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;

/**
 * Class AdminModelEditorAdvancedController
 * @package skeeks\cms\modules\admin\controllers
 */
abstract class AdminModelEditorSmartController extends AdminModelEditorController
{

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
                    'files' =>
                    [
                        "label"     => "Файлы",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasFiles::className()
                            ]
                        ]
                    ],


                    'comments' =>
                    [
                        "label"     => "Комментарии",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasComments::className()
                            ]
                        ]
                    ],

                    'votes' =>
                    [
                        "label"     => "Голоса",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasVotes::className()
                            ]
                        ]
                    ],


                    'subscribes' =>
                    [
                        "label"     => "Голоса",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasSubscribes::className()
                            ]
                        ]
                    ],


                    'meta-data' =>
                    [
                        "label"     => "Мета данные",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasMetaData::className()
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }



    /**
     * @param $dataAction
     * @throws Exception
     */
    protected function _actionIsAllow($dataAction)
    {
        //Если нужно учитывать наличие поведения у сущьности
        if (!isset($dataAction["behaviors"]))
        {
            return true;
        }

        $model = $this->getCurrentModel();
        $behaviorsNeed = $dataAction["behaviors"];

        if (is_array($behaviorsNeed))
        {
            return $this->_checkBehaviorsForModel($model, $behaviorsNeed);

        } else if (is_string($behaviorsNeed))
        {
            return $this->_checkBehaviorsForModel($model, [$behaviorsNeed]);
        } else
        {
            throw new Exception();
        }

    }
    /**
     *
     * TODO: нужно выносить часто нужно знать
     *
     * Проверка наличия поведений у модели
     * Если хотя бы одного нету будет false
     *
     * TODO: думаю нужно делать по типу механимзма валидаций skeeks\sx\Validator
     *
     * @param ActiveRecord $model
     * @param array $behaviors
     * @return bool
     */
    protected function _checkBehaviorsForModel(ActiveRecord $model, array $behaviors = [])
    {

        foreach ($behaviors as $behaviorNeed)
        {
            if (!$this->_checkBehaviorForModel($model, $behaviorNeed))
            {
                return false;
            }
        }


        return true;
    }


    /**
     * TODO: нужно выносить часто нужно знать
     *
     * Проверка есть ли у модели поведение
     *
     * @param ActiveRecord $model
     * @param Behavior $behaviorNeed
     * @return bool
     */
    protected function _checkBehaviorForModel(ActiveRecord $model, $behaviorNeed)
    {
        foreach ($model->getBehaviors() as $behavior)
        {
            if ($behavior instanceof $behaviorNeed)
            {
                return true;
            }
        }

        return false;
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