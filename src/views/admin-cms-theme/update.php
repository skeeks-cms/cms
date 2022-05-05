<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
 * @var $this yii\web\View
 * @var $cmsTheme \skeeks\cms\models\CmsTheme
 */
$this->registerCss(<<<CSS
.sx-row .sx-label {
    color: gray;
    line-height: 1;
    font-size: 12px;
}
.sx-row {
    margin: 10px 0;
}
.sx-green {
    color: green;
}

.sx-back a {
    font-size: 12px;
    line-height: 1;
    color: #656464;
}

CSS
);
?>
<div class="sx-design">
    <div class="sx-back">
        <a href="<?php echo \yii\helpers\Url::to(['index']); ?>">
            &larr;&nbsp;Вернуться назад
        </a>
    </div>

    <h1>Тема «<?php echo $cmsTheme->themeName; ?>»</h1>
    <?php if ($cmsTheme->is_active) : ?>
        <div class="alert-default alert sx-green">
            ✓ В данный момент эта тема используется на вашем сайте!
        </div>
    <?php else : ?>
        <div class="alert-default alert">
            В данный момент эта тема не используется на вашем сайте
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-9">


            <? $form = \skeeks\cms\backend\widgets\ActiveFormAjaxBackend::begin([
                    'clientCallback' => new \yii\web\JsExpression(<<<JS
                    function (ActiveFormAjaxSubmit) {
                        ActiveFormAjaxSubmit.on('success', function(e, response) {
                            $("#sx-result").empty();
                
                            if (response.data.html) {
                                $("#sx-result").append(response.data.html);
                            }
                        });
                        ActiveFormAjaxSubmit.on('error', function(e, response) {
                            alert("111");
                        });
                    }
JS
                    )

            ]); ?>
            <?= $form->errorSummary($configModel); ?>

            <? if ($fields = $configModel->builderFields()) : ?>
                <?
                echo (new \skeeks\yii2\form\Builder([
                    'models'     => $configModel->builderModels(),
                    'model'      => $configModel,
                    'activeForm' => $form,
                    'fields'     => $configModel->builderFields(),
                ]))->render(); ?>

            <? else : ?>
                У данной темы нет настроек!
            <? endif; ?>

            <?= $form->errorSummary($configModel); ?>
            <?= $form->buttonsStandart($configModel, ['apply']); ?>

            <? $form::end(); ?>


        </div>
        <div class="col-3">
            <div class="alert-default alert">
                <b>Данные темы</b>
                <div class="sx-row">
                    <div class="sx-label">Название
                    </div>
                    <div class="sx-value"><?php echo $cmsTheme->themeName; ?></div>
                </div>
                
                <div class="sx-row">
                    <div class="sx-label">Код
                        <i style="color: silver;" data-toggle="tooltip" data-html="true"
                           title="Уникальный код темы, обычно его используют программисты настраивая тему!"
                           class="far fa-question-circle"></i>
                    </div>
                    <div class="sx-value"><?php echo $cmsTheme->code; ?></div>
                </div>
                <?php if($cmsTheme->themeDescription) : ?>
                    <div class="sx-row">
                        <div class="sx-label">Описание
                        </div>
                        <div class="sx-value"><?php echo $cmsTheme->themeDescription; ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="sx-row">
                    <div class="sx-label">Дирректории <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                         title="Пути к файлам шаблона, обычно используют программисты настраивая тему!"
                                                         class="far fa-question-circle"></i></div>
                    <?php $paths = \yii\helpers\ArrayHelper::getValue($cmsTheme->objectTheme->pathMap, "@app/views"); ?>
                    <?php if ($paths) : ?>
                        <?php foreach ($paths as $path) : ?>
                            <p><?php echo $path; ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="sx-row">
                    <?php if (!$cmsTheme->is_active) : ?>
                        <a href="<?php echo \yii\helpers\Url::to(['enable', 'code' => $cmsTheme->code]); ?>" class="btn btn-primary">
                            ✓ Включить тему
                        </a>

                        <i style="color: silver;" data-toggle="tooltip" data-html="true"
                           title="Нажимая на эту кнопку, вы примените данную тему, вместе со всеми параметрами к вашему сайту!"
                           class="far fa-question-circle"></i>
                    <?php else : ?>
                        <div class="sx-green">
                            ✓ Тема подключена!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
