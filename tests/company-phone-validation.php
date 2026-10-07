<?php
// Isolated regression checks; no application database or CRM writes.
define('YII_ENABLE_ERROR_HANDLER', false);
$vendor = getenv('TEST_VENDOR') ?: '/app/vendor';
require $vendor . '/autoload.php';
require $vendor . '/yiisoft/yii2/Yii.php';

new yii\console\Application([
    'id' => 'company-phone-validation',
    'basePath' => sys_get_temp_dir(),
    'vendorPath' => $vendor,
    'extensions' => [],
    'components' => [
        'db' => ['class' => yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
        'cache' => ['class' => yii\caching\DummyCache::class],
        'i18n' => ['translations' => ['skeeks/*' => [
            'class' => yii\i18n\PhpMessageSource::class,
            'basePath' => $vendor . '/skeeks/cms/src/messages',
        ]]],
    ],
]);

// Disable logging/cache lifecycle only, preserving the production model rules.
class TestCompanyPhone extends skeeks\cms\models\CmsCompanyPhone
{
    public function behaviors() { return []; }
}

$db = Yii::$app->db;
$db->createCommand('CREATE TABLE cms_company_phone (
    id INTEGER PRIMARY KEY, cms_company_id INTEGER, value TEXT, name TEXT,
    sort INTEGER, created_by INTEGER, updated_by INTEGER, created_at INTEGER, updated_at INTEGER
)')->execute();
$db->createCommand()->insert('cms_company_phone', [
    'id' => 1, 'cms_company_id' => 10, 'value' => '+7 341 250-68-77',
])->execute();

function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

foreach (['+7 341 250-68-77', '+73412506877', '8 (3412) 50-68-77', ' +7 341 250-68-77 '] as $input) {
    $model = new TestCompanyPhone(['cms_company_id' => 10, 'value' => $input]);
    check(!$model->validate(), 'Duplicate accepted: ' . $input);
    check($model->hasErrors('value'), 'Duplicate error must appear on the phone field');
    check(!$model->hasErrors('cms_company_id'), 'Duplicate error attached to hidden company field');
    check($model->getFirstError('value') === 'Этот номер телефона уже добавлен в компанию.', 'Unclear duplicate error');
    $errors = yii\widgets\ActiveForm::validate($model);
    check(isset($errors['testcompanyphone-value']), 'AJAX response missing phone error');
}

$model = new TestCompanyPhone(['cms_company_id' => 20, 'value' => '+73412506877']);
check($model->validate(), 'Another company must be allowed to use the same phone');
check($model->value === '+7 341 250-68-77', 'Phone was not normalized');
check($model->save(), 'A new company phone must save');
check(TestCompanyPhone::findOne($model->id)->value === '+7 341 250-68-77', 'Saved phone is not normalized');

$existing = TestCompanyPhone::findOne(1);
$existing->value = '8 (3412) 50-68-77';
$existing->name = 'Рабочий';
check($existing->save(), 'Editing the existing phone must not report a duplicate');
check(TestCompanyPhone::findOne(1)->name === 'Рабочий', 'Description was not saved');

$model = new TestCompanyPhone(['cms_company_id' => 10, 'value' => '+7 912 006-09-09']);
check($model->save(), 'A different phone in the same company must save');
$model->value = '+73412506877';
check(!$model->save() && $model->hasErrors('value'), 'Editing into a duplicate must fail');

foreach (['', '123', 'invalid'] as $input) {
    $model = new TestCompanyPhone(['cms_company_id' => 10, 'value' => $input]);
    check(!$model->validate() && $model->hasErrors('value'), 'Invalid/empty phone accepted');
}
$model = new TestCompanyPhone(['value' => '+73412506877']);
check(!$model->validate() && $model->hasErrors('cms_company_id'), 'Missing company accepted');
echo "PASS company phone validation, AJAX errors, normalization, create and update\n";
