<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model skeeks\cms\models\AuthItem */

?>
<div class="auth-item-update">

	<!--<h1><?/*= Html::encode($this->title) */?></h1>-->
	<?php
    echo $this->render('_form', [
        'model' => $model,
    ]);
    ?>
</div>
