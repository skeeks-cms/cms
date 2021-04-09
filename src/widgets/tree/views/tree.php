<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.12.2016
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget */
$widget = $this->context;

$params = \Yii::$app->request->getQueryParams();

$this->registerJs(<<<JS
$(".sx-tree-search input").on("change", function() {
    console.log("change");
    var jTreeSearch = $(this).closest(".sx-tree-search");
    $(".btn-search", jTreeSearch).click();
    
});

$(".sx-tree-search .btn").on("click", function() {
    
    var jTreeSearch = $(this).closest(".sx-tree-search");
    var search = $("input", jTreeSearch).val();
    
    var jPjax = $(this).closest("[data-pjax-container]");
    
    var url = new URL(window.location.href);
    url.searchParams.set($("input", jTreeSearch).attr("name"), search);
    
    /*var urlParams = new URLSearchParams(window.location.search);
    urlParams.set($("input", jTreeSearch).attr("name"), search);
    window.location.search = urlParams;*/
    
    $.pjax({url: url.pathname + url.search, container: '#' + jPjax.attr("id")});
    return false;
    
});
JS
);
?>
<div class="row">
    <div class="sx-container-tree">
        <?= \yii\helpers\Html::beginTag("div", $widget->options); ?>

        <? if ($widget->isSearchEnabled) : ?>
            <div class="sx-tree-search" style="max-width: 300px; margin-bottom: 10px;">
                <input type="text" value="<?php echo $widget->searchValue; ?>" name="<?php echo $widget->searchRequestName; ?>" class="form-control" placeholder="Поиск по разделам"/>
                <button class="btn btn-search">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        <? endif; ?>
        <div class="cms-tree-wrapper">
            <?= $widget->renderNodes($widget->models); ?>
        </div>

        <?= \yii\helpers\Html::endTag("div"); ?>
    </div>
</div>

