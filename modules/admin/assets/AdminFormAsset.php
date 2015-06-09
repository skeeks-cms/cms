<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.06.2015
 */
namespace skeeks\cms\modules\admin\assets;
/**
 * Class AdminFormAsset
 * @package skeeks\cms\modules\admin\assets
 */
class AdminFormAsset extends AdminAsset
{
    public $css =
    [
        'css/form.css',
    ];

    public $js = [];

    public $depends =
    [
        'skeeks\cms\modules\admin\assets\AdminAsset',
    ];
}

