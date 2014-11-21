<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var skeeks\cms\models\searchs\AuthItemSearch $searchModel
 */
$this->title = Yii::t('app', 'Roles');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="role-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create Role'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php
    Pjax::begin([
        'enablePushState'=>false,
    ]);
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'name',
                'label' => Yii::t('app', 'Name'),
            ],
            [
                'attribute' => 'description',
                'label' => Yii::t('app', 'Description'),
            ],
            ['class' => 'yii\grid\ActionColumn',],
        ],
    ]);
    Pjax::end();
    ?>

</div>