<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\console\controllers;

use skeeks\cms\helpers\FileHelper;
use yii\console\Controller;

/**
 * Project SkeekS CMS initialization
 *
 * @package skeeks\cms\console\controllers
 */
class InitController extends Controller
{
    public function actionIndex()
    {
        $conf = [
            'setWritable' => [
                'console/runtime',
                'common/runtime',
                'frontend/runtime',
                'frontend/web/assets',
            ],
            'setExecutable' => [
                'yii',
            ],
        ];

        echo "\n  Start initialization ...\n\n";

        $callbacks = ['setCookieValidationKey', 'setWritable', 'setExecutable', 'createSymlink'];
        foreach ($callbacks as $callback) {
            if (!empty($conf[$callback])) {
                $this->$callback(ROOT_DIR, $conf[$callback]);
            }
        }
        echo "\n  ... initialization completed.\n\n";
    }


    function getFileList($root, $basePath = '')
    {
        $files = [];
        $handle = opendir($root);
        while (($path = readdir($handle)) !== false) {
            if ($path === '.git' || $path === '.svn' || $path === '.' || $path === '..') {
                continue;
            }
            $fullPath = "$root/$path";
            $relativePath = $basePath === '' ? $path : "$basePath/$path";
            if (is_dir($fullPath)) {
                $files = array_merge($files, $this->getFileList($fullPath, $relativePath));
            } else {
                $files[] = $relativePath;
            }
        }
        closedir($handle);
        return $files;
    }

    function copyFile($root, $source, $target, &$all, $params)
    {
        if (!is_file($root . '/' . $source)) {
            echo "       skip $target ($source not exist)\n";
            return true;
        }
        if (is_file($root . '/' . $target)) {
            if (file_get_contents($root . '/' . $source) === file_get_contents($root . '/' . $target)) {
                echo "  unchanged $target\n";
                return true;
            }
            if ($all) {
                echo "  overwrite $target\n";
            } else {
                echo "      exist $target\n";
                echo "            ...overwrite? [Yes|No|All|Quit] ";
                $answer = !empty($params['overwrite']) ? $params['overwrite'] : trim(fgets(STDIN));
                if (!strncasecmp($answer, 'q', 1)) {
                    return false;
                } else {
                    if (!strncasecmp($answer, 'y', 1)) {
                        echo "  overwrite $target\n";
                    } else {
                        if (!strncasecmp($answer, 'a', 1)) {
                            echo "  overwrite $target\n";
                            $all = true;
                        } else {
                            echo "       skip $target\n";
                            return true;
                        }
                    }
                }
            }
            file_put_contents($root . '/' . $target, file_get_contents($root . '/' . $source));
            return true;
        }
        echo "   generate $target\n";
        @mkdir(dirname($root . '/' . $target), 0777, true);
        file_put_contents($root . '/' . $target, file_get_contents($root . '/' . $source));
        return true;
    }

    function setWritable($root, $paths)
    {
        foreach ($paths as $writable) {
            if (!is_dir("$root/$writable")) {
                echo "      create dir and chmod 0777 $writable\n";
                FileHelper::createDirectory("$root/$writable", 0777);
            } else {
                echo "      chmod 0777 $writable\n";
                @chmod("$root/$writable", 0777);
            }
        }
    }

    function setExecutable($root, $paths)
    {
        foreach ($paths as $executable) {
            echo "      chmod 0755 $executable\n";
            @chmod("$root/$executable", 0755);
        }
    }

    function setCookieValidationKey($root, $paths)
    {
        foreach ($paths as $file) {
            echo "   generate cookie validation key in $file\n";
            $file = $root . '/' . $file;
            $length = 32;
            $bytes = openssl_random_pseudo_bytes($length);
            $key = strtr(substr(base64_encode($bytes), 0, $length), '+/=', '_-.');
            $content = preg_replace('/(("|\')cookieValidationKey("|\')\s*=>\s*)(""|\'\')/', "\\1'$key'",
                file_get_contents($file));
            file_put_contents($file, $content);
        }
    }

    function createSymlink($root, $links)
    {
        foreach ($links as $link => $target) {
            echo "      symlink " . $root . "/" . $target . " " . $root . "/" . $link . "\n";
            //first removing folders to avoid errors if the folder already exists
            @rmdir($root . "/" . $link);
            @symlink($root . "/" . $target, $root . "/" . $link);
        }
    }

}