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
use skeeks\cms\controllers\AdminSubscribeController;
use skeeks\cms\Exception;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\CanBeLinkedToModel;
use skeeks\cms\models\behaviors\HasAdultStatus;
use skeeks\cms\models\behaviors\HasComments;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMetaData;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasSeoPageUrl;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\HasSubscribes;
use skeeks\cms\models\behaviors\HasVotes;
use skeeks\cms\models\behaviors\HasPublications;

use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\models\Comment;
use skeeks\cms\models\Publication;
use skeeks\cms\models\Search;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\Subscribe;
use skeeks\cms\models\Vote;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModelBehaviors;
use skeeks\cms\modules\admin\controllers\helpers\rules\ModelHasBehaviors;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\Model;
use yii\base\View;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\filters\VerbFilter;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Response;
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

                /*"actions" =>
                [
                    'related-properties' =>
                    [
                        "label"     => "Дополнительные свойства",
                        'icon'      => 'glyphicon glyphicon-plus-sign',
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasRelatedProperties::className()
                            ]
                        ]
                    ],


                    'files' =>
                    [
                        "label"     => "Файлы",
                        "icon"     => "glyphicon glyphicon-cloud",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasFiles::className()
                            ]
                        ]
                    ],



                    'system' =>
                    [
                        "label"     => "Служебные данные",
                        'icon'      => 'glyphicon glyphicon-user',
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => [
                                    TimestampBehavior::className(),
                                    TimestampPublishedBehavior::className(),
                                    BlameableBehavior::className(),
                                ],
                                "useOr" => true
                            ]
                        ]
                    ],
                ]*/
        ]);
    }


    public function actionRelatedProperties()
    {
        return $this->output(\Yii::$app->cms->moduleAdmin()->renderFile("base-actions/related-properties.php", [
            "model"             => $this->getModel(),
        ]));
    }

    /**
     * @return array
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionSortablePriority()
    {
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;

            if ($keys = \Yii::$app->request->post('keys'))
            {
                $counter = count($keys);

                foreach ($keys as $key)
                {
                    $priority = $counter * 1000;
                    $model = $this->_findModel($key);
                    if ($model)
                    {
                        $model->priority = $priority;
                        $model->save(false);
                    }

                    $counter = $counter - 1;
                }
            }

            return [
                'success' => true,
                'message' => 'Изминения сохранены',
            ];
        }
    }


    /**
     * @return string|\yii\web\Response
     */
    public function actionFiles()
    {
        return $this->output(\Yii::$app->cms->moduleAdmin()->renderFile("base-actions/files.php", [
            "model"             => $this->getModel(),

        ]));
    }


    public function actionSystem()
    {
        /*$model = $this->getModel();

        if ($model->load(\Yii::$app->request->post()) && $model->save(false))
        {
            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
            return $this->redirectRefresh();
        } else
        {
            return $this->output(\Yii::$app->cms->moduleAdmin()->renderFile("base-actions/system.php", [
                "model" => $this->getModel()
            ]));
        }*/



        $model = $this->getModel();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(\Yii::$app->request->post()) && $model->save(false))
        {
            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
            if (!\Yii::$app->request->isAjax)
            {
                return $this->redirectRefresh();
            }

        } else
        {
            if (\Yii::$app->request->isPost)
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }
        }

        return $this->output(\Yii::$app->cms->moduleAdmin()->renderFile("base-actions/system.php", [
            "model" => $this->getModel()
        ]));
    }

}