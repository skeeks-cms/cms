<?php

$autoloadCandidates = [
    dirname(__DIR__).'/vendor/autoload.php',
    dirname(__DIR__, 3).'/autoload.php',
    '/app/vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;
        break;
    }
}

use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\queries\CmsCompanyQuery;
use skeeks\cms\models\queries\CmsUserQuery;
use yii\db\Expression;

$failed = [];

$check = function ($title, $expected, $actual) use (&$failed) {
    if ($expected !== $actual) {
        $failed[] = $title
            .": ожидалось ".json_encode($expected, JSON_UNESCAPED_UNICODE)
            .", получено ".json_encode($actual, JSON_UNESCAPED_UNICODE);
    }
};

//Один и тот же номер записывают как угодно, значимая часть у него одна
$sameNumber = [
    '+7 495 005-79-26',
    '8 (495) 005-79-26',
    '+7(495)0057926',
    '74950057926',
    '84950057926',
    '4950057926',
];
foreach ($sameNumber as $variant) {
    $check("searchDigits('{$variant}')", '4950057926', PhoneHelper::searchDigits($variant));
    $check("isSearchablePhone('{$variant}')", true, PhoneHelper::isSearchablePhone($variant));
}

//Часть номера тоже ищется, но уже без отбрасывания кода страны
$check("searchDigits('005-79-26')", '0057926', PhoneHelper::searchDigits('005-79-26'));

//Не телефоны
foreach (['Скикс', 'ООО Ромашка', 'ivan@skeeks.com', '', '   ', '12'] as $notPhone) {
    $check("isSearchablePhone('{$notPhone}')", false, PhoneHelper::isSearchablePhone($notPhone));
}

//Условие поиска строится по «оцифрованной» колонке
$condition = PhoneHelper::likeCondition('phones.value', '8 (495) 005-79-26');
$check('likeCondition: оператор', 'like', $condition[0]);
$check('likeCondition: колонка — выражение', true, $condition[1] instanceof Expression);
$check('likeCondition: значение', '4950057926', $condition[2]);
$check(
    'likeCondition: колонка чистится от разделителей',
    true,
    strpos((string)$condition[1], "REPLACE(") === 0 && strpos((string)$condition[1], 'phones.value') !== false
);
$check('likeCondition: не телефон', null, PhoneHelper::likeCondition('phones.value', 'Ромашка'));

//Точное сравнение номеров: только полный номер, часть не годится
$equal = PhoneHelper::equalCondition('phones.value', '84950057926');
$check('equalCondition: значение', '%4950057926', $equal[2]);
$check('equalCondition: без экранирования', false, $equal[3]);
$check('equalCondition: часть номера', null, PhoneHelper::equalCondition('phones.value', '0057926'));

//Разбор запроса на слова: телефон остаётся целым, остальное режется
$words = new ReflectionMethod(CmsUserQuery::class, 'searchWords');
$words->setAccessible(true);
$userQuery = new CmsUserQuery(CmsUser::class);

$check('searchWords: телефон с пробелами', ['+7 495 005-79-26'], $words->invoke($userQuery, '+7 495 005-79-26'));
$check('searchWords: ФИО', ['Иванов', 'Иван'], $words->invoke($userQuery, '  Иванов   Иван  '));
$check('searchWords: пусто', [], $words->invoke($userQuery, '   '));
$check('searchWords: лимит слов', 2, count($words->invoke($userQuery, 'один два три', 2)));

//Каждое слово ищется отдельно и все слова обязательны
$userCondition = new ReflectionMethod(CmsUserQuery::class, 'searchWordCondition');
$userCondition->setAccessible(true);
$built = $userCondition->invoke($userQuery, '84950057926');
$check('CmsUserQuery: условие — OR', 'or', $built[0]);
$check(
    'CmsUserQuery: телефон нормализуется',
    true,
    (bool)array_filter($built, function ($part) {
        return is_array($part) && isset($part[1]) && $part[1] instanceof Expression;
    })
);

$companyCondition = new ReflectionMethod(CmsCompanyQuery::class, 'searchWordCondition');
$companyCondition->setAccessible(true);
$companyQuery = new CmsCompanyQuery(CmsCompany::class);
$built = $companyCondition->invoke($companyQuery, '84950057926', true);
$expressions = array_filter($built, function ($part) {
    return is_array($part) && isset($part[1]) && $part[1] instanceof Expression;
});
$check('CmsCompanyQuery: телефоны компании и контактов', 2, count($expressions));

$built = $companyCondition->invoke($companyQuery, '84950057926', false);
$expressions = array_filter($built, function ($part) {
    return is_array($part) && isset($part[1]) && $part[1] instanceof Expression;
});
$check('CmsCompanyQuery: без контактов только телефон компании', 1, count($expressions));

if ($failed) {
    fwrite(STDERR, "FAILED: entity-search-phone-normalization\n");
    foreach ($failed as $message) {
        fwrite(STDERR, "  - ".$message."\n");
    }
    exit(1);
}

echo "entity-search-phone-normalization: OK\n";
