<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\relatedProperties\models\RelatedElementModel */
?>

<div class="sx-box sx-mt-10">
    <div class="sx-box-head sx-p-10">
        <div class="row">
            <div class="col-md-12">
                <h2 class="pull-left">Дополнительные свойства</h2>

                <a class="btn btn-default pull-right btn-xs" href="#" onclick="sx.dialog({
                    'title': 'Справка',
                    'content' : '<p>Вы можете создавать самостоятельно любые свойства.</p>'
                }); return false;">
                    <i class="glyphicon glyphicon-question-sign"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="sx-box-body sx-p-10">
        <? if ($model->relatedProperties) : ?>
            <?= $model->renderRelatedPropertiesForm(); ?>
        <? else : ?>
            Дополнительные свойства не заданы
        <? endif; ?>
    </div>
</div>