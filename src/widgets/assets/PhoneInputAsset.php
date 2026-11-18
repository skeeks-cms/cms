<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\assets;

use yii\web\AssetBundle;

/**
 * The soft phone mask for inputs with data-sx-phone-input (a text property of the format «Телефон»).
 * No dependencies: it works on public site pages as well as in the administration.
 */
class PhoneInputAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/widgets/assets/src/phone-input';

    public $js = [
        'phone-input.js',
    ];
}
