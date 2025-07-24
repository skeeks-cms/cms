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
CSS
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
    <div class="col-12">
        <div class="d-flex">
            <button type="submit" class="btn btn-primary" style="margin-right: 1rem;">Отправить</button>
            <div class="sx-success-result my-auto" style="flex-grow: 1; color: green;"></div>
        </div>
    </div>

</div>
<?php echo $form->errorSummary([$log], ['header' => false]); ?>

<?php $form::end(); ?>
