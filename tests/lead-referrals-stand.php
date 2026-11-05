<?php
/**
 * «Откуда узнали» (crm\LeadReferrals, owner's decision 05.11.2026) on the stand database, rolled back:
 * - rules: marks (utm, yclid), the first external page only without utm marks, the longer referrer pattern wins,
 *   the channel, the called number; an inactive item is never chosen; rule text validation;
 * - a chosen item is never overwritten; a person changing it clears the automatic note;
 * - the channel «Визит»: by hand and from a form with that channel (the form creation entry);
 * - the CRM setting «обязательно для ручных сделок»;
 * - call tracking (fillByCall) with the feed entry; EVENT_DETECT of a package by code;
 * - the report by «Откуда узнали» with channels inside; a used item cannot be deleted.
 *
 * Usage: LEAD_REFERRALS_TEST=local php tests/lead-referrals-stand.php
 */

if (getenv('LEAD_REFERRALS_TEST') !== 'local') {
    throw new RuntimeException('Set LEAD_REFERRALS_TEST=local.');
}
define('ROOT_DIR', getenv('TEST_APP_ROOT') ?: '/app');
define('YII_ENV', 'dev');
define('YII_DEBUG', true);
require ROOT_DIR.'/vendor/skeeks/cms/bootstrap.php';
$config = new Yiisoft\Config\Config(new Yiisoft\Config\ConfigPaths(ROOT_DIR, 'config'), null, [Yiisoft\Config\Modifier\RecursiveMerge::groups('web', 'web-'.ENV, 'params', 'params-web-'.ENV)], 'params-web-'.ENV);
$c = $config->get($config->has('web-'.ENV) ? 'web-'.ENV : 'web');
$c['components']['request']['scriptFile'] = ROOT_DIR.'/frontend/web/index.php';
$c['components']['request']['scriptUrl'] = '/index.php';
$c['components']['request']['hostInfo'] = 'http://localhost';
$c['components']['user']['enableSession'] = false;
new yii\web\Application($c);
Yii::$app->errorHandler->unregister();

use skeeks\cms\crm\LeadReferralEvent;
use skeeks\cms\crm\LeadReferrals;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadReferral;
use skeeks\cms\models\CmsLog;
use skeeks\cms\services\CmsLeadReportService;
use skeeks\cms\services\CmsLeadService;
use yii\base\Event;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};

if (!CmsLead::referralReady()) {
    throw new RuntimeException('Run cms/migrate first: m261105_100000.');
}
$siteId = (int)Yii::$app->skeeks->site->id;
$settings = Yii::$app->crmSettings;
$savedRequired = $settings->referralRequiredManual;
$uniq = substr(md5(uniqid('', true)), 0, 6);
$phone = static fn() => '+7 999 '.random_int(100, 999).'-'.random_int(10, 99).'-'.random_int(10, 99);

