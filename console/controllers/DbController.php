<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.03.2016
 */
namespace skeeks\cms\console\controllers;

use Yii;
use yii\console\controllers\MigrateController;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Working with the mysql database
 *
 * @package skeeks\cms\controllers
 */
class DbController extends \yii\console\Controller
{
    /**
     * Restore from the dump
     * @param null $fileName The path to the dump file
     */
    public function actionRestore($fileName = null)
    {
        try
        {
            $this->stdout("The installation process is running the database\n");
            \Yii::$app->dbDump->restore($fileName);
            $this->stdout("Dump successfully installed\n", Console::FG_GREEN);
        } catch(\Exception $e)
        {
            $this->stdout("In the process of restoring the dump occurred error: {$e->getMessage()}\n", Console::FG_RED);
        }
    }

    /**
     * Creating a dump
     */
    public function actionDump()
    {
        try
        {
            $result = \Yii::$app->dbDump->dump();
            $this->stdout("Dump the database was created successfully: {$result}\n", Console::FG_GREEN);
        } catch(\Exception $e)
        {
            $this->stdout("During the dump error occurred: {$e->getMessage()}\n", Console::FG_RED);
        }
    }

    /**
     * Cache invalidation database structure
     */
    public function actionRefresh()
    {
        \Yii::$app->db->getSchema()->refresh();
        $this->stdout("Db schema refreshed\n", Console::FG_GREEN);
    }
}