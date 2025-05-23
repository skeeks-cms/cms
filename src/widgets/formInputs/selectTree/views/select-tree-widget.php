<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\formInputs\selectTree\DaterangeInputWidget */
$widget = $this->context;
?>
<?= \yii\helpers\Html::beginTag('div', $widget->wrapperOptions); ?>
<?= $elementForm; ?>

<div class="row">
    <div class="col-md-12">
        <div class="sx-selected-tree-input">
            <ul class="sx-selected">
                <?php if ($widget->sections) : ?>
                    <?php foreach ($widget->sections as $tree) : ?>
                        <li data-id="<?= $tree->id; ?>">
                            <div class="sx-selected-value" data-href="<?= $tree->url; ?>" title="<?= $widget->getNodeName($tree); ?>">
                                <?= $tree->name; ?>
                            </div>
                            <div class="sx-close-btn pull-right">×</div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <?php /*echo $widget->sections ? "style='display: none;'" : ""; */?>
    <div class="col-md-12 sx-select-tree" style='display: none;'>
        <div class="col-md-12 sx-select-tree-widget">
            <?php $treeWidgetClass = $widget->treeWidgetClass; ?>
            <?php $widgetTree = $treeWidgetClass::begin($widget->treeWidgetOptions); ?>
            <?

            $widget->clientOptions['pjaxid'] = $widgetTree->pjax->id;
            $options = \yii\helpers\Json::encode($widget->clientOptions);

            if ($widget->multiple) {
                $this->registerJs(<<<JS
                (function(window, sx, $, _)
                {
                    new sx.classes.treeinput.SelectTreeInputMultiple({$options});

                })(window, sx, sx.$, sx._);
JS
                );
            } else {
                $this->registerJs(<<<JS
                (function(window, sx, $, _)
                {
                    new sx.classes.treeinput.SelectTreeInputSingle({$options});

                })(window, sx, sx.$, sx._);
JS
                );
            }

            ?>
            <?
            $className = $widgetTree->className();
            $className::end();
            ?>
        </div>
    </div>
</div>
<?= \yii\helpers\Html::endTag('div'); ?>
