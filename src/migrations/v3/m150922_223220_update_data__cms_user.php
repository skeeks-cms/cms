<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150922_223220_update_data__cms_user extends Migration
{
    public function safeUp()
    {
        if ($users = \skeeks\cms\models\User::find()->all())
        {
            /**
             * @var $user \skeeks\cms\models\User
             */
            foreach ($users as $user)
            {
                if (!method_exists($user, 'getMainImageSrc'))
                {
                    continue;
                }

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
    }

    public function down()
    {
        echo "m150922_223220_update_data__cms_user cannot be reverted.\n";
        return false;
    }
}
