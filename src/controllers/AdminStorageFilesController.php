<?php
/**
 * AdminStorageFilesController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 25.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\UserColumnData;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\Comment;
use skeeks\cms\models\Publication;
use skeeks\cms\models\StorageFile;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModelBehaviors;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\widgets\GridView;
use Yii;
use yii\base\Event;
use yii\base\WidgetEvent;
use yii\bootstrap\Alert;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;
use yii\web\Response;

/**
 * Class AdminStorageFilesController
 * @package skeeks\cms\controllers
 */
class AdminStorageFilesController extends BackendModelStandartController
{
    public $enableCsrfValidation = false;

    public function init()
    {
        $this->name = "Файловое хранилище";
        $this->modelClassName = StorageFile::class;

        $this->permissionName = 'cms/admin-storage-files';
        $this->generateAccessActions = true;

        parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'upload' => ['post'],
                    //'remote-upload'  => ['post'],
                    /*'link-to-model'  => ['post'],
                    'link-to-models' => ['post'],*/
                ],
            ],
        ]);
    }


    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'upload' => [
                    'generateAccess' => false,
                    'class'          => BackendAction::class,
                    'name'           => "Загрузить файлы",
                    'isVisible'      => false,
                    'priority'       => 10,
                    'callback'       => [$this, 'actionUpload'],
                ],

                'index' => [
                    'generateAccess'  => false,
                    'accessCallback'  => function () {
                        return (\Yii::$app->user->can("cms/admin-storage-files/index") || \Yii::$app->user->can("cms/admin-storage-files/index/own"));
                    },
                    'on beforeRender' => function (Event $event) {

                        if ($CKEditorFuncNum = \Yii::$app->request->get("CKEditorFuncNum")) {
                            \Yii::$app->view->registerJs(<<<JS
                            if (window.opener) {
                                if (window.opener.CKEDITOR) {
                                    sx.EventManager.on("submitElement", function(e, data) {
                                        window.opener.CKEDITOR.tools.callFunction({$CKEditorFuncNum}, data.basenamesrc);
                                        window.close();
                                    });
                                }
                            }
JS
                            );
                        }

                        $event->content = \skeeks\cms\widgets\StorageFileManager::widget([
                                'clientOptions' => [
                                    'completeUploadFile' => new \yii\web\JsExpression(<<<JS
        function(data)
        {
            window.location.reload();
        }
JS
                                    ),
                                ],
                            ])."<br />";
                    },
                    'on init'         => function ($e) {
                        $action = $e->sender;

                    },
                    "filters"         => [
                        'visibleFilters' => [
                            'q',
                            //'id',
                        ],
                        'autoFilters'    => [
                            'id',
                        ],
                        'filtersModel'   => [
                            'fields' => [
                                'q' => [
                                    'label'          => 'Поиск',
                                    'elementOptions' => [
                                        'placeholder' => 'Поиск (название, описание)',
                                    ],
                                    'on apply'       => function (QueryFiltersEvent $e) {
                                        /**
                                         * @var $query ActiveQuery
                                         */
                                        $query = $e->dataProvider->query;

                                        if ($e->field->value) {
                                            $query->andWhere([
                                                'or',
                                                ['like', CmsStorageFile::tableName().'.id', $e->field->value],
                                                ['like', CmsStorageFile::tableName().'.name', $e->field->value],
                                                ['like', CmsStorageFile::tableName().'.cluster_file', $e->field->value],
                                                ['like', CmsStorageFile::tableName().'.original_name', $e->field->value],
                                                ['like', CmsStorageFile::tableName().'.name_to_save', $e->field->value],
                                                ['like', CmsStorageFile::tableName().'.description_short', $e->field->value],
                                                ['like', CmsStorageFile::tableName().'.description_full', $e->field->value],
                                            ]);
                                        }
                                    },
                                ],
                            ],
                        ],
                    ],
                    'grid'            => [
                        'on init'        => function (Event $event) {

                            if (!\Yii::$app->user->can("cms/admin-storage-files/index") && \Yii::$app->user->can("cms/admin-storage-files/index/own")) {
                                $query = $event->sender->dataProvider->query;
                                $query->andWhere(['created_by' => \Yii::$app->user->identity->id]);
                            }


                            /**
                             * @var $dataProvider ActiveDataProvider
                             * @var $query ActiveQuery
                             */
                            $query = $event->sender->dataProvider->query;

                            $query->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);

                            /*/**
                             * @var $query ActiveQuery
                            $query = $event->sender->dataProvider->query;
                            if ($this->content) {
                                $query->andWhere(['content_id' => $this->content->id]);
                            }*/
                        },
                        'defaultOrder'   => [
                            'created_at' => SORT_DESC,
                        ],
                        'visibleColumns' => [
                            'checkbox',
                            'actions',
                            //'id',

                            'name',
                            //'cluster_id',
                            //'mime_type',
                            //'extension',
                            'size',
                            'created_at',
                            'created_by',

                            /*'image_id',
                            'name',

                            'tree_id',
                            'additionalSections',
                            'published_at',
                            'priority',

                            'created_by',

                            'active',

                            'view',*/
                        ],
                        'columns'        => [
                            'created_by' => [
                                'class' => UserColumnData::class,
                            ],
                            'updated_by' => [
                                'class' => UserColumnData::class,
                            ],
                            'created_at' => [
                                'class' => DateTimeColumnData::class,
                            ],
                            'updated_at' => [
                                'class' => DateTimeColumnData::class,
                            ],

                            'cluster_id' => [
                                'value'  => function (StorageFile $model) {
                                    $model->cluster_id;
                                    $cluster = \Yii::$app->storage->getCluster($model->cluster_id);
                                    return $cluster->name;
                                },
                                'format' => 'raw',
                            ],

                            'cluster_id' => [
                                'value'  => function (StorageFile $model) {
                                    $model->cluster_id;
                                    $cluster = \Yii::$app->storage->getCluster($model->cluster_id);
                                    return $cluster->name;
                                },
                                'format' => 'raw',
                            ],


                            'name' => [
                                'value'  => function (StorageFile $model) {
                                    $result = [];

                                    if ($model->downloadName) {
                                        $result[] = $model->downloadName;
                                    }

                                    if ($model->name) {
                                        $result[] = $model->name;
                                    }


                                    $result[] = Html::tag('label', $model->extension, [
                                            'title' => $model->extension,
                                            'class' => "",
                                            'style' => "    font-size: 10px;
    padding: 2px;
    padding-bottom: 4px;
    padding-left: 4px;
    padding-right: 4px;
    background: silver;
        margin-bottom: 0;
        margin-right: 5px;
        border-radius: 20px;
        color: white;
            line-height: 1;
    text-align: center;
    white-space: nowrap;
    color: #fff;
        ",
                                        ]).Html::tag('label', $model->mime_type, [
                                            'title' => $model->mime_type,
                                            'class' => "",
                                            'style' => "    font-size: 10px;
    padding: 2px;
    padding-bottom: 4px;
    padding-left: 4px;
    padding-right: 4px;
    background: silver;
    color: white;
        margin-bottom: 0;
        margin-right: 5px;
        border-radius: 20px;
            line-height: 1;
    text-align: center;
    white-space: nowrap;
    color: #fff;
        ",
                                        ]);

                                    $info = implode("<br />", $result);

                                    if ($model->isImage() && $model->size < 1024 * 1024 * 4) {

                                        $preview = \Yii::$app->imaging->getPreview($model, new \skeeks\cms\components\imaging\filters\Thumbnail());

                                        return "<div class='row no-gutters sx-trigger-action' style='cursor: pointer;'>
                                                <div class='' style='width: 50px;'>
                                                <a href='".$model->src."' style='text-decoration: none; border-bottom: 0;' class='sx-fancybox' target='_blank' data-pjax='0' title='".\Yii::t('skeeks/cms', 'Increase')."'>
                                                    <img src='".$preview->src."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a></div>
                                                <div style='margin-left: 5px;'>".$info."</div></div>";;
                                    }

                                    return "<div class='row no-gutters sx-trigger-action' style='cursor: pointer;'>
                                                <div class='' style='width: 50px;'><a href='".$model->src."' style='text-decoration: none; border-bottom: 0;' class='sx-fancybox' target='_blank' data-pjax='0' title='".\Yii::t('skeeks/cms',
                                            'Increase')."'>".\yii\helpers\Html::tag('span', $model->extension,
                                            [
                                                'class' => 'label label-primary u-label u-label-primary',
                                                'style' => '
                                                font-size: 18px;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    line-height: 38px;',
                                            ])
                                        ."</a></div>
                                                <div style='margin-left: 5px;'>".$info."</div></div>";
                                },
                                'format' => 'raw',
                            ],


                            'size' => [
                                'headerOptions'  => [
                                    'style' => 'width: 110px;',
                                ],
                                'contentOptions' => [
                                    'style' => 'width: 110px;',
                                ],
                                'value'          => function (StorageFile $model) {
                                    return $model->size ? \Yii::$app->formatter->asShortSize($model->size) : "";
                                },
                                'format'         => 'raw',
                            ],


                        ],

                        'on afterRun' => function (WidgetEvent $event) {

                            /**
                             * @var $grid GridView
                             * @var $query ActiveQuery
                             */
                            $grid = $event->sender;
                            $query = clone $grid->dataProvider->query;

                            $tableName = CmsStorageFile::tableName();
                            $result = $query->select([$tableName.".id", 'total_size' => new Expression("SUM({$tableName}.size)")])
                                //->createCommand()->rawSql;
                                ->asArray()->one();

                            /*print_r($query->select([$tableName.".id", 'total_size' => new Expression("SUM({$tableName}.size)")])->createCommand()->rawSql);
                            die;*/

                            $total_size = ArrayHelper::getValue($result, 'total_size');
                            $size = \Yii::$app->formatter->asShortSize($total_size);

                            if ($total_size > 0) {
                                $event->result = Alert::widget([
                                    'closeButton' => false,
                                    'options' => [
                                        'class' => 'alert alert-default',
                                    ],
                                    'body'    => <<<HTML
    <div class="g-font-weight-300">
    <span class="g-font-size-40">Всего: <span title="" style="">{$size}</span></span>
    </div>
    HTML
                                    ,
                                ]);
                            }

                        },
                    ],
                ],


                'stat' => [
                    'generateAccess' => true,
                    'class'          => ViewBackendAction::class,
                    'name'           => 'Статистика',
                    'icon'           => 'fas fa-info-circle',
                    'priority'       => 500,
                ],

                'delete-tmp-dir' =>
                    [
                        'priority' => 200,
                        "class"    => AdminOneModelEditAction::className(),
                        "name"     => "Удалить временные файлы",
                        "icon"     => "far fa-minus-square",
                        "method"   => "post",
                        "request"  => "ajax",
                        "callback" => [$this, 'actionDeleteTmpDir'],
                    ],

                'download' =>
                    [
                        'priority' => 150,
                        "class"    => AdminOneModelEditAction::className(),
                        "name"     => "Скачать",
                        "icon"     => "fas fa-download",
                        "method"   => "post",
                        "callback" => [$this, 'actionDownload'],
                    ],

                'create' => new UnsetArrayValue(),
            ]);
    }

    public function actionDownload()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $success = false;

        /**
         * @var StorageFile $file
         */
        $file = $this->model;
        $file->src;


        header('Content-type: '.$file->mime_type);
        header('Content-Disposition: attachment; filename="'.$file->downloadName.'"');
        echo file_get_contents($file->cluster->getAbsoluteUrl($file));
        die;

    }

    public function actionDeleteTmpDir()
    {
        if (\Yii::$app->request->isAjax) {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $success = false;

            /**
             * @var StorageFile $file
             */
            $file = $this->model;
            $file->deleteTmpDir();

            return [
                'message' => "Временные файлы удалены",
                'success' => true,
            ];
        }
    }


    public function actionUpload()
    {
        sleep(2);
        $response = [
            'success' => false,
        ];

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = Yii::$app->getRequest();

        $dir = \skeeks\sx\Dir::runtimeTmp();

        $uploader = new \skeeks\widget\simpleajaxuploader\backend\FileUpload("imgfile");
        $file = $dir->newFile()->setExtension($uploader->getExtension());

        $originalName = $uploader->getFileName();

        $uploader->newFileName = $file->getBaseName();
        $result = $uploader->handleUpload($dir->getPath().DIRECTORY_SEPARATOR);

        if (!$result) {
            $response["msg"] = $uploader->getErrorMsg();
            return $result;

        } else {

            $storageFile = Yii::$app->storage->upload($file, array_merge([
                "name"          => "",
                "original_name" => $originalName,
            ]));


            if ($request->get('modelData') && is_array($request->get('modelData'))) {
                $storageFile->setAttributes($request->get('modelData'));
            }

            $storageFile->save(false);

            $response["success"] = true;
            $response["file"] = $storageFile;
            return $response;
        }

        return $response;
    }

    public function _actionRemoteUpload()
    {
        $response =
            [
                'success' => false,
            ];

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        $get = Yii::$app->getRequest();

        $request = Yii::$app->getRequest();

        if (\Yii::$app->request->post('link')) {
            $storageFile = Yii::$app->storage->upload(\Yii::$app->request->post('link'), array_merge(
                [
                    "name"          => isset($model->name) ? $model->name : "",
                    "original_name" => basename($post['link']),
                ]
            ));

            if ($request->post('modelData') && is_array($request->post('modelData'))) {
                $storageFile->setAttributes($request->post('modelData'));
            }

            $storageFile->save(false);
            $response["success"] = true;
            $response["file"] = $storageFile;
            return $response;
        }

        return $response;
    }


    /**
     * Прикрепить к моделе другой файл
     * @return RequestResponse
     * @see skeeks\cms\widgets\formInputs\StorageImage
     */
    public function _actionLinkToModel()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            try {
                if (!\Yii::$app->request->post('file_id') || !\Yii::$app->request->post('modelId') || !\Yii::$app->request->post('modelClassName') || !\Yii::$app->request->post('modelAttribute')) {
                    throw new \yii\base\Exception("Не достаточно входных данных");
                }

                $file = CmsStorageFile::findOne(\Yii::$app->request->post('file_id'));
                if (!$file) {
                    throw new \yii\base\Exception("Возможно файл уже удален или не загрузился");
                }

                if (!is_subclass_of(\Yii::$app->request->post('modelClassName'), ActiveRecord::className())) {
                    throw new \yii\base\Exception("Невозможно привязать файл к этой моделе");
                }

                $className = \Yii::$app->request->post('modelClassName');
                /**
                 * @var $model ActiveRecord
                 */
                $model = $className::findOne(\Yii::$app->request->post('modelId'));
                if (!$model) {
                    throw new \yii\base\Exception("Модель к которой необходимо привязать файл не найдена");
                }

                if (!$model->hasAttribute(\Yii::$app->request->post('modelAttribute'))) {
                    throw new \yii\base\Exception("У модели не найден атрибут привязки файла: ".\Yii::$app->request->post('modelAttribute'));
                }

                //Удаление старого файла
                if ($oldFileId = $model->{\Yii::$app->request->post('modelAttribute')}) {
                    /**
                     * @var $oldFile CmsStorageFile
                     * @var $file CmsStorageFile
                     */
                    $oldFile = CmsStorageFile::findOne($oldFileId);
                    $oldFile->delete();
                }

                $model->{\Yii::$app->request->post('modelAttribute')} = $file->id;
                if (!$model->save(false)) {
                    throw new \yii\base\Exception("Не удалось сохранить модель");
                }

                $file->name = $model->name;
                $file->save(false);

                $rr->success = true;
                $rr->message = "";

            } catch (\Exception $e) {
                $rr->success = false;
                $rr->message = $e->getMessage();
            }

        }

        return $rr;
    }

    /**
     * Прикрепить к моделе другой файл
     * @return RequestResponse
     * @see skeeks\cms\widgets\formInputs\StorageImage
     */
    public function _actionLinkToModels()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            try {
                if (!\Yii::$app->request->post('file_id') || !\Yii::$app->request->post('modelId') || !\Yii::$app->request->post('modelClassName') || !\Yii::$app->request->post('modelRelation')) {
                    throw new \yii\base\Exception("Не достаточно входных данных");
                }

                $file = CmsStorageFile::findOne(\Yii::$app->request->post('file_id'));
                if (!$file) {
                    throw new \yii\base\Exception("Возможно файл уже удален или не загрузился");
                }

                if (!is_subclass_of(\Yii::$app->request->post('modelClassName'), ActiveRecord::className())) {
                    throw new \yii\base\Exception("Невозможно привязать файл к этой моделе");
                }

                $className = \Yii::$app->request->post('modelClassName');
                /**
                 * @var $model ActiveRecord
                 */
                $model = $className::findOne(\Yii::$app->request->post('modelId'));
                if (!$model) {
                    throw new \yii\base\Exception("Модель к которой необходимо привязать файл не найдена");
                }

                if (!$model->hasProperty(\Yii::$app->request->post('modelRelation'))) {
                    throw new \yii\base\Exception("У модели не найден атрибут привязки к файлам modelRelation: ".\Yii::$app->request->post('modelRelation'));
                }

                try {
                    $model->link(\Yii::$app->request->post('modelRelation'), $file);

                    if (!$file->name) {
                        $file->name = $model->name;
                        $file->save(false);
                    }

                    $rr->success = true;
                    $rr->message = "";
                } catch (\Exception $e) {
                    $rr->success = false;
                    $rr->message = $e->getMessage();
                }


            } catch (\Exception $e) {
                $rr->success = false;
                $rr->message = $e->getMessage();
            }

        }

        return $rr;
    }

}
