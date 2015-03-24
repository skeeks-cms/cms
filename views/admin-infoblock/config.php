<?php
/**
 * config
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.02.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\base\Widget */
/* @var $infoblock \skeeks\cms\models\Infoblock */
?>

<?= $model->renderConfigForm((array) $infoblock->protected_widget_params); ?>
