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
use skeeks\cms\components\Breadcrumbs;
use skeeks\cms\components\Cms;
use skeeks\cms\components\CmsToolbar;
use skeeks\cms\components\ConsoleComponent;
use skeeks\cms\components\CurrentSite;
use skeeks\cms\components\Imaging;
use skeeks\cms\components\storage\Storage;
use skeeks\cms\i18n\I18N;
use skeeks\cms\models\CmsSite;

/**
 * @property Storage $storage
 * @property Cms $cms
 * @property Imaging $imaging
 * @property Breadcrumbs $breadcrumbs
 * @property CmsSite $currentSite
 * @property ConsoleComponent $console
 * @property I18N $i18n
 *
 * @property \yii\web\User|UserIde $user
 *
 * Class Application
 * @package yii\web
 */
class Application
{
}