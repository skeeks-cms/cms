<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.05.2015
 */

namespace skeeks\cms\models\behaviors;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\CmsTheme;
use skeeks\cms\models\CmsUserLog;
use yii\base\Behavior;
use yii\base\Event;
use yii\caching\Cache;
use yii\caching\TagDependency;
use yii\console\Application;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class HasUserLog extends Behavior
{
    /**
     * @var
     */
    public $model_code;

    const ACTION_UPDATE = 'update';
    const ACTION_INSERT = 'insert';
    const ACTION_DELETE = 'delete';

    /**
     * Атрибуты которые не надо логировать при обновлении
     * @var string[]
     */
    public $no_log_attributes = ['created_at', 'updated_at', 'updated_by', 'created_by', 'cms_site_id', 'id'];

    /**
     * @return array
     */
    public function events()
    {
        //Если это не web приложение не надо работать
        if (!\Yii::$app instanceof \yii\web\Application) {
            return [];
        }

        /*if (!YII_ENV_DEV) {
            return [];
        }*/

        return [
            BaseActiveRecord::EVENT_BEFORE_UPDATE => "logBeforeUpdate",

            BaseActiveRecord::EVENT_AFTER_UPDATE => "logAfterUpdate",
            BaseActiveRecord::EVENT_AFTER_INSERT => "logAfterInsert",
            BaseActiveRecord::EVENT_AFTER_DELETE => "logAfterDelete",
        ];
    }


    public $new_attributes = [];


    /**
     * @param Event $e
     * @return void
     */
    public function logBeforeUpdate(Event $e)
    {
        $model = $e->sender;
        $attrs = $model->toArray();

        foreach ($attrs as $key => $value)
        {
            if (in_array($key, $this->no_log_attributes)) {
                unset($attrs[$key]);
                continue;
            }

            if ($model->isAttributeChanged($key, false)) {

                $oldValue = $model->getOldAttribute($key);

                if ($oldValue === null && $value == '') {
                    continue;
                }

                //$oldAttributes[$attribute] = $oldValue;
                $this->new_attributes[$key] = $value;
            }
        }
    }
    /**
     * @return $this
     */
    public function logAfterUpdate(AfterSaveEvent $e)
    {
        if ($this->new_attributes) {
            /**
             * @var $model ActiveRecord
             */
            $model = $e->sender;

            $cmsUserLog = new CmsUserLog();
            $cmsUserLog->model = $model::tableName();
            $cmsUserLog->model_pk = (string) $model->getPrimaryKey();
            $cmsUserLog->action_data = ["attrs" => $this->new_attributes];
            $cmsUserLog->action_type = self::ACTION_UPDATE;
            if (!$cmsUserLog->save()) {
                \Yii::error("Не добавился лог: " . print_r($cmsUserLog->errors, true));
            }

        }

        return $this;
    }

    /**
     * @return $this
     */
    public function logAfterInsert(Event $e)
    {
        $model = $e->sender;

        $attrs = $model->toArray();


        foreach ($attrs as $key => $value)
        {
            if (in_array($key, $this->no_log_attributes)) {
                unset($attrs[$key]);
                continue;
            }
        }


        $cmsUserLog = new CmsUserLog();
        $cmsUserLog->model = $model::tableName();
        $cmsUserLog->model_pk = (string) $model->getPrimaryKey();
        $cmsUserLog->action_data = ["attrs" => $attrs];
        $cmsUserLog->action_type = self::ACTION_INSERT;
        if (!$cmsUserLog->save()) {
            \Yii::error("Не добавился лог: " . print_r($cmsUserLog->errors, true));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function logAfterDelete(Event $e)
    {
        $model = $e->sender;

        $attrs = $model->toArray();

        $cmsUserLog = new CmsUserLog();
        $cmsUserLog->model = $model::tableName();
        $cmsUserLog->model_pk = (string) $model->getPrimaryKey();
        $cmsUserLog->action_type = self::ACTION_DELETE;
        if (!$cmsUserLog->save()) {
            \Yii::error("Не добавился лог: " . print_r($cmsUserLog->errors, true));
        }

        return $this;
    }
}