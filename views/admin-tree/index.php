<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

use skeeks\cms\modules\admin\widgets\Pjax;
?>

<? Pjax::begin([
    'id' => 'sx-pjax-tree',
    'blockPjaxContainer' => false,
    'blockContainer' => '.sx-panel',
]);?>
<div class="col-md-12">
<?= \skeeks\cms\modules\admin\widgets\Tree::widget([
    "models" => $models
]); ?>
</div>

<? Pjax::end(); ?>