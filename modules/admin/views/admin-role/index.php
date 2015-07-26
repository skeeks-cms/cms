<?php
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\GridView;
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var skeeks\cms\models\searchs\AuthItemSearch $searchModel
 */
?>
<div class="role-index">



    <?php

    echo \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'adminController' => $controller,
        'settingsData' =>
        [
            'orderBy' => ''
        ],
        'columns' => [

            [
                'attribute' => 'name',
                'label' => Yii::t('app', 'Name'),
            ],
            [
                'attribute' => 'description',
                'label' => Yii::t('app', 'Description'),
            ],

            /*['class' => 'yii\grid\ActionColumn',],*/
        ],
    ]);

    ?>

</div>