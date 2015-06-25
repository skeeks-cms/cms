<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */

use \skeeks\cms\components\marketplace\models\PackageModel;
use \skeeks\cms\models\CmsExtension;
$self = $this;
?>
<div class="sx-box sx-p-10 sx-mb-10 sx-bg-primary">
    <p>В этом разделе показаны все расширения, которые успешно установлены и используются в вашем проекте.</p>
    <p>Вы так же, можете ознакомиться с версией установленного расширения, посмотреть его в маркетплейс.</p>
</div>
<? if ($allModels = CmsExtension::fetchAllWhithMarketplace()) :  ?>
    <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
        'dataProvider' => (new \yii\data\ArrayDataProvider([
            'allModels' => $allModels,
            'pagination' => [
                'defaultPageSize' => 200
            ]
        ])),
        //'layout' => "{summary}\n{items}\n{pager}",
        'columns' =>
        [
            [
                'class' => \yii\grid\DataColumn::className(),
                'value' => function(CmsExtension $model) use ($self)
                {
                    return $self->render('_image-column', [
                        'model' => $model
                    ]);

                },
                'format' => 'raw'
            ],

            'version',
        ]
    ])?>
<? endif; ?>
