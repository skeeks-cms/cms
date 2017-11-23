<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \Yii::t('skeeks/cms', "User List");
$this->params['breadcrumbs'][] = $this->title;
?>

<section id="contentBox">
    <div id="main" class="eh">
        <div class="game-index sx-list">
            <h1><?= Html::encode($this->title) ?></h1>
            <?php /*echo $this->render('_search', ['model' => $searchModel]); */ ?>
            <?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([
                'showHeader' => false,
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [

                    [
                        'class' => \yii\grid\DataColumn::className(),
                        'value' => function($model, $key, $index, $widget) {
                            //return "<img src='" . $model->() . "' style='width: 100px;' />";
                        },
                        'attribute' => 'image',
                        'format' => 'html',
                    ],

                    [
                        'class' => \yii\grid\DataColumn::className(),
                        'value' => function($model, $key, $index, $widget) {
                            return Html::a($model->getDisplayName(), $model->getPageUrl());
                        },
                        'attribute' => 'name',
                        'format' => 'html',
                    ],
                ],
            ]); ?>
        </div>
    </div>
</section>