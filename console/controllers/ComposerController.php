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
 * Полезные комманды для работы с композером
 *
 * @package skeeks\cms\controllers
 */
class ComposerController extends Controller
{
    public $defaultAction = 'version';

    /**
     * @var
     * --verbose (-v): Increase verbosity of messages.
     */
    public $verbose;

    /**
     * @var
     * --profile: Display timing and memory usage information
     */
    public $profile = true;

    /**
     * @var bool
     * --no-interaction (-n): Do not ask any interactive question.
     */
    public $noInteraction = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return ArrayHelper::merge(parent::options($actionID), [
            'verbose', 'profile', 'noInteraction'
        ]);
    }

    public function init()
    {
        parent::init();

        //Проверка наличия composer, установка если нет.
        $composer = ROOT_DIR . "/composer.phar";
        if (!file_exists($composer))
        {
            $this->systemCmdRoot('php -r "readfile(\'https://getcomposer.org/installer\');" | php');
            $this->actionSelfUpdate();
        }

        if (!file_exists($composer))
        {
            throw new \InvalidArgumentException("composer.phar file не найден");
        }
    }






    /**
     * Текущая версия установленного композера
     */
    public function actionVersion()
    {
        $this->_composerCmd('-V');
    }

    /**
     * Хелп компоезра
     */
    public function actionHelp()
    {
        $this->_composerCmd('--help');
    }

    /**
     * Обновление композера
     */
    public function actionSelfUpdate($version = "1.0.0-alpha10")
    {
        $this->_composerCmd('self-update ' . $version);
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

        $this->_composerCmd("archive {$package} {$version} {$dir} {$format}");
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

        $this->_composerCmd('status');
    }

    /**
     * Установка необходимых composer asset plugin-ов
     * @param string $version
     */
    public function actionUpdateAssetPlugins($version = "1.0.3")
    {
        $this->_composerCmd('global require "fxp/composer-asset-plugin:' . $version . '"');
    }


    /**
     * Обновление зависимостей composer
     * In order to get the latest versions of the dependencies and to update the composer.lock file, you should use the update command.
     * @param $options
     *
     *  --prefer-source: Install packages from source when available.
        --prefer-dist: Install packages from dist when available.
        --ignore-platform-reqs: ignore php, hhvm, lib-* and ext-* requirements and force the installation even if the local machine does not fulfill these. See also the platform config option.
        --dry-run: Simulate the command without actually doing anything.
        --dev: Install packages listed in require-dev (this is the default behavior).
        --no-dev: Skip installing packages listed in require-dev. The autoloader generation skips the autoload-dev rules.
        --no-autoloader: Skips autoloader generation.
        --no-scripts: Skips execution of scripts defined in composer.json.
        --no-plugins: Disables plugins.
        --no-progress: Removes the progress display that can mess with some terminals or scripts which don't handle backspace characters.
        --optimize-autoloader (-o): Convert PSR-0/4 autoloading to classmap to get a faster autoloader. This is recommended especially for production, but can take a bit of time to run so it is currently not done by default.
        --lock: Only updates the lock file hash to suppress warning about the lock file being out of date.
        --with-dependencies Add also all dependencies of whitelisted packages to the whitelist.
        --prefer-stable: Prefer stable versions of dependencies.
        --prefer-lowest: Prefer lowest versions of dependencies. Useful for testing minimal versions of requirements, generally used with --prefer-stable.
     */
    public function actionUpdate(/*...*/)
    {
        $options = func_get_args();

        $optionsString = "";
        if ($options)
        {
            $optionsString = implode(" ", $options);
        }

        $this->_composerCmd('update ' . $optionsString);
    }

    /**
     * Просмотр установленных пакетов или информации об одном из них
     * @param string $package
     * @param string $version
     */
    public function actionShow($package = '', $version = "")
    {
        $this->_composerCmd('show ' . $package . " " . $version);
    }

    /**
     * Установка пакета
     * The require command adds new packages to the composer.json file from the current directory. If no file exists one will be created on the fly.
     *
     * @param string $package  skeeks/cms-module:*
     * @param bool|true $noUpdate --no-update: Disables the automatic update of the dependencies.
     */
    public function actionRequire($package, $update = 0)
    {
        $options = "";
        if ((int) $update == 0)
        {
            $options = "--no-update";
        }

        $this->_composerCmd('require ' . $package . ' ' . $options);
    }
    /**
     * Удаление пакета
     * The remove command removes packages from the composer.json file from the current directory.
     *
     * @param string $package
     * @param bool|true $noUpdate --no-update: Disables the automatic update of the dependencies.
     */
    public function actionRemove($package, $update = 0)
    {
        $options = "";
        if ((int) $update == 0)
        {
            $options = "--no-update";
        }

        $this->_composerCmd('remove ' . $package . " " . $options);
    }

    /**
     * Откатить модифицированные файлы venodr-s
     */
    public function actionRevertModifiedFiles()
    {
        $result = \Yii::$app->console->execute('cd '  . ROOT_DIR . '; COMPOSER_HOME=.composer php composer.phar status');

        if ($result)
        {
            $dirs = explode("\n", $result);

            if ($dirs)
            {
                foreach ($dirs as $dirPath)
                {
                    //FileHelper::removeDirectory($dirPath);
                    echo \Yii::$app->console->execute('cd ' . $dirPath . '; git checkout -f 2>&1; git clean -f -d 2>&1');
                }
            }
        }

    }








    /**
     * @param $cmd
     */
    protected function _composerCmd($cmd)
    {
        if ((bool) $this->verbose === true)
        {
            $cmd .= " --verbose";
        }

        if ((bool) $this->profile === true)
        {
            $cmd .= " --profile";
        }

        if ((bool) $this->noInteraction === true)
        {
            $cmd .= " --no-interaction";
        }

        $this->systemCmdRoot('COMPOSER_HOME=.composer php composer.phar ' . $cmd);
    }
}