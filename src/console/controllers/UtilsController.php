<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\console\controllers;

use skeeks\cms\components\storage\ClusterLocal;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentProperty2content;
use skeeks\cms\models\CmsSearchPhrase;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUserPhone;
use skeeks\cms\models\StorageFile;
use skeeks\cms\shop\models\ShopCmsContentElement;
use skeeks\cms\Skeeks;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\controllers\HelpController;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Productivity SkeekS CMS
 *
 * @package skeeks\cms\console\controllers
 */
class UtilsController extends Controller
{
    /**
     * Получение списка всех возможных консольных команд
     * Используется в console ssh для автокомплита
     */
    public function actionAllCmd()
    {
        /**
         * @var $controllerHelp HelpController
         */
        $controllerHelp = \Yii::$app->createController('help')[0];
        $commands = $controllerHelp->getCommands();

        foreach ($controllerHelp->getCommands() as $controller) {
            $subController = \Yii::$app->createController($controller)[0];
            $actions = $controllerHelp->getActions($subController);

            if ($actions) {
                foreach ($actions as $actionId) {
                    $commands[] = $controller."/".$actionId;
                }
            }
        };

        $this->stdout(implode("\n", $commands));
    }

    /**
     * Читка всех сгенерированных миниатюр
     */
    public function actionClearAllThumbnails()
    {
        /**
         * @var $files StorageFile[]
         */
        ini_set('memory_limit', '2048M');

        if ($files = StorageFile::find()->count()) {
            /**
             * @var $file CmsStorageFile
             */
            foreach (StorageFile::find()->orderBy(['id' => SORT_ASC])->each(100) as $file) {
                $this->stdout("{$file->id}");
                if ($file->deleteTmpDir()) {
                    $this->stdout(" - true\n", Console::FG_GREEN);
                } else {
                    $this->stdout(" - false\n", Console::FG_RED);
                }
            }
        }
    }


    /*private $_dir_level = 0;
    private $_dir_current = 0;

    private function _clearStorageFilesDir($dir, $level) {

        $this->stdout("{$dir} - {$level}\n");
        
        $dirs = FileHelper::findDirectories($dir);
        $files = FileHelper::findFiles($dir);


        if ($files) {
            foreach ($files as $filePath)
            {
                
            }
        }
        
        if ($dirs)
        {
            foreach ($dirs as $subDir)
            {
                $this->_clearStorageFilesDir($subDir, $level + 1);
            }
        }
    }
    public function _actionClearStorageFiles($isDelete = 0)
    {
        $cluster = \Yii::$app->storage->getCluster();
        if ($cluster instanceof ClusterLocal) {
            if (!$cluster->rootBasePath) {
                throw new Exception("Не задана rootBasePath у " . $cluster->id);
            }

            $totalStorageSize = StorageFile::find()
                ->cluster($cluster->id)
                ->select(["id", 'total_size' => new Expression("SUM(size)")])
                ->asArray()
                ->one();
            
            print_r($totalStorageSize);
            die;

            $totalStorageFies = StorageFile::find()
                ->cluster($cluster->id)
                ->count();
            

            $this->stdout("{$cluster->id}\n");
            $this->stdout("Total files: {$totalStorageFies}\n");
            $this->stdout("Total file size: {$totalStorageFies}\n");
            
            $this->_clearStorageFilesDir($cluster->rootBasePath, $level = 1);

        }
    }*/

