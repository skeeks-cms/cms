<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsWebNotify;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * `/~n/<id>`: the link of a notification in mail and messengers. A job that
 * sends it runs in the console and does not know the administration prefix,
 * so the web request resolves it: the recipient signs in, the notification
 * (its whole group) becomes read and the browser goes to the record.
 */
class NotifyController extends Controller
{
    public function actionOpen($id)
    {
        if (\Yii::$app->user->isGuest) {
            return \Yii::$app->user->loginRequired();
        }
        $notify = CmsWebNotify::find()->andWhere(['id' => (int)$id, 'cms_user_id' => (int)\Yii::$app->user->id])->one();
        if (!$notify) {
            throw new NotFoundHttpException('Уведомление не найдено: оно удалено или адресовано другому человеку.');
        }
        CmsWebNotify::updateAll(['is_read' => 1, 'is_seen' => 1], $notify->group_key
            ? ['cms_user_id' => (int)$notify->cms_user_id, 'group_key' => $notify->group_key]
            : ['id' => (int)$notify->id]);

        return $this->redirect($notify->getOpenUrl());
    }
}
