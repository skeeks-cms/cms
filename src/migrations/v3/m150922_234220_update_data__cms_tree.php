<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150922_234220_update_data__cms_tree extends Migration
{
    public function safeUp()
    {
        if ($models = \skeeks\cms\models\Tree::find()->all())
        {
            /**
             * @var $model \skeeks\cms\models\Tree
             */
            foreach ($models as $model)
            {
                if (!method_exists($model, 'getMainImageSrc'))
                {
                    continue;
                }

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

    public function down()
    {
        echo "m150922_234220_update_data__cms_tree cannot be reverted.\n";
        return false;
    }
}
