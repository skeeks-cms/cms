<?php
/**
 * crm\OwnerAssignment and `cms/crm/assign-owners` on the stand database, rolled back: the main
 * responsible of the old base by its old responsibles (owner's decision 30.10.2026). Owners' clients
 * get the owner as main (the current main owner, else the author owner, else the earliest owner link)
 * and «Всегда по согласованию»; the own clients of one keep employee get him as main; shared,
 * review and other-only clients are not changed; other participants keep their links and levels;
 * every change is in the journal of the access; a repeated run changes nothing.
 *
 * The fixtures are raw old-model rows (links without a main); existing workers stand in for the
 * owners, so no real client is touched, and everything is rolled back.
 *
 * CRM_OWNER_ASSIGNMENT_TEST=local php tests/crm-owner-assignment-stand.php
 * (TEST_APP_ROOT — the application root, /app by default)
 */
if (getenv('CRM_OWNER_ASSIGNMENT_TEST') !== 'local') {
    throw new RuntimeException('Set CRM_OWNER_ASSIGNMENT_TEST=local.');
}
define('ROOT_DIR', getenv('TEST_APP_ROOT') ?: '/app');
define('YII_ENV', 'dev');
define('YII_DEBUG', true);
require ROOT_DIR.'/vendor/skeeks/cms/bootstrap.php';
$config = new Yiisoft\Config\Config(new Yiisoft\Config\ConfigPaths(ROOT_DIR, 'config'), null, [Yiisoft\Config\Modifier\RecursiveMerge::groups('console', 'console-'.ENV, 'params', 'params-console-'.ENV)], 'params-console-'.ENV);
new yii\console\Application($config->get($config->has('console-'.ENV) ? 'console-'.ENV : 'console'));

