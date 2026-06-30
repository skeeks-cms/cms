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

<div class="row">
    <div class="col-12">
        <?php

        $this->registerCss(<<<CSS
.cke_bottom {
background: none !important;
border-top: none !important;
}
.cke_path {
display: none !important;
}
.cke_chrome {
box-shadow: none !important;
border: 1px solid #C2C2C2 !important;
border-radius: 0.5rem !important;
}
.cke_top {
background: none !important;
border-bottom: 1px solid #C2C2C2 !important;
border-radius: 0.5rem !important;
}
.cke_inner {
border-radius: 0.5rem !important;
}
.cke_toolgroup {
background: none !important;
border: none !important;
}
.sx-comment-pin-field {
    margin: 0 0 1rem 0;
}
.sx-comment-pin-toggle {
    align-items: center;
    background: #f5f7fa;
    border: 1px solid #dfe5ec;
    border-radius: 999px;
    color: #667085;
    cursor: pointer;
    display: inline-flex;
    font-size: 0.88rem;
    font-weight: 600;
    gap: 0.45rem;
    line-height: 1;
    margin: 0;
    padding: 0.55rem 0.85rem;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}
.sx-comment-pin-toggle:hover {
    background: #edf6ff;
    border-color: #b8d8f4;
    color: #1e6ba8;
}
.sx-comment-pin-toggle:focus,
.sx-comment-pin-toggle:active {
    outline: none;
}
.sx-comment-pin-toggle.is-active {
    background: #e8f7f4;
    border-color: #9edbd0;
    color: #11806f;
}
CSS
        );
        $this->registerJs(<<<JS
$("body").off("click.sxCommentPinToggle").on("click.sxCommentPinToggle", ".sx-comment-pin-toggle", function(e) {
    e.preventDefault();

    var jBtn = $(this);
    var jInput = $("#" + jBtn.data("input"));
    var isActive = !jBtn.hasClass("is-active");

    jBtn.toggleClass("is-active", isActive);
    jInput.val(isActive ? 1 : 0);

    return false;
});
JS
        );
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

    <div class="col-12">
        <div style="display: none;">
            <?php echo $form->field($log, "model_code"); ?>
            <?php echo $form->field($log, "model_id"); ?>
        </div>
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
    <div class="col-12 sx-comment-pin-field">
        <?php echo \yii\helpers\Html::activeHiddenInput($log, 'is_pinned', [
            'id' => $pinInputId,
            'value' => 0,
        ]); ?>
        <button type="button" class="sx-comment-pin-toggle" data-input="<?php echo $pinInputId; ?>">
            <i class="fas fa-thumbtack"></i>
            <span><?php echo \yii\helpers\Html::encode($pinLabel); ?></span>
        </button>
    </div>
    <div class="col-12">
        <div class="d-flex">
            <button type="submit" class="btn btn-primary" style="margin-right: 1rem;">Отправить</button>
            <div class="sx-success-result my-auto" style="flex-grow: 1; color: green;"></div>
        </div>
    </div>

</div>
<?php echo $form->errorSummary([$log], ['header' => false]); ?>

<?php $form::end(); ?>
