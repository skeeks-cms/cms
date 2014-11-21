<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model skeeks\cms\models\AuthItem */

$this->title = Yii::t('app', 'Create Permission');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Permissions'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="auth-item-create">

	<h1><?= Html::encode($this->title) ?></h1>

	<?php echo $this->render('_form', [
        'model' => $model,
    ]); ?>

</div>
