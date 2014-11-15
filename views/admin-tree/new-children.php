<?php
/**
 * new-children
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.11.2014
 * @since 1.0.0
 */
?>

<?= $this->render('_form', [
    'model' => $model
]); ?>

<hr />
<?= \yii\helpers\Html::a('Упорядочить по алфавиту', '#', ['class' => 'btn btn-xs btn-primary']) ?> |
<?= \yii\helpers\Html::a('Пересчитать приоритеты', '#', ['class' => 'btn btn-xs btn-primary']) ?>

<?= $this->render('list', [
    'searchModel'   => $searchModel,
    'dataProvider'  => $dataProvider,
    'controller'    => $controller,
]); ?>