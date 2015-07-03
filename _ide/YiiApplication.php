<?php
/**
 * Псевдо класс только для подсказок IDE
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */

namespace yii\web;

use skeeks\cms\_ide\UserIde;
use skeeks\cms\components\Breadcrumbs;
use skeeks\cms\components\Cms;
use skeeks\cms\components\CmsSearchComponent;
use skeeks\cms\components\CmsToolbar;
use skeeks\cms\components\ControlToolbar;
use skeeks\cms\components\CurrentSite;
use skeeks\cms\components\db\DbDumpComponent;
use skeeks\cms\components\Imaging;
use skeeks\cms\components\Langs;
use skeeks\cms\components\marketplace\MarketplaceApi;
use skeeks\cms\components\PublicationTypes;
use skeeks\cms\components\RegisteredActionViews;
use skeeks\cms\components\RegisteredModels;
use skeeks\cms\components\RegisteredModelTypes;
use skeeks\cms\components\Seo;
use skeeks\cms\components\storage\Storage;
use skeeks\cms\components\TreeTypes;
use skeeks\cms\mail\Mailer;
use skeeks\cms\modules\admin\components\Menu;
use skeeks\cms\modules\admin\components\settings\AdminSettings;
use yii\authclient\Collection;

/**
 *
 * @property RegisteredModels               $registeredModels
 * @property Storage                        $storage
 * @property Menu                           $adminMenu
 * @property Cms                            $cms
 * @property Imaging                        $imaging
 * @property Seo                            $seo
 * @property Breadcrumbs                    $breadcrumbs
 * @property CmsToolbar                     $cmsToolbar
 * @property Mailer                         $mailer
 * @property AdminSettings                  $admin
 * @property CurrentSite                    $currentSite
 * @property Collection                     $authClientCollection
 * @property DbDumpComponent                $dbDump
 * @property CmsSearchComponent             $cmsSearch
 * @property MarketplaceApi                 $cmsMarkeplace
 *
 * @property \yii\web\User|UserIde                 $user
 *
 * Class Application
 * @package yii\web
 */
class Application
{}