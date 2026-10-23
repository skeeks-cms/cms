<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\formInputs\attachments;

use yii\web\AssetBundle;

/**
 * Form attachments (AttachmentsInputWidget). Registered only by pages that
 * render the field.
 */
class AttachmentsAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/formInputs/attachments/assets';

    public $css = [
        'attachments.css',
    ];

    public $js = [
        'attachments.js',
    ];

    // attachments.js adds sx.Attachments: the sx core must load first (see RichTextAsset).
    public $depends = [
        'yii\web\YiiAsset',
        'skeeks\sx\assets\Core',
    ];
}
