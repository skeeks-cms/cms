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

    <hr/>
<?php /*= \yii\helpers\Html::a('Пересчитать приоритеты по алфавиту', '#', ['class' => 'btn btn-xs btn-primary']) ?> |
<?= \yii\helpers\Html::a('Пересчитать приоритеты по дате добавления', '#', ['class' => 'btn btn-xs btn-primary']) ?> |
<?= \yii\helpers\Html::a('Пересчитать приоритеты по дате обновления', '#', ['class' => 'btn btn-xs btn-primary']) */ ?>
<?= $this->render('_recalculate-children-priorities', [
    'model' => $model
]); ?>

<?= $this->render('list', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider,
    'controller' => $controller,
]); ?>