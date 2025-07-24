<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/**
 * @var $widget \skeeks\cms\widgets\admin\CmsLogListWidget
 */
$widget = $this->context;

if (!$widget->is_show_model) {

    $this->registerCss(<<<CSS
    .sx-log-list .sx-model {
        display: none !important;    
    }
CSS
);
}

$this->registerCss(<<<CSS


.sx-list .sx-files img {
    border-radius: var(--border-radius);
    max-width: 10rem;
    border: 1px solid var(--color-light-gray);
}

.sx-list .sx-controlls a {
    color: #a1a1a1;
}
.sx-log-list .sx-hidden-content .sx-hidden {
    display: none;
}
.sx-log-list .sx-right-btn {
    position: absolute;
    right: 1rem;
    bottom: 1rem;
}
.sx-log-list .sx-edit-btn {
    color: silver;
    cursor: pointer;
    opacity: 0;
    margin-right: 5px;
    transition: 0.4s;
}

.sx-log-list .sx-item {
    position: relative;
}
.sx-log-list .sx-item .sx-files .sx-file-item {
    margin-bottom: 0.25rem;
}
.sx-log-list .sx-item .sx-files .sx-files-block {
    background: var(--bg-color-light);
    padding: 1rem;
    border-radius: var(--border-radius);
}

.sx-log-list .sx-item .sx-files .sx-title {
    color: var(--color-gray);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    margin-top: 1rem;
}
.sx-log-list .sx-item:hover .sx-edit-btn {
    opacity: 1;
}

CSS
);

\skeeks\cms\assets\LinkActvationAsset::register($this);
$this->registerJs(<<<JS
new sx.classes.LinkActivation('.sx-comment-wrapper');
JS
);

$dataProvider = new \yii\data\ActiveDataProvider([
    'query' => $widget->query,
    'sort'       => [
        'defaultOrder' => [
            'created_at' => SORT_DESC,
        ],
    ],
    'pagination' => [
        'defaultPageSize' => 50,
    ],
]);

?>

<? echo \yii\widgets\ListView::widget(\yii\helpers\ArrayHelper::merge([
    'dataProvider' => $dataProvider,
    'itemView'     => '_log-list-item',
    'emptyText'    => '<div class="sx-block">Записей нет</div>',
    'options'      => [
        'class' => '',
        'tag'   => 'div',
    ],
    'itemOptions'  => [
        'tag'   => 'div',
        'class' => 'sx-item-wrapper col-12',
    ],
    'pager'        => [
        'container' => '.sx-list',
        'item'      => '.sx-item-wrapper',
        'class'     => \skeeks\cms\themes\unify\widgets\ScrollAndSpPager::class,
    ],
    //'summary'      => "Всего товаров: {totalCount}",
    'summary'      => false,
    //"\n{items}<div class=\"box-paging\">{pager}</div>{summary}<div class='sx-js-pagination'></div>",
    'layout'       => '<div class="row"><div class="col-md-12 sx-list-summary">{summary}</div></div>
    <div class="no-gutters row sx-list sx-log-list">{items}</div>
    <div class="row"><div class="col-md-12">{pager}</div></div>',
], (array) $widget->list_view_config))
?>
