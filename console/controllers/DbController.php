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

/**
 * Работа с базоый данных mysql
 *
 * @package skeeks\cms\controllers
 */
class DbController extends Controller
{
    /**
     * Просмотр созданных бекапов баз данных
     */
    public function actionDumpList()
    {
        if (!\Yii::$app->dbDump->backupDir->isExist() || !$files = \Yii::$app->dbDump->backupDir->findFiles())
        {
            $this->stdoutN('Бэкапов не найдено');
            return;
        }

        $this->stdoutN('Найдено бэкапов баз данных: ' . count($files));

        foreach ($files as $file)
        {
            $this->stdoutN($file->getBaseName() . " (" . $file->size()->toString() . ")");
        }
    }

    /**
     * Установить базу данных из бэкап файла
     */
    public function actionDumpRestore($fileName)
    {
        \Yii::$app->dbDump->dumpRestore($fileName);
    }
}