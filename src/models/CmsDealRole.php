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
 * A ready-made role of client people: «Директор (полный доступ)», «Бухгалтер»…
 * Its levels are copied into a grant when the role is chosen (CmsClientAccess);
 * editing a role does not change existing grants by itself.
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property string      $name
 * @property string|null $code system roles: full, accountant, marketer, viewer
 * @property string|null $description
 * @property int         $is_full all areas, including areas added later
 * @property int         $is_active
 * @property int         $priority
 *
 * @property CmsDealRoleArea[] $areaRows
 * @property array             $levels area => level
 */
class CmsDealRole extends \skeeks\cms\base\ActiveRecord
{
    const CODE_FULL = 'full';
    const CODE_ACCOUNTANT = 'accountant';
    const CODE_MARKETER = 'marketer';
    const CODE_VIEWER = 'viewer';

    /** @var array|null */
    private $_levels;

    public static function tableName()
    {
        return '{{%cms_deal_role}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique'],
            [['description'], 'string'],
            [['code'], 'string', 'max' => 64],
            [['code'], 'default', 'value' => null],
            [['code'], 'unique', 'skipOnEmpty' => true],
            [['is_full', 'is_active'], 'boolean'],
            [['is_full'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => 1],
            [['priority'], 'integer'],
            [['priority'], 'default', 'value' => 100],
            [['levels'], 'safe'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name'        => 'Название',
            'code'        => 'Код',
            'description' => 'Описание',
            'is_full'     => 'Полный доступ',
            'is_active'   => 'Можно выбирать',
            'priority'    => 'Сортировка',
            'levels'      => 'Области доступа',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'is_full'   => 'Все области доступа, в том числе те, что пакеты добавят позже',
            'is_active' => 'Выключенная роль остаётся у людей, но её нельзя выбрать заново',
        ]);
    }

    public function getAreaRows()
    {
        return $this->hasMany(CmsDealRoleArea::class, ['cms_deal_role_id' => 'id']);
    }

    /**
     * @return array area => level
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
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->_levels !== null) {
            CmsDealRoleArea::deleteAll(['cms_deal_role_id' => $this->id]);
            foreach ($this->_levels as $area => $level) {
                $row = new CmsDealRoleArea(['cms_deal_role_id' => $this->id, 'area' => $area, 'level' => $level]);
                $row->save(false);
            }
            unset($this->areaRows);
        }
    }

    public function beforeDelete()
    {
        if (CmsClientAccess::find()->andWhere(['cms_deal_role_id' => $this->id])->exists()) {
            $this->addError('name', 'Роль назначена людям: её можно выключить, но не удалить.');
            return false;
        }
        return parent::beforeDelete();
    }

    /**
     * Readable levels: «Задачи: ставить задачи; отчёты: смотреть».
     */
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
        return (string)$this->name;
    }

    public static function dealAccess(): DealAccess
    {
        return \Yii::$app->get('dealAccess');
    }
}
