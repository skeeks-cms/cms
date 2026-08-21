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

$pageSize = 50;
$targetPage = null;
$targetLogId = (int)\Yii::$app->request->get('sx-log-id');

if ($targetLogId && $widget->query) {
    $targetLog = (clone $widget->query)
        ->andWhere(['id' => $targetLogId])
        ->one();

    if ($targetLog) {
        $beforeCount = (clone $widget->query)
            ->andWhere([
                'or',
                ['>', 'created_at', $targetLog->created_at],
                [
                    'and',
                    ['created_at' => $targetLog->created_at],
                    ['>', 'id', $targetLog->id],
                ],
            ])
            ->count();
        $targetPage = (int)floor($beforeCount / $pageSize);
    }
}

$pagination = [
    'defaultPageSize' => $pageSize,
];
if ($targetPage !== null) {
    $pagination['page'] = $targetPage;
}

$dataProvider = new \yii\data\ActiveDataProvider([
    'query' => $widget->query,
    'sort'       => [
        'defaultOrder' => [
            'created_at' => SORT_DESC,
            'id'         => SORT_DESC,
        ],
    ],
    'pagination' => $pagination,
]);

$logListClass = $widget->is_show_model
    ? 'sx-log-list'
    : 'sx-log-list sx-log-list--hide-model';

?>

<? echo \yii\widgets\ListView::widget(\yii\helpers\ArrayHelper::merge([
    'dataProvider' => $dataProvider,
    'itemView'     => '_log-list-item',
    'viewParams'   => [
        'is_show_pin_controls' => (bool)$widget->is_show_pin_controls,
        'is_raised'            => (bool)$widget->is_raised,
    ],
    'emptyText'    => false,
    'options'      => [
        'class' => 'sx-log-list-view',
        'tag'   => 'div',
    ],
    'itemOptions'  => [
        'tag'   => 'div',
        'class' => 'sx-item-wrapper sx-log-list__item',
    ],
    'pager'        => [
        'container' => '.sx-list',
        'item'      => '.sx-item-wrapper',
        'class'     => \skeeks\cms\backend\widgets\BackendScrollAndSpPager::class,
    ],
    //'summary'      => "Всего товаров: {totalCount}",
    'summary'      => false,
    //"\n{items}<div class=\"box-paging\">{pager}</div>{summary}<div class='sx-js-pagination'></div>",
    'layout'       => '<div class="sx-list-summary sx-log-list__summary">{summary}</div>
    <div class="sx-list '.$logListClass.'">{items}</div>
    <div class="sx-log-list__pager">{pager}</div>',
], (array) $widget->list_view_config))
?>
