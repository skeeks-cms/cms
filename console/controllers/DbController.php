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

/**
 * Working with the mysql database
 *
 * @package skeeks\cms\controllers
 */
class DbController extends Controller
{
    /**
     * Restore from the dump
     * @param null $fileName The path to the dump file
     */
    public function actionRestoreFromDump($fileName = null)
    {
        try
        {
            $this->stdout("The installation process is running the database\n");
            \Yii::$app->dbDump->restoreFromDump($fileName);
            $this->stdout("Dump successfully installed\n", Console::FG_GREEN);
        } catch(\Exception $e)
        {
            $this->stdout("In the process of restoring the dump occurred error: {$e->getMessage()}\n", Console::FG_RED);
        }
    }

    /**
     * Creating a dump
     */
    public function actionToDump()
    {
        try
        {
            $result = \Yii::$app->dbDump->toDump();
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
    }

    /**
     * Проведение всех миграций всех подключенных расширений
     */
    public function actionApplyMigrations()
    {
        $tmpMigrateDir = \Yii::getAlias('@runtime/db-migrate');

        FileHelper::removeDirectory($tmpMigrateDir);
        FileHelper::createDirectory($tmpMigrateDir);

        if (!is_dir($tmpMigrateDir))
        {
            $this->stdoutN('could not create a temporary directory migration');
        }

        $this->stdoutN('Tmp migrate dir is ready');
        $this->stdoutN('Copy migrate files');

        if ($dirs = $this->_findMigrationDirs())
        {
            foreach ($dirs as $path)
            {
                FileHelper::copyDirectory($path, $tmpMigrateDir);
            }
        }

        $appMigrateDir = \Yii::getAlias("@console/migrations");
        if (is_dir($appMigrateDir))
        {
            FileHelper::copyDirectory($appMigrateDir, $tmpMigrateDir);
        }


        $cmd = "php yii migrate --migrationPath=" . $tmpMigrateDir . '  --interactive=0';
        $this->systemCmdRoot($cmd);

        /*$cmd = "php yii migrate --interactive=0";
        $this->systemCmdRoot($cmd);*/

        \Yii::$app->db->getSchema()->refresh();
    }


    /**
     * @return array
     */
    private function _findMigrationDirs()
    {
        $result = [];

        foreach ($this->_findMigrationPossibleDirs() as $migrationPath)
        {
            if (is_dir($migrationPath))
            {
                $result[] = $migrationPath;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    private function _findMigrationPossibleDirs()
    {
        $result = [];

        foreach (\Yii::$app->extensions as $code => $data)
        {
            if ($data['alias'])
            {
                foreach ($data['alias'] as $code => $path)
                {
                    $migrationsPath = $path . '/migrations';
                    $result[] = $migrationsPath;


                }
            }
        }

        return $result;
    }
}