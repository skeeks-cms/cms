<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserSchedule;
use yii\base\Exception;
use yii\base\Widget;

class CmsWebNotifyWidget extends Widget
{
    /**
     * Enables admin-only idle and stale work reminders.
     * Client cabinets normally keep this disabled.
     *
     * @var bool
     */
    public $enableWorkReminders = false;

    /**
     * The personal notification settings page ("⚙" in the window). Null — the
     * administration profile page for those who have the administration.
     *
     * @var string|array|false|null
     */
    public $settingsUrl;

    public function getSettingsUrl(): ?string
    {
        if ($this->settingsUrl === false) {
            return null;
        }
        if ($this->settingsUrl !== null) {
            return \yii\helpers\Url::to($this->settingsUrl);
        }
        if (\Yii::$app->user->can(\skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS)) {
            return \yii\helpers\Url::to(['/cms/admin-profile/notifications']);
        }

        return null;
    }

    public function getUser()
    {
        return \Yii::$app->user->identity;
    }

    public function run()
    {
        $error = '';
        
        $CmsUserSchedule = $this->enableWorkReminders
            ? CmsUserSchedule::find()->user($this->user)->notEnd()->one()
            : null;
        
        if ($this->enableWorkReminders && \Yii::$app->request->post() && \Yii::$app->request->post('action-type')) {

            //die;
            try {
                if (\Yii::$app->request->post('action-type') == 'start') {
                    if ($CmsUserSchedule) {
                        throw new Exception("Для начала нужно завершить работу.");
                    }

                    $CmsUserSchedule = new CmsUserSchedule();
                    $CmsUserSchedule->cms_user_id = $this->user->id;
                    $CmsUserSchedule->start_at = time();
                    $CmsUserSchedule->end_at = null;
                    if (!$CmsUserSchedule->save()) {
                        //throw new Exception(print_r($CmsUserSchedule->errors, true));
                    }

                    $this->user->refresh();
                }
                
                if (\Yii::$app->request->post('action-type') == 'stop') {


                    if (!$CmsUserSchedule) {
                        throw new Exception("Вы не в работе");
                    }

                    $CmsUserSchedule = CmsUserSchedule::find()->user($this->user)->notEnd()->one();
                    $CmsUserSchedule->load(\Yii::$app->request->post());

                    //TODO: Поправить проверка на день
                    if (!$CmsUserSchedule->end_at) {
                        $CmsUserSchedule->end_at = time();
                    }


                    if (!$CmsUserSchedule->save()) {
                        //throw new Exception(print_r($CmsUserSchedule->errors, true));
                    }

                    $this->user->refresh();
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('web-notify');
    }
}
