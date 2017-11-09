<?php

namespace skeeks\cms\composer;

use Composer\Installer\LibraryInstaller;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\FileHelper;
use yii\helpers\ArrayHelper;

class Installer extends LibraryInstaller
{
    public static function clearDirs(Event $event)
    {
        $params = $event->getComposer()->getPackage()->getExtra();

        if (isset($params[__METHOD__]['dirs']) && is_array($params[__METHOD__]['dirs'])) {
            foreach ($params[__METHOD__]['dirs'] as $dir)
            {
                if (is_dir($dir))
                {
                    $dir = realpath($dir);
                    echo "\tclear dir: {$dir}\n";
                    FileHelper::removeDirectory($dir);
                    FileHelper::createDirectory($dir);
                }
            }
        }
    }
}