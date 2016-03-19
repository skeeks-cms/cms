<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (—ÍËÍ—)
 * @date 19.03.2016
 */

namespace skeeks\cms\console\controllers;
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
    public function actionFlushRuntimes()
    {
        $paths = ArrayHelper::getValue(\Yii::$app->cms->tmpFolderScheme, 'runtime');

        if ($paths)
        {
            foreach ($paths as $path)
            {
                FileHelper::removeDirectory(\Yii::getAlias($path));
                FileHelper::createDirectory(\Yii::getAlias($path));
            }
        }
    }
}