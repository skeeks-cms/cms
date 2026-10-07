<?php
/** Isolated SQLite + real Yii User lifecycle; no site data or external delivery. */
define('YII_ENABLE_ERROR_HANDLER', false);
require $argv[1];
require dirname($argv[1]).'/yiisoft/yii2/Yii.php';
require dirname(__DIR__).'/src/migrations/m261007_140000_user_sessions.php';

use skeeks\cms\web\SessionUser;
use skeeks\cms\models\CmsUserSession;
use skeeks\cms\components\UserSessions;

class TestIdentity implements yii\web\IdentityInterface
{
    public $id;
    public static function findIdentity($id) { $i = new self(); $i->id = $id; return $i; }
    public static function findIdentityByAccessToken($token, $type = null) { return null; }
    public function getId() { return $this->id; }
    public function getAuthKey() { return 'test-auth-key-'.$this->id; }
    public function validateAuthKey($key) { return $key === $this->getAuthKey(); }
}
class MemorySession extends yii\web\Session
{
    public $data = [];
    public function init() {}
    public function open() {}
    public function close() {}
    public function getIsActive() { return true; }
    public function getHasSessionId() { return true; }
    public function regenerateID($deleteOldSession = false) {}
    public function get($key, $defaultValue = null) { return $this->data[$key] ?? $defaultValue; }
    public function set($key, $value) { $this->data[$key] = $value; }
    public function remove($key) { $value = $this->data[$key] ?? null; unset($this->data[$key]); return $value; }
    public function destroy() { $this->data = []; }
}
class TestRequest extends yii\web\Request
{
    public $jar;
    public function getCookies() { return $this->jar; }
    public function getUserAgent() { return 'Mozilla/5.0 Android SkeekSMobile/0.1'; }
    public function getUserIP() { return '127.0.0.1'; }
    public function getIsSecureConnection() { return true; }
}
class TestUser extends SessionUser
{
    protected function regenerateCsrfToken() {}
}
$app = new yii\web\Application(['id' => 'sessions-test', 'basePath' => __DIR__, 'components' => [
    'request' => ['class' => TestRequest::class, 'cookieValidationKey' => 'test-only', 'scriptFile' => __FILE__, 'scriptUrl' => '/index.php'],
    'db' => ['class' => yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
    'cache' => ['class' => yii\caching\ArrayCache::class],
    'session' => ['class' => MemorySession::class],
    'userSessions' => ['class' => UserSessions::class],
]]);
$migration = new m261007_140000_user_sessions(['db' => $app->db, 'compact' => true]);
ob_start(); $migration->safeUp(); ob_end_clean();
$checks = 0;
function check($ok, $message) { global $checks; ++$checks; if (!$ok) throw new RuntimeException($message); }
function requestUser($session = null, array $cookies = []): TestUser {
    global $app;
    $app->set('session', $session ?: new MemorySession());
    $app->set('response', new yii\web\Response());
    $app->request->jar = new yii\web\CookieCollection($cookies);
    $app->set('user', new TestUser(['identityClass' => TestIdentity::class, 'trackSessions' => true, 'enableAutoLogin' => true]));
    return $app->user;
}
$first = requestUser();
check($first->login(TestIdentity::findIdentity(1), 3600), 'login');
$firstSession = $app->session;
$firstId = $first->currentSession->id;
$cookies = $app->response->cookies->toArray();
check(count($cookies) === 2, 'remember me has login and per-session cookie');
check(!array_key_exists('secret_hash', $first->currentSession->toArray()), 'secret hidden from serialization');
$again = requestUser($firstSession, $cookies);
check($again->id === 1 && $again->currentSession->id === $firstId, 'session survives next request');
$restored = requestUser(null, $cookies);
check($restored->id === 1 && $restored->currentSession->id === $firstId, 'remember me reuses same registry record');
$second = requestUser(); $second->login(TestIdentity::findIdentity(1), 3600);
$secondId = $second->currentSession->id;
$secondSession = $app->session;
check($secondId !== $firstId, 'independent device');
check($app->userSessions->revoke(2, $firstId) === 0, 'cannot revoke another account');
$app->userSessions->revoke(1, $firstId);
check(requestUser($firstSession, $cookies)->isGuest, 'revoked session denied');
check(requestUser(null, $cookies)->isGuest, 'revoked remember me denied');
check(!requestUser($secondSession)->isGuest, 'other device preserved');
$user = requestUser($secondSession);
$app->userSessions->revoke(1, null, (int)$secondId);
check(!$user->isGuest && requestUser($secondSession)->currentSession->id === $secondId, 'logout others retains current');
$app->userSessions->revoke(1);
check(requestUser($secondSession)->isGuest, 'logout all');
$legacySession = new MemorySession(); $legacySession->set('__id', 1); $legacySession->set('__authKey', 'test-auth-key-1');
check(requestUser($legacySession)->isGuest, 'untracked legacy session requires fresh login');
$legacyCookies = $cookies; unset($legacyCookies['_identity_SESSION']);
check(requestUser(null, $legacyCookies)->isGuest, 'legacy remember me cannot resurrect access');
$expired = requestUser(); $expired->login(TestIdentity::findIdentity(1), 3600);
$expiredId = $expired->currentSession->id; $expiredSession = $app->session;
CmsUserSession::updateAll(['expires_at' => time() - 1], ['id' => $expiredId]);
check(requestUser($expiredSession)->isGuest, 'expired registry denied');
$plain = requestUser(); $plain->trackSessions = false;
check($plain->login(TestIdentity::findIdentity(1), 0), 'opt-out preserves Yii login');
check($app->db->getTableSchema('cms_mobile_installation') === null, 'core schema has no mobile tables');
check(!$app->has('mobilePush'), 'core runs without mobile component');
class DeviceActionFixture {
    use \skeeks\cms\controllers\traits\UserDevicesActions;
    public function getActions() { return $this->deviceActions(); }
}
check(array_keys((new DeviceActionFixture())->getActions()) === ['devices', 'revoke-device'], 'core profile exposes session actions only');
check($app->userSessions->renderDetails(new yii\web\View(), CmsUserSession::findOne($secondId)) === '', 'core renders without an extension');
// A shorter remember-me duration must not cap the active PHP session.
$long = requestUser(); $app->session->timeout = 31536000;
$long->login(TestIdentity::findIdentity(1), 2592000);
check($long->currentSession->expires_at >= time() + 31535990, 'remember me does not shorten PHP lifetime');
$longId = $long->currentSession->id;
$longSession = $app->session; $longCookies = $app->response->cookies->toArray();
$renewed = requestUser($longSession, $longCookies); $renewed->autoRenewCookie = true;
check(!$renewed->isGuest && count($app->response->cookies) === 2, 'cookie renewal refreshes both credentials');
$absolute = requestUser(); $app->session->timeout = 31536000; $absolute->absoluteAuthTimeout = 600;
$absolute->login(TestIdentity::findIdentity(1), 2592000);
check($absolute->currentSession->expires_at <= time() + 600, 'absolute timeout still caps registry');
$expireId = $absolute->currentSession->id; $expireSession = $app->session;
CmsUserSession::updateAll(['expires_at' => time() - 1], ['id' => $expireId]);
$events = []; $expiredUser = requestUser($expireSession);
$expiredUser->on(yii\web\User::EVENT_BEFORE_LOGOUT, function () use (&$events) { $events[] = 'before'; });
$expiredUser->on(yii\web\User::EVENT_AFTER_LOGOUT, function () use (&$events) { $events[] = 'after'; });
check($expiredUser->isGuest && $events === ['before', 'after'], 'expired registry emits logout lifecycle');
check(CmsUserSession::findOne($expireId)->revoked_at !== null, 'expired session gets extension revocation cleanup');
$old = time() - 40 * 86400;
CmsUserSession::updateAll(['revoked_at' => $old], ['id' => $expireId]);
check($app->userSessions->cleanup() === 1, 'retention removes only old terminal session');
check(CmsUserSession::findOne($longId) !== null, 'retention preserves active sessions');
// One policy governs fresh tracked logins regardless of the caller's old duration.
$policyUser = requestUser(); $policyUser->sessionLifetime = 10 * 86400; $policyUser->sessionIdleTimeout = 2 * 86400;
$policyUser->login(TestIdentity::findIdentity(1), 2592000);
$policyRow = $policyUser->currentSession;
$policySession = $app->session; $policyCookies = $app->response->cookies->toArray();
check(abs($policyRow->absolute_expires_at - time() - 10 * 86400) <= 2, 'configured absolute lifetime replaces caller duration');
check(abs($policyRow->expires_at - time() - 2 * 86400) <= 2, 'configured inactivity window');
$cookieData = json_decode($policyCookies['_identity']->value, true);
check($cookieData[2] === 10 * 86400, 'remember me uses the same policy rather than hardcoded month');
CmsUserSession::updateAll(['last_seen_at' => time() - 120, 'expires_at' => time() + 60], ['id' => $policyRow->id]);
check(!requestUser($policySession, $policyCookies)->isGuest, 'active tracked request');
$policyRow->refresh();
check($policyRow->expires_at >= time() + 2 * 86400 - 2, 'activity extends idle deadline');
CmsUserSession::updateAll(['last_seen_at' => time() - 120, 'absolute_expires_at' => time() + 90], ['id' => $policyRow->id]);
check(!requestUser($policySession)->isGuest, 'active near absolute deadline');
$policyRow->refresh();
check($policyRow->expires_at <= time() + 90, 'activity never exceeds absolute deadline');
CmsUserSession::updateAll(['expires_at' => time() - 1], ['id' => $policyRow->id]);
check(requestUser(null, $policyCookies)->isGuest, 'remember cookie cannot bypass idle expiration');
class CmsPolicyFixture extends \skeeks\cms\components\Cms { public function init() {} }
$policy = new CmsPolicyFixture();
$fields = ['auth_session_idle_days', 'auth_session_max_days'];
check($policy->validate($fields), 'default policy validates');
$policy->auth_session_idle_days = 31; $policy->auth_session_max_days = 30;
check(!$policy->validate($fields), 'idle cannot exceed absolute lifetime');
$policy->auth_session_idle_days = -1;
check(!$policy->validate($fields), 'negative idle rejected');
$policy->auth_session_idle_days = 0; $policy->auth_session_max_days = 0;
check(!$policy->validate($fields), 'unlimited absolute lifetime rejected');
echo "OK: $checks core session checks without cms-mobile.\n";
