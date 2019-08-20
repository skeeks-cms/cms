<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @property integer $id
 * @property integer $cms_user_id
 * @property string  $value
 * @property boolean $is_approved
 * @property boolean $is_main
 * @property string|null  $approved_key
 * @property integer|null  $approved_key_at
 *
 * @property CmsUser $cmsUser
 * @property string $approveUrl
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsUserEmail extends \skeeks\cms\base\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_email}}';
    }


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_UPDATE, [$this, "beforeSaveEvent"]);
    }

    /**
     * @param $event
     */
    public function beforeSaveEvent($event)
    {
        if (!$this->is_main) {
            if ($this->cms_user_id) {
                if (!static::find()->where(['is_main' => 1])->andWhere([
                    '!=',
                    'id',
                    $this->id,
                ])->andWhere(['cms_user_id' => $this->cms_user_id])->count()) {
                    $this->is_main = 1;
                }
            }
        } else if ($this->is_main) {
            if ($this->cms_user_id) {
                static::updateAll(['is_main' => 0], [
                    'and',
                    ['cms_user_id' => $this->cms_user_id],
                    ['!=', 'id', $this->id],
                ]);
            }
        }
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_user_id', 'created_at', 'updated_at'], 'integer'],
            [['cms_user_id'], 'required'],
            [['value'], 'required'],
            [['value'], 'email'],
            [['value', 'approved_key'], 'string', 'max' => 255],
            [['is_main', 'is_approved'], 'boolean'],
            [['value'], 'unique'],
            [['is_main'], 'default', 'value' => 0],
            [['is_approved'], 'default', 'value' => 0],
            [['approved_key_at'], 'integer'],
            [['approved_key_at', 'approved_key'], 'default', 'value' => null],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id' => Yii::t('skeeks/cms', 'User'),
            'value'   => "Email",
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        $userClass = isset(\Yii::$app->user) ? \Yii::$app->user->identityClass : CmsUser::class;
        return $this->hasOne($userClass, ['id' => 'cms_user_id']);
    }


    /**
     * @deprecated
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->getCmsUser();
    }


    public function genereteApprovedKey()
    {
        $this->approved_key = \Yii::$app->security->generateRandomString();
        $this->approved_key_at = time();

        if (!$this->save()) {
            throw new Exception('Не сохранились данные ключа');
        }

        return $this;
    }


    /**
     * @return bool
     */
    public function submitApprovedKey()
    {
        \Yii::$app->mailer->view->theme->pathMap = ArrayHelper::merge(\Yii::$app->mailer->view->theme->pathMap, [
            '@app/mail' => [
                '@skeeks/cms/mail-templates',
            ],
        ]);

        return \Yii::$app->mailer->compose('@app/mail/approve-email', [
            'approveUrl'     => $this->approveUrl,
            'email' => $this->value,
        ])
            ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName])
            ->setTo($this->value)
            ->setSubject('Подтверждение email '.\Yii::$app->cms->appName)
            ->send();
    }

    /**
     * Ссылка на подтверждение email адреса
     *
     * @param bool $scheme
     * @return string
     */
    public function getApproveUrl($scheme = true)
    {
        return Url::to(['/cms/auth/approve-email', 'token' => $this->approved_key], $scheme);
    }
}
