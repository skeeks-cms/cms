<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\formInputs\richText;

use yii\web\AssetBundle;

/**
 * The rich-text editor (RichTextInputWidget). Registered only by pages that
 * render the field; a page that inserts editors later (AJAX) registers it
 * itself or depends on it.
 */
class RichTextAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/richText/assets';

    public $css = [
        'rich-text.css',
    ];

    public $js = [
        'rich-text.js',
    ];

    // rich-text.js adds sx.RichText: the sx core must load first, or the core finds a bare window.sx and does not start.
    public $depends = [
        'yii\web\YiiAsset',
        'skeeks\sx\assets\Core',
    ];
}
