<?php
/**
 * jquery scrollbar plugin используетсся для красивого скроллбара справа
 * AppAsset
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\assets;
use yii\web\AssetBundle;

/**
 * Class AppAsset
 * @package backend\assets
 */
class JqueryScrollbarAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/modules/admin/assets';

    public $css = [
        'plugins/jquery.scrollbar/jquery.scrollbar.css',
    ];
    public $js = [
        'plugins/jquery.scrollbar/jquery.scrollbar.min.js',
    ];
    public $depends = [];
}
