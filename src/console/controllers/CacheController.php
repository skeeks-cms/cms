<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\console\controllers;

use skeeks\cms\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Allows you to flush cache.
 *
 * see list of available components to flush:
 *
 *     yii cms/cache
 *
 * flush particular components specified by their names:
 *
 *     yii cms/cache/flush first second third
 *
 * flush all cache components that can be found in the system
 *
 *     yii cms/cache/flush-all
 *
 *
 *     yii cms/cache/flush-runtimes
 *     yii cms/cache/flush-assets
 *     yii cms/cache/flush-tmp-config
 *
 * Note that the command uses cache components defined in your console application configuration file. If components
 * configured are different from web application, web application cache won't be cleared. In order to fix it please
 * duplicate web application cache components in console config. You can use any component names.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @author Mark Jebri <mark.github@yandex.ru>
 * @since 2.0
 */
class CacheController extends \yii\console\controllers\CacheController
{
    /**
     * Clear rintimes directories
     */
    public function actionFlushRuntimes()
    {
        $paths = ArrayHelper::getValue(\Yii::$app->cms->tmpFolderScheme, 'runtime');


        $this->stdout("Clear runtimes directories\n", Console::FG_YELLOW);

        if ($paths) {
            foreach ($paths as $path) {
                $realPath = \Yii::getAlias($path);
                $this->stdout("\tClear runtime directory: {$realPath}\n");
                FileHelper::removeDirectory(\Yii::getAlias($path));
                FileHelper::createDirectory(\Yii::getAlias($path));
            }
        }
    }

    /**
     * Clear asstes directories
     */
    public function actionFlushAssets()
    {
        $paths = ArrayHelper::getValue(\Yii::$app->cms->tmpFolderScheme, 'assets');
        $this->stdout("Clear assets directories\n", Console::FG_YELLOW);

        if ($paths) {
            foreach ($paths as $path) {
                $realPath = \Yii::getAlias($path);
                $this->stdout("\tClear asset directory: {$realPath}\n");
                FileHelper::removeDirectory(\Yii::getAlias($path));
                FileHelper::createDirectory(\Yii::getAlias($path));
            }
        }
    }
}