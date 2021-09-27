<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.12.2016
 */
/**
* @var $this yii\web\View
* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget
* @var $model \skeeks\cms\models\CmsTree
*/
$widget = $this->context;
?>
<?php /*if($widget->searchValue) : */?><!--
    <?/* if (in_array($model->id, \yii\helpers\ArrayHelper::map($widget->models, 'id', 'id'))) : */?>
        <?/* if ($childs = $model->getDescendants()->andWhere(['like', 'name', $widget->searchValue])->all()) : */?>
            <?/* foreach ($childs as $child) : */?>
                
                <?/*= \yii\helpers\Html::beginTag('li', [
                    "class" => "sx-tree-node sx-tree-node-{$child->id} " . ($widget->isOpenNode($child) ? " open" : ""),
                    "data-id" => $child->id,
                    "title" => ""
                ]); */?>
            
                <div class="row">
                    
                    <?/*= $widget->renderNodeContent($child); */?>
                </div>
            
                <?/*= \yii\helpers\Html::endTag('li'); */?>
                    
            <?/* endforeach; */?>
        <?/* endif; */?>
    <?/* endif; */?>
--><?php /*else : */?>
    <?= \yii\helpers\Html::beginTag('li', [
        "class" => "sx-tree-node sx-tree-node-{$model->id} " . ($widget->isOpenNode($model) ? " open" : ""),
        "data-id" => $model->id,
        "title" => ""
    ]); ?>

    <? if ($widget->searchValue) : ?>
        <?
            $isShowChilds = $model->getDescendants()->andWhere(['like', 'name', $widget->searchValue])->exists();
            $isExistChildren = $model->getChildren()->exists();
            $isShowCurrent = (strpos(\skeeks\cms\helpers\StringHelper::strtolower($model->name), (string) $widget->searchValue) !== false);
        ?>

        <? if ($isShowCurrent || $isShowChilds) : ?>
            <div class="row">
                <?php if ($isExistChildren) : ?>
                    <div class="sx-node-open-close">
                        <?php if ($widget->isOpenNode($model)) : ?>
                            <a href="<?= $widget->getOpenCloseLink($model); ?>" class="btn btn-sm btn-default">
                                <span class="fa fa-minus" title="<?= \Yii::t('skeeks/cms', "Minimize"); ?>"></span>
                            </a>
                        <?php else
                            : ?>
                            <a href="<?= $widget->getOpenCloseLink($model);
                            ?>" class="btn btn-sm btn-default">
                                <span class="fa fa-plus" title="<?= \Yii::t('skeeks/cms', "Restore"); ?>"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?= $widget->renderNodeContent($model); ?>
            </div>
        
            <? if ($isShowChilds) : ?>
                <!-- Construction of child elements -->
                <?php if ($widget->isOpenNode($model) && $model->children) : ?>
                    <?= $widget->renderNodes($model->children); ?>
                <?php endif; ?>
            <? elseif ($isExistChildren) : ?>
                Есть еще подразделы...
            <? endif; ?>
            
            
        <? endif; ?>
        

        

    <? else : ?>

        <div class="row">
            <?php if ($model->children) : ?>
                <div class="sx-node-open-close">
                    <?php if ($widget->isOpenNode($model)) : ?>
                        <a href="<?= $widget->getOpenCloseLink($model); ?>" class="btn btn-sm btn-default">
                            <span class="fa fa-minus" title="<?= \Yii::t('skeeks/cms', "Minimize"); ?>"></span>
                        </a>
                    <?php else
                        : ?>
                        <a href="<?= $widget->getOpenCloseLink($model);
                        ?>" class="btn btn-sm btn-default">
                            <span class="fa fa-plus" title="<?= \Yii::t('skeeks/cms', "Restore"); ?>"></span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?= $widget->renderNodeContent($model); ?>
        </div>

        <!-- Construction of child elements -->
        <?php if ($widget->isOpenNode($model) && $model->children) : ?>
            <?= $widget->renderNodes($model->children); ?>
        <?php endif; ?>
    
    <? endif; ?>


    <?= \yii\helpers\Html::endTag('li'); ?>
<?php /*endif; */?>



