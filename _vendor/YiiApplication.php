<?php
/**
 * Этот класс никогда не запускается, служит просто для подсказок IDE
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */

namespace yii\web;

use skeeks\cms\components\Breadcrumbs;
use skeeks\cms\components\Cms;
use skeeks\cms\components\CmsToolbar;
use skeeks\cms\components\ControlToolbar;
use skeeks\cms\components\CurrentSite;
use skeeks\cms\components\Imaging;
use skeeks\cms\components\Langs;
use skeeks\cms\components\PublicationTypes;
use skeeks\cms\components\RegisteredActionViews;
use skeeks\cms\components\RegisteredLayouts;
use skeeks\cms\components\RegisteredModels;
use skeeks\cms\components\RegisteredModelTypes;
use skeeks\cms\components\RegisteredWidgets;
use skeeks\cms\components\Seo;
use skeeks\cms\components\storage\Storage;
use skeeks\cms\components\TreeTypes;
use skeeks\cms\mail\Mailer;
use skeeks\cms\modules\admin\components\Menu;
use skeeks\cms\modules\admin\components\settings\AdminSettings;

/**
 *
 * @property RegisteredWidgets              $registeredWidgets
 * @property RegisteredModels               $registeredModels
 * @property RegisteredLayouts              $registeredLayouts
 * @property Storage                        $storage
 * @property Menu                           $adminMenu
 * @property CurrentSite                    $currentSite
 * @property Langs                          $langs
 * @property Cms                            $cms
 * @property Imaging                        $imaging
 * @property Seo                            $seo
 * @property Breadcrumbs                    $breadcrumbs
 * @property CmsToolbar                     $cmsToolbar
 * @property Mailer                         $mailer
 * @property AdminSettings                  $admin
 *
 * Class Application
 * @package yii\web
 */
class Application
{}