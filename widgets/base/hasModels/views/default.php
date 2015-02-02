<?php
/**
 * default
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.02.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */
/* @var $dataProvider \yii\data\ActiveDataProvider */
/* @var $widget \skeeks\cms\widgets\base\hasModels\WidgetHasModels */
/* @var $search \skeeks\cms\models\Search */

?>

<? echo \yii\widgets\ListView::widget([
    'dataProvider'      => $dataProvider,
    'itemView'          => 'one',
    'layout'            => "{summary}{pager}\n{items}\n{pager}"
])?>