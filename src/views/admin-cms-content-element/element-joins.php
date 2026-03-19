<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */

/* @var $controller \skeeks\cms\shop\controllers\AdminCmsContentElementController */
/* @var $model \skeeks\cms\models\CmsContentElement */
$controller = $this->context;
$model = $controller->model;

$this->registerCss(<<<CSS
    .card-prod--photo img {
        max-width: 100%;
    }
    .card-prod {
        margin-top: 1rem;
        margin-right: 1rem;
        margin-bottom: 2rem;
    }
.card-prod--title {
    line-height: 1.1;
}
.card-prod--photo {
    margin-bottom: 0.5rem;
}
.card-prod--photo img {
    border-radius: 0.5rem;
}
CSS
);

$ajaxBackendUrl = \yii\helpers\Url::to(['element-joins-dettach', 'content_id' => $model->content_id]);
$modelId = $model->id;

$this->registerJs(<<<JS
    var modelId = {$modelId};
    $("body").on("click", ".sx-btn-dettach", function() {
        var jProduct = $(this).closest(".product-item");
        var product2_id = jProduct.data("key");
        
        var Ajax = sx.ajax.preparePostQuery("$ajaxBackendUrl", {
            'product_id': product2_id,
        });
        Ajax.execute();
        
        jProduct.fadeOut("500", function() {
            jProduct.remove();
        });
        return false; 
    });
JS
);

?>


<?

if ($product_ids = \Yii::$app->request->post("product_ids")) {
    /*print_r($product_ids);die;*/
    if ($product_ids) {

        $model->joinToModel($product_ids);

    }
}

?>

<?php $contents = \skeeks\cms\models\CmsContent::find()->all(); ?>
<?php if ($contents) : ?>
    <?php foreach ($contents as $content) : ?>


            <div class="sx-block">
            <? $form = \yii\widgets\ActiveForm::begin(); ?>
                <h3><?php echo $content->name; ?></h3>
            <p>Выберите <?php echo $content->name; ?> и нажмите кнопку "Связать"</p>
            <div class="row">
                <div class="col-auto my-auto">
                    <?
                    echo \skeeks\cms\backend\widgets\SelectModelDialogContentElementWidget::widget([
                        'name'                   => 'product_ids',
                        'multiple'               => true,
                        'closeDialogAfterSelect' => false,
                        'selectBtn'              => [
                            'content' => '<i class="fa fa-list" aria-hidden="true"></i> Выбрать',
                        ],
                        'content_id'             => $content->id,
                        'dialogRoute'            => [
                            '/cms/admin-cms-content-element',
                        ],
                    ]);
                    ?>
                </div>
                <div class="col-auto my-auto">
                    <button type="submit" class="btn btn-primary">Связать</button>
                </div>
            </div>
            <? $form::end(); ?>


                <?php


if ($model->cms_content_model_id) {

    $dataProvider = new \yii\data\ActiveDataProvider();
    $dataProvider->query = \skeeks\cms\models\CmsContentElement::find()->contentId($content->id);

    $dataProvider->query->andWhere(['cms_content_model_id' => $model->cms_content_model_id]);
    $dataProvider->query->andWhere(['!=', 'id', $model->id]);
    $dataProvider->query->groupBy(['id']);
}


if ($model->cms_content_model_id) {
    echo \yii\widgets\ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView'     => 'element-item',
        'emptyText'    => '',
        'options'      => [
            'class' => '',
            'tag'   => 'div',
        ],
        'itemOptions'  => [
            'tag'   => 'div',
            'class' => 'col-lg-2 col-sm-6 product-item',
        ],
        'pager'        => [
            'container' => '.list-view-products',
            'item'      => '.product-item',
            'class'     => \skeeks\cms\themes\unify\widgets\ScrollAndSpPager::class,
        ],
        //"\n{items}<div class=\"box-paging\">{pager}</div>{summary}<div class='sx-js-pagination'></div>",
        'layout'       => '<div class="row"><div class="col-md-12">{summary}</div></div>
    <div class="no-gutters row list-view-products">{items}</div>
    <div class="row"><div class="col-md-12">{pager}</div></div>',
    ]);

}
?>

        </div>

    <?php endforeach; ?>
<?php endif; ?>






