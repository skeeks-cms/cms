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
/**
 * Class AdminGridAsset
 * @package skeeks\cms\modules\admin\assets
 */
class AdminGridAsset extends AdminAsset
{
    public $css =
    [
        'css/grid.css',
        'css/table.css',
    ];

    public $js = [];

    public $depends =
    [
        'skeeks\cms\modules\admin\assets\AdminAsset',
    ];
}

