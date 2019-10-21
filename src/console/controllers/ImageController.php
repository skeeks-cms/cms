<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\console\controllers;

use skeeks\imagine\Image;
use skeeks\cms\models\CmsStorageFile;
use yii\base\Exception;
use yii\helpers\Console;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ImageController extends \yii\console\Controller
{
    /**
     * Изменение размера у оригинальных картинок в хранилище
     *
     * @param int $maxWidth
     * @param int $maxHeight
     *
     * @throws Exception
     */
    public function actionResizeOriginalImages($maxWidth = 1024, $maxHeight = 768)
    {
        $query = CmsStorageFile::find()
            ->andWhere(['>', 'image_height', $maxHeight])
            ->andWhere(['>', 'image_width', $maxWidth])
        ;

        if ($total = $query->count()) {
            $this->stdout("Неоптимизированных картинок: {$total} \n");
        } else {
            $this->stdout("Неоптимизированных картинок нет\n");
            return;
        }

        /**
         * @var CmsStorageFile $storageFile
         */
        foreach ($query->each(50) as $storageFile) {

            $fileRoot = $storageFile->getRootSrc();
            $this->stdout("\tКартинка: {$storageFile->id}\n");
            $this->stdout("\t\t{$fileRoot}\n");
            $this->stdout("\t\t{$storageFile->image_width}x{$storageFile->image_height}\n");
            $fileSize = filesize($fileRoot);
            $this->stdout("\t\t{$fileSize}\n");

            $size = Image::getImagine()->open($fileRoot)->getSize();
            $height = ($size->getHeight() * $maxWidth) / $size->getWidth();

            $newHeight = (int)round($height);
            $newWidth = $maxWidth;
            $this->stdout("\t\tnew: {$newWidth}x{$newHeight}\n");

            Image::thumbnail($fileRoot, $newWidth, $newHeight)->save($fileRoot);


            $newSize = Image::getImagine()->open($fileRoot)->getSize();
            $storageFile->image_height = $newSize->getHeight();
            $storageFile->image_width = $newSize->getWidth();



            clearstatcache();
            $fileSize = filesize($fileRoot);
            $this->stdout("\t\tnew: {$fileSize}\n");

            $storageFile->size = $fileSize;

            if (!$storageFile->save()) {
                $error = "Не сохранились данные по новой картинке: " . print_r($storageFile->errors, true);
                //throw new Exception("Не сохранились данные по новой картинке: " . print_r($storageFile->errors, true));
                $this->stdout("\t\t{$error}\n");
                continue;
            }

            $this->stdout("\t\tsaved\n");
        }
    }

    /**
     *
     * Пересчет размеравсех файлов в хранилище
     *
     * @return $this
     * @throws Exception
     */
    public function actionRecalculateFileSize()
    {
        $query = CmsStorageFile::find();

        if ($total = $query->count()) {
            $this->stdout("Файлов в хранилище: {$total} \n");
        } else {
            $this->stdout("Нет файлов в хранилище\n");
            return "";
        }

        clearstatcache();

        /**
         * @var CmsStorageFile $storageFile
         */
        foreach ($query->each(50) as $storageFile) {

            $fileRoot = $storageFile->getRootSrc();

            $this->stdout("\tФайл: {$storageFile->id}\n");


            $fileSize = filesize($fileRoot);

            if ($fileSize != $storageFile->size) {
                $oldSize = \Yii::$app->formatter->asShortSize($storageFile->size);
                $newSize = \Yii::$app->formatter->asShortSize($fileSize);
                $this->stdout("\t\tOld size: $oldSize\n", Console::FG_RED);
                $this->stdout("\t\tNew size: $newSize\n", Console::FG_RED);

                $storageFile->size = $fileSize;

                if (!$storageFile->save()) {
                    throw new Exception("Не сохранились данные по Файлу: " . print_r($storageFile->errors, true));
                } else {
                    $this->stdout("\t\tsaved\n");
                }
            }


        }
    }
}