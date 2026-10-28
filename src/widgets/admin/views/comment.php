<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/**
 * @var $widget \skeeks\cms\widgets\admin\CmsCommentWidget
 */
$widget = $this->context;


?>

<?php
$log = new \skeeks\cms\models\CmsLog();
$log->model_code = $widget->model->skeeksModelCode;
$log->model_id = $widget->model->id;
$isPjax = (int) $widget->isPjax;
$pinInputId = \yii\helpers\Html::getInputId($log, 'is_pinned');
$pinLabel = $widget->pinnedLabel ?: $log->getAttributeLabel('is_pinned');

$form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
    'action'                 => \yii\helpers\Url::to($widget->backend_url),
    'enableAjaxValidation'   => false,
    'enableClientValidation' => false,
    'options'                => [
        'class' => 'sx-comment-form',
    ],
    'clientCallback'         => new \yii\web\JsExpression(<<<JS
function (ActiveFormAjaxSubmit) {
    
    ActiveFormAjaxSubmit.on('success', function(e, response) {

        var isPjax = {$isPjax};
        
        ActiveFormAjaxSubmit.AjaxQueryHandler.set("allowResponseSuccessMessage", false);
        ActiveFormAjaxSubmit.AjaxQueryHandler.set("allowResponseErrorMessage", false);
        
        $(".sx-success-result", ActiveFormAjaxSubmit.jForm).empty().append("<div class='sx-message'>✓ " + response.message + "</div>");
        
        var jPjax = ActiveFormAjaxSubmit.jForm.closest("[data-pjax-container]");
        
        if (jPjax.length) {
            console.log(jPjax);
            setTimeout(function() {
                $.pjax.reload({ container: "#" + jPjax.attr("id"), async:false });
            }, 1000);
        } else {
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        }
        
        
    });
    
    ActiveFormAjaxSubmit.on('error', function(e, response) {
        ActiveFormAjaxSubmit.AjaxQueryHandler.set("allowResponseSuccessMessage", false);
        ActiveFormAjaxSubmit.AjaxQueryHandler.set("allowResponseErrorMessage", false);
        
        $(".error-summary ul", ActiveFormAjaxSubmit.jForm).empty().append("<li>" +  response.message + "</li>");
        $(".error-summary", ActiveFormAjaxSubmit.jForm).show();
    });
}
JS
    ),
]); ?>

<div class="sx-comment-form__layout">
    <div class="sx-comment-form__editor">
        <?php

        // The comment is cleaned with the same preset in AdminCmsLogController::addComment().
        echo $form->field($log, "comment")->widget(
            \skeeks\cms\widgets\formInputs\richText\RichTextInputWidget::class,
            [
                'preset'      => 'basic',
                'paragraph'   => 'div',
                'minHeight'   => 110,
                'placeholder' => $widget->placeholder,
                // Screenshots and files given to the text go to the attachments below.
                'attachLabel' => $widget->isShowAttachments ? 'Прикрепить файлом' : false,
            ])->label(false); ?>
    </div>

    <?php echo \yii\helpers\Html::activeHiddenInput($log, 'model_code'); ?>
    <?php echo \yii\helpers\Html::activeHiddenInput($log, 'model_id'); ?>

    <?php if ($widget->isShowAttachments) : ?>
        <?php // Files wait in the temporary area; HasStorageFileMulti stores them with the comment. ?>
        <?php echo \skeeks\cms\widgets\formInputs\attachments\AttachmentsInputWidget::widget([
            'model'   => $log,
            'attribute' => 'fileIds',
            'options' => ['class' => 'sx-comment-form__attachments'],
        ]); ?>
    <?php endif; ?>
    <?php // One row, as in the letter form: send, attach, pin. ?>
    <div class="sx-comment-actions">
        <button type="submit" class="sx-button sx-button--primary">Отправить</button>
        <?php if ($widget->isShowCancel) : ?>
            <button type="button" class="sx-button sx-button--secondary" data-sx-comment-cancel>Отмена</button>
        <?php endif; ?>
        <?php if ($widget->isShowAttachments) : ?>
            <?php echo \skeeks\cms\widgets\formInputs\attachments\AttachmentsInputWidget::button(); ?>
        <?php endif; ?>
        <?php if ($widget->isShowPin) : ?>
            <div class="sx-comment-pin-field">
                <?php echo \yii\helpers\Html::activeHiddenInput($log, 'is_pinned', [
                    'id' => $pinInputId,
                    'value' => 0,
                ]); ?>
                <?php // A quiet toggle like "Прикрепить": the pin and the label, no frame; pressed — accent. ?>
                <button type="button"
                        class="sx-comment-pin-toggle"
                        data-input="<?php echo $pinInputId; ?>"
                        aria-pressed="false">
                    <?php echo \skeeks\cms\backend\helpers\BackendIcon::render('pin', ['size' => 16]); ?>
                    <span><?php echo \yii\helpers\Html::encode($pinLabel); ?></span>
                </button>
            </div>
        <?php endif; ?>
        <div class="sx-success-result sx-text--success" aria-live="polite"></div>
    </div>

</div>
<?php echo $form->errorSummary([$log], ['header' => false]); ?>

<?php $form::end(); ?>
