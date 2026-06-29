<?php
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsLog
 * @var $is_show_pin_controls bool
 */
$log = $model;
$is_show_pin_controls = isset($is_show_pin_controls) ? (bool)$is_show_pin_controls : false;
$isTaskLog = $log->model_code == \skeeks\cms\models\CmsTask::class;
$pinLabel = $isTaskLog ? 'Результат задачи' : 'Закрепить';
$unpinLabel = $isTaskLog ? 'Убрать из результата' : 'Открепить';
$togglePinUrl = \yii\helpers\Url::to(['/cms/admin-cms-log/toggle-pin']);
?>
<div class="sx-block sx-item">
    <div class="sx-controlls d-flex" style="margin-bottom: 0.25rem;">
        <div class="d-flex sx-log-meta" style="flex-grow: 1;">
            <div class="sx-log-meta-item"><?php echo \Yii::$app->formatter->asDatetime($log->created_at); ?></div>
            <div class="sx-log-meta-item"><?php echo $log->logTypeAsText; ?></div>
            <?php if ($is_show_pin_controls) : ?>
            <button type="button"
                    class="sx-log-pin-toggle <?php echo $log->is_pinned ? 'is-pinned' : ''; ?>"
                    data-id="<?php echo (int)$log->id; ?>"
                    data-url="<?php echo $togglePinUrl; ?>"
                    data-value="<?php echo $log->is_pinned ? 0 : 1; ?>"
                    title="<?php echo \yii\helpers\Html::encode($log->is_pinned ? $unpinLabel : $pinLabel); ?>">
                <i class="fas fa-thumbtack"></i>
                <span><?php echo $log->is_pinned ? 'Закреплено' : $pinLabel; ?></span>
            </button>
            <?php endif; ?>
        </div>
        <div style="margin-right: 1rem; font-size: 0.8rem; color: #a1a1a1;" class="my-auto">
            <?php
            if ($log->createdBy) {
                $u = $log->createdBy;
                $u->post = null;
                echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                    'user'    => $u,
                    'isSmall' => true,
                ]);
            }
            ?>
        </div>
    </div>
    <div class="sx-headers"></div>
    <div class="">

        <?php if ($log->model && $log->model->id != $model->id) : ?>
            <div class="d-flex sx-model" style="flex-grow: 1;">
                <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                    'controllerId'            => \yii\helpers\ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$log->model_code, 'controller']),
                    'modelId'                 => $log->model->id,
                    'isRunFirstActionOnClick' => true,
                    'options'                 => [
                        'class' => 'sx-dashed',
                        'style' => 'cursor: pointer; border-bottom: 1px dashed; color: #a1a1a1;',
                    ],
                ]); ?>
                <?php echo $log->model->asText; ?>
                <?php $widget::end(); ?>
            </div>
        <?php endif; ?>

        <?php if ($log->subModel) : ?>
            <div class="d-flex" style="flex-grow: 1;">
                <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                    'controllerId'            => \yii\helpers\ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$log->sub_model_code, 'controller']),
                    'modelId'                 => $log->subModel->id,
                    'isRunFirstActionOnClick' => true,
                    'options'                 => [
                        'class' => 'sx-dashed',
                        'style' => 'cursor: pointer; border-bottom: 1px dashed; color: #a1a1a1;',
                    ],
                ]); ?>
                <?php echo $log->subModel->asText; ?>
                <?php $widget::end(); ?>
            </div>
        <?php endif; ?>

        <div class="sx-comment-wrapper">
            <?php echo $model->render(); ?>
        </div>

        <?php if ($model->files) : ?>

            <?
            $files = $model->files;
            $images = [];

            foreach ($files as $key => $file) {
                if ($file->isImage()) {
                    $images[] = $file;
                    unset($files[$key]);
                }
            }
            ?>
            <div class="sx-files">
                <?php if ($images) : ?>
                    <div class="sx-title">Приложенные изображения:</div>
                    <div class="sx-files-block">
                        <? foreach ($images as $key => $file) : ?>
                            <a href="<?php echo $file->src; ?>" target="_blank" data-pjax="0">
                                <img src="<?php echo $file->src; ?>"/>
                            </a>
                        <? endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($files) : ?>
                    <div class="sx-title">Приложенные файлы:</div>
                    <div class="sx-files-block">
                        <? foreach ($files as $key => $file) : ?>
                        <div class="sx-file-item">
                            <a href="<?php echo $file->src; ?>" download="<?php echo $file->original_name; ?>" target="_blank" data-pjax="0"><?php echo $file->original_name; ?></a>
                            <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-storage-files/download', 'pk' => $file->id]); ?>" target="_blank" data-pjax="0" class="btn btn-xs btn-default">скачать (<?php echo \Yii::$app->formatter->asShortSize($file->size); ?>)</a>
                        </div>
                        <? endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="sx-right-btn">
        <?
        \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
            'controllerId' => "/cms/admin-cms-log",
            'modelId'      => $model->id,
            'tag'          => 'div',
            'options'      => [
                'title' => 'Редактировать',
                'class' => 'sx-edit-btn btn btn-default',
            ],
        ]);
        ?>
        <i class="fas fa-ellipsis-v"></i>
        <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>
    </div>
</div>
