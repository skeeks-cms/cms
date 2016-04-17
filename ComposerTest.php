<?php

namespace skeeks\cms;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class ComposerTest
{
    public static function postUpdate(Event $event)
    {
        $composer = $event->getComposer();
        echo 'postUpdate';
        // do stuff
    }

    public static function postAutoloadDump(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir . '/autoload.php';

        some_function_from_an_autoloaded_file();
    }

}