    /**
     * Tree normalization
     */
    public function actionNormalizeTree()
    {
        if (!CmsTree::find()->count()) {
            $this->stdout("Tree not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Tree normalize pids!\n", Console::FG_YELLOW);

        /**
         * @var CmsTree $tree
         */
        foreach (CmsTree::find()->orderBy(['id' => SORT_ASC])->andWhere(['level' => 0])->each(10) as $tree) {
            $this->_normalizeTreePids($tree);
        }
    }

    protected function _normalizeTreePids(CmsTree $tree)
    {
        if ($tree->level == 0) {
            $this->stdout("\tStart normalize tree: {$tree->id} - {$tree->name}\n", Console::BOLD);
            if ((int)$tree->pids != (int)$tree->id) {
                if (\Yii::$app->db->createCommand()->update(CmsTree::tableName(), [
                    'pids' => $tree->id,
                ], ['id' => $tree->id])->execute()) {
                    $this->stdout("\t{$tree->id} - {$tree->name}: is normalized\n", Console::FG_GREEN);
                } else {
                    $this->stdout("\t{$tree->id} - {$tree->name}: not save!\n", Console::FG_RED);
                    return false;
                }
            }
        } else {
            $newPids = $tree->parent->pids."/".$tree->id;
            if ($newPids != $tree->pids) {
                if (\Yii::$app->db->createCommand()->update(CmsTree::tableName(), [
                    'pids' => $newPids,
                ], ['id' => $tree->id])->execute()) {
                    $this->stdout("\t{$tree->id} - {$tree->name}: is normalized\n", Console::FG_GREEN);
                } else {
                    $this->stdout("\t{$tree->id} - {$tree->name}: not save!\n", Console::FG_RED);
                    return false;
                }
            }
        }

        if ($tree->children) {
            foreach ($tree->children as $tree) {
                $this->_normalizeTreePids($tree);
            }
        }
    }


    /**
     * Tree normalization
     */
    public function actionNormalizeContent()
    {
        if (!CmsContent::find()->count()) {
            $this->stdout("Content not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Tree normalize content!\n", Console::FG_YELLOW);

        /**
         * @var CmsContentProperty $cmsContentProperty
         */
        foreach (CmsContentProperty::find()->orderBy(['id' => SORT_ASC])->each(10) as $cmsContentProperty) {
            $this->stdout("\t content property: {$cmsContentProperty->name}\n", Console::FG_YELLOW);

            if (!$cmsContentProperty->content_id) {
                continue;
            }

            if (!CmsContentProperty2content::find()
                ->where(['cms_content_id' => $cmsContentProperty->content_id])
                ->andWhere(['cms_content_property_id' => $cmsContentProperty->id])
                ->exists()
            ) {
                if ((new CmsContentProperty2content([
                    'cms_content_id'          => $cmsContentProperty->content_id,
                    'cms_content_property_id' => $cmsContentProperty->id,
                ]))->save()) {
                    $this->stdout("\t Created: {$cmsContentProperty->name}\n", Console::FG_GREEN);
                } else {
                    $this->stdout("\t NOT Created: {$cmsContentProperty->name}\n", Console::FG_RED);
                }
            }
        }
    }


    /**
     * Обновляет адреса страниц элементов контента
     * @param null $contentId
     */
    public function actionUpdateContentElementCodes($contentId = null)
    {
        ini_set('memory_limit', '4095M'); // 4 GBs minus 1 MB

        $base_memory_usage = memory_get_usage();
        $this->memoryUsage(memory_get_usage(), $base_memory_usage);

        $contentElementClass = CmsContentElement::class;
        if (class_exists(ShopCmsContentElement::class)) {
            $contentElementClass = ShopCmsContentElement::class;
        }

        $query = $contentElementClass::find()//->andWhere(['>', 'id', 202300])
        ;
        if ($contentId) {
            $query->andWhere(['content_id' => $contentId]);
        }

        if (!$count = $query->count()) {
            $this->stdout("Content elements not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Found elements: {$count}!\n", Console::BOLD);

        foreach ($query->orderBy([
            'content_id' => SORT_ASC,
            'id'         => SORT_ASC,
        ])->each(10) as $cmsContentElement) {
            $this->stdout("\t{$cmsContentElement->id}: {$cmsContentElement->name}\n");

            try {
                $cmsContentElement->code = '';
                if (!$cmsContentElement->save()) {
                    $this->stdout("\tError:".print_r($cmsContentElement->errors, true)."\n", Console::FG_RED);
                    sleep(5);
                }

            } catch (\Exception $e) {
                $this->stdout("\tError:".$e->getMessage()."\n", Console::FG_RED);
                sleep(5);
            }

            //$this->stdout($this->memoryUsage(memory_get_usage(), $base_memory_usage) . "\n");

        }
    }

    public function memoryUsage($usage, $base_memory_usage)
    {
        return \Yii::$app->formatter->asSize($usage - $base_memory_usage);
    }

    /**
     * Deleting content items
     *
     * @param null $contentId content id
     */
    public function actionRemoveContentElements($contentId = null)
    {
        $query = CmsContentElement::find()->cmsSite();
        if ($contentId) {
            $query->andWhere(['content_id' => $contentId]);
        }

        if (!$count = $query->count()) {
            $this->stdout("Content elements not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Found elements: {$count}!\n", Console::BOLD);

        foreach ($query->orderBy([
            'content_id' => SORT_ASC,
            'id'         => SORT_ASC,
        ])->each(10) as $cmsContentElement) {
            $this->stdout("\t{$cmsContentElement->id}: {$cmsContentElement->name}");

            if ($cmsContentElement->delete()) {
                $this->stdout(" - deleted\n", Console::FG_GREEN);
            } else {
                $this->stdout(" - NOT deleted\n", Console::FG_RED);
            }
        }
    }

    /**
     * Приведение телефонов пользователей к единому формату
     *
     * @return void
     */
    public function actionNormalizeUserPhones()
    {
        $q = CmsUserPhone::find();
        $this->stdout("Found: {$q->count()}!\n", Console::BOLD);
        $this->stdout("Wait: 5 sec....\n", Console::BOLD);
        sleep(5);

        /**
         * @var
         */
        foreach ($q->each() as $userPhone) {
            $this->stdout("\t{$userPhone->value}\n");

            $phone = $userPhone->value;
            $first = substr($phone, 0, 1);
            if ($first == 8) {
                $newPhone = substr($phone, 1, strlen($phone));
                $newPhone = trim("+7".$newPhone);

                $userPhone->value = $newPhone;
                $this->stdout("\t\t new -> {$newPhone}\n");
            } elseif ($first == 9) {
                $newPhone = trim("+7".$phone);
                $userPhone->value = $newPhone;
                $this->stdout("\t\t new -> {$newPhone}\n");
            }

            if (!$userPhone->save()) {
                $this->stdout("\t\tошибка!\n");
            }

        }
    }
    /**
     * Удаляет файлы которые остались в хранилище но нет в базе хранилища
     *
     * @return void
     */
    public function actionRemoveFilesNotStorage($onlyCheck = 1)
    {
        //Skeeks::unlimited();
        ini_set("memory_limit", "50G");
        
        $clusters = \Yii::$app->storage->getClusters();
        foreach ($clusters as $cluster) {
            $this->stdout("cluster: {$cluster->id}\n");
            $this->stdout("cluster: {$cluster->rootBasePath}\n");
            //sleep(3);
            $totalStorageSize = StorageFile::find()
                /*->cluster($cluster->id)*/
                ->andWhere(['cluster_id' => $cluster->id])
                ->select(["id", 'total_size' => new Expression("SUM(size)")])
                ->asArray()
                ->one();

            $totalStorageFies = StorageFile::find()
                /*->cluster($cluster->id)*/
                ->andWhere(['cluster_id' => $cluster->id])
                ->count();
            

            $this->stdout("{$cluster->id}\n");
            $this->stdout("Total files: {$totalStorageFies}\n");
            $this->stdout("Total files size: " . \Yii::$app->formatter->asShortSize(ArrayHelper::getValue($totalStorageSize, "total_size")) . "\n\n");
            
            
            if (!$cluster instanceof ClusterLocal) {
                $this->stdout("Это не локальное хранилище!\n");
                continue;
            }
            $this->stdout("Запуск скрипта через 5 сек.");
            sleep(1);
            $this->stdout(" (5");
            sleep(1);
            $this->stdout(" 4");
            sleep(1);
            $this->stdout(" 3");
            sleep(1);
            $this->stdout(" 2");
            sleep(1);
            $this->stdout(" 1)\n\n");
            sleep(1);

            $counterFiles = 0;
            $counterFileSizes = 0;
            
            $needDeleteFiles = 0;
            $needDeleteFileSizes = 0;
            
            $firstLevel = FileHelper::findFiles($cluster->rootBasePath, ['recursive' => true]);
            foreach ($firstLevel as $filePath)
            {
                //Нормализация пути к файлу
                $clusterFilePath = str_replace($cluster->rootBasePath, "", $filePath);
                $tmpExplode = explode("/", $clusterFilePath);
                foreach ($tmpExplode as $c => $f)
                {
                    if (!$f) {
                        unset($tmpExplode[$c]);
                    }
                }
                $clusterFilePath = implode("/", $tmpExplode);
                
                //Проверка уровня в хранилище
                $arr = explode("/", $clusterFilePath);
                $fileDirLevel = count($arr) - 1;
                
                
                    
                $arr = explode("/", $clusterFilePath);
                if ($fileDirLevel < $cluster->directoryLevel) {
                    
                    $counterFiles = $counterFiles + 1;
                    $counterFileSizes = $counterFileSizes + filesize($filePath);
                    
                    $this->stdout("\t{$clusterFilePath}\n");
                    $this->stdout("\t\tМеньше уровня удалить!\n");

                    $needDeleteFiles = $needDeleteFiles + 1;
                    $needDeleteFileSizes = $needDeleteFileSizes + filesize($filePath);

                    if ($onlyCheck == 1) {

                    } else {
                        if (!unlink($filePath)) {
                            throw new Exception("Не удаляется файл");
                        }
                    }

                } elseif ($fileDirLevel > $cluster->directoryLevel) {
                    //$this->stdout("\t{$clusterFilePath} " . $fileDirLevel . "\n");
                    //$this->stdout("\t\tБольше уровня не трогать!\n");
                } else {
                    $counterFiles = $counterFiles + 1;
                    $counterFileSizes = $counterFileSizes + filesize($filePath);
                    
                    //$this->stdout("\t{$clusterFilePath} " . $fileDirLevel . "\n");
                    //$this->stdout("\t\tПроверить в базе!\n");
                    
                    $cmsStorageFile = StorageFile::find()
                        ->andWhere(['cluster_id' => $cluster->id])
                        ->andWhere(['cluster_file' => $clusterFilePath])
                        //->clusterFile($cluster->id, $clusterFilePath)
                        ->one();
                    
                    if (!$cmsStorageFile) {
                        $this->stdout("\t{$clusterFilePath}\n");
                        $this->stdout("\t\tНет в базе удалить!\n");
                        $needDeleteFiles = $needDeleteFiles + 1;
                        $needDeleteFileSizes = $needDeleteFileSizes + filesize($filePath);
                        
                        if ($onlyCheck == 1) {
                            
                        } else {
                            if (!unlink($filePath)) {
                                throw new Exception("Не удаляется файл");
                            }
                        }
                    }
                    
                    
                }
            }
            
            $this->stdout("Total files (data from db): {$totalStorageFies}\n");
            $this->stdout("Total files size (data from db): " . \Yii::$app->formatter->asShortSize(ArrayHelper::getValue($totalStorageSize, "total_size")) . "\n\n");
            
            $this->stdout("Calc total files: {$counterFiles}\n");
            $this->stdout("Calc files size: " . \Yii::$app->formatter->asShortSize($counterFileSizes) . "\n\n");
            
            $this->stdout("Calc files for delete: {$needDeleteFiles}\n");
            $this->stdout("Calc files size for delete: " . \Yii::$app->formatter->asShortSize($needDeleteFileSizes) . "\n\n");
            
            
            //check
            $deltaFiles = $counterFiles - $needDeleteFiles;
            $deltaFilesSizes = $counterFileSizes - $needDeleteFileSizes;
            $this->stdout("Calc after delete: {$deltaFiles}\n");
            $this->stdout("Calc after delete: " . \Yii::$app->formatter->asShortSize($deltaFilesSizes) . "\n\n");
            
            if ($deltaFiles == $totalStorageFies) {
                $this->stdout("Данные по количеству сходятся, можно запускать скритп!\n", Console::FG_GREEN);
            }
            if ($deltaFilesSizes == ArrayHelper::getValue($totalStorageSize, "total_size")) {
                $this->stdout("Данные по размеру сходятся, можно запускать скритп!\n", Console::FG_GREEN);
            }
        }
    }
    
    /**
     * Удаляет файлы которые остались в хранилище но нет в базе хранилища
     *
     * @return void
     */
    public function _actionRemoveFilesNotStorage($onlyCheck = 1)
    {
        $clusters = \Yii::$app->storage->getClusters();
        foreach ($clusters as $cluster) {
            $this->stdout("cluster: {$cluster->id}\n");
            $this->stdout("cluster: {$cluster->rootBasePath}\n");
            //sleep(3);
            $totalStorageSize = StorageFile::find()
                ->cluster($cluster->id)
                ->select(["id", 'total_size' => new Expression("SUM(size)")])
                ->asArray()
                ->one();

            $totalStorageFies = StorageFile::find()
                ->cluster($cluster->id)
                ->count();
            

            $this->stdout("{$cluster->id}\n");
            $this->stdout("Total files: {$totalStorageFies}\n");
            $this->stdout("Total files size: " . \Yii::$app->formatter->asShortSize(ArrayHelper::getValue($totalStorageSize, "total_size")) . "\n\n");
            
            
            $this->stdout("Запуск скрипта через 5 сек.");
            sleep(1);
            $this->stdout(" (5");
            sleep(1);
            $this->stdout(" 4");
            sleep(1);
            $this->stdout(" 3");
            sleep(1);
            $this->stdout(" 2");
            sleep(1);
            $this->stdout(" 1)\n\n");
            sleep(1);

            $counterFiles = 0;
            $counterFileSizes = 0;
            
            $needDeleteFiles = 0;
            $needDeleteFileSizes = 0;
            
            $firstLevel = FileHelper::findDirectories($cluster->rootBasePath, ['recursive' => false]);
            foreach ($firstLevel as $firstLevelDir)
            {
                $this->stdout("\t{$firstLevelDir}\n");
                continue;
                $secondLevel = FileHelper::findDirectories($firstLevelDir, ['recursive' => false]);

                if ($secondLevel) {
                    foreach ($secondLevel as $secondLevelDir)
                    {
                        $this->stdout("\t\t{$secondLevelDir}\n");
                        
                        $thirdLevel = FileHelper::findDirectories($secondLevelDir, ['recursive' => false]);

                        if ($thirdLevel) {
                            foreach ($thirdLevel as $thirdLevelDir)
                            {
                                $this->stdout("\t\t\t{$thirdLevelDir}\n");
                                $files = FileHelper::findFiles($thirdLevelDir, ['recursive' => false]);
                                if ($files) {
                                    foreach ($files as $file)
                                    {
                                        
                                        $cluster_file_name = str_replace($cluster->rootBasePath . "/", "", $file);
                                        
                                        $cmsStorageFile = StorageFile::find()->clusterFile($cluster->id, $cluster_file_name)->one();
                                        if (!$cmsStorageFile) {
                                            $this->stdout("\t\t\t\t{$file}\n");
                                            
                                            $this->stdout("\t\t\t\tУдалить!\n");
                                            $this->stdout("\t\t\t\t{$cluster->id}\n");
                                            $this->stdout("\t\t\t\t{$cluster_file_name}\n");

                                            unlink($file);

                                        } else {
                                            //$this->stdout("\t\t\t\tНЕ Удалять!\n");
                                        }
                                    }
                                } else {
                                    $this->stdout("\t\t\tNo Files!!!\n");
                                    FileHelper::removeDirectory($thirdLevelDir);
                                }
                            }

                        }
                        /*if ($files) {
                            foreach ($files as $file)
                            {
                                $this->stdout("\t\t\t{$file}\n");
                            }
                        }*/
                    }
                }
            }

        }
    }
}