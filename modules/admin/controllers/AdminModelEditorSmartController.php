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

use skeeks\cms\App;
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
use yii\base\View;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

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


    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionMetaData()
    {
        $model = $this->getModel();

        if ($model->load(\Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['meta-data', 'id' => $model->id]);
        } else
        {
            return $this->output(App::moduleAdmin()->renderFile("base-actions/meta-data.php", [
                "model" => $this->getModel()
            ]));
        }
    }
}