use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmProtection;
use skeeks\cms\crm\OwnerAssignment;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks) {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
};
if (!OwnerAssignment::ready()) {
    throw new RuntimeException('The CRM migrations are not applied.');
}
$db = Yii::$app->db;
$transaction = $db->beginTransaction();
try {
    $workers = array_map('intval', (new yii\db\Query())->select('id')->from('cms_user')->where(['is_worker' => 1])->orderBy(['id' => SORT_ASC])->limit(5)->column());
    $check(count($workers) === 5, 'five workers on the stand');
    [$w1, $w2, $k, $r, $x] = $workers;
    $now = time();
    $company = static function (string $name, ?int $author, array $users) use ($db, $now): int {
        $db->createCommand()->insert('cms_company', ['name' => 'Проверка: владельцы — '.$name, 'created_by' => $author, 'created_at' => $now,
            'crm_protection' => CrmProtection::FREE])->execute();
        $id = (int)$db->getLastInsertID();
        foreach ($users as $user) {
            $db->createCommand()->insert('cms_company2manager', ['cms_company_id' => $id, 'cms_user_id' => $user, 'is_main' => 0, 'access_level' => 'full'])->execute();
        }

        return $id;
    };
    $c = [
        'owner' => $company('владелец', null, [$w1]),
        'author' => $company('автор-владелец', $w1, [$w2, $w1]),
        'earliest' => $company('ранняя связь', null, [$w2, $w1]),
        'keep' => $company('свой клиент', null, [$k]),
        'shared' => $company('общая', null, [$w1, $k]),
        'review' => $company('на разбор', null, [$r]),
        'reviewKeep' => $company('на разбор и свой', null, [$k, $r]),
        'other' => $company('другой сотрудник', null, [$x]),
        'ownerOther' => $company('владелец и другой', null, [$w1, $x]),
    ];
    $db->createCommand()->update('cms_company2manager', ['access_level' => 'deal'], ['cms_company_id' => $c['ownerOther'], 'cms_user_id' => $x])->execute();
    $db->createCommand()->insert('cms_user', ['username' => 'proverka-owners-'.$now, 'first_name' => 'Проверка: владельцы', 'is_worker' => 0, 'created_at' => $now, 'updated_at' => $now,
        'cms_site_id' => (int)(new yii\db\Query())->select('id')->from('cms_site')->orderBy(['id' => SORT_ASC])->scalar(),
        'crm_protection' => CrmProtection::FREE, 'auth_key' => 'x', 'password_hash' => 'x'])->execute();
    $person = (int)$db->getLastInsertID();
    $db->createCommand()->insert('cms_user2manager', ['client_id' => $person, 'worker_id' => $w2, 'is_main' => 0, 'access_level' => 'full'])->execute();

    $service = new OwnerAssignment([$w1, $w2], [$k], [$r]);
    $fixture = static function (array $plan) use ($c, $person): array {
        $rows = [];
        foreach ($plan as $row) {
            $key = $row['code'] === 'client' ? ($row['id'] === $person ? 'person' : null) : array_search($row['id'], $c, true);
            if ($key !== false && $key !== null) {
                $rows[$key] = $row;
            }
        }

        return $rows;
    };
    $plan = $fixture($service->plan());
    $check(count($plan) === 10, 'every fixture is in the plan');
    foreach (['owner' => 'owner', 'author' => 'owner', 'earliest' => 'owner', 'keep' => 'keep', 'shared' => 'shared', 'review' => 'review', 'reviewKeep' => 'review',
                 'other' => 'other', 'ownerOther' => 'owner', 'person' => 'owner'] as $key => $group) {
        $check($plan[$key]['group'] === $group, "$key: group $group (".$plan[$key]['group'].')');
    }
    $check($plan['owner']['user_id'] === $w1 && $plan['author']['user_id'] === $w1 && $plan['earliest']['user_id'] === $w2 && $plan['person']['user_id'] === $w2,
        'owner choice: the only owner, the author owner, the earliest owner link');
    $check($plan['keep']['user_id'] === $k && !$plan['keep']['always'], 'keep: the employee, no «always»');
    foreach (['shared', 'review', 'reviewKeep', 'other'] as $key) {
        $check(!$plan[$key]['change'] && $plan[$key]['user_id'] === null, "$key: no change");
    }
    $before = (int)(new yii\db\Query())->from('cms_crm_access_log')->count();
    $logBefore = (int)(new yii\db\Query())->select('MAX(id)')->from('cms_crm_access_log')->scalar();

    [$mains, $always] = $service->apply($plan);
    $check($mains === 6 && $always === 5, "apply: 6 mains, 5 «always» ($mains, $always)");
    $access = CrmAccess::instance();
    $access->resetMemo();
    foreach (['owner' => $w1, 'author' => $w1, 'earliest' => $w2, 'ownerOther' => $w1, 'keep' => $k] as $key => $user) {
        $check($access->mainResponsibleId('company', $c[$key]) === $user, "$key: main $user");
    }
    $check($access->mainResponsibleId('client', $person) === $w2, 'person: main');
    $protection = static fn(string $table, int $id) => (string)(new yii\db\Query())->select('crm_protection')->from($table)->where(['id' => $id])->scalar();
    foreach (['owner', 'author', 'earliest', 'ownerOther'] as $key) {
        $check($protection('cms_company', $c[$key]) === CrmProtection::ALWAYS, "$key: «always»");
    }
    $check($protection('cms_user', $person) === CrmProtection::ALWAYS, 'person: «always»');
    foreach (['keep', 'shared', 'review', 'reviewKeep', 'other'] as $key) {
        $check($protection('cms_company', $c[$key]) === CrmProtection::FREE, "$key: protection kept");
    }
    foreach (['shared', 'review', 'reviewKeep', 'other'] as $key) {
        $check(!(new yii\db\Query())->from('cms_company2manager')->where(['cms_company_id' => $c[$key], 'is_main' => 1])->exists(), "$key: no main set");
    }
    $check((string)(new yii\db\Query())->select('access_level')->from('cms_company2manager')->where(['cms_company_id' => $c['ownerOther'], 'cms_user_id' => $x])->scalar() === 'deal',
        'another participant keeps his link and level');
    $check((int)(new yii\db\Query())->from('cms_company2manager')->where(['cms_company_id' => $c['author']])->count() === 2, 'no link removed');
    $events = (new yii\db\Query())->select(['n' => 'COUNT(*)'])->from('cms_crm_access_log')->where(['>', 'id', $logBefore])->groupBy('event')->indexBy('event')->column();
    $check((int)($events['main_auto'] ?? 0) === 6 && (int)($events['protection'] ?? 0) === 5, 'journal: 6 main_auto, 5 protection '.json_encode($events));

    // A repeated run: nothing left to change, nothing written.
    $again = $fixture($service->plan());
    $check(!array_filter($again, static fn($row) => $row['change']), 'a repeated plan has no changes');
    $check($service->apply($again) === [0, 0], 'a repeated apply changes nothing');
    $check((int)(new yii\db\Query())->from('cms_crm_access_log')->count() === $before + 11, 'a repeated apply writes nothing');

    // The console command: the plan writes nothing.
    $command = static fn() => new class('crm', Yii::$app->getModule('cms')) extends skeeks\cms\console\controllers\CrmController {
        public string $out = '';

        public function stdout($string)
        {
            $this->out .= $string;

            return strlen($string);
        }

        public function stderr($string)
        {
            $this->out .= $string;

            return strlen($string);
        }
    };
    $controller = $command();
    $code = $controller->runAction('assign-owners', ['owners' => "$w1,$w2", 'keep' => (string)$k, 'review' => (string)$r]);
    $out = $controller->out;
    $check($code === 0 && strpos($out, 'Ничего не записано') !== false && strpos($out, 'СПОРНО: у владельца и у сотрудника одновременно') !== false
        && strpos($out, 'Проверка: владельцы — общая') !== false, 'the command prints the plan and the contested records');
    $check((int)(new yii\db\Query())->from('cms_crm_access_log')->count() === $before + 11, 'the plan of the command writes nothing');
    $usage = $command()->runAction('assign-owners', []);
    $check($usage !== 0, 'the command without owners refuses');
} finally {
    $transaction->rollBack();
}
echo "OK crm-owner-assignment-stand: {$checks} checks\n";
