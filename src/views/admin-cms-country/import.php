<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */

use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>
<? \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
    'method' => 'post'
]); ?>
    <p>Кнопка ниже загрузит единый справочник стран на сайт, с кодамм (соответствующими стандартам), флагами и т.д.</p>
    <button class="btn btn-primary">Получить данные</button>
<? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>