$transaction = Yii::$app->db->beginTransaction();
try {
    // A known list: the items of the site are switched off, the test ones are the starting list under other names.
    CmsLeadReferral::updateAll(['is_active' => 0], ['cms_site_id' => $siteId]);
    $items = [];
    foreach (LeadReferrals::defaults() as $code => [$name, $sort, $rules]) {
        $item = new CmsLeadReferral(['cms_site_id' => $siteId, 'name' => 'Т'.$uniq.' '.$name, 'code' => 't'.$uniq.'_'.$code, 'sort' => $sort, 'match_rules' => $rules]);
        $check($item->save(), 'a starting item is valid: '.$name.' '.implode(' ', $item->getFirstErrors()));
        $items[$code] = $item;
    }
    $call = new CmsLeadReferral(['cms_site_id' => $siteId, 'name' => 'Т'.$uniq.' Коллтрекинг', 'sort' => 200, 'match_rules' => 'phone=+7 (900) 111-22-33']);
    $check($call->save(), 'a call tracking item is valid');
    $off = new CmsLeadReferral(['cms_site_id' => $siteId, 'name' => 'Т'.$uniq.' Выключен', 'sort' => 1, 'is_active' => 0, 'match_rules' => 'utm_source=yandex']);
    $check($off->save(), 'an inactive item is valid');

    // Rule texts.
    $bad = new CmsLeadReferral(['cms_site_id' => $siteId, 'name' => 'Т'.$uniq.' плохой', 'match_rules' => "utm_source=yandex\nпросто текст"]);
    $check(!$bad->validate() && strpos($bad->getFirstError('match_rules'), 'Строка 2') === 0, 'a bad line is named');
    $bad->match_rules = 'channel=nowhere';
    $check(!$bad->validate() && strpos($bad->getFirstError('match_rules'), 'канала') !== false, 'an unknown channel is refused');

    $form = static function (string $ref, array $data = [], array $attributes = []) use ($siteId, $uniq, $phone) {
        return (new CmsLeadService())->createFromSource(array_merge([
            'name' => 'Проверка: откуда узнали '.$uniq.' '.$ref, 'cms_site_id' => $siteId, 'source_type' => CmsLead::SOURCE_FORM,
            'source_ref' => 'referral-test:'.$uniq.':'.$ref, 'phones' => [$phone()], 'status' => CmsLead::STATUS_NEW,
            'source_url' => 'https://www.skeeks.com/contacts', 'source_data' => $data,
        ], $attributes));
    };
    $is = static fn(CmsLead $lead, string $code) => (int)$lead->cms_lead_referral_id === (int)$items[$code]->id;

    // 1. Direct by its marks; the inactive item with the same marks is not chosen.
    $direct = $form('direct', ['utm' => ['utm_source' => 'yandex', 'utm_medium' => 'cpc', 'utm_campaign' => 'tile']]);
    $check($is($direct, 'yandex_direct'), 'utm_source=yandex utm_medium=cpc → «Яндекс Директ»');
    $check(strpos((string)$direct->referral_note, 'utm_source=yandex') !== false, 'the note names the marks');

    // 2. No marks, from a search engine → «Поиск (SEO)»; from the maps path → «Яндекс Карты» (the longer pattern).
    $seo = $form('seo', ['utm' => ['sx_first_referrer' => 'https://yandex.ru/search/?text=плитка']]);
    $check($is($seo, 'seo'), 'no marks, from yandex.ru → «Поиск (SEO)»: '.var_export([$seo->cms_lead_referral_id, $seo->referral_note, LeadReferrals::context($seo)], true));
    $check(strpos((string)$seo->referral_note, 'переход с yandex.ru') !== false, 'the note names the page');
    $maps = $form('maps', ['referrer' => 'https://yandex.ru/maps/org/plitka/123/']);
    $check($is($maps, 'yandex_maps'), 'yandex.ru/maps → «Яндекс Карты», not «Поиск»');
    $gis = $form('2gis', ['utm' => ['sx_first_referrer' => 'https://2gis.ru/moscow/firm/1']]);
    $check($is($gis, '2gis'), '2gis.ru → «2ГИС»');

    // 3. Marks win: with utm marks the page is not looked at; yclid alone is a mark of Direct.
    $marked = $form('marked', ['utm' => ['utm_source' => 'partner_site', 'sx_first_referrer' => 'https://yandex.ru/']]);
    $check(!$marked->cms_lead_referral_id, 'unknown utm marks: the page is not used, the field stays empty');
    $yclid = $form('yclid', ['utm' => ['yclid' => '123456', 'sx_first_referrer' => 'https://yandex.ru/']]);
    $check($is($yclid, 'yandex_direct'), 'yclid → «Яндекс Директ» before the page');
    $own = $form('own', ['utm' => ['sx_first_referrer' => 'https://skeeks.com/']]);
    $check(!$own->cms_lead_referral_id, 'a page of the own site is not a referrer');

    // 4. Letters and mailings.
    $letter = $form('letter', ['utm' => ['utm_source' => 'crm', 'utm_medium' => 'email', 'utm_campaign' => 'personal']]);
    $check($is($letter, 'mailing'), 'a link of a CRM letter (utm_medium=email) → «Рассылка»');
    $mailing = $form('mailing', [], ['source_type' => CmsLead::SOURCE_MAILING]);
    $check($is($mailing, 'mailing'), 'the channel «Рассылка» → «Рассылка»');

    // 5. A chosen item is never overwritten.
    $chosen = $form('chosen', ['utm' => ['utm_source' => 'yandex', 'utm_medium' => 'cpc']], ['cms_lead_referral_id' => (int)$items['recommendation']->id]);
    $check($is($chosen, 'recommendation') && $chosen->referral_note === null, 'a chosen item stays, no automatic note');

    // 6. A person changes the automatic value: the note goes.
    $direct->refresh();
    $direct->cms_lead_referral_id = (int)$items['avito']->id;
    $check($direct->save(false), 'the changed deal is saved');
    $direct->refresh();
    $check($is($direct, 'avito') && $direct->referral_note === null, 'a changed value has no automatic note');

    // 7. The channel «Визит»: a form with that channel keeps the form creation entry.
    $visit = $form('visit', ['form_send_id' => 987654, 'utm' => []], ['source_type' => CmsLead::SOURCE_VISIT, 'source_name' => 'Анкета салона']);
    $check($visit->source_type === CmsLead::SOURCE_VISIT && $visit->isFormSubmission(), 'a visit from a form is a form submission');
    $entry = CmsLog::find()->andWhere(['model_code' => $visit->skeeksModelCode, 'model_id' => (int)$visit->id, 'log_type' => CmsLog::LOG_TYPE_COMMENT])->orderBy(['id' => SORT_ASC])->one();
    $check($entry && strpos((string)$entry->comment, 'Отправлена форма «Анкета салона» №987654') === 0, 'the creation entry names the form: '.($entry ? $entry->comment : '—'));
    $check(CmsLead::find()->fromSource(CmsLead::SOURCE_VISIT, 'referral-test:'.$uniq.':visit', $siteId)->exists(), 'the visit is found by its channel and reference');

    // 8. By hand: a visit with a chosen item; the setting «обязательно» refuses an empty one, only by hand.
    // CmsLead::init() sets the channel «Создан вручную»: the form loads its values after it, so does the test.
    $manual = new CmsLead();
    $manual->setAttributes(['cms_site_id' => $siteId, 'name' => 'Проверка: визит '.$uniq, 'source_type' => CmsLead::SOURCE_VISIT,
        'cms_lead_referral_id' => (int)$items['signboard']->id]);
    $check($manual->save(), 'a visit by hand is saved: '.implode(' ', $manual->getFirstErrors()));
    $check($is($manual, 'signboard') && CmsLead::sources()[CmsLead::SOURCE_VISIT] === 'Визит в салон / офис', 'the visit keeps the chosen item');
    $settings->referralRequiredManual = 1;
    $empty = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Проверка: пусто '.$uniq, 'source_type' => CmsLead::SOURCE_MANUAL]);
    $check(!$empty->validate() && $empty->hasErrors('cms_lead_referral_id'), 'required by the setting: a deal by hand without it is refused');
    $auto = $form('required-auto', []);
    $check((bool)$auto->id, 'required by the setting: a form request is saved without it');
    $settings->referralRequiredManual = 0;
    $check($empty->validate(['cms_lead_referral_id']), 'the setting off: a deal by hand without it is valid');
    $foreign = new CmsLead(['cms_site_id' => $siteId, 'name' => 'Проверка: чужой '.$uniq, 'cms_lead_referral_id' => 99999999]);
    $check(!$foreign->validate(['cms_lead_referral_id']), 'an item of no site is refused');

    // 9. Call tracking: the called number fills an empty field once, with a feed entry; a chosen one stays.
    $byCall = new CmsLead();
    $byCall->setAttributes(['cms_site_id' => $siteId, 'name' => 'Проверка: звонок '.$uniq, 'source_type' => CmsLead::SOURCE_PHONE]);
    $check($byCall->save() && !$byCall->cms_lead_referral_id, 'a deal of a call without marks is empty');
    $check(!LeadReferrals::fillByCall($byCall, '+7 495 000-00-00'), 'another number fills nothing');
    $check(LeadReferrals::fillByCall($byCall, '8 900 111 22 33'), 'the tracked number fills it');
    $byCall->refresh();
    $check((int)$byCall->cms_lead_referral_id === (int)$call->id, 'the call tracking item is set');
    $feed = CmsLog::find()->andWhere(['model_code' => $byCall->skeeksModelCode, 'model_id' => (int)$byCall->id])
        ->andWhere(['like', 'comment', 'Откуда узнали'])->count();
    $check((int)$feed === 1, 'one feed entry');
    $check(!LeadReferrals::fillByCall($byCall, '8 900 111 22 33'), 'a filled field is not filled again');
    $check(!LeadReferrals::fillByCall($chosen, '8 900 111 22 33'), 'a chosen item is not replaced by a call');

    // 10. A package names the item by its code (EVENT_DETECT).
    $handler = static function (LeadReferralEvent $event) use ($uniq) {
        if (strpos((string)$event->lead->name, 'by-package') !== false) {
            $event->code = 't'.$uniq.'_social';
            $event->note = 'Проверка: пакет';
        }
    };
    Event::on(LeadReferrals::class, LeadReferrals::EVENT_DETECT, $handler);
    $byPackage = $form('by-package', ['utm' => ['utm_source' => 'yandex', 'utm_medium' => 'cpc']]);
    Event::off(LeadReferrals::class, LeadReferrals::EVENT_DETECT, $handler);
    $check($is($byPackage, 'social') && $byPackage->referral_note === 'Проверка: пакет', 'the package wins over the rules');

    // 11. The report: items with their channels; the filter of one item. It counts the deals the viewer may see: an administrator.
    Yii::$app->user->setIdentity(\skeeks\cms\models\CmsUser::findOne((int)(getenv('REFERRAL_ADMIN') ?: 1)));
    $report = (new CmsLeadReportService())->build(time() - 3600, time() + 3600, null, null);
    $rows = [];
    foreach ($report['byReferral'] as $row) {
        $rows[$row['title']] = $row;
    }
    $directRow = $rows['Т'.$uniq.' Яндекс Директ'] ?? null;
    $check($directRow && $directRow['leads'] >= 1 && $directRow['children'][0]['title'] === CmsLead::sources()[CmsLead::SOURCE_FORM],
        'the report row «Яндекс Директ» with the channel «Конструктор форм» inside');
    $check(isset($rows['Т'.$uniq.' Вывеска / проходил мимо']) && in_array('Визит в салон / офис', array_column($rows['Т'.$uniq.' Вывеска / проходил мимо']['children'], 'title'), true),
        'the visit is counted under its item with the channel «Визит»: '.json_encode(array_map(static fn($r) => [$r['title'], $r['leads'], array_column($r['children'], 'title')], $report['byReferral']), JSON_UNESCAPED_UNICODE));
    $only = (new CmsLeadReportService())->build(time() - 3600, time() + 3600, null, null, (string)$items['mailing']->id);
    $check(count($only['byReferral']) === 1 && $only['total']['leads'] === 2, 'the filter of one item: '.$only['total']['leads']);

    // 12. A used item cannot be deleted; the inactive current value stays in the list of the deal.
    $check($items['avito']->delete() === false, 'a used item is not deleted');
    $check(isset(LeadReferrals::items($siteId, (int)$off->id)[(int)$off->id]) && !isset(LeadReferrals::items($siteId)[(int)$off->id]),
        'an inactive item is offered only as the current value');

    echo "OK: {$checks} checks\n";
} finally {
    $settings->referralRequiredManual = $savedRequired;
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}
