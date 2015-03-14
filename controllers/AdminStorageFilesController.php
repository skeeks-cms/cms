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

use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\Comment;
use skeeks\cms\models\Publication;
use skeeks\cms\models\searchs\Publication as PublicationSearch;
use skeeks\cms\models\StorageFile;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModelBehaviors;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class AdminStorageFilesController
 * @package skeeks\cms\controllers
 */
class AdminStorageFilesController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление файлами хранилища";
        $this->_modelShowAttribute      = "src";
        $this->_modelClassName          = StorageFile::className();

        $this->modelValidate = true;
        $this->enableScenarios = true;

        parent::init();
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = ArrayHelper::merge(parent::behaviors(), [

            self::BEHAVIOR_ACTION_MANAGER =>
            [
                "actions" =>
                [
                    'main-image' =>
                    [
                        "label"     => "Сделать главным изображением",
                        "icon"     => "glyphicon glyphicon-asterisk",
                        "method"        => "post",
                        "request"       => "ajax",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasDescriptionsBehavior::className()
                            ]
                        ]
                    ],

                    'add-to-images' =>
                    [
                        "label"     => "Добавить в группу изображения",
                        "icon"     => "glyphicon glyphicon-picture",
                        "method"        => "post",
                        "request"       => "ajax",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasDescriptionsBehavior::className()
                            ]
                        ]
                    ],

                    'add-to-files' =>
                    [
                        "label"     => "Добавить в группу файлы",
                        "icon"     => "glyphicon glyphicon-folder-open",
                        "method"        => "post",
                        "request"       => "ajax",
                        "rules"     =>
                        [
                            [
                                "class"     => HasModelBehaviors::className(),
                                "behaviors" => HasDescriptionsBehavior::className()
                            ]
                        ]
                    ],

                ]
            ]
        ]);

        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['create']);

        return $behaviors;
    }

    public function actionAddToImages()
    {
        if (\Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $success = false;

            try
            {
                /**
                 * @var StorageFile $file
                 */
                $file = $this->getCurrentModel();
                if (!$file->isImage())
                {
                    throw new Exception("Этот файл не является файлом изображения");
                }

                if (!$file->isLinked())
                {
                    throw new Exception("Этот файл не привязан к модели");
                }

                if (!$toModel = $file->getLinkedToModel())
                {
                    throw new Exception("Модель не найдена");
                }

                Validate::ensure(new HasBehavior(HasFiles::className()), $toModel);

                if (!$group = $toModel->getFilesGroups()->getComponent("images"))
                {
                    throw new Exception("У модели нет группы главное изображение");
                }

                $group->attachFile($file);
                $group->save();

                $message = 'Этот файл добавлен в группу изображений';
                //\Yii::$app->getSession()->setFlash('success', $message);
                $success = true;
            } catch (\Exception $e)
            {
                $message = $e->getMessage();
                    //\Yii::$app->getSession()->setFlash('error', $message);
                $success = false;
            }



            return [
                'message' => $message,
                'success' => $success,
            ];

        }
    }



    public function actionAddToFiles()
    {
        if (\Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $success = false;

            try
            {
                /**
                 * @var StorageFile $file
                 */
                $file = $this->getCurrentModel();

                if (!$file->isLinked())
                {
                    throw new Exception("Этот файл не привязан к модели");
                }

                if (!$toModel = $file->getLinkedToModel())
                {
                    throw new Exception("Модель не найдена");
                }

                Validate::ensure(new HasBehavior(HasFiles::className()), $toModel);

                if (!$group = $toModel->getFilesGroups()->getComponent("files"))
                {
                    throw new Exception("У модели нет группы главное изображение");
                }

                $group->attachFile($file);
                $group->save();

                $message = 'Этот файл добавлен в группу файлов';
                //\Yii::$app->getSession()->setFlash('success', $message);
                $success = true;
            } catch (\Exception $e)
            {
                $message = $e->getMessage();
                    //\Yii::$app->getSession()->setFlash('error', $message);
                $success = false;
            }



            return [
                'message' => $message,
                'success' => $success,
            ];

        }
    }


    /**
     * @return \yii\web\Response
     * @throws \Exception
     */
    public function actionMainImage()
    {

        if (\Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $success = false;

            try
            {
                /**
                 * @var StorageFile $file
                 */
                $file = $this->getCurrentModel();
                if (!$file->isImage())
                {
                    throw new Exception("Этот файл не является файлом изображения");
                }

                if (!$file->isLinked())
                {
                    throw new Exception("Этот файл не привязан к модели");
                }

                if (!$toModel = $file->getLinkedToModel())
                {
                    throw new Exception("Модель не найдена");
                }

                Validate::ensure(new HasBehavior(HasFiles::className()), $toModel);

                if (!$group = $toModel->getFilesGroups()->getComponent("image"))
                {
                    throw new Exception("У модели нет группы главное изображение");
                }

                if ($group->items)
                {
                    foreach ($group->items as $item)
                    {
                        $group->detachFile($item);
                    }
                }

                $group->attachFile($file);
                $group->save();

                $message = 'Этот файл сделан главным изображением';
                //\Yii::$app->getSession()->setFlash('success', $message);
                $success = true;
            } catch (\Exception $e)
            {
                $message = $e->getMessage();
                    //\Yii::$app->getSession()->setFlash('error', $message);
                $success = false;
            }



            return [
                'message' => $message,
                'success' => $success,
            ];

        }
        /*else
        {
            if ($this->getCurrentModel())
            {
                \Yii::$app->getSession()->setFlash('success', 'Запись успешно удалена');
            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не получилось удалить запись');
            }

            if ($ref = UrlHelper::getCurrent()->getRef())
            {
                return $this->redirect($ref);
            } else
            {
                return $this->goBack();
            }
        }*/

            //return $this->redirect(\Yii::$app->request->getReferrer());
    }

}
