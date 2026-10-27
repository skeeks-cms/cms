<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers\traits;

use skeeks\cms\notify\Notifications;
use Yii;

/**
 * The personal page "Уведомления" (what and where, quiet hours, muted kinds,
 * channels of the person). The same actions serve the administration profile
 * and, later, the client cabinet (step У5).
 */
trait UserNotifyActions
{
    protected function notifyActions(): array
    {
        return [
            'notifications' => [
                'class'           => \skeeks\cms\backend\BackendAction::class,
                'name'            => 'Уведомления',
                'icon'            => 'svg:bell',
                'permissionNames' => [],
                'accessCallback'  => static function () {
                    return !Yii::$app->user->isGuest;
                },
                'isVisible'       => true,
                'priority'        => 20,
                'callback'        => [$this, 'actionNotifications'],
            ],
        ];
    }

    public function actionNotifications()
    {
        if (Yii::$app->user->isGuest) {
            throw new \yii\web\UnauthorizedHttpException('Войдите в свой аккаунт.');
        }
        $userId = (int)Yii::$app->user->id;
        /** @var Notifications $notifications */
        $notifications = Yii::$app->notifications;
        $request = Yii::$app->request;

        if ($request->isPost) {
            $message = 'Сохранено.';
            $do = (string)$request->post('do');
            if (strpos($do, 'copy:') === 0) {
                $request->setBodyParams(array_merge($request->post(), ['channel' => substr($do, 5)]));
                $do = 'copy';
            }
            switch ($do) {
                case 'preset':
                    if (!$notifications->applyPreset($userId, (string)$request->post('preset'))) {
                        $message = null;
                    }
                    break;
                case 'copy':
                    if (!$notifications->copyCrm($userId, (string)$request->post('channel'))) {
                        $message = null;
                    }
                    break;
                case 'unmute':
                    $notifications->mute($userId, (string)$request->post('kind'), false);
                    $message = 'Уведомления этого вида снова включены.';
                    break;
                case 'quiet':
                    $profile = $notifications->profile($userId);
                    $profile->is_quiet = (int)(bool)$request->post('is_quiet');
                    $profile->quiet_from = self::notifyMinutes((string)$request->post('quiet_from'), (int)$profile->quiet_from);
                    $profile->quiet_to = self::notifyMinutes((string)$request->post('quiet_to'), (int)$profile->quiet_to);
                    $profile->save(false);
                    break;
                default:
                    $notifications->saveModes($userId, (array)$request->post('modes', []), (array)$request->post('kinds', []));
            }
            if ($message) {
                Yii::$app->session->setFlash('success', $message);
            } else {
                Yii::$app->session->setFlash('error', 'Не удалось сохранить.');
            }

            return $this->redirect(['notifications']);
        }

        return $this->render('@skeeks/cms/views/profile/notifications', [
            'userId'        => $userId,
            'notifications' => $notifications,
        ]);
    }

    private static function notifyMinutes(string $time, int $default): int
    {
        return preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $m) ? (int)$m[1] * 60 + (int)$m[2] : $default;
    }
}
