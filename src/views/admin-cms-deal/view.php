<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsDeal */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;
$this->render("@skeeks/cms/shop/views/admin-shop-store-doc-move/view-css");
?>

<!--<div class="row">
    <div class="col-12">
        <h5>Данные платежа</h5>
    </div>
</div>-->

<div class="sx-properties-wrapper sx-columns-1 sx-block" style="max-width: 700px;">
    <ul class="sx-properties" style="padding: 10px;">



        <li>
            <span class="sx-properties--name">
                Активность
            </span>
            <span class="sx-properties--value">
                <?php if($model->is_active) : ?>
                    <?php if($model->end_at) : ?>
                        <?php if(time() > $model->end_at) : ?>
                            <span style="color: red; font-weight: bold">Просрочена! (<?php echo \Yii::$app->formatter->asDate($model->end_at); ?>)</span>
                        <?php else : ?>
                            <span style="color: green;">Активна до (<?php echo \Yii::$app->formatter->asDate($model->end_at); ?>)</span>
                        <?php endif; ?>

                    <?php else : ?>
                        <span style="color: green;">Активна</span>
                    <?php endif; ?>

                <?php else : ?>
                    Не активна
                <?php endif; ?>


            </span>
        </li>


        <li>
            <span class="sx-properties--name">
                Тип сделки
            </span>
            <span class="sx-properties--value">
                <?php echo $model->dealType->name;  ?>
            </span>
        </li>




        <li>
            <span class="sx-properties--name">
                Сумма
            </span>
            <span class="sx-properties--value">

                <?php
                    echo "{$model->moneyAsText}";
                ?>
            </span>
        </li>

        <?php if($model->cms_user_id) : ?>
            <li>
                <span class="sx-properties--name">
                    Контрагент
                </span>
                <span class="sx-properties--value">
                    <?php echo \skeeks\cms\widgets\admin\CmsUserViewWidget::widget(['cmsUser' => $model->cmsUser]); ?>
                </span>
            </li>
        <?php endif; ?>

        <?php if($model->cms_company_id) : ?>
            <li>
                <span class="sx-properties--name">
                    Компания
                </span>
                <span class="sx-properties--value">
                    <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                        'controllerId'            => '/cms/admin-cms-company',
                        'modelId'                 => $model->company->id,
                        'isRunFirstActionOnClick' => true,
                        'options'                 => [
                            'class' => 'sx-dashed',
                            'style' => 'cursor: pointer; border-bottom: 1px dashed;',
                        ],
                    ]); ?>
                        <?php echo $model->company->name; ?>
                    <?php $widget::end(); ?>
                </span>
            </li>
        <?php endif; ?>



        <li>
            <span class="sx-properties--name">
                Название
            </span>
            <span class="sx-properties--value">
                <?php echo $model->name;  ?>
            </span>
        </li>

        <?php if($model->description) : ?>
            <li>
                <span class="sx-properties--name">
                    Описание
                </span>
                <span class="sx-properties--value">
                    <?php echo $model->description;  ?>
                </span>
            </li>
        <?php endif; ?>







    </ul>
</div>

