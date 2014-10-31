<?php
/**
 * view
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */
use yii\widgets\DetailView;
/* @var $this yii\web\View */
/* @var $model common\models\Game */
?>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'name',
        'description_short:ntext',
        'description_full:ntext',
        'meta_title',
        'meta_description:ntext',
        'meta_keywords:ntext',
        'developer',
        'publisher',
        'seo_page_name',
        //'genre_ids',
        //'platform_ids',
        //'developer_ids',
        //'publisher_ids',
        /*'album_image_id',
        'album_file_id',
        'count_comment',
        'count_subscribe',
        'count_vote',
        'count_vote_up',*/
    ],
]) ?>

