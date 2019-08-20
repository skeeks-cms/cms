<?php

use skeeks\cms\mail\helpers\Html;

/* @var $this yii\web\View */
/* @var $resetLink */

?>
<?= Html::beginTag('p'); ?>
Здравствуйте!<br><br>
Для аткивации вашей учетной записи перейдите по ссылке: <?= $approveUrl; ?>
<?= Html::endTag('p'); ?>

