<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var mdm\admin\models\AuthItem $model
 */
?>
<div class="auth-item-update">

	<?php
    echo $this->render('_form', [
        'model' => $model,
    ]);
    ?>
</div>
