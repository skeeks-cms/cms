<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 07.03.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\base\console\Controller;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use skeeks\sx\Dir;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\helpers\Json;

/**
 * Единовременное обновление
 *
 * @package skeeks\cms\controllers
 */
class OnceUpdateController extends Controller
{
    /**
     * Просмотр созданных бекапов баз данных
     */
    public function actionUpTo210()
    {
        if ($users = \skeeks\cms\models\User::find()->all())
        {
            /**
             * @var $user \skeeks\cms\models\User
             */
            foreach ($users as $user)
            {
                //$user->getFiles()
                $imageSrc = $user->getMainImageSrc();
                if ($imageSrc)
                {
                    $storageFile = \skeeks\cms\models\CmsStorageFile::find()->where(['src' => $imageSrc])->one();
                    if ($storageFile)
                    {
                        $user->image_id = $storageFile->id;
                        $user->save(false);
                    }
                }
            }
        }



        if ($models = \skeeks\cms\models\Tree::find()->all())
        {
            /**
             * @var $model \skeeks\cms\models\Tree
             */
            foreach ($models as $model)
            {
                //$user->getFiles()
                $imageSrc = $model->getMainImageSrcOld();
                if ($imageSrc)
                {
                    $storageFile = \skeeks\cms\models\CmsStorageFile::find()->where(['src' => $imageSrc])->one();
                    if ($storageFile)
                    {
                        $model->image_id = $storageFile->id;
                        $model->image_full_id = $storageFile->id;

                        $model->save(false);
                    }
                }
            }
        }


        if ($models = \skeeks\cms\models\CmsContentElement::find()->all())
        {
            /**
             * @var $model \skeeks\cms\models\CmsContentElement
             */
            foreach ($models as $model)
            {
                //$user->getFiles()
                $imageSrc = $model->getMainImageSrcOld();
                if ($imageSrc)
                {
                    $storageFile = \skeeks\cms\models\CmsStorageFile::find()->where(['src' => $imageSrc])->one();
                    if ($storageFile)
                    {
                        $model->image_id = $storageFile->id;
                        $model->image_full_id = $storageFile->id;

                        $model->save(false);
                    }
                }
            }
        }
    }


    public function actionUpTo22()
    {
        $rows = (new \yii\db\Query())
            ->select(['id', 'files'])
            ->from('cms_tree')
            ->all();

        if ($rows)
        {
            foreach ($rows as $row)
            {
                /**
                 * @var \skeeks\cms\models\CmsTree $model
                 */
                if (!$modelId = \yii\helpers\ArrayHelper::getValue($row, 'id'))
                {
                    continue;
                }

                if (!$model = \skeeks\cms\models\CmsTree::findOne($modelId))
                {
                    continue;
                }

                $files = \yii\helpers\ArrayHelper::getValue($row, 'files');
                if (!$files)
                {
                    continue;
                }

                $files = Json::decode($files);

                if ($images = \yii\helpers\ArrayHelper::getValue($files, 'images'))
                {

                    foreach ($images as $src)
                    {
                        $storageFile = \skeeks\cms\models\StorageFile::find()->where(['src' => $src])->one();

                        if ($storageFile)
                        {
                            if ( !$model->getCmsTreeImages()->andWhere(['storage_file_id' => $storageFile->id])->one() )
                            {
                                $model->link('images', $storageFile);
                            }
                        }
                    }
                }

                if ($files = \yii\helpers\ArrayHelper::getValue($files, 'files'))
                {
                    foreach ($files as $src)
                    {
                        $storageFile = \skeeks\cms\models\StorageFile::find()->where(['src' => $src])->one();
                        if ($storageFile)
                        {
                            if ( !$model->getCmsTreeFiles()->andWhere(['storage_file_id' => $storageFile->id])->one() )
                            {
                                $model->link('files', $storageFile);
                            }
                        }
                    }
                }
            }
        }



        $rows = (new \yii\db\Query())
            ->select(['id', 'files'])
            ->from('cms_content_element')
            ->all();

        if ($rows)
        {
            foreach ($rows as $row)
            {
                /**
                 * @var \skeeks\cms\models\CmsContentElement $model
                 */
                if (!$modelId = \yii\helpers\ArrayHelper::getValue($row, 'id'))
                {
                    continue;
                }

                if (!$model = \skeeks\cms\models\CmsContentElement::findOne($modelId))
                {
                    continue;
                }

                $files = \yii\helpers\ArrayHelper::getValue($row, 'files');
                if (!$files)
                {
                    continue;
                }

                $files = Json::decode($files);

                if ($images = \yii\helpers\ArrayHelper::getValue($files, 'images'))
                {
                    foreach ($images as $src)
                    {
                        $storageFile = \skeeks\cms\models\StorageFile::find()->where(['src' => $src])->one();
                        if ($storageFile)
                        {
                            if ( !$model->getCmsContentElementImages()->andWhere(['storage_file_id' => $storageFile->id])->one() )
                            {
                                $model->link('images', $storageFile);
                            }
                        }
                    }
                }

                if ($files = \yii\helpers\ArrayHelper::getValue($files, 'files'))
                {
                    foreach ($files as $src)
                    {
                        $storageFile = \skeeks\cms\models\StorageFile::find()->where(['src' => $src])->one();
                        if ($storageFile)
                        {
                            if ( !$model->getCmsContentElementFiles()->andWhere(['storage_file_id' => $storageFile->id])->one() )
                            {
                                $model->link('files', $storageFile);
                            }
                        }
                    }
                }
            }
        }
    }

}