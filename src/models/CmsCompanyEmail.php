<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use yii\helpers\ArrayHelper;
/**
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_company_id
 * @property string      $value Email
 * @property string|null $name Примечание к Email
 * @property int         $sort
 *
 * @property CmsCompany  $cmsCompany
 */
class CmsCompanyEmail extends ActiveRecord
{
    use HasLogTrait;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_company_email}}';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            CmsLogBehavior::class => [
                'class'           => CmsLogBehavior::class,
                'parent_relation' => 'cmsCompany',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_company_id', 'sort'], 'integer'],
            [['cms_company_id', 'value'], 'required'],
            [['value', 'name'], 'string', 'max' => 255],

            [
                ['cms_company_id', 'value'],
                'unique',
                'targetAttribute' => ['cms_company_id', 'value'],
                //'message' => 'Этот email уже занят'
            ],

            [['name'], 'default', 'value' => null],

            [['value'], "filter", 'filter' => 'trim'],
            [
                ['value'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ],
            [['value'], "email", 'enableIDN' => true],
            // П2а: another company with this email — only with a reason, marked as a possible duplicate.
            [['duplicate_reason'], 'string', 'max' => 2000],
            [['value'], 'validateCrmDuplicate'],
        ]);
    }

    /** «Почему это другая компания», when another company has this email (crm\CrmDuplicateGuard). */
    public $duplicate_reason;

    /** @var int[] companies with the same email found by the validation */
    private array $_duplicateOf = [];

    public function validateCrmDuplicate($attribute): void
    {
        $this->_duplicateOf = [];
        // A new row, another value or another company (a contact moved to a third company) is checked anew.
        if (!\skeeks\cms\crm\CrmDuplicateGuard::ready()
            || (!$this->isNewRecord && !$this->isAttributeChanged('value', false) && !$this->isAttributeChanged('cms_company_id', false))) {
            return;
        }
        $others = \skeeks\cms\crm\CrmDuplicateGuard::companiesWith('email', (string)$this->value, $this->cms_company_id ? (int)$this->cms_company_id : null,
            $this->isNewRecord ? null : (int)$this->id);
        if (!$others) {
            return;
        }
        if (\skeeks\cms\crm\CrmDuplicateGuard::refuses((string)$this->duplicate_reason)) {
            $this->addError($attribute, \skeeks\cms\crm\CrmDuplicateGuard::message('Email '.$this->value, $others));
            return;
        }
        $this->_duplicateOf = $others;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->_duplicateOf && $this->cms_company_id) {
            \skeeks\cms\crm\CrmDuplicateGuard::mark((int)$this->cms_company_id, $this->_duplicateOf, (string)$this->duplicate_reason);
            $this->_duplicateOf = [];
        }
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_company_id' => "Компания",
            'name'           => "Описание",
            'value'          => "Email",
            'duplicate_reason' => \skeeks\cms\crm\CrmDuplicateGuard::reasonLabel(),
            'sort'           => "Сортировка",
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    /**
     * @return string
     */
    public function asText()
    {
        return $this->value;
    }
}