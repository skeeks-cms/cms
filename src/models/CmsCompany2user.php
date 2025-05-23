<?php

namespace skeeks\cms\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int         $id
 * @property int         $created_by
 * @property int         $updated_by
 * @property int         $created_at
 * @property int         $updated_at
 * @property int         $cms_company_id Компания
 * @property int         $cms_user_id Пользователь
 * @property string|null $comment
 * @property int         $sort
 * @property int         $is_root
 * @property int         $is_notify
 *
 * @property CmsUser     $cmsUser
 * @property CmsCompany  $cmsCompany
 */
class CmsCompany2user extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_company2user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            
            [['comment'], 'string'],
            
            [['sort'], 'integer'],
            [['is_root'], 'integer'],
            [['is_notify'], 'integer'],
            
            [['cms_company_id', 'cms_user_id'], 'integer'],
            [['cms_company_id', 'cms_user_id'], 'required'],
            [['cms_company_id', 'cms_user_id'], 'unique', 'targetAttribute' => ['cms_company_id', 'cms_user_id']],
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'comment'    => Yii::t('app', 'Комментарий'),
            'cms_user_id'    => Yii::t('app', 'Контрагент'),
            'cms_company_id' => Yii::t('app', 'Компания'),
            'is_root' => Yii::t('app', 'Есть доступ к компании'),
            'is_notify' => Yii::t('app', 'Получать уведомления по компании'),
            'sort' => Yii::t('app', 'Сортировка'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'comment'    => Yii::t('app', 'Должность в компании или прочяя информация о клиенте'),
            'is_root'    => Yii::t('app', 'Опция открывает доступ к сделкам, документам, оплатам и услугам по этой компании'),
            'is_notify'    => Yii::t('app', 'Этот контакт участвует в получении уведомлений по данной компании'),
            'sort'    => Yii::t('app', 'Порядок сортировки контакта в компании, чем меньше цифра тем выше человек'),
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        $userClass = \Yii::$app->user->identityClass;
        return $this->hasOne($userClass, ['id' => 'cms_user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    public function asText()
    {
        return $this->cmsUser->shortDisplayName;
    }
}