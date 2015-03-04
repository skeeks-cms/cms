<?
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.02.2015
 * @since 1.0.0
 */
use skeeks\cms\modules\admin\widgets\Pjax;
?>

<? Pjax::begin([
    'id' => 'sx-pjax-tree',
    'blockPjaxContainer' => false,
    'blockContainer' => '.sx-panel',
]);?>
<div class="col-md-8">
<?= \skeeks\cms\modules\admin\widgets\Tree::widget([
    "models" => $models
]); ?>
</div>

<? Pjax::end(); ?>