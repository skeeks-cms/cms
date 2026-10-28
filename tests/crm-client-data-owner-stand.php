<?php
/**
 * Release audit F03 (29.10.2026) on the stand: an existing client data row — a phone, an email, a
 * link or an address of a company or a person — never changes its owner through the form. As an
 * employee with "edit" on client A, a real GET of the update form (a valid CSRF) and a POST that
 * moves the row to a protected client B he does not see must be refused, the owner in the database
 * stays A; the ordinary update of the same row still saves. The fixture is committed (the web
 * requests use their own connection) and removed at the end.
 *
 * CRM_CLIENT_DATA_OWNER_TEST=local php tests/crm-client-data-owner-stand.php
 *   TEST_APP_ROOT — the application root, /app by default
 *   TEST_HTTP_BASE (http://nginx), TEST_HTTP_HOST (localhost:8080) — the local site
 *   TEST_EDIT_USER_ID — the employee (1663 by default), TEST_ADMIN_USER_ID — the main responsible (1)
 */
if (getenv('CRM_CLIENT_DATA_OWNER_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_CLIENT_DATA_OWNER_TEST=local.');
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

use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;

$site = \skeeks\cms\models\CmsSite::find()->default()->one();
Yii::$app->skeeks->site = $site;
$worker = (int)(getenv('TEST_EDIT_USER_ID') ?: 1663);
$admin = (int)(getenv('TEST_ADMIN_USER_ID') ?: 1);
$access = CrmAccess::instance();
$uniq = (string)mt_rand(100000, 999999);
$tag = 'Проверка F03-'.$uniq;
$company = static function (string $name, ?string $level) use ($access, $admin, $worker) {
    $model = new CmsCompany(['name' => $name]);
    $model->save(false);
    CmsCompany2manager::deleteAll(['cms_company_id' => $model->id]);
    $access->setMain('company', (int)$model->id, $admin, CrmAccess::LEVEL_EDIT);
    if ($level) {
        $access->grantLink('company', (int)$model->id, $worker, $level);
    } else {
        $access->setApprovalRequired('company', (int)$model->id, true);
    }
    return $model;
};
$person = static function (string $name, ?string $level) use ($access, $admin, $worker, $site) {
    $model = new CmsUser(['first_name' => $name, 'is_worker' => 0, 'auth_key' => Yii::$app->security->generateRandomString(), 'cms_site_id' => $site->id]);
    $model->save(false);
    CmsUser2manager::deleteAll(['client_id' => $model->id]);
    $access->grantLink('client', (int)$model->id, $admin, CrmAccess::LEVEL_FULL);
    if ($level) {
        $access->grantLink('client', (int)$model->id, $worker, $level);
    }
    return $model;
};
$A = $company($tag.' A', CrmAccess::LEVEL_EDIT);
$B = $company($tag.' B', null);
$P1 = $person($tag.' P1', CrmAccess::LEVEL_EDIT);
$P2 = $person($tag.' P2', null);
$phone = static fn(string $last) => '+7 999 '.substr($uniq, 0, 3).'-'.substr($uniq, 3, 2).'-'.substr($uniq, 5, 1).$last;
$rows = [
    'admin-cms-company-phone' => [\skeeks\cms\models\CmsCompanyPhone::class, 'cms_company_id', $A->id, $B->id, ['value' => $phone('1')]],
    'admin-cms-company-email' => [\skeeks\cms\models\CmsCompanyEmail::class, 'cms_company_id', $A->id, $B->id, ['value' => 'f03-'.$uniq.'@f03.test']],
    'admin-cms-company-link' => [\skeeks\cms\models\CmsCompanyLink::class, 'cms_company_id', $A->id, $B->id,
        ['url' => 'https://f03-'.$uniq.'.test', 'link_type' => (string)array_key_first(\skeeks\cms\models\CmsCompanyLink::getLinkTypes())]],
    'admin-cms-company-address' => [\skeeks\cms\models\CmsCompanyAddress::class, 'cms_company_id', $A->id, $B->id, ['value' => 'Москва, '.$tag, 'latitude' => 55.75, 'longitude' => 37.61]],
    'admin-user-phone' => [\skeeks\cms\models\CmsUserPhone::class, 'cms_user_id', $P1->id, $P2->id, ['value' => $phone('2')]],
    'admin-user-email' => [\skeeks\cms\models\CmsUserEmail::class, 'cms_user_id', $P1->id, $P2->id, ['value' => 'f03-'.$uniq.'-p@f03.test']],
    // The form posts the empty floor, entrance and apartment (their trim filter does not take null).
    'admin-user-address' => [\skeeks\cms\models\CmsUserAddress::class, 'cms_user_id', $P1->id, $P2->id, ['value' => 'Тверь, '.$tag, 'latitude' => 56.86, 'longitude' => 35.9,
        'floor' => '', 'apartment_number' => '', 'entrance' => '']],
];

$jar = tempnam(sys_get_temp_dir(), 'f03');
$http = static function (string $path, ?array $post = null) use ($jar) {
    $curl = curl_init(rtrim(getenv('TEST_HTTP_BASE') ?: 'http://nginx', '/').$path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array_merge(['Host: '.(getenv('TEST_HTTP_HOST') ?: 'localhost:8080')], $post !== null ? ['X-Requested-With: XMLHttpRequest'] : [])]);
    if ($post !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = (string)curl_exec($curl);
    $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$code, $body];
};
$http('/site?id='.$worker);
$fail = 0;
try {
    foreach ($rows as $route => [$class, $column, $own, $foreign, $attributes]) {
        $row = new $class(array_merge([$column => $own], $attributes));
        if ($row->hasAttribute('cms_site_id')) {
            $row->cms_site_id = $site->id;
        }
        $row->save(false);
        try {
            [$code, $page] = $http('/~sxx/cms/'.$route.'/update?pk='.$row->id);
            if ($code !== 200 || !preg_match('~name="csrf-param" content="([^"]+)"~', $page, $p) || !preg_match('~name="csrf-token" content="([^"]+)"~', $page, $t)) {
                echo "FAIL  $route: the update form did not open ($code)\n";
                $fail++;
                continue;
            }
            $csrf = [$p[1] => html_entity_decode($t[1])];
            $form = (new $class())->formName();
            // A crafted POST with a valid CSRF moves the row to the foreign client: refused, the owner stays.
            [$code] = $http('/~sxx/cms/'.$route.'/update?pk='.$row->id, $csrf + [$form => array_merge($attributes, [$column => $foreign]), 'submit-btn' => 'save']);
            $after = (int)$class::find()->select($column)->andWhere(['id' => $row->id])->scalar();
            $ok = $after === (int)$own && $code === 403;
            $fail += $ok ? 0 : 1;
            printf("%-5s %s: moving POST %d, the owner %s\n", $ok ? 'ok' : 'FAIL', $route, $code, $after === (int)$own ? 'stays' : 'changed to '.$after);
            // The ordinary update of the same row (the owner posted as it is) still saves.
            $name = 'Имя '.$uniq;
            [$code] = $http('/~sxx/cms/'.$route.'/update?pk='.$row->id, $csrf + [$form => $attributes + [$column => $own, 'name' => $name, 'comment' => ''], 'submit-btn' => 'save']);
            $row->refresh();
            $ok = $code === 200 && (int)$row->$column === (int)$own && (!$row->hasAttribute('name') || (string)$row->name === $name);
            $fail += $ok ? 0 : 1;
            printf("%-5s %s: the ordinary update POST %d\n", $ok ? 'ok' : 'FAIL', $route, $code);
        } finally {
            $class::deleteAll(['id' => $row->id]);
        }
    }
} finally {
    foreach ([$A, $B] as $model) {
        CmsCompany2manager::deleteAll(['cms_company_id' => $model->id]);
        $model->delete();
    }
    foreach ([$P1, $P2] as $model) {
        CmsUser2manager::deleteAll(['client_id' => $model->id]);
        $model->delete();
    }
    @unlink($jar);
}
if ($fail) {
    echo "FAIL: $fail\n";
    exit(1);
}
echo 'OK crm-client-data-owner-stand: '.count($rows)." kinds of rows, none moved\n";
