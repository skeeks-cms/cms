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
use skeeks\cms\models\behaviors\HasComments;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMetaData;
use skeeks\cms\models\behaviors\HasSeoPageUrl;
use skeeks\cms\models\behaviors\HasSubscribes;
use skeeks\cms\models\behaviors\HasVotes;

use skeeks\cms\models\Comment;
use skeeks\cms\models\Search;
use skeeks\cms\models\Subscribe;
use skeeks\cms\models\Vote;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModelBehaviors;
use skeeks\cms\modules\admin\controllers\helpers\rules\ModelHasBehaviors;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\View;
use yii\grid\GridView;
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
                    'descriptions' =>
                    [
                        "label"     => "Описание",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasDescriptionsBehavior::className()
                            ]
                        ]
                    ],

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
                        "label"     => "Подписаны",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasSubscribes::className()
                            ]
                        ]
                    ],

                    'seo-page-url' =>
                    [
                        "label"     => "Адрес на сайте",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasSeoPageUrl::className()
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
        $allowFields = [];

        if ($behaviors = $this->getModel()->getBehaviors())
        {
            foreach ($behaviors as $behavior)
            {
                if ($behavior instanceof HasFiles)
                {
                    $allowFields = array_merge($allowFields, array_keys($behavior->fields));
                }
            }

        }

        return $this->output(App::moduleAdmin()->renderFile("base-actions/files.php", [
            "model"         => $this->getModel(),
            "allowFields"   => $allowFields
        ]));
    }


    public function actionComments()
    {
        $search = new Search(Comment::className());
        $dataProvider   = $search->search(\Yii::$app->request->queryParams);
        $searchModel    = $search->getLoadedModel();

        $dataProvider->query->andWhere($this->getCurrentModel()->getRef()->toArray());

        $controller = App::moduleCms()->createControllerByID("admin-comment");

        return $this->output(App::moduleCms()->renderFile("admin-comment/index.php", [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $controller,
        ]));
    }

    public function actionVotes()
    {
        $search = new Search(Vote::className());
        $dataProvider   = $search->search(\Yii::$app->request->queryParams);
        $searchModel    = $search->getLoadedModel();

        $dataProvider->query->andWhere($this->getCurrentModel()->getRef()->toArray());

        $controller = App::moduleCms()->createControllerByID("admin-vote");

        return $this->output(App::moduleCms()->renderFile("admin-vote/index.php", [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $controller,
        ]));
    }

    public function actionSubscribes()
    {
        $search = new Search(Subscribe::className());
        $dataProvider   = $search->search(\Yii::$app->request->queryParams);
        $searchModel    = $search->getLoadedModel();

        $dataProvider->query->andWhere($this->getCurrentModel()->getRef()->toArray());

        $controller = App::moduleCms()->createControllerByID("admin-subscribe");

        return $this->output(App::moduleCms()->renderFile("admin-subscribe/index.php", [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $controller,
        ]));
    }

    public function actionSeoPageUrl()
    {
        $model = $this->getModel();

        if ($model->load(\Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['seo-page-url', 'id' => $model->id]);
        } else
        {
            return $this->output(App::moduleAdmin()->renderFile("base-actions/seo-page-url.php", [
                "model" => $this->getModel()
            ]));
        }
    }

    /**
     * @return string|\yii\web\Response
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

    /**
     * @return string|\yii\web\Response
     */
    public function actionDescriptions()
    {
        $model = $this->getModel();

        if ($model->load(\Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['descriptions', 'id' => $model->id]);
        } else
        {
            return $this->output(App::moduleAdmin()->renderFile("base-actions/descriptions.php", [
                "model" => $this->getModel()
            ]));
        }
    }
}