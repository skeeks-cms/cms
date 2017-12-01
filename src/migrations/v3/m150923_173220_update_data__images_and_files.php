<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m150923_173220_update_data__images_and_files extends Migration
{
    public function safeUp()
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

    public function safeDown()
    {
        echo "m150923_173220_update_data__images_and_files cannot be reverted.\n";
        return false;
    }
}