<?php
/**
 * UpdateController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\console\Controller;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use skeeks\sx\Dir;
use Yii;
use yii\helpers\Console;

class UpdateController extends Controller
{
    public function init()
    {
        parent::init();
        $this->_initRootPath();
    }

    public function actionProject()
    {
        $this->stdoutBlock("Cms project updater");

        $this->systemCmd("rm -f " . \Yii::getAlias('@root/composer.lock'));

        $this
            ->actionComposer()
            ->actionUpdate()
            ->actionMigration()
            ->actionClearRuntime()
            ->actionGenerateModulesConfigFile()
            ->actionDbRefresh()
            ->actionRbacUpdate()
        ;




        $this->stdoutBlock("Update chmods");
        $this->systemCmd("chown -R www-data:www-data " . \Yii::getAlias('@root'));
    }


    /**
     * @return $this
     */
    public function actionRbacUpdate()
    {
        $this->stdoutBlock("Обновление и добавления прав доступа");
        $this->systemCmd("php yii cms/rbac/init");

        return $this;
    }
    /**
     * @return $this
     */
    public function actionGenerateModulesConfigFile()
    {
        $this->stdoutBlock("generate modules config file");
        \Yii::$app->cms->generateModulesConfigFile();

        return $this;
    }

    public function actionDbRefresh()
    {
        $this->stdoutBlock('Инвалидация кэша стуктуры базы данных');
        \Yii::$app->db->getSchema()->refresh();

        return $this;
    }
    public function actionClearRuntime()
    {
        $this->stdoutBlock("clear all runtime dirs");

        $dir = new Dir(\Yii::getAlias('@common/runtime'));
        $dir->clear();

        $dir = new Dir(\Yii::getAlias('@frontend/runtime'));
        $dir->clear();

        $dir = new Dir(\Yii::getAlias('@frontend2/runtime'));
        $dir->clear();

        $dir = new Dir(\Yii::getAlias('@frontend/web/assets'));
        $dir->clear();

        return $this;$dir = new Dir(\Yii::getAlias('@frontend2/web/assets'));
        $dir->clear();

        return $this;
    }

    public function actionMigration()
    {
        $this->stdoutBlock("Update migrations");

        $cmd = "php yii migrate --migrationPath=@skeeks/cms/migrations" ;
        $this->systemCmd($cmd);

        foreach (\Yii::$app->extensions as $code => $data)
        {
            if ($data['alias'])
            {
                foreach ($data['alias'] as $code => $path)
                {
                    $cmd = "php yii migrate --migrationPath=" . $path . '/migrations' ;
                    $this->systemCmd($cmd);
                }
            }
        }

        $this->systemCmd("php yii migrate");

        return $this;
    }

    public function actionComposer()
    {
        $this->stdoutBlock("Обновление композера");
        $composer = \Yii::getAlias('@root/composer.phar');
        if (file_exists($composer))
        {
            $this->stdoutN("composer есть, обновляем его");
            $this->systemCmd("php composer.phar self-update");
        } else
        {
            $this->stdoutN("composer не надйен");
            $this->systemCmd('php -r "readfile(\'https://getcomposer.org/installer\');" | php');
            $this->systemCmd('php composer.phar global require "fxp/composer-asset-plugin:1.0.0-beta2"');
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function actionUpdate()
    {
        $this->stdoutBlock("Обновление зависимостей и библиотек");
        $this->systemCmd("php composer.phar update");
        return $this;
    }

    protected function _initRootPath()
    {
        \Yii::setAlias('root', dirname(\Yii::getAlias('@common')));
    }
}