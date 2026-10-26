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
     * Skips the synchronization of is_root with the company-wide full grant
     * (CmsClientAccess); set by CmsClientAccess when it creates the link itself.
     * @var bool
     */
    public $skipAccessSync = false;

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
            'is_root' => Yii::t('app', 'Полный доступ ко всем услугам'),
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
            'is_root'    => Yii::t('app', 'Все услуги (сделки) компании и все области доступа: задачи, отчёты, документы, счета, управление. Ограниченный доступ выдаётся в карточке сделки'),
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

    /**
     * is_root and the company-wide full grant (CmsClientAccess) are written together or not at all.
     */
    public function transactions()
    {
        return [self::SCENARIO_DEFAULT => self::OP_ALL];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->skipAccessSync || !static::accessTableExists()) {
            return;
        }
        if (!$insert && !array_key_exists('is_root', $changedAttributes)) {
            return;
        }

        $grant = CmsClientAccess::find()->andWhere(['cms_user_id' => $this->cms_user_id, 'scope_key' => 'c:'.(int)$this->cms_company_id])->one();
        if ($this->is_root) {
            if (!$grant || !$grant->is_full) {
                $grant = $grant ?: new CmsClientAccess(['cms_user_id' => (int)$this->cms_user_id, 'cms_company_id' => (int)$this->cms_company_id]);
                $role = CmsDealRole::find()->andWhere(['code' => CmsDealRole::CODE_FULL])->one();
                $grant->cms_deal_role_id = $role ? $role->id : null;
                $grant->is_full = 1;
                $grant->is_custom = 0;
                $grant->skipRootSync = true;
                //Validated save; a failure rolls back the is_root change too (transactions()).
                if (!$grant->save()) {
                    throw new \RuntimeException('Не удалось выдать полный доступ: '.implode('; ', $grant->getFirstErrors()));
                }
            }
        } elseif ($grant && $grant->is_full) {
            $grant->skipRootSync = true;
            $grant->delete();
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();

        //Leaving the company ends every access of the person to its deals.
        if (!$this->skipAccessSync && static::accessTableExists()) {
            foreach (CmsClientAccess::find()->andWhere(['cms_user_id' => $this->cms_user_id, 'cms_company_id' => $this->cms_company_id])->all() as $grant) {
                $grant->skipRootSync = true;
                $grant->delete();
            }
        }
    }

    /**
     * The code may run before the migration of "Сделка как услуга" is applied.
     */
    protected static function accessTableExists(): bool
    {
        return (bool)static::getDb()->getTableSchema(CmsClientAccess::tableName());
    }
}