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

        echo $form->field($log, "comment")->widget(
            \skeeks\yii2\ckeditor\CKEditorWidget::class,
            [
                'preset'        => false,
                'clientOptions' => [
                    'enterMode' => 2,
                    'placeholder' => 'test',
                    'editorplaceholder' => 'test',
                    /*'editorplaceholder' => 'test',*/
                    /*'placeholder' => 'test',
                    'editorplaceholder' => 'test',*/
                    'height'    => 120,
                    'allowedContent' => false,
                    //'extraPlugins'   => 'ckwebspeech,lineutils,dialogui',
                    'toolbar'   => [
                        [
                            'name'   => 'basicstyles',
                            'groups' => ['basicstyles', 'cleanup'],
                            'items'  => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'],
                        ],
                        [
                            'name'   => 'paragraph',
                            'groups' => ['list', 'indent', 'blocks', 'align', 'bidi'],
                            'items'  => [
                                'NumberedList',
                                'BulletedList',
                                '-',
                                'Blockquote',
                                '-',
                                'JustifyLeft',
                                'JustifyCenter',
                                'JustifyRight',
                                'JustifyBlock',
                            ],
                        ],

                        /*[
                            'name'   => 'paragraph',
                            'groups' => ['list', 'indent', 'blocks', 'align', 'bidi'],
                            'items'  => [
                                'NumberedList',
                                'BulletedList',
                                '-',
                                'Blockquote',
                                '-',
                                'JustifyLeft',
                                'JustifyCenter',
                                'JustifyRight',
                                'JustifyBlock',

                            ],
                        ],*/
                        ['name' => 'links', 'items' => ['Link','Unlink']],

                    ],
                ],

            ])->label(false); ?>
    </div>

    <?php echo \yii\helpers\Html::activeHiddenInput($log, 'model_code'); ?>
    <?php echo \yii\helpers\Html::activeHiddenInput($log, 'model_id'); ?>

    <div class="sx-comment-form__attachments">
        <?php echo $form->field($log, "fileIds")->widget(\skeeks\cms\widgets\AjaxFileUploadWidget::class, [
            //'accept'            => 'image/*',
            'multiple' => true,
            /*'is_show_file_info' => false,
            'is_allow_deselect' => false,
            'tools'             => [
                'remote' => new \yii\helpers\UnsetArrayValue(),
            ],*/
        ])->label(false); ?>
    </div>
    <div class="sx-comment-pin-field">
        <?php echo \yii\helpers\Html::activeHiddenInput($log, 'is_pinned', [
            'id' => $pinInputId,
            'value' => 0,
        ]); ?>
        <button type="button"
                class="sx-button sx-button--secondary sx-button--sm sx-comment-pin-toggle"
                data-input="<?php echo $pinInputId; ?>"
                aria-pressed="false">
            <i class="fas fa-thumbtack"></i>
            <span><?php echo \yii\helpers\Html::encode($pinLabel); ?></span>
        </button>
    </div>
    <div class="sx-comment-actions">
        <button type="submit" class="sx-button sx-button--primary">Отправить</button>
        <div class="sx-success-result sx-text--success" aria-live="polite"></div>
    </div>

</div>
<?php echo $form->errorSummary([$log], ['header' => false]); ?>

<?php $form::end(); ?>
