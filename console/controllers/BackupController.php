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
 * Резервные копии
 *
 * @package skeeks\cms\controllers
 */
class BackupController extends Controller
{
    /**
     * Просмотр созданных бекапов баз данных
     */
    public function actionDbList()
    {
        $dbBackupDir = new Dir(BACKUP_DIR . "/db");
        if (!$dbBackupDir->isExist() || !$files = $dbBackupDir->findFiles())
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
     * Бэкап базы данных
     */
    public function actionGoDb()
    {
        $dbBackupDir = new Dir(BACKUP_DIR . "/db");
        if (!$dbBackupDir->isExist())
        {
            $dbBackupDir->make();
        }

        $dsnData = $this->getDsnData();
        $username = \Yii::$app->db->username;
        $password = \Yii::$app->db->password;
        $dbname = ArrayHelper::getValue($dsnData, 'dbname');
        $host = ArrayHelper::getValue($dsnData, 'host');

        $file = $dbBackupDir->newFile(date('Y-m-d_H:i:s') . ".sql.gz");
        $filePath = $file->getPath();

        $cmd = "mysqldump -h{$host} -u {$username} -p{$password} {$dbname} | gzip > {$filePath}";
        $this->systemCmd($cmd);

    }

    /**
     * Бэкап файлов
     */
    public function actionFiles()
    {

    }

    /**
     * Полный бэкап, база файлы все.
     */
    public function actionAll()
    {

    }






    /**
     * @return array
     */
    public function getDsnData()
    {
        //TODO: it's bad tmp code
        $dsnData = [];

        $dsn = \Yii::$app->db->dsn;
        if ($strpos = strpos($dsn, ':'))
        {
            $dsn = substr($dsn, ($strpos + 1), strlen(\Yii::$app->db->dsn));
        };

        $dsnDataTmp = explode(';', $dsn);
        if ($dsnDataTmp)
        {
            foreach ($dsnDataTmp as $data)
            {
                $tmpData = explode("=", $data);
                $dsnData[$tmpData[0]] = $tmpData[1];
            }
        }

        $dsnData['username'] = \Yii::$app->db->username;
        $dsnData['password'] = \Yii::$app->db->password;

        return $dsnData;
    }
}