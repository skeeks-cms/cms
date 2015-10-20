<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var skeeks\cms\models\AuthItem $model
 */

$this->title = \Yii::t('app', 'Create Rule');
$this->params['breadcrumbs'][] = ['label' => \Yii::t('app', 'Rules'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="auth-item-create">

	<h1><?= Html::encode($this->title) ?></h1>

	<?php echo $this->render('_form', [
        'model' => $model,
    ]); ?>

</div>
