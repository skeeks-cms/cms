<?php
namespace skeeks\cms\components;

use skeeks\cms\models\CmsUserSession;
use Yii;

/** Shared browser/mobile login registry. Never stores PHP session IDs. */
class UserSessions extends \yii\base\Component
{
    /** Raised inside the revocation transaction; listeners must not perform external I/O. */
    public const EVENT_REVOKED = 'revoked';
    public const EVENT_RENDER_DETAILS = 'renderDetails';
    public $realm = 'default';
    public $maxLifetime = 31536000;
    /** Optional presentation labels supplied by integrations, never auth factors. */
    public $userAgentLabels = [];

    public function create($identity, int $duration, int $idleTimeout = 0): array
    {
        $lifetime = min($this->maxLifetime, max(1, $duration));
        $idleTimeout = max(0, min($lifetime, $idleTimeout));
        $secret = Yii::$app->security->generateRandomString(64);
        $row = new CmsUserSession();
        $row->setAttributes([
            'realm' => $this->realm, 'cms_user_id' => $identity->getId(),
            'secret_hash' => hash('sha256', $secret),
            'auth_key_hash' => hash('sha256', (string)$identity->getAuthKey()),
            'label' => $this->deviceLabel(Yii::$app->request->userAgent ?? ''),
            'created_at' => time(), 'last_seen_at' => time(),
            'expires_at' => time() + ($idleTimeout ?: $lifetime),
            'absolute_expires_at' => time() + $lifetime,
            'idle_timeout' => $idleTimeout,
        ], false);
        $row->save(false);
        return [$row, $secret];
    }

    /** Only authenticated foreground requests extend inactivity, never queue workers. */
    public function touch(CmsUserSession $row): void
    {
        $now = time();
        $expires = $row->idle_timeout
            ? min((int)$row->absolute_expires_at, $now + (int)$row->idle_timeout)
            : (int)$row->expires_at;
        CmsUserSession::updateAll(['last_seen_at' => $now, 'expires_at' => $expires],
            ['and', ['id' => $row->id, 'revoked_at' => null], ['>', 'expires_at', $now]]);
    }

    public function resolve(?string $secret, $identity): ?CmsUserSession
    {
        if (!$secret || strlen($secret) !== 64 || !$identity) { return null; }
        return CmsUserSession::find()->where([
            'realm' => $this->realm, 'cms_user_id' => $identity->getId(),
            'secret_hash' => hash('sha256', $secret), 'revoked_at' => null,
            'auth_key_hash' => hash('sha256', (string)$identity->getAuthKey()),
        ])->andWhere(['>', 'expires_at', time()])->one();
    }

    public function revoke(int $userId, ?int $id = null, ?int $exceptId = null): int
    {
        return CmsUserSession::getDb()->transaction(function () use ($userId, $id, $exceptId) {
            $where = ['and', ['realm' => $this->realm, 'cms_user_id' => $userId, 'revoked_at' => null]];
            if ($id !== null) { $where[] = ['id' => $id]; }
            if ($exceptId !== null) { $where[] = ['<>', 'id', $exceptId]; }
            $ids = CmsUserSession::find()->select('id')->where($where)->column();
            $count = CmsUserSession::updateAll(['revoked_at' => time()], ['id' => $ids]);
            if ($ids) {
                $this->trigger(self::EVENT_REVOKED, new \skeeks\cms\events\UserSessionsRevokedEvent([
                    'sessionIds' => $ids, 'userId' => $userId, 'realm' => $this->realm,
                ]));
            }
            return $count;
        });
    }

    public function renderDetails(\yii\web\View $view, CmsUserSession $session): string
    {
        $event = new \skeeks\cms\events\UserSessionDetailsEvent(['view' => $view, 'session' => $session]);
        $this->trigger(self::EVENT_RENDER_DETAILS, $event);
        return $event->html;
    }

    public function deviceLabel(string $ua): string
    {
        $os = preg_match('/Android/i', $ua) ? 'Android' :
            (preg_match('/iPhone|iPad/i', $ua) ? 'iOS' :
            (stripos($ua, 'Windows') !== false ? 'Windows' :
            (stripos($ua, 'Macintosh') !== false ? 'macOS' : 'Linux / другая ОС')));
        $app = strpos($ua, 'Edg/') !== false ? 'Edge' :
            (strpos($ua, 'Firefox/') !== false ? 'Firefox' :
            (strpos($ua, 'Chrome/') !== false ? 'Chrome' :
            (strpos($ua, 'Safari/') !== false ? 'Safari' : 'Браузер')));
        foreach ($this->userAgentLabels as $marker => $label) {
            if ($marker !== '' && strpos($ua, $marker) !== false) { $app = mb_substr($label, 0, 100); break; }
        }
        return $app.' · '.$os;
    }

    /** Bounded cleanup; may be called by the installed scheduler without requiring cms-job. */
    public function cleanup(int $retentionSeconds = 2592000, int $limit = 500): int
    {
        if ($retentionSeconds < 86400 || $limit < 1 || $limit > 10000) { throw new \InvalidArgumentException('Invalid retention or batch size.'); }
        $cutoff = time() - $retentionSeconds;
        $condition = ['and', ['realm' => $this->realm], ['or', ['<', 'expires_at', $cutoff], ['<', 'revoked_at', $cutoff]]];
        $ids = CmsUserSession::find()->select('id')->where($condition)->orderBy('id')->limit($limit)->column();
        return $ids ? CmsUserSession::deleteAll(['and', ['id' => $ids], $condition]) : 0;
    }
}
