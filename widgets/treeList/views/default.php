<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.03.2015
 */
/**
 * @var \skeeks\cms\models\Publication[] $models
 * @var \skeeks\cms\widgets\publications\Publications $widget
 * @var yii\data\Pagination $pages
 */
?>


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
/* @var $widget \skeeks\cms\widgets\publicationsAll\PublicationsAll */
/* @var $search \skeeks\cms\models\Search */
?>
<?/* foreach ($dataProvider->getModels() as $model) : */?><!--
    <?/*= $this->render('_one', ['model' => $model])*/?>
--><?/* endforeach; */?>
<? if ($widget->title): ?>
    <h2><?= $widget->title ?></h2>
<? endif; ?>

<?/* \skeeks\cms\modules\admin\widgets\Pjax::begin([
    'id' => 'sx-pjax-tree',
]);*/?>

<ul class="unstyled">
<? echo \yii\widgets\ListView::widget([
    'dataProvider'      => $dataProvider,
    'itemView'          => '_one',
    'emptyText'          => '',
    'layout'            => "<p>{summary}</p>{pager}\n{items}\n{pager}"
])?>
</ul>

<?/* \skeeks\cms\modules\admin\widgets\Pjax::end(); */?>
