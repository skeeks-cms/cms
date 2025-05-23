<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsProject */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;
?>


    <div class="sx-block">
        <?php if ($model->description) : ?>
            <div style="margin-bottom: 1rem;"><?php echo $model->description; ?></div>
        <?php endif; ?>


        <div class="sx-properties-wrapper sx-columns-1">
            <ul class="sx-properties">
                <!--<li>
                <span class="sx-properties--name">
                    Создан
                </span>
                    <span class="sx-properties--value">
                    <?php /*echo \Yii::$app->formatter->asDate($model->created_at) */ ?>
                </span>
                </li>-->


                <li>
                <span class="sx-properties--name">
                    Тип проекта
                </span>
                    <span class="sx-properties--value">
    
                    <?php if ($model->is_private) : ?>
                        Закрытый
                    <?php else : ?>
                        Открытый
                    <?php endif; ?>
    
                </span>
                </li>

                <?php if ($model->cms_company_id) : ?>
                <li>
                    <span class="sx-properties--name">
                        Компания
                    </span>
                    <span class="sx-properties--value">

                            <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                'controllerId'            => '/cms/admin-cms-company',
                                'modelId'                 => $model->cmsCompany->id,
                                'isRunFirstActionOnClick' => true,
                                'options'                 => [
                                    'class' => 'sx-dashed',
                                    'style' => 'cursor: pointer; border-bottom: 1px dashed;',
                                ],
                            ]); ?>
                            <?php echo $model->cmsCompany->name; ?>
                            <?php $widget::end(); ?>
                    </span>
                </li>
                <?php endif; ?>

                <?php if ($model->managers) : ?>
                    <li>
                <span class="sx-properties--name">
                    Работают с проектом
                </span>
                        <span class="sx-properties--value">
                    <?php foreach ($model->managers as $manager) : ?>
                        <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget(['user' => $manager, "isSmall" => true]); ?>
                    <?php endforeach; ?>
                </span>
                    </li>
                <?php endif; ?>

                <?php if ($model->users) : ?>
                    <li>
                <span class="sx-properties--name">
                    Работают с проектом
                </span>
                        <span class="sx-properties--value">
                    <?php foreach ($model->users as $user) : ?>
                        <?php echo \skeeks\cms\widgets\admin\CmsUserViewWidget::widget(['cmsUser' => $user, "isSmall" => true]); ?>
                    <?php endforeach; ?>
                </span>
                    </li>
                <?php endif; ?>


                <li>
                <span class="sx-properties--name">
                    Количество задач
                </span>
                    <span class="sx-properties--value">
    
                    <?php
                    $count = $model->getTasks()->count();
                    if ($count) : ?>
                        <?php echo \Yii::$app->formatter->asInteger($count); ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
    
                </span>
                </li>
            </ul>
        </div>


    </div>

<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-comments',
]); ?>

    <div class="row">
        <div class="col-12">
            <div class="sx-block">
                <?php echo \skeeks\cms\widgets\admin\CmsCommentWidget::widget([
                    'model' => $model,
                ]); ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                'query'         => $model->getLogs()->comments(),
                'is_show_model' => false,
            ]); ?>
        </div>
    </div>

<?php $pjax::end(); ?>