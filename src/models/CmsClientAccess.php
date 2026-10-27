<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

use skeeks\cms\deal\DealAccess;
use yii\helpers\ArrayHelper;

/**
 * Access of a client person to one deal (cms_deal_id) or to every deal of a
 * company (cms_deal_id is null) — "Сделка как услуга".
 *
 * The levels of the chosen role are copied into the grant; a grant edited by
 * hand is marked is_custom. A company-wide full grant is the same thing as
 * CmsCompany2user::is_root, both are kept in sync, so code that still reads
 * is_root keeps working.
 *
 * Checks never read this model directly: use DealAccess (Yii::$app->dealAccess).
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_user_id
 * @property int|null    $cms_company_id
 * @property int|null    $cms_deal_id
 * @property string      $scope_key
 * @property int|null    $cms_deal_role_id
 * @property int         $is_full
 * @property int         $is_custom
 * @property string|null $comment
 *
 * @property CmsUser               $cmsUser
 * @property CmsCompany|null       $cmsCompany
 * @property CmsDeal|null          $cmsDeal
 * @property CmsDealRole|null      $cmsDealRole
 * @property CmsClientAccessArea[] $areaRows
 * @property array                 $levels area => level
 */
class CmsClientAccess extends \skeeks\cms\base\ActiveRecord
{
    /** @var array|null */
    private $_levels;

    /** @var bool levels came from a form or code, not from the role */
    private $_levelsPosted = false;

    /**
     * Skips the CmsCompany2user::is_root synchronization (used by that model itself).
     * @var bool
     */
    public $skipRootSync = false;

