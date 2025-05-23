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
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ImageController extends \yii\console\Controller
{

    protected function _wait($time = 10) {

        for ($i = 0; $i <= $time; $i ++) {
            $else = $time - $i;
            $this->stdout("\tСтарт через {$else} сек...\n");
            sleep(1);
        }
    }


    /**
     * @param $maxHeight Максимальная высота картинки
     * @param $maxFileSize Размер файла в МБ
     * @param $quantity Качество на выходе
     * @return void
     */
    public function actionResizeOriginalImagesVertical($maxHeight = 1600, $maxFileSize = 1, $quantity = 95)
    {
        $query = CmsStorageFile::find()
            ->andWhere(['>', 'image_height', 'image_width'])
            ->andWhere(['>', 'image_height', $maxHeight])
            ->andWhere(['>', 'size', 1024*1024*$maxFileSize])
            ->orderBy(['size' => SORT_DESC])
        ;


        if ($total = $query->count()) {
            $sizeQuery = clone $query;
            $sizeQuery->select(['sumsize' => new Expression('sum(size)')]);
            $data = $sizeQuery->asArray()->one();
            $formatedSumSize = \Yii::$app->formatter->asShortSize((float)ArrayHelper::getValue($data, 'sumsize'));

            $this->stdout("Неоптимизированных картинок: {$total} шт. ({$formatedSumSize}) \n");
        } else {
            $this->stdout("Неоптимизированных картинок нет\n");
            return;
        }


        $this->_wait(5);


        /**
         * @var CmsStorageFile $storageFile
         */
        foreach ($query->each(50) as $storageFile) {

            $fileRoot = $storageFile->getRootSrc();
            $this->stdout("\tКартинка: {$storageFile->id}\n");
            $this->stdout("\t\t{$fileRoot}\n");
            $this->stdout("\t\tРазрешение: {$storageFile->image_width}x{$storageFile->image_height}\n");
            $fileSize = filesize($fileRoot);
            $fileSizeFormated = \Yii::$app->formatter->asShortSize($fileSize);
            $this->stdout("\t\tРазмер: {$fileSizeFormated}\n");

            $size = Image::getImagine()->open($fileRoot)->getSize();
            $width = ($size->getWidth() * $maxHeight) / $size->getHeight();

            $newWidth = (int)round($width);
            $newHeight = $maxHeight;
            $this->stdout("\t\tНовое разрешение: {$newWidth}x{$newHeight}\n");
            

            Image::thumbnail($fileRoot, $newWidth, $newHeight)->save($fileRoot,[
                'jpeg_quality' => $quantity,
                'webp_quality' => $quantity,
            ]);

            $newSize = Image::getImagine()->open($fileRoot)->getSize();
            $storageFile->image_height = $newSize->getHeight();
            $storageFile->image_width = $newSize->getWidth();



            clearstatcache();
            $fileSize = filesize($fileRoot);
            $fileSizeFormated = \Yii::$app->formatter->asShortSize($fileSize);
            $this->stdout("\t\tНовый размер файла: {$fileSizeFormated}\n");

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
     * Изменение размера у оригинальных картинок в хранилище
     *
     * @param int $maxWidth
     * @param int $maxHeight
     *
     * @throws Exception
     */
    public function actionResizeOriginalImages($maxWidth = 1920, $maxHeight = 1200, $quantity = 100)
    {
        $query = CmsStorageFile::find()
            ->andWhere(['>', 'image_height', $maxHeight])
            ->andWhere(['>', 'image_width', $maxWidth])
            //->orderBy(['image_width' => SORT_DESC])
            ->orderBy(['size' => SORT_DESC])
        ;



        if ($total = $query->count()) {
            $sizeQuery = clone $query;
            $sizeQuery->select(['sumsize' => new Expression('sum(size)')]);
            $data = $sizeQuery->asArray()->one();
            $formatedSumSize = \Yii::$app->formatter->asShortSize((float)ArrayHelper::getValue($data, 'sumsize'));

            $this->stdout("Неоптимизированных картинок: {$total} шт. ({$formatedSumSize}) \n");
        } else {
            $this->stdout("Неоптимизированных картинок нет\n");
            return;
        }


        $this->_wait(5);

        /**
         * @var CmsStorageFile $storageFile
         */
        foreach ($query->each(50) as $storageFile) {

            $fileRoot = $storageFile->getRootSrc();
            $this->stdout("\tКартинка: {$storageFile->id}\n");
            $this->stdout("\t\t{$fileRoot}\n");
            $this->stdout("\t\tРазрешение: {$storageFile->image_width}x{$storageFile->image_height}\n");
            $fileSize = filesize($fileRoot);
            $fileSizeFormated = \Yii::$app->formatter->asShortSize($fileSize);
            $this->stdout("\t\tРазмер: {$fileSizeFormated}\n");

            $size = Image::getImagine()->open($fileRoot)->getSize();
            $height = ($size->getHeight() * $maxWidth) / $size->getWidth();

            $newHeight = (int)round($height);
            $newWidth = $maxWidth;
            $this->stdout("\t\tНовое разрешение: {$newWidth}x{$newHeight}\n");

            Image::thumbnail($fileRoot, $newWidth, $newHeight)->save($fileRoot,[
                'jpeg_quality' => $quantity,
                'webp_quality' => $quantity,
            ]);

            $newSize = Image::getImagine()->open($fileRoot)->getSize();
            $storageFile->image_height = $newSize->getHeight();
            $storageFile->image_width = $newSize->getWidth();



            clearstatcache();
            $fileSize = filesize($fileRoot);
            $fileSizeFormated = \Yii::$app->formatter->asShortSize($fileSize);
            $this->stdout("\t\tНовый размер файла: {$fileSizeFormated}\n");

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