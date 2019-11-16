<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\console\controllers;

use skeeks\cms\models\User;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Working with the mysql database
 *
 * @package skeeks\cms\controllers
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    protected $_runtimeMigrationPath = '@runtime/db-migrate';

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * It checks the existence of the [[migrationPath]].
     * @param \yii\base\Action $action the action to be executed.
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        $this->migrationPath = \Yii::getAlias($this->_runtimeMigrationPath);
        $this->_copyMigrations();

        return parent::beforeAction($action);
    }

    /**
     * @throws \Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    protected function _copyMigrations()
    {
        $this->stdout("Copy the migration files in a single directory\n", Console::FG_YELLOW);

        $tmpMigrateDir = \Yii::getAlias($this->_runtimeMigrationPath);

        FileHelper::removeDirectory($tmpMigrateDir);
        FileHelper::createDirectory($tmpMigrateDir);

        if (!is_dir($tmpMigrateDir)) {
            $this->stdout("Could not create a temporary directory migration\n");
            die;
        }

        $this->stdout("\tCreated a directory migration\n");

        if ($dirs = $this->_findMigrationDirs()) {
            foreach ($dirs as $path) {
                FileHelper::copyDirectory($path, $tmpMigrateDir);
            }
        }

        $this->stdout("\tThe copied files modules migrations\n");

        $appMigrateDir = \Yii::getAlias("@console/migrations");
        if (is_dir($appMigrateDir)) {
            FileHelper::copyDirectory($appMigrateDir, $tmpMigrateDir);
        }

        $this->stdout("\tThe copied files app migrations\n\n");
    }


    /**
     * @return array
     */
    private function _findMigrationDirs()
    {
        $result = [];

        foreach ($this->_findMigrationPossibleDirs() as $migrationPath) {
            if (is_dir($migrationPath)) {
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

        foreach (\Yii::$app->extensions as $code => $data) {
            if ($data['alias']) {
                foreach ($data['alias'] as $code => $path) {
                    $migrationsPath = $path . '/migrations';
                    $result[] = $migrationsPath;
                }
            }
        }

        return $result;
    }
}