<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */
/* @var string $packagistCode */
/* @var $packageModel PackageModel */

use \skeeks\cms\components\marketplace\models\PackageModel;
$self = $this;
?>
<!--<div class="sx-box sx-p-10 sx-mb-10 sx-bg-primary">
    <p>Вы можете производить установку любых решений, используя SkeekS CMS маркетплейс или минуя его.</p>
    <p>Для установки нового решения, вам необходимо указать его Packagist Code</p>
</div>-->

<? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
    'method' => 'get',
    'options' =>
    [
        'class' => 'form-inline'
    ]
]); ?>

    <form class="form-inline">
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-addon">Packagist code</div>
          <?= \yii\helpers\Html::textInput('packagistCode', $packagistCode, [
                'class' => 'form-control',
                'placeholder' => 'skeeks/cms'
            ]); ?>
        </div>
      </div>

        <?= \yii\helpers\Html::button('Найти', [
            'type'  => 'submit',
            'class' => 'btn btn-primary'
        ]); ?>
    </form>

    <? if ($packageModel) : ?>
        <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
            'dataProvider' => (new \yii\data\ArrayDataProvider([
                'allModels' => [$packageModel],
                'pagination' => [
                    'defaultPageSize' => 1
                ]
            ])),
            'layout' => "{items}",
            'columns' =>
            [
                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model) use ($self)
                    {
                        return $self->render('_package-column', [
                            'model' => $model
                        ]);
                    },
                    'format' => 'raw'
                ],

                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model)
                    {
                        if ($model->isInstalled())
                        {
                            return $model->createCmsExtension()->version;
                        } else
                        {
                            $code = $model->packagistCode;
                            return <<<HTML
<a data-pjax="0"  class="btn btn-default btn-danger" target="_blank" title="">
    <i class="glyphicon glyphicon-download-alt"></i> Установить
</a>
<pre>
php yii cms/update/install {$code}:*
</pre>
HTML;
;
                        }
                    },
                    'label' => 'Версия',
                    'format' => 'raw'
                ],

            ]
        ])?>
    <? endif; ?>
<? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>