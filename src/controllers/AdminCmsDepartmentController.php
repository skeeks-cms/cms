<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsUser;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\UnsetArrayValue;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsDepartmentController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Структура компании");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsDepartment::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'class'     => AdminAction::class,
                'name'      => "Структура компании",
                'callback'  => [$this, 'index'],
                'isVisible' => false,
            ],

            /*'list'  => [
                'isVisible'       => false,
                "filters" => false,
                "backendShowings" => false,
                'on beforeRender' => function (Event $event) {
                    if ($pid = \Yii::$app->request->get("pid")) {
                        if ($model = CmsDepartment::find()->where(['id' => $pid])->one()) {
                            $event->content = Html::tag("h2", $model->fullName);
                        }

                    }
                },
                'grid'    => [
                    'defaultOrder'   => [
                        'sort' => SORT_ASC,
                    ],
                    'on init' => function (Event $event) {
                        if (!CmsDepartment::find()->one()) {
                            //Создать первый отдел
                            $cmsDepartment = new CmsDepartment();
                            $cmsDepartment->makeRoot();
                            $cmsDepartment->name = 'Руководство компании';
                            $cmsDepartment->save();
                        }
                    },
                    'visibleColumns' => [
                        'actions',
                        'name',
                        'worker_id',
                        'sort',
                    ],
                    'columns'        => [
                        'name' => [
                            'class' => DefaultActionColumn::class
                        ],
                        'custom'     => [
                            'attribute' => 'name',
                            'format'    => 'raw',
                            'value'     => function (CmsCountry $model) {

                                $data = [];
                                $data[] = Html::a($model->asText, "#", ['class' => 'sx-trigger-action']);

                                $info = implode("<br />", $data);

                                return "<div class='row no-gutters'>
                                            <div class='sx-trigger-action' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none; border-bottom: 0;'>
                                                    <img src='".($model->flag ? $model->flag->src : Image::getCapSrc())."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a>
                                            </div>
                                            <div style='margin: auto 5px;'>".$info."</div>
                                        </div>";;
                            },
                        ],

                    ],
                ],
            ],*/

            "create" => new UnsetArrayValue(),
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],

            'delete-multi' => new UnsetArrayValue(),

        ]);
    }

    public function index()
    {
        if ($root_id = \Yii::$app->request->get('root_id')) {
            $query = CmsDepartment::find()->where([CmsDepartment::tableName().'.id' => $root_id]);
        } else {
            $query = CmsDepartment::find()->andWhere(['pid' => null]);
        }

        $models = $query
            ->orderBy([CmsDepartment::tableName().".sort" => SORT_ASC])
            ->all();

        return $this->render($this->action->id, ['models' => $models]);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsDepartment
         */
        $model = $action->model;
        $model_id = null;
        if ($model->isNewRecord) {
            $model_id = $model->id;
        }

        return [
            'name',
            /*'pid'  => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass'  => CmsDepartment::class,
                    'searchQuery' => function ($word = '') use ($model_id) {
                        $query = CmsDepartment::find();
                        if ($model_id) {
                            $query->andWhere([
                                '!=',
                                'pid',
                                $model_id,
                            ]);
                        }
                        if ($word) {
                            if ($word) {
                                $query->search($word);
                            }
                        }
                        return $query;
                    },
                ],
            ],*/
            'worker_id'  => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass'  => CmsUser::class,
                    'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->isWorker();
                        if ($word) {
                            if ($word) {
                                $query->search($word);
                            }
                        }
                        return $query;
                    },
                ],
            ],
            'workers'  => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass'  => CmsUser::class,
                    'multiple'  => true,
                    'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->isWorker();
                        if ($word) {
                            if ($word) {
                                $query->search($word);
                            }
                        }
                        return $query;
                    },
                ],
            ],
            /*'sort' => [
                'class' => NumberField::class,
            ],*/
        ];
    }


    public function actionNewChildren()
    {
        /**
         * @var CmsDepartment $parent
         */
        $parent = $this->model;

        if (\Yii::$app->request->isPost) {
            $post = \Yii::$app->request->post();


            $childTree = new CmsDepartment();
            $parent = CmsDepartment::find()->where(['id' => $post["pid"]])->one();

            $childTree->load($post);

            if (!$childTree->sort) {
                $childTree->sort = 100;

                //Элемент с большим приоритетом
                if ($treeChildrens = $parent->getChildren()->orderBy(['sort' => SORT_DESC])->one()) {
                    $childTree->sort = $treeChildrens->sort + 100;
                }
            }


            $response = ['success' => false];

            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            try {
                if ($parent && $childTree->appendTo($parent)->save()) {

                    $response['success'] = true;
                } else {
                    throw new Exception(print_r($childTree->errors, true));
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }


    /**
     * Пересортирует элементы дерева при перетаскивании
     */
    //TODO от swapPriorities нет пользы, когда приоритеты нод равны (закомментировання часть)
    //TODO нужно сделать так, чтобы при равных приортетах менялись приоритеты
    //TODO пока что циклом меняем приоритеты всех нод
    public function actionResort()
    {
        $response = [
            'success' => false,
        ];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (\Yii::$app->request->isPost) {
            $tree = new CmsDepartment();

            $post = \Yii::$app->request->post();

            //$ids = array_reverse(array_filter($post['ids']));
            $ids = array_filter($post['ids']);

            $priority = 100;

            foreach ($ids as $id) {
                $node = $tree->find()->where(['id' => $id])->one();
                $node->sort = $priority;
                $node->save(false);
                $priority += 100;
            }

            $response['success'] = true;
        }


        return $response;
    }
}
