<?php
use yii\helpers\Html;
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\AuthItem $model
 */
?>
<div class="auth-item-create">

	<?php echo $this->render('_form', [
        'model' => $model,
    ]); ?>

</div>