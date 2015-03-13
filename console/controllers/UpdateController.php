<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.02.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\base\console\Controller;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use skeeks\sx\Dir;
use Yii;
use yii\helpers\Console;

/**
 * Утилита для обновления проекта а так же отдельных его частей.
 *
 * @package skeeks\cms\console\controllers
 */
class UpdateController extends Controller
{
    public $defaultAction = 'all';

    public function init()
    {
        parent::init();
        $this->_initRootPath();
    }

    /*public $all = false;
    /**
     * @inheritdoc
     */
    /*public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'all'
        ]);
    }*/

    /**
     * Полное обновление проекта
     *
     * @param int $autoremove стереть существующие файлы и скачать заново
     */
    public function actionAll($autoremove = 0)
    {
        if ($autoremove)
        {
            $this->stdoutN('    - remove all');

            //$this->systemCmdRoot("rm -rf .composer");
            $this->systemCmdRoot("rm -f composer.lock");
            $this->systemCmdRoot("rm -f composer.phar");
            //$this->systemCmdRoot("rm -rf vendor");
        }

        $this->actionComposerUpdate();
        $this->actionUpdateComposerJson();
        $this->actionMigration();
        $this->actionClearRuntimes();
        $this->actionGenerateModulesConfigFile();
        $this->actionDbRefresh();
        $this->actionRbacUpdate();
    }


    /**
     * Обновление и добавления прав доступа
     */
    public function actionRbacUpdate()
    {
        $this->systemCmdRoot("php yii cms/rbac/init");
    }

    /**
     * Генерация файла со списком модулей
     */
    public function actionGenerateModulesConfigFile()
    {
        \Yii::$app->cms->generateModulesConfigFile();
    }

    /**
     * Инвалидация кэша стуктуры базы данных
     */
    public function actionDbRefresh()
    {
        \Yii::$app->db->getSchema()->refresh();
    }

    /**
     * Читска временный файлов (assets и runtimes)
     */
    public function actionClearRuntimes()
    {
        $dir = new Dir(\Yii::getAlias('@console/runtime'));
        $dir->clear();

        $dir = new Dir(\Yii::getAlias('@common/runtime'));
        $dir->clear();

        $dir = new Dir(\Yii::getAlias('@frontend/runtime'));
        $dir->clear();
        $dir = new Dir(\Yii::getAlias('@frontend/web/assets'));
        $dir->clear();
    }

    /**
     * Проведение всех миграций всех подключенных модулей
     */
    public function actionMigration()
    {
        $cmd = "php yii migrate --migrationPath=@skeeks/cms/migrations --interactive=0" ;
        $this->systemCmdRoot($cmd);

        foreach (\Yii::$app->extensions as $code => $data)
        {
            if ($data['alias'])
            {
                foreach ($data['alias'] as $code => $path)
                {
                    $migrationsPath = $path . '/migrations';

                    if (PHP_OS === 'Windows')
                    {
                        $migrationsPath = str_replace("/", "\\", $migrationsPath);
                    }


                    if (is_dir($migrationsPath))
                    {
                        $cmd = "php yii migrate --migrationPath=" . $migrationsPath . '  --interactive=0' ;
                        $this->systemCmdRoot($cmd);
                    }

                }
            }
        }

        $this->systemCmdRoot("php yii migrate");
    }

    /**
     * Обновление проверка и установка композера, а также глобальных asset-plugin
     */
    public function actionComposerUpdate()
    {
        $composer = \Yii::getAlias('@root/composer.phar');
        if (file_exists($composer))
        {
            $this->stdoutN("composer есть, обновляем его");
            $this->systemCmdRoot("php composer.phar self-update");
        } else
        {
            $this->stdoutN("composer не надйен");
            $this->systemCmdRoot('php -r "readfile(\'https://getcomposer.org/installer\');" | php');
            $this->systemCmdRoot('COMPOSER_HOME=.composer php composer.phar global require \"fxp/composer-asset-plugin:1.0.0\" --profile"');
        }
    }

    /**
     * Обновление зависимостей и библиотек, через композер
     */
    public function actionUpdateComposerJson()
    {
        $this->systemCmdRoot("COMPOSER_HOME=.composer php composer.phar update --profile");
    }

    protected function _initRootPath()
    {
        \Yii::setAlias('root', dirname(\Yii::getAlias('@common')));
    }
}