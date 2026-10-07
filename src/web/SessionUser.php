<?php
namespace skeeks\cms\web;

use Yii;
use yii\web\Cookie;
use skeeks\cms\models\CmsUserSession;

/** Opt-in revocable sessions, including Yii remember-me restoration. */
class SessionUser extends \yii\web\User
{
    public $trackSessions = false;
    /** Set by Cms after its settings are loaded; null preserves custom integrations. */
    public $sessionLifetime;
    public $sessionIdleTimeout = 0;
    private $restoringSecret;
    private const SESSION_KEY = '__skeeks_login_secret';

    public function getCurrentSession(): ?CmsUserSession
    {
        $identity = $this->getIdentity();
        return $this->trackSessions && $this->enableSession && $identity
            ? Yii::$app->userSessions->resolve(Yii::$app->session->get(self::SESSION_KEY), $identity)
            : null;
    }

    public function switchIdentity($identity, $duration = 0)
    {
        if (!$this->trackSessions || !$this->enableSession) {
            parent::switchIdentity($identity, $duration);
            return;
        }
        $session = Yii::$app->session;
        if ($identity && !$this->restoringSecret && $duration > 0 && $this->sessionLifetime !== null) {
            $duration = $this->sessionLifetime;
        }
        $previous = $session->get(self::SESSION_KEY);
        $previousIdentity = $this->getIdentity(false);
        if (!$previousIdentity && $session->get($this->idParam) !== null) {
            $identityClass = $this->identityClass;
            $previousIdentity = $identityClass::findIdentity($session->get($this->idParam));
        }
        if ($previous && $previousIdentity && !$this->restoringSecret) {
            $row = CmsUserSession::findOne(['realm' => Yii::$app->userSessions->realm,
                'cms_user_id' => $previousIdentity->getId(), 'secret_hash' => hash('sha256', $previous)]);
            if ($row) { Yii::$app->userSessions->revoke((int)$row->cms_user_id, (int)$row->id); }
        }
        $session->remove(self::SESSION_KEY);
        if ($identity) {
            if ($this->restoringSecret) {
                $secret = $this->restoringSecret;
            } else {
                // Remember-me must not shorten an otherwise valid PHP session.
                // Yii still enforces authTimeout independently as its idle timeout.
                $lifetime = $this->sessionLifetime ?? max((int)$duration, (int)$session->timeout);
                if ($this->absoluteAuthTimeout) { $lifetime = min($lifetime, $this->absoluteAuthTimeout); }
                [, $secret] = Yii::$app->userSessions->create($identity, (int)$lifetime, (int)$this->sessionIdleTimeout);
            }
            $session->set(self::SESSION_KEY, $secret);
        }
        parent::switchIdentity($identity, $duration);
    }

    protected function renewAuthStatus()
    {
        parent::renewAuthStatus();
        if (!$this->trackSessions || !$this->enableSession || !$this->getIdentity(false)) { return; }
        $row = $this->getCurrentSession();
        if (!$row) {
            // Legacy sessions and cookies must sign in once when tracking is enabled.
            if (!$this->logout(false)) {
                // A beforeLogout veto cannot keep a revoked credential authenticated.
                $this->switchIdentity(null);
            }
            return;
        }
        if ((int)$row->last_seen_at < time() - 60) {
            Yii::$app->userSessions->touch($row);
        }
    }

    protected function loginByCookie()
    {
        if (!$this->trackSessions) { parent::loginByCookie(); return; }
        $data = parent::getIdentityAndDurationFromCookie();
        $secret = Yii::$app->request->cookies->getValue($this->credentialCookieName());
        if (!$data || !is_string($secret) || !Yii::$app->userSessions->resolve($secret, $data['identity'])) {
            $this->removeIdentityCookie();
            return;
        }
        $this->restoringSecret = $secret;
        try { parent::loginByCookie(); } finally { $this->restoringSecret = null; }
    }

    protected function sendIdentityCookie($identity, $duration)
    {
        parent::sendIdentityCookie($identity, $duration);
        if ($this->trackSessions) {
            $row = $this->getCurrentSession();
            if (!$row) { $this->removeIdentityCookie(); return; }
            Yii::$app->response->cookies->add(new Cookie(array_merge($this->identityCookie, [
                'name' => $this->credentialCookieName(),
                'value' => Yii::$app->session->get(self::SESSION_KEY),
                'expire' => min(time() + $duration, $row->expires_at), 'httpOnly' => true,
                'secure' => Yii::$app->request->isSecureConnection, 'sameSite' => Cookie::SAME_SITE_LAX,
            ])));
        }
    }

    protected function renewIdentityCookie()
    {
        parent::renewIdentityCookie();
        if (!$this->trackSessions) { return; }
        $identity = $this->getIdentity(false);
        $row = $identity ? Yii::$app->userSessions->resolve(Yii::$app->session->get(self::SESSION_KEY), $identity) : null;
        $cookie = Yii::$app->response->cookies->get($this->identityCookie['name']);
        if ($row && $cookie) {
            Yii::$app->response->cookies->add(new Cookie(array_merge($this->identityCookie, [
                'name' => $this->credentialCookieName(),
                'value' => Yii::$app->session->get(self::SESSION_KEY),
                'expire' => min($cookie->expire, $row->expires_at),
                'httpOnly' => true, 'secure' => Yii::$app->request->isSecureConnection,
                'sameSite' => Cookie::SAME_SITE_LAX,
            ])));
        }
    }

    protected function removeIdentityCookie()
    {
        parent::removeIdentityCookie();
        if ($this->trackSessions) {
            Yii::$app->response->cookies->remove(new Cookie(array_merge($this->identityCookie, [
                'name' => $this->credentialCookieName(),
            ])));
        }
    }

    private function credentialCookieName(): string { return $this->identityCookie['name'].'_SESSION'; }
}
