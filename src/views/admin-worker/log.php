<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsUser */

$controller = $this->context;
$action = $controller->action;
$model = $action->model;
?>

<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-worker-logs',
]); ?>

    <div class="row">
        <div class="col-12">
            <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                'query' => \skeeks\cms\models\CmsLog::find()->andWhere(['created_by' => $model->id]),
                'is_show_model' => true,
            ]); ?>
        </div>
    </div>

<?php $pjax::end(); ?>
