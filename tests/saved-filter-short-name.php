<?php
// Saved filter names «раздел + значение»: an ordinary Russian word goes lower case, abbreviations, markings and Latin names keep
// their case. Usage: php tests/saved-filter-short-name.php [/path/to/vendor/autoload.php]
$autoload = $argv[1] ?? dirname(__DIR__, 3) . '/autoload.php';
require $autoload;
require dirname($autoload) . '/yiisoft/yii2/Yii.php';
new yii\console\Application(['id' => 'test', 'basePath' => __DIR__, 'charset' => 'UTF-8']);

use skeeks\cms\models\CmsSavedFilter;

$cases = [
    'Компенсационный' => 'компенсационный',
    'Прямого усиления' => 'прямого усиления',
    'Для монтажа на плату' => 'для монтажа на плату',
    'Взрывозащищённое исполнение (Ex)' => 'взрывозащищённое исполнение (Ex)',
    'Ёмкостные' => 'ёмкостные',
    'ДТК' => 'ДТК',
    'LEM' => 'LEM',
    'SSI' => 'SSI',
    'РУМО' => 'РУМО',
    'ВОДП-ВТ' => 'ВОДП-ВТ',
    'Allegro' => 'Allegro',
    'Texas Instruments' => 'Texas Instruments',
    'Горизонт-Плюс' => 'Горизонт-Плюс',
    'iC-Haus' => 'iC-Haus',
    '4…20 мА' => '4…20 мА',
    '±1 %' => '±1 %',
    'А' => 'А',
    '' => '',
];
$failed = 0;
foreach ($cases as $in => $expected) {
    $actual = CmsSavedFilter::lcfirstWord($in);
    if ($actual !== $expected) {
        $failed++;
        echo "FAIL: «{$in}» → «{$actual}», expected «{$expected}»\n";
    }
}
if ($failed) {
    exit(1);
}
echo 'PASS: ' . count($cases) . " saved filter name cases\n";
