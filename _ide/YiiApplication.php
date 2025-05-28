<?php
/**
 * The pseudo-only IDE tips
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */

namespace yii\web;

use skeeks\cms\_ide\UserIde;
use skeeks\cms\components\Adult;
use skeeks\cms\components\Breadcrumbs;
use skeeks\cms\components\Cms;
use skeeks\cms\components\CmsToolbar;
use skeeks\cms\components\ConsoleComponent;
use skeeks\cms\components\Imaging;
use skeeks\cms\components\LegalComponent;
use skeeks\cms\components\storage\Storage;
use skeeks\cms\i18n\I18N;
use skeeks\cms\Skeeks;

/**
 * @property Storage                                           $storage
 * @property Cms                                               $cms
 * @property LegalComponent                                    $legal
 * @property Imaging                                           $imaging
 * @property Breadcrumbs                                       $breadcrumbs
 * @property Skeeks                                            $skeeks
 * @property ConsoleComponent                                  $console
 * @property I18N                                              $i18n
 * @property \skeeks\cms\web\View                              $view
 * @property Adult                                             $adult
 *
 * @property \yii\web\User|UserIde|\skeeks\cms\components\User $user
 *
 * Class Application
 * @package yii\web
 */
class Application
{
}