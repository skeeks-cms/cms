<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\helpers\StringHelper;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "cms_user_email".
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property int         $cms_user_id
 * @property string      $value Email
 * @property string|null $name Примечание к Email
 * @property int         $sort
 * @property int         $is_approved Email подтвержден?
 * @property string|null $approved_key Ключ для подтверждения Email
 * @property int|null    $approved_key_at Время генерация ключа
 *
 * @property CmsSite     $cmsSite
 * @property CmsUser     $cmsUser
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
     * @return \skeeks\cms\query\CmsActiveQuery
     */
    /*public static function find()
    {
        return static::find()->cmsSite();
    }*/

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'cms_user_id', 'sort', 'is_approved', 'approved_key_at'], 'integer'],
            [['cms_user_id', 'value'], 'required'],
            [['value', 'name', 'approved_key'], 'string', 'max' => 255],
            [['cms_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['cms_user_id' => 'id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['updated_by' => 'id']],

            [['cms_site_id', 'value'], 'unique', 'targetAttribute' => ['cms_site_id', 'value'], 'message' => 'Этот email уже занят'],
            [['cms_user_id', 'value'], 'unique', 'targetAttribute' => ['cms_user_id', 'value'], 'message' => 'Этот email уже занят'],

            [['is_approved'], 'default', 'value' => 0],
            [['name', 'approved_key_at', 'approved_key'], 'default', 'value' => null],



            [['value'], "filter", 'filter' => 'trim'],
            [
                ['value'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ],
            [['value'], "email"],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id' => Yii::t('skeeks/cms', 'User'),
            'name'        => "Описание",
            'value'       => "Email",
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
     * @return $this
     * @throws Exception
     */
    public function genereteApprovedKey()
    {
        if (\Yii::$app->cms->approved_key_is_letter) {
            $this->approved_key = \Yii::$app->security->generateRandomString((int)\Yii::$app->cms->email_approved_key_length);
        } else {
            $permitted_chars = '0123456789012345678901234567890123456789';
            // Output: 54esmdr0qf
            $random = substr(str_shuffle($permitted_chars), 0, (int)\Yii::$app->cms->email_approved_key_length);
            $this->approved_key = $random;
        }

        $this->approved_key_at = time();

        if (!$this->save()) {
            throw new Exception('Не сохранились данные ключа: '.print_r($this->errors, true));
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
            'approveUrl'  => $this->approveUrl,
            'approveCode' => $this->approved_key,
            'email'       => $this->value,
        ])
            ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName])
            ->setTo(trim($this->value))
            ->setSubject('Подтверждение e-mail')
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
