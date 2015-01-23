<?php
/**
 * Нетранутая тема http://bootstrapmaster.com/live/real/index.html#table.html
 * Для быстрого старта, подключаем без разбора
 * TODO: избавиться от лишних js и css
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\assets;
use skeeks\cms\base\AssetBundle;

/**
 * Class AppAsset
 * @package backend\assets
 */
class ThemeRealAdminAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/modules/admin/assets';

    public $css = [
        'themes/real-admin/css/jquery.mmenu.css',
        'themes/real-admin/css/simple-line-icons.css',
        'themes/real-admin/css/font-awesome.min.css',
        'themes/real-admin/css/add-ons.min.css',
        'themes/real-admin/css/style.min.css',
    ];
    public $js = [
        'themes/real-admin/js/jquery.mmenu.min.js',
    ];
    public $depends = [
    ];
}
