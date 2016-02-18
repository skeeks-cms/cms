<?php
/**
 * AdminAsset
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\assets;
use skeeks\cms\assets\FancyboxAssets;
use skeeks\cms\base\AssetBundle;
use yii\helpers\Json;


/**
 * Class AppAsset
 * @package skeeks\cms\modules\admin
 */
class AdminAsset extends AssetBundle
{

    public $sourcePath = '@skeeks/cms/modules/admin/assets';

    public $css = [
        'css/panel.css',
        'css/sidebar.css',
        'css/app.css',
    ];
    public $js = [
        'js/classes/Blocker.js',
        'js/classes/OldNav.js',
        'js/classes/Menu.js',
        'js/classes/Iframe.js',
        'js/classes/Window.js',
        'js/classes/modal/Dialog.js',
        'js/classes/Fullscreen.js',
        'js/classes/UserLastActivity.js',
        //'js/App.js',
        'js/Admin.js',
    ];
    public $depends = [

        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapPluginAsset',

        '\skeeks\sx\assets\Custom',
        '\skeeks\sx\assets\ComponentAjaxLoader',
        '\skeeks\cms\modules\admin\assets\JqueryScrollbarAsset',
        '\skeeks\cms\modules\admin\assets\ThemeRealAdminAsset',
        '\skeeks\cms\assets\FancyboxAssets',
    ];

    /**
     * Registers this asset bundle with a view.
     * @param View $view the view to be registered with
     * @return static the registered asset bundle instance
     */
    public function registerAssetFiles($view)
    {
        if (\Yii::$app->request->isPjax)
        {
            return parent::registerAssetFiles($view);
        }

        parent::registerAssetFiles($view);
    }
}

