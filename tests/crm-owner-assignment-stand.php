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
$c = $config->get($config->has('console-'.ENV) ? 'console-'.ENV : 'console');
// User::getSubordinates() reads the current user component, as the web application has it.
$c['components']['user'] = ['class' => yii\web\User::class, 'identityClass' => skeeks\cms\models\CmsUser::class, 'enableSession' => false];
new yii\console\Application($c);

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
    // Contacts without links of their own: of an owner company, of a keep company, of both (contested), of a company without a main.
    $contact = static function (string $name, array $companies) use ($db, $now): int {
        $db->createCommand()->insert('cms_user', ['username' => 'proverka-owners-'.$name.'-'.$now, 'first_name' => 'Проверка: контакт '.$name, 'is_worker' => 0,
            'created_at' => $now, 'updated_at' => $now, 'cms_site_id' => (int)(new yii\db\Query())->select('id')->from('cms_site')->orderBy(['id' => SORT_ASC])->scalar(),
            'crm_protection' => CrmProtection::FREE, 'auth_key' => 'x', 'password_hash' => 'x'])->execute();
        $id = (int)$db->getLastInsertID();
        foreach ($companies as $company) {
            $db->createCommand()->insert('cms_company2user', ['cms_company_id' => $company, 'cms_user_id' => $id])->execute();
        }

        return $id;
    };
    $people = [
        'person' => $person,
        'contactOwner' => $contact('владельца', [$c['owner']]),
        'contactKeep' => $contact('своего', [$c['keep'], $c['other']]),
        'contactBoth' => $contact('общий', [$c['owner'], $c['keep']]),
        'contactNoMain' => $contact('без основного', [$c['other']]),
    ];

    $service = new OwnerAssignment([$w1, $w2], [$k], [$r]);
    $fixture = static function (array $plan) use ($c, $people): array {
        $rows = [];
        foreach ($plan as $row) {
            $key = array_search($row['id'], $row['code'] === 'client' ? $people : $c, true);
            if ($key !== false && $key !== null) {
                $rows[$key] = $row;
            }
        }

        return $rows;
    };
    $plan = $fixture($service->plan());
    $check(count($plan) === 13 && !isset($plan['contactNoMain']), 'every fixture is in the plan, a contact of a company without a main is not ('.count($plan).')');
    foreach (['owner' => 'owner', 'author' => 'owner', 'earliest' => 'owner', 'keep' => 'keep', 'shared' => 'shared', 'review' => 'review', 'reviewKeep' => 'review',
                 'other' => 'other', 'ownerOther' => 'owner', 'person' => 'owner',
                 'contactOwner' => 'contact', 'contactKeep' => 'contact', 'contactBoth' => 'contact_contested'] as $key => $group) {
        $check($plan[$key]['group'] === $group, "$key: group $group (".$plan[$key]['group'].')');
    }
    $check($plan['contactOwner']['user_id'] === $w1 && $plan['contactOwner']['always'], 'a contact of an owner company: the owner, «always»');
    $check($plan['contactKeep']['user_id'] === $k && !$plan['contactKeep']['always'], 'a contact of a keep company (and of one without a main): the employee');
    $check($plan['contactBoth']['user_id'] === null && !$plan['contactBoth']['change'], 'a contact of companies of different mains: only listed');
    $check($plan['owner']['user_id'] === $w1 && $plan['author']['user_id'] === $w1 && $plan['earliest']['user_id'] === $w2 && $plan['person']['user_id'] === $w2,
        'owner choice: the only owner, the author owner, the earliest owner link');
    $check($plan['keep']['user_id'] === $k && !$plan['keep']['always'], 'keep: the employee, no «always»');
    foreach (['shared', 'review', 'reviewKeep', 'other'] as $key) {
        $check(!$plan[$key]['change'] && $plan[$key]['user_id'] === null, "$key: no change");
    }
    $before = (int)(new yii\db\Query())->from('cms_crm_access_log')->count();
    $logBefore = (int)(new yii\db\Query())->select('MAX(id)')->from('cms_crm_access_log')->scalar();

    [$mains, $always] = $service->apply($plan);
    $check($mains === 8 && $always === 6, "apply: 8 mains, 6 «always» ($mains, $always)");
    $access = CrmAccess::instance();
    $access->resetMemo();
    foreach (['owner' => $w1, 'author' => $w1, 'earliest' => $w2, 'ownerOther' => $w1, 'keep' => $k] as $key => $user) {
        $check($access->mainResponsibleId('company', $c[$key]) === $user, "$key: main $user");
    }
    $check($access->mainResponsibleId('client', $person) === $w2, 'person: main');
    $check($access->mainResponsibleId('client', $people['contactOwner']) === $w1 && $access->mainResponsibleId('client', $people['contactKeep']) === $k,
        'contacts: the main of their company');
    $check($access->mainResponsibleId('client', $people['contactBoth']) === null && $access->mainResponsibleId('client', $people['contactNoMain']) === null,
        'contested and no-main contacts: no main');
    $check((string)(new yii\db\Query())->select('crm_protection')->from('cms_user')->where(['id' => $people['contactOwner']])->scalar() === CrmProtection::ALWAYS
        && (string)(new yii\db\Query())->select('crm_protection')->from('cms_user')->where(['id' => $people['contactKeep']])->scalar() === CrmProtection::FREE,
        'contacts: «always» only with an owner');
    // A personal order of the contact (no company) is seen by the main of his company now.
    $db->createCommand()->insert('shop_payment', ['cms_user_id' => $people['contactKeep'], 'amount' => 100, 'is_debit' => 1, 'created_at' => $now,
        'cms_site_id' => (int)(new yii\db\Query())->select('id')->from('cms_site')->orderBy(['id' => SORT_ASC])->scalar()])->execute();
    $personalPayment = (int)$db->getLastInsertID();
    $payments = 'shop_payment';
    $check((new yii\db\Query())->from($payments)->andWhere(['id' => $personalPayment])->andWhere($access->clientRecordCondition($k, $payments))->exists(),
        'the personal payment of the contact is seen by the main of his company');
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
    $check((int)($events['main_auto'] ?? 0) === 8 && (int)($events['protection'] ?? 0) === 6, 'journal: 8 main_auto, 6 protection '.json_encode($events));
    $check((new yii\db\Query())->from('cms_crm_access_log')->where(['>', 'id', $logBefore])
        ->andWhere(['record_code' => 'client', 'record_id' => $people['contactKeep'], 'event' => 'main_auto'])->andWhere(['like', 'comment', 'основной контакта'])->exists(),
        'journal: the contact event says why');

    // A repeated run: nothing left to change, nothing written.
    $again = $fixture($service->plan());
    $check(!array_filter($again, static fn($row) => $row['change']), 'a repeated plan has no changes');
    $check($service->apply($again) === [0, 0], 'a repeated apply changes nothing');
    $check((int)(new yii\db\Query())->from('cms_crm_access_log')->count() === $before + 14, 'a repeated apply writes nothing');

    // protect-owners: every client whose main is an owner — also one given from the former pool — becomes «always»;
    // a client of a keep employee does not; a repeated run changes nothing; the protection agent keeps «always».
    $c['pool'] = $company('из пула', null, [$w2]);
    $c['poolKeep'] = $company('из пула сотруднику', null, [$k]);
    $db->createCommand()->update('cms_company2manager', ['is_main' => 1], ['cms_company_id' => [$c['pool'], $c['poolKeep']]])->execute();
    $access->resetMemo();
    $protectionRows = static function () use ($service, $c): array {
        return array_values(array_filter($service->protectionPlan(), static fn($row) => $row['code'] === 'company' && in_array($row['id'], $c, true)));
    };
    $rows = $protectionRows();
    $check(count($rows) === 1 && $rows[0]['id'] === $c['pool'] && $rows[0]['user_id'] === $w2, 'protect plan: only the owner client not yet «always» '.json_encode($rows));
    $logBeforeProtect = (int)(new yii\db\Query())->select('MAX(id)')->from('cms_crm_access_log')->scalar();
    $check($service->applyProtection($rows) === 1 && $protection('cms_company', $c['pool']) === CrmProtection::ALWAYS
        && $protection('cms_company', $c['poolKeep']) === CrmProtection::FREE, 'protect apply: the owner client «always», the keep client unchanged');
    $check((new yii\db\Query())->from('cms_crm_access_log')->where(['>', 'id', $logBeforeProtect])
        ->andWhere(['event' => 'protection', 'record_code' => 'company', 'record_id' => $c['pool'], 'user_id' => $w2])->exists(), 'protect: the journal has the event');
    $check($protectionRows() === [] && $service->applyProtection($protectionRows()) === 0, 'protect: a repeated run changes nothing');
    (new CrmProtection())->recalc('company', $c['pool']);
    $check($protection('cms_company', $c['pool']) === CrmProtection::ALWAYS, 'the protection agent (recalc) keeps «always»');

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
    $check((int)(new yii\db\Query())->from('cms_crm_access_log')->count() === $before + 15, 'the plan of the command writes nothing');
    $usage = $command()->runAction('assign-owners', []);
    $check($usage !== 0, 'the command without owners refuses');

    // The whole release sequence (audit F4-01, 30.10.2026) with the real roles of the site: a company of the
    // former pool (no links) and its historical contact (no author, no history of his own). The first
    // assign-owners cannot give the contact a main — the company has none yet; assign-pool-main gives the
    // company to an owner and skips the contact (a person in a company is not in the pool). protect-owners
    // must not run then: the contact would stay free, and «Работать» would make a deal without a request.
    // The repeated assign-owners gives the contact the main of his company and «always».
    $ids = static fn(string $name, string $default) => array_values(array_filter(array_map('intval', explode(',', getenv($name) ?: $default))));
    $realOwners = $ids('TEST_OWNERS', '1,23');
    $realKeep = $ids('TEST_KEEP', '110');
    $realReview = $ids('TEST_REVIEW', '1663');
    foreach (array_merge($realOwners, $realKeep, $realReview) as $id) {
        $check(\skeeks\cms\models\CmsUser::find()->andWhere(['id' => $id, 'is_worker' => 1])->exists(), "the worker $id is on the stand");
    }
    $owner = $realOwners[0];
    $poolCompany = $company('из пула с контактом', null, []);
    $poolContact = $contact('исторический', [$poolCompany]);
    $check((new yii\db\Query())->select('created_by')->from('cms_user')->where(['id' => $poolContact])->scalar() === null
        && !(new yii\db\Query())->from(['u' => 'cms_user'])->where(['u.id' => $poolContact])->andWhere(CrmAccess::historyCondition('client', 'u.id'))->exists(),
        'the historical contact: no author, no history of his own');
    $sequence = new OwnerAssignment($realOwners, $realKeep, $realReview);
    $ours = static fn(array $rows) => array_values(array_filter($rows, static fn($row) => ($row['code'] === 'company' && (int)$row['id'] === $poolCompany)
        || ($row['code'] === 'client' && (int)$row['id'] === $poolContact)));
    $changes = static fn(array $rows) => array_values(array_filter($rows, static fn($row) => $row['change']));
    $first = $ours($sequence->plan());
    $check($first === [], 'sequence 1, assign-owners: neither the pool company nor its contact '.json_encode($first));
    $poolPlan = $ours((new CrmProtection())->poolMainPlan($owner));
    $check(count($poolPlan) === 1 && $poolPlan[0]['code'] === 'company' && $poolPlan[0]['user_id'] === $owner,
        'sequence 2, assign-pool-main: the company to the owner, the contact is not in the pool '.json_encode($poolPlan));
    $check((new CrmProtection())->applyHistoryMainPlan($poolPlan) === 1, 'sequence 2: applied');
    $access->resetMemo();
    // The miss: the contact waits for the main of his company, protect-owners does not see him.
    $pending = $ours($sequence->pendingOwnerContacts());
    $check(count($pending) === 1 && (int)$pending[0]['id'] === $poolContact && $pending[0]['user_id'] === $owner && $pending[0]['always'],
        'F4-01: after the pool the contact of the owner company is pending '.json_encode($pending));
    $check(!array_filter($sequence->protectionPlan(), static fn($row) => $row['code'] === 'client' && (int)$row['id'] === $poolContact)
        && $access->mainResponsibleId('client', $poolContact) === null && !$access->approvalRequired('client', $poolContact),
        'F4-01: protect-owners does not see the contact, he is free without a main');
    $protectBefore = (int)(new yii\db\Query())->from('cms_crm_access_log')->count();
    $controller = $command();
    $code = $controller->runAction('protect-owners', ['owners' => implode(',', $realOwners), 'apply' => 1]);
    $check($code !== 0 && strpos($controller->out, 'повторите cms/crm/assign-owners') !== false
        && (int)(new yii\db\Query())->from('cms_crm_access_log')->count() === $protectBefore
        && $protection('cms_company', $poolCompany) === CrmProtection::FREE,
        'protect-owners --apply refuses while contacts are pending and writes nothing');
    // The fix: assign-owners again with the same options, then protect-owners.
    // The repeated assign-owners also makes the pool company of the owner «always» (it is an owner row now).
    $second = $changes($ours($sequence->plan()));
    $secondContact = array_values(array_filter($second, static fn($row) => $row['code'] === 'client'));
    $check(count($second) === 2 && count($secondContact) === 1 && $secondContact[0]['group'] === OwnerAssignment::CONTACT
        && $secondContact[0]['user_id'] === $owner && $secondContact[0]['always'],
        'sequence 3, repeated assign-owners: the contact gets the main of his company '.json_encode($second));
    $check($sequence->apply($second) === [1, 2], 'sequence 3: one main, two «always»');
    $access->resetMemo();
    $check($ours($sequence->pendingOwnerContacts()) === [], 'sequence 3: nothing pending');
    $controller = $command();
    $code = $controller->runAction('protect-owners', ['owners' => implode(',', $realOwners)]);
    $check($code === 0 && strpos($controller->out, 'Ничего не записано') !== false && $ours($sequence->protectionPlan()) === [],
        'sequence 4, protect-owners: nothing of the sequence left to protect');
    $check($access->mainResponsibleId('client', $poolContact) === $access->mainResponsibleId('company', $poolCompany)
        && $access->mainResponsibleId('company', $poolCompany) === $owner, 'the main of the contact is the main of his company');
    $check($protection('cms_user', $poolContact) === CrmProtection::ALWAYS && $protection('cms_company', $poolCompany) === CrmProtection::ALWAYS,
        'the contact and the company «always»');
    $check($changes($ours($sequence->plan())) === [] && $ours((new CrmProtection())->poolMainPlan($owner)) === []
        && $ours($sequence->protectionPlan()) === [] && $ours($sequence->pendingOwnerContacts()) === [], 'every repeated plan of the sequence is empty');
    $check($access->approvalRequired('client', $poolContact) && $access->approvalRequired('company', $poolCompany), 'the approval is required');
    foreach (array_merge($realKeep, $realReview) as $actor) {
        Yii::$app->user->setIdentity(\skeeks\cms\models\CmsUser::findOne($actor));
        $access->resetMemo();
        $result = (new \skeeks\cms\crm\LeadTriage())->workWithClient($access->candidateRef('client', $poolContact), $actor, 'Проверка: владельцы — порядок');
        $check($result['lead'] === null && $result['request'] !== null, "«Работать» under $actor: a request to the owner, no deal");
    }
} finally {
    $transaction->rollBack();
}
echo "OK crm-owner-assignment-stand: {$checks} checks\n";
