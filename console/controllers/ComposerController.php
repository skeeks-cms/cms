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
 * Полезные комманды для работы с композером
 *
 * @package skeeks\cms\controllers
 */
class ComposerController extends Controller
{
    public $defaultAction = 'version';

    /**
     * @var
     * Increase verbosity of messages.
     */
    public $verbose;

    /**
     * @var
     * Display timing and memory usage information
     */
    public $profile = true;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return ArrayHelper::merge(parent::options($actionID), [
            'verbose', 'profile'
        ]);
    }

    /**
     * Текущая версия установленного композера
     */
    public function actionVersion()
    {
        $this->composerCmd('-V');
    }

    /**
     * Хелп компоезра
     */
    public function actionHelp()
    {
        $this->composerCmd('--help');
    }

    /**
     * Обновление композера
     */
    public function actionSelfUpdate($version = "1.0.0-alpha10")
    {
        $this->composerCmd('self-update ' . $version);
    }

    /**
     * Создание архива пакета
     * This command is used to generate a zip/tar archive for a given package in a given version. It can also be used to archive your entire project without excluded/ignored files.
     *
     * @param string $package название пакета который нужно архивировать
     * @param string $version версия этого пакета
     * @param string $dir дирриктория где будет создан архив
     * @param string $format формат архива (tar|zip)
     */
    public function actionArchive($package, $version, $dir = BACKUP_DIR, $format = 'tar')
    {
        if ($format)
        {
            $format = "--format=" . $format;
        }

        if ($dir)
        {
            $dir = "--dir=" . $dir;
        }

        $this->composerCmd("archive {$package} {$version} {$dir} {$format}");
    }

    /**
     * Текущий статус проекта
     * f you often need to modify the code of your dependencies and they are installed from source, the status command allows you to check if you have local changes in any of them.
     * @param bool|true $files Показывать измененные файлы
     */
    public function actionStatus($files = true)
    {
        if ($files)
        {
            $this->verbose = true;
        }

        $this->composerCmd('status');
    }







    /**
     * @param $cmd
     */
    public function composerCmd($cmd)
    {
        if ($this->verbose)
        {
            $cmd .= " --verbose";
        }

        if ($this->profile)
        {
            $cmd .= " --profile";
        }

        $this->systemCmdRoot('php composer.phar ' . $cmd);
    }
}