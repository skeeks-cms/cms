<?php

namespace skeeks\cms\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "crm_client_map".
 *
 * @property int        $id
 * @property int        $created_by
 * @property int        $updated_by
 * @property int        $created_at
 * @property int        $updated_at
 * @property int        $cms_company_id Компания
 * @property int        $cms_user_id Пользователь
 *
 * @property CmsUser    $cmsUser
 * @property CmsCompany $cmsCompany
 */
class CmsCompany2manager extends \skeeks\cms\base\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_company2manager}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_company_id', 'cms_user_id'], 'integer'],
            [['cms_company_id', 'cms_user_id'], 'required'],
            [['cms_company_id', 'cms_user_id'], 'unique', 'targetAttribute' => ['cms_company_id', 'cms_user_id'],
                // The automatic link of the creator (crm\CrmAccess::assignCreatorMain()) takes a repeated insert (insert()).
                'when' => fn() => !\skeeks\cms\crm\CrmAccess::isAutoCreatorLink(CmsCrmAccessRequest::RECORD_COMPANY, (int)$this->cms_company_id, (int)$this->cms_user_id)],
        ], \skeeks\cms\crm\CrmAccess::levelRules());
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id'    => Yii::t('app', 'Контрагент'),
            'cms_company_id' => Yii::t('app', 'Компания'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
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

    /**
     * The client was just created and its creator got the main link (crm\CrmAccess::assignCreatorMain()): the
     * creating code giving the same employee again merges into that link instead of failing on the unique key.
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if (\skeeks\cms\crm\CrmAccess::isAutoCreatorLink(CmsCrmAccessRequest::RECORD_COMPANY, (int)$this->cms_company_id, (int)$this->cms_user_id)
            && ($existing = static::findOne(['cms_company_id' => (int)$this->cms_company_id, 'cms_user_id' => (int)$this->cms_user_id]))) {
            $this->setAttributes($existing->getAttributes(), false);
            $this->setOldAttributes($existing->getAttributes());
            $this->setIsNewRecord(false);
            \skeeks\cms\crm\CrmAccess::confirmAutoMain(CmsCrmAccessRequest::RECORD_COMPANY, (int)$this->cms_company_id);

            return true;
        }

        return parent::insert($runValidation, $attributes);
    }

    /** The client keeps exactly one main responsible (crm\CrmAccess::ensureMain()). */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            \skeeks\cms\crm\CrmAccess::instance()->onLinkInserted(CmsCrmAccessRequest::RECORD_COMPANY, (int)$this->cms_company_id, (int)$this->cms_user_id,
                $this->hasAttribute('access_level') && $this->access_level ? (string)$this->access_level : null);
        }
        \skeeks\cms\crm\CrmAccess::instance()->ensureMain(CmsCrmAccessRequest::RECORD_COMPANY, (int)$this->cms_company_id, (bool)$insert);
    }

    public function afterDelete()
    {
        parent::afterDelete();
        // The main one left: the next one by the rule (a participant "full", his head, the default administrator).
        \skeeks\cms\crm\CrmAccess::instance()->ensureMain(CmsCrmAccessRequest::RECORD_COMPANY, (int)$this->cms_company_id, false,
            $this->hasAttribute('is_main') && $this->is_main ? (int)$this->cms_user_id : null);
    }
}
