<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\assets\CmsAsset;
use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasUserLog;
use skeeks\cms\rbac\models\CmsAuthAssignment;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_user_log".
 *
 * @property int $id
 * @property int|null $created_by
 * @property int|null $created_at
 * @property string|null $user_ip
 * @property int $cms_site_id
 * @property string $model
 * @property string $model_pk
 * @property string $action_type
 * @property string|null $action_data
 * @property string|null $comment
 *
 * @property CmsSite $cmsSite
 * @property CmsUser $createdBy
 */
class CmsUserLog extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_user_log';
    }

    public function behaviors()
    {
        $result = ArrayHelper::merge(parent::behaviors(), [
            HasJsonFieldsBehavior::class      => [
                'class'  => HasJsonFieldsBehavior::class,
                'fields' => ['action_data'],
            ],
        ]);

        ArrayHelper::remove($result, HasUserLog::class);

        return $result;
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_site_id'], 'integer'],
            [['model', 'model_pk', 'action_type'], 'required'],
            [['comment'], 'string'],
            [['created_by_name'], 'string'],
            [['action_data'], 'safe'],
            [['user_ip'], 'string', 'max' => 20],
            [['model', 'model_pk', 'action_type'], 'string', 'max' => 255],

            [
                'created_by_name',
                'default',
                'value' => function () {
                    if (!\Yii::$app->user->isGuest) {
                        return \Yii::$app->user->identity->shortDisplayName;
                    }

                    return null;
                },
            ],
            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],
            [
                'user_ip',
                'default',
                'value' => function () {
                    if (isset(\Yii::$app->request)) {
                        return \Yii::$app->request->userIP;
                    }
                },
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'user_ip' => 'User Ip',
            'cms_site_id' => 'Cms Site ID',
            'model' => 'Model',
            'model_pk' => 'Model Pk',
            'action_type' => 'Action Type',
            'action_data' => 'Action Data',
            'comment' => 'Comment',
        ]);
    }

    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::class, ['id' => 'cms_site_id']);
    }

}