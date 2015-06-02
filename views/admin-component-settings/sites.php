<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $component \skeeks\cms\base\Component
 */
/* @var $this yii\web\View */
?>

<?= $this->render('_header', [
    'component' => $component
]); ?>


    <div class="sx-box sx-mb-10 sx-p-10">
        <p>Этот компонент может иметь персональные настройки. И работает по разному в зависимости от того на каком из сайтов показывается.</p>
        <p>В том случае если сайт не имеет персональных настроек, будут использоваться настройки по умолчанию.</p>
        <? if ($settings = \skeeks\cms\models\CmsComponentSettings::baseQuerySites($component)->count()) : ?>
            <p><b>Количество настроенных сайтов:</b> <?= $settings; ?></p>
            <button type="submit" class="btn btn-danger btn-xs" onclick="sx.ComponentSettings.Remove.removeSites(); return false;">
                <i class="glyphicon glyphicon-remove"></i> сбросить настройки для всех сайтов
            </button>
            <small>.</small>
        <? else: ?>
            <small>Ни один сайт, еще не имеет персональных настроек для этого компонента</small>
        <? endif; ?>
    </div>

    <?
        $search = new \skeeks\cms\models\Search(\skeeks\cms\models\CmsSite::className());
        $search->search(\Yii::$app->request->get());
        $search->getDataProvider()->query->andWhere(['active' => \skeeks\cms\components\Cms::BOOL_Y]);

    ?>
    <?= \skeeks\cms\modules\admin\widgets\GridViewHasSettings::widget([
        'dataProvider' => $search->getDataProvider(),
        'filterModel' => $search->getLoadedModel(),
        'columns' => [

            [
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\CmsSite $model, $key, $index, $this)
                {
                    return \yii\helpers\Html::a('<i class="glyphicon glyphicon-cog"></i>',
                    \skeeks\cms\helpers\UrlHelper::constructCurrent()->setRoute('cms/admin-component-settings/site')->set('site_id', $model->id)->toString(),
                    [
                        'class' => 'btn btn-default btn-xs',
                        'title' => 'Настроить'
                    ]);
                },

                'format'    => 'raw',
            ],

            'name',
            'code',

            [
                'class'         => \skeeks\cms\grid\ComponentSettingsColumn::className(),
                'component'     => $component,
            ],
        ]
    ])?>


<?= $this->render('_footer'); ?>
