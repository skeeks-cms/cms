<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */
/* @var $this yii\web\View */
?>

<div class="sx-box sx-mt-10">
    <div class="sx-box-head sx-p-10">
        <h2>Дополнительные свойства</h2>
    </div>
    <div class="sx-box-body sx-p-10">
        <?= $model->renderRelatedPropertiesForm(); ?>
    </div>
</div>