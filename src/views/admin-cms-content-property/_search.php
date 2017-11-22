<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.05.2016
 */
$filter = new \yii\base\DynamicModel([
    'id',
    'tree_ids',
    'content_ids',
]);
$filter->addRule('id', 'integer');
$filter->addRule('content_ids', 'integer');
$filter->addRule('tree_ids', 'integer');

$filter->load(\Yii::$app->request->get());

if ($filter->id) {
    $dataProvider->query->andWhere(['id' => $filter->id]);
}

if ($filter->content_ids) {
    $dataProvider->query->joinWith('cmsContentProperty2contents as contentMap')->andWhere(['contentMap.cms_content_id' => $filter->content_ids]);
}

if ($filter->tree_ids) {
    $dataProvider->query->joinWith('cmsContentProperty2trees as treeMap')->andWhere(['treeMap.cms_tree_id' => $filter->content_ids]);
}

?>
<?php $form = \skeeks\cms\modules\admin\widgets\filters\AdminFiltersForm::begin([
    'action' => '/' . \Yii::$app->request->pathInfo,
]); ?>

<?= $form->field($searchModel, 'name')->setVisible(true)->textInput([
    'placeholder' => \Yii::t('skeeks/cms', 'Search by name')
]); ?>

<?= $form->field($searchModel, 'component')->setVisible(true)
    ->widget(
        \skeeks\widget\chosen\Chosen::class, [
            'items' => \Yii::$app->cms->relatedHandlersDataForSelect
        ]
    );
?>

<?= $form->field($filter, 'content_ids')->label(\Yii::t('skeeks/cms', 'Content'))->setVisible(true)->widget(
    \skeeks\widget\chosen\Chosen::class,
    [
        'multiple' => true,
        'items' => \skeeks\cms\models\CmsContent::getDataForSelect()
    ]
); ?>

<?= $form->field($filter, 'tree_ids')->label(\Yii::t('skeeks/cms', 'Sections'))->setVisible(true)
    ->widget(
        \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
    )/*->widget(
        \skeeks\widget\chosen\Chosen::class,
        [
            'multiple' => true,
            'items' => \skeeks\cms\helpers\TreeOptions::getAllMultiOptions()
        ]
    )*/
; ?>

<?= $form->field($searchModel, 'id') ?>

<?= $form->field($searchModel, 'code'); ?>

<?= $form->field($searchModel, 'active')->listBox(\yii\helpers\ArrayHelper::merge([
    '' => ' - '
], \Yii::$app->cms->booleanFormat()), [
    'size' => 1
]); ?>

<?php $form::end(); ?>
