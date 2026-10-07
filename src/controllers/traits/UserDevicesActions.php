<?php
namespace skeeks\cms\controllers\traits;

use Yii;
use skeeks\cms\models\CmsUserSession;
use skeeks\cms\web\SessionUser;
use yii\web\NotFoundHttpException;

trait UserDevicesActions
{
    protected function deviceActions(): array
    {
        $enabled = Yii::$app->user instanceof SessionUser && Yii::$app->user->trackSessions;
        $actions = [];
        foreach (['devices' => 'Устройства и сеансы', 'revoke-device' => 'Выйти'] as $id => $name) {
            $method = 'action'.str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
            $actions[$id] = ['class' => \skeeks\cms\backend\BackendAction::class,
                'name' => $name, 'icon' => 'svg:lock', 'permissionNames' => [],
                'accessCallback' => static function () { return !Yii::$app->user->isGuest; },
                'isVisible' => $enabled && $id === 'devices', 'priority' => 30,
                'callback' => [$this, $method]];
        }
        return $actions;
    }

    protected function deviceSession(): CmsUserSession
    {
        if (!(Yii::$app->user instanceof SessionUser) || !($row = Yii::$app->user->currentSession)) {
            throw new \yii\web\UnauthorizedHttpException('Войдите в свой аккаунт.');
        }
        return $row;
    }

    protected function requireDevicePost(): void
    {
        if (!Yii::$app->request->isPost) { throw new \yii\web\MethodNotAllowedHttpException('Используйте POST.'); }
        // Controller's normal CSRF validation remains enabled.
        $this->deviceSession();
    }

    public function actionDevices()
    {
        $current = $this->deviceSession();
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        $sessions = CmsUserSession::find()->where(['realm' => $current->realm,
            'cms_user_id' => $current->cms_user_id, 'revoked_at' => null])
            ->andWhere(['>', 'expires_at', time()])->orderBy(['last_seen_at' => SORT_DESC])->all();
        return $this->render('@skeeks/cms/views/profile/devices', compact('current', 'sessions'));
    }

    public function actionRevokeDevice()
    {
        $this->requireDevicePost();
        $current = $this->deviceSession();
        $mode = Yii::$app->request->post('mode', 'one');
        $id = (int)Yii::$app->request->post('id');
        if (!in_array($mode, ['one', 'others', 'all'], true) || ($mode === 'one' && $id < 1)) {
            throw new \yii\web\BadRequestHttpException('Выберите сеанс.');
        }
        if ($mode === 'one' && !CmsUserSession::find()->where(['id' => $id,
            'realm' => $current->realm, 'cms_user_id' => $current->cms_user_id])->exists()) {
            throw new NotFoundHttpException('Сеанс не найден.');
        }
        Yii::$app->userSessions->revoke((int)$current->cms_user_id,
            $mode === 'one' ? $id : null, $mode === 'others' ? (int)$current->id : null);
        if ($mode === 'all' || ($mode === 'one' && $id === (int)$current->id)) {
            Yii::$app->user->logout();
            $url = Yii::$app->user->loginUrl;
            if (is_array($url) && isset($url[0])) { $url[0] = '/'.ltrim($url[0], '/'); }
            return $this->redirect($url);
        }
        Yii::$app->session->setFlash('success', 'Выбранные сеансы завершены.');
        return $this->redirect(['devices']);
    }

}
