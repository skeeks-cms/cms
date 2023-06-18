<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */



$this->registerCSS(<<<CSS
.sx-main-col {
    background: var(--bg-gray);
}
.sx-project-content {
    background: white;
    border-radius: 24px;
    padding: 20px 30px 20px;
    margin-bottom: 20px;
    max-width: 800px;
}
.sx-title-block {
    margin-bottom: 12px; 
}
.sx-back a {
    font-style: normal;
    font-weight: 400;
    font-size: 12px;
    line-height: 26px;
    color: #656464;
}
.sx-small-info {
    color: silver;
    font-size: 0.9rem;
}


CSS
);
?>


<div class="sx-title-block d-flex">
    <div class="my-auto" style="width: 100%;">
        <h1>Действия пользователей</h1>
    </div>
</div>

<? echo \yii\widgets\ListView::widget([
    'dataProvider' => new \yii\data\ActiveDataProvider([
        'query' => \skeeks\cms\models\CmsUserLog::find()
            ->andWhere(['is not', 'created_by', null])
            ->orderBy(['id' => SORT_DESC])
            ->cmsSite()
    ]),
    'itemView'     => '_log-item',
    'emptyText'    => '',
    'options'      => [
        'class' => '',
        'tag'   => 'div',
    ],
    'itemOptions'  => [
        'tag'   => 'div',
        'class' => 'sx-log-wrapper col-12',
    ],
    'pager'        => [
        'container' => '.sx-log-list',
        'item'      => '.sx-log-wrapper',
        'class'     => \skeeks\cms\themes\unify\widgets\ScrollAndSpPager::class,
    ],
    //'summary'      => "Всего товаров: {totalCount}",
    'summary'      => false,
    //"\n{items}<div class=\"box-paging\">{pager}</div>{summary}<div class='sx-js-pagination'></div>",
    'layout'       => '<div class="row"><div class="col-md-12 sx-log-list-summary">{summary}</div></div>
    <div class="no-gutters row sx-log-list">{items}</div>
    <div class="row"><div class="col-md-12">{pager}</div></div>',
])
?>


