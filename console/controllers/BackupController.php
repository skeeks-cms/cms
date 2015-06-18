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
use Yii;
use yii\helpers\Console;

/**
 * Резервные копии
 *
 * @package skeeks\cms\controllers
 */
class BackupController extends Controller
{
    /**
     * Бэкап базы данных
     */
    public function actionDb()
    {

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
}