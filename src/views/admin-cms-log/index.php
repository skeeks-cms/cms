<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
 * @var $this yii\web\View
 */

use skeeks\cms\models\CmsLog;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$request = \Yii::$app->request;
$logType = (string)$request->get('log_type', '');
$modelCode = (string)$request->get('model_code', '');
$createdBy = (string)$request->get('created_by', '');

$query = CmsLog::find();

if ($logType) {
    $query->andWhere(['log_type' => $logType]);
}

if ($modelCode) {
    $query->andWhere(['model_code' => $modelCode]);
}

if ($createdBy !== '') {
    $query->andWhere(['created_by' => (int)$createdBy]);
}

$modelCodes = CmsLog::find()
    ->select('model_code')
    ->andWhere(['not', ['model_code' => null]])
    ->andWhere(['!=', 'model_code', ''])
    ->groupBy('model_code')
    ->orderBy(['model_code' => SORT_ASC])
    ->column();

$modelOptions = [];
foreach ($modelCodes as $code) {
    $modelOptions[$code] = (string)ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$code, 'name_many'], ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$code, 'name_one'], $code));
}
asort($modelOptions);

$userClass = \Yii::$app->user->identityClass;
$users = $userClass::find()
    ->andWhere(['id' => CmsLog::find()->select('created_by')->andWhere(['not', ['created_by' => null]])])
    ->orderBy(['id' => SORT_ASC])
    ->all();

$userOptions = [];
foreach ($users as $user) {
    $userOptions[$user->id] = (string)$user;
}

$this->registerCss(<<<CSS
.sx-log-filters {
    align-items: flex-end;
    background: #fff;
    border-radius: 0.85rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 1rem;
}
.sx-log-filters .sx-filter-field {
    flex: 1 1 14rem;
    min-width: 12rem;
}
.sx-log-filters .sx-filter-actions {
    display: flex;
    flex: 0 0 auto;
    gap: 0.5rem;
}
.sx-log-filters label {
    color: #8b95a1;
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
}
.sx-log-filters .form-control {
    border-color: #dfe5ec;
    border-radius: 0.55rem;
    box-shadow: none;
    min-height: 2.5rem;
}
CSS
);
?>

<?php echo Html::beginForm(Url::to(['index']), 'get', ['class' => 'sx-log-filters']); ?>
    <div class="sx-filter-field">
        <label for="sx-log-filter-type">Тип действия</label>
        <?php echo Html::dropDownList('log_type', $logType, ['' => 'Все действия'] + CmsLog::typeList(), [
            'id' => 'sx-log-filter-type',
            'class' => 'form-control',
        ]); ?>
    </div>

    <div class="sx-filter-field">
        <label for="sx-log-filter-model">Сущность</label>
        <?php echo Html::dropDownList('model_code', $modelCode, ['' => 'Все сущности'] + $modelOptions, [
            'id' => 'sx-log-filter-model',
            'class' => 'form-control',
        ]); ?>
    </div>

    <div class="sx-filter-field">
        <label for="sx-log-filter-user">Кто сделал</label>
        <?php echo Html::dropDownList('created_by', $createdBy, ['' => 'Все пользователи'] + $userOptions, [
            'id' => 'sx-log-filter-user',
            'class' => 'form-control',
        ]); ?>
    </div>

    <div class="sx-filter-actions">
        <?php echo Html::submitButton('Показать', ['class' => 'btn btn-primary']); ?>
        <?php echo Html::a('Сбросить', ['index'], ['class' => 'btn btn-default']); ?>
    </div>
<?php echo Html::endForm(); ?>

<?php
echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
    'query' => $query,
]);
?>
