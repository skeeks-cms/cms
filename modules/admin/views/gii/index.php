<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */
use \Yii;
?>
<iframe src="<?= \skeeks\cms\helpers\UrlHelper::construct('gii'); ?>" width="100%"  height="900" id="sx-smart-frame"></iframe>

<?/* $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        new sx.classes.Iframe('sx-smart-frame', {
            'autoHeight'        : true,
            'heightSelector'    : 'html'
        });
    })(sx, sx.$, sx._);
JS
)*/?>