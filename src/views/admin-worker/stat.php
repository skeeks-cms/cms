<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;
$dm = new \skeeks\cms\base\DynamicModel([
    'from',
    'to',
]);
$dm->addRule(['from', 'to'], 'string');
$dm->load(\Yii::$app->request->post());

$q = \skeeks\cms\models\CmsContentElement::find()->from([
    'c' => \skeeks\cms\models\CmsContentElement::tableName(),
])
    ->cmsSite()
    ->joinWith('cmsContent as cmsContent')
    ->andWhere(['c.created_by' => $model->id])
    ->groupBy(['c.content_id'])
    ->select([
        'count'        => new \yii\db\Expression("count(1)"),
        "content_id"   => 'c.content_id',
        "content_name" => 'cmsContent.name',
    ]);

$qTree = \skeeks\cms\models\CmsTree::find()->from([
        't' => \skeeks\cms\models\CmsTree::tableName(),
    ])
    ->cmsSite()
    ->andWhere(['t.created_by' => $model->id])
    ->select([
        'count'        => new \yii\db\Expression("count(1)"),
    ]);
    
$qCollection = \skeeks\cms\shop\models\ShopCollection::find()->from([
        'sc' => \skeeks\cms\shop\models\ShopCollection::tableName(),
    ])
    ->andWhere(['sc.created_by' => $model->id])
    ->select([
        'count'        => new \yii\db\Expression("count(1)"),
    ]);
    
if ($dm->from) {
    $start = strtotime($dm->from . " 00:00:00");
    $q->andWhere(['>=', 'c.created_at', $start]);
    $qCollection->andWhere(['>=', 'sc.created_at', $start]);
    $qTree->andWhere(['>=', 't.created_at', $start]);
}
if ($dm->to) {
    $to = strtotime($dm->to . " 23:59:59");
    $q->andWhere(['<=', 'c.created_at', $to]);
    $qCollection->andWhere(['<=', 'sc.created_at', $to]);
    $qTree->andWhere(['<=', 't.created_at', $to]);
}

$all = $q
    ->asArray()
    ->all();

$allTree = $qTree
    ->asArray()
    ->all();

$allCollection = $qCollection
    ->asArray()
    ->all();

?>
<?php $form = \yii\widgets\ActiveForm::begin(); ?>
<div class="row sx-bg-secondary" style="padding: 5px;">
    <div class="col">
        <?php echo $form->field($dm, 'from')->textInput(['type' => 'date'])->label("Начало периода"); ?>
    </div>
    <div class="col">
        <?php echo $form->field($dm, 'to')->textInput(['type' => 'date'])->label("Конец периода"); ?>
    </div>
    <div class="col my-auto">
        <button type="submit" class="btn btn-primary">Отправить</button>
    </div>
</div>
<?php $form::end(); ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-12 mx-auto">
        <h4>Статистика добавления:</h4>
        <?php if ($all) : ?>
            <?php foreach ($all as $data) : ?>
                <div>
                    <?php echo $data['content_name']; ?>: <b><?php echo $data['count']; ?></b>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($allTree) : ?>
            <?php foreach ($allTree as $data) : ?>
                <div>
                    Разделов добавлено: <b><?php echo $data['count']; ?></b>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($allCollection) : ?>
            <?php foreach ($allCollection as $data) : ?>
                <div>
                    Коллекций добавлено: <b><?php echo $data['count']; ?></b>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