    public static function tableName()
    {
        return '{{%cms_client_access}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_user_id'], 'required'],
            [['cms_user_id', 'cms_company_id', 'cms_deal_id', 'cms_deal_role_id'], 'integer'],
            [['cms_company_id', 'cms_deal_id', 'cms_deal_role_id'], 'default', 'value' => null],
            [['is_full', 'is_custom'], 'boolean'],
            [['is_full', 'is_custom'], 'default', 'value' => 0],
            [['comment'], 'string', 'max' => 255],
            [['levels'], 'safe'],
            [['cms_user_id'], 'exist', 'targetClass' => CmsUser::class, 'targetAttribute' => 'id'],
            [['cms_deal_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsDeal::class, 'targetAttribute' => 'id'],
            [['cms_company_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsCompany::class, 'targetAttribute' => 'id'],
            [['cms_deal_role_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsDealRole::class, 'targetAttribute' => 'id'],
            [['cms_deal_id'], 'validateScope', 'skipOnEmpty' => false],
            [['scope_key'], 'string', 'max' => 32],
            [['cms_user_id'], 'unique', 'targetAttribute' => ['cms_user_id', 'scope_key'], 'message' => 'У этого человека уже есть доступ здесь.'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id'      => 'Человек',
            'cms_company_id'   => 'Компания',
            'cms_deal_id'      => 'Услуга',
            'cms_deal_role_id' => 'Роль',
            'is_full'          => 'Полный доступ',
            'is_custom'        => 'Настроено вручную',
            'comment'          => 'Комментарий',
            'levels'           => 'Области доступа',
        ]);
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->normalizeScope();

        //The role fills the levels; posted levels that differ from the role mark the grant as configured by hand.
        $role = $this->cms_deal_role_id ? CmsDealRole::findOne((int)$this->cms_deal_role_id) : null;
        if ($role) {
            $roleLevels = $role->is_full ? [] : $role->getLevels();
            if ($this->_levelsPosted) {
                $posted = $this->is_full ? [] : (array)$this->_levels;
                ksort($posted);
                ksort($roleLevels);
                $this->is_custom = (int)((bool)$this->is_full !== (bool)$role->is_full || $posted !== $roleLevels);
            } elseif (!$this->is_custom) {
                $this->is_full = (int)$role->is_full;
                $this->_levels = $roleLevels;
            }
        } elseif ($this->_levelsPosted) {
            $this->is_custom = 1;
        }

        return true;
    }

    /**
     * The company follows the deal; scope_key ("c:<company>" / "d:<deal>") makes
     * (person, scope) unique. Called before validation and again before saving,
     * so save(false) cannot write a grant without its scope.
     */
    public function normalizeScope(): void
    {
        if ($this->cms_deal_id && ($deal = CmsDeal::findOne((int)$this->cms_deal_id))) {
            $this->cms_company_id = $deal->cms_company_id ?: null;
        }
        $this->scope_key = $this->cms_deal_id ? 'd:'.(int)$this->cms_deal_id : ($this->cms_company_id ? 'c:'.(int)$this->cms_company_id : '');
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->normalizeScope();
        if ($this->scope_key === '') {
            throw new \yii\base\InvalidArgumentException('Доступ клиента без услуги и компании сохранить нельзя.');
        }
        return true;
    }

    /**
     * The grant, its areas and the synchronized company link are written together.
     */
    public function transactions()
    {
        return [self::SCENARIO_DEFAULT => self::OP_ALL];
    }

    public function validateScope($attribute)
    {
        if (!$this->cms_deal_id && !$this->cms_company_id) {
            $this->addError($attribute, 'Нужно выбрать услугу или компанию.');
        }
    }

    public function getCmsUser()
    {
        return $this->hasOne(CmsUser::class, ['id' => 'cms_user_id']);
    }

    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    public function getCmsDeal()
    {
        return $this->hasOne(CmsDeal::class, ['id' => 'cms_deal_id']);
    }

    public function getCmsDealRole()
    {
        return $this->hasOne(CmsDealRole::class, ['id' => 'cms_deal_role_id']);
    }

    public function getAreaRows()
    {
        return $this->hasMany(CmsClientAccessArea::class, ['cms_client_access_id' => 'id']);
    }

    /**
     * @return array area => level (as granted, before what is open in a deal)
     */
    public function getLevels(): array
    {
        if ($this->_levels === null) {
            $this->_levels = $this->isNewRecord ? [] : ArrayHelper::map($this->areaRows, 'area', function ($row) {
                return (int)$row->level;
            });
        }
        return $this->_levels;
    }

    public function setLevels($levels)
    {
        $this->_levels = static::dealAccess()->normalizeLevels((array)$levels);
        $this->_levelsPosted = true;
    }

    public function getIsCompanyWide(): bool
    {
        return !$this->cms_deal_id;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->_levels !== null) {
            CmsClientAccessArea::deleteAll(['cms_client_access_id' => $this->id]);
            if (!$this->is_full) {
                foreach ($this->_levels as $area => $level) {
                    (new CmsClientAccessArea(['cms_client_access_id' => $this->id, 'area' => $area, 'level' => $level]))->save(false);
                }
            }
            unset($this->areaRows);
        }

        //A person with access to a company deal belongs to that company.
        if ($this->cms_company_id) {
            $link = CmsCompany2user::find()->andWhere(['cms_company_id' => $this->cms_company_id, 'cms_user_id' => $this->cms_user_id])->one();
            if (!$link) {
                $link = new CmsCompany2user([
                    'cms_company_id' => (int)$this->cms_company_id,
                    'cms_user_id'    => (int)$this->cms_user_id,
                    'is_root'        => (int)($this->isCompanyWide && $this->is_full),
                ]);
                $link->skipAccessSync = true;
                if (!$link->save()) {
                    throw new \RuntimeException('Человек не добавлен в компанию: '.implode('; ', $link->getFirstErrors()));
                }
            }
        }

        //is_root follows the company-wide full grant: recount it for the previous and the current company
        //(a grant narrowed from the company to a deal, moved, or losing "full").
        if (!$this->skipRootSync) {
            $companies = [(int)$this->cms_company_id];
            if (array_key_exists('cms_company_id', $changedAttributes)) {
                $companies[] = (int)$changedAttributes['cms_company_id'];
            }
            if (array_key_exists('cms_user_id', $changedAttributes)) {
                static::syncRoot((int)$changedAttributes['cms_user_id'], (int)$this->cms_company_id);
            }
            foreach (array_unique(array_filter($companies)) as $companyId) {
                static::syncRoot((int)$this->cms_user_id, $companyId);
            }
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if (!$this->skipRootSync && $this->cms_company_id) {
            static::syncRoot((int)$this->cms_user_id, (int)$this->cms_company_id);
        }
    }

    /**
     * CmsCompany2user::is_root = the person has a company-wide full grant in that company.
     */
    public static function syncRoot(int $userId, int $companyId): void
    {
        if (!$userId || !$companyId) {
            return;
        }
        $isRoot = static::find()->andWhere(['cms_user_id' => $userId, 'scope_key' => 'c:'.$companyId, 'is_full' => 1])->exists();
        CmsCompany2user::updateAll(['is_root' => (int)$isRoot], ['cms_company_id' => $companyId, 'cms_user_id' => $userId]);
    }

    /**
     * «Директор (полный доступ)» / «Маркетолог / менеджер, настроено вручную».
     */
    public function getRoleAsText(): string
    {
        $name = $this->cmsDealRole ? $this->cmsDealRole->name : ($this->is_full ? 'Полный доступ' : 'Без роли');
        return $this->is_custom ? $name.', настроено вручную' : $name;
    }

    public function getLevelsAsText(): string
    {
        if ($this->is_full) {
            return 'Все области';
        }
        $parts = [];
        $levels = $this->getLevels();
        foreach (array_keys(static::dealAccess()->getAreaList()) as $area) {
            if (!empty($levels[$area])) {
                $parts[] = static::dealAccess()->areaName($area).': '.mb_strtolower(static::dealAccess()->levelName($area, (int)$levels[$area]));
            }
        }
        return $parts ? implode('; ', $parts) : 'Ничего';
    }

    public function asText()
    {
        return $this->cmsUser ? (string)$this->cmsUser->shortDisplayName : '#'.$this->id;
    }

    public static function dealAccess(): DealAccess
    {
        return \Yii::$app->get('dealAccess');
    }
}
