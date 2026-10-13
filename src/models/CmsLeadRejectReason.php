<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\query\CmsActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * A site-defined reason for rejecting a lead, so losses can be counted.
 *
 * Once a site has reasons, a rejected lead must name one; the free-text
 * reject_reason of the lead becomes an optional comment.
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property string      $name
 * @property int         $sort
 * @property int         $is_no_answer Preselected by the "no answer" closing form
 *
 * @property CmsLead[]   $leads
 */
class CmsLeadRejectReason extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_lead_reject_reason}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_site_id'], 'default', 'value' => static function () {
                return \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
            }],
            [['name'], 'filter', 'filter' => 'trim'],
            [['sort'], 'default', 'value' => 100],
            [['is_no_answer'], 'default', 'value' => 0],
            [['name', 'cms_site_id'], 'required'],
            [['cms_site_id', 'sort'], 'integer'],
            [['is_no_answer'], 'boolean'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique', 'targetAttribute' => ['cms_site_id', 'name'], 'message' => 'Такая причина уже есть.'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => 'Название',
            'sort' => 'Порядок',
            'is_no_answer' => 'Для кнопки «Не дозвонились»',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'name' => 'Например: «Дорого», «Не дозвонились», «Не наш профиль», «Ушли к конкурентам».',
            'sort' => 'Причины идут в списке по возрастанию числа.',
            'is_no_answer' => 'Эта причина будет выбрана, когда лид закрывают из окна «Не дозвонились».',
        ]);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        // Only one reason of a site is the "no answer" default.
        if ($this->is_no_answer && ($insert || array_key_exists('is_no_answer', $changedAttributes))) {
            static::updateAll(
                ['is_no_answer' => 0],
                ['and', ['cms_site_id' => $this->cms_site_id], ['!=', 'id', $this->id]]
            );
        }
    }

    public function getLeads()
    {
        return $this->hasMany(CmsLead::class, ['cms_lead_reject_reason_id' => 'id']);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        if ($this->getLeads()->exists()) {
            $this->addError('name', 'Эта причина уже указана у лидов, удалить её нельзя.');
            return false;
        }

        return true;
    }

    /**
     * Reasons of a site in list order.
     *
     * @return CmsLeadRejectReason[]
     */
    public static function forSite(?int $siteId = null): array
    {
        return static::find()
            ->cmsSite($siteId ?? (\Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : 0))
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /** Whether the site uses the reason list; false until the table exists. */
    public static function siteHasReasons(?int $siteId): bool
    {
        try {
            return static::find()->cmsSite((int)$siteId)->exists();
        } catch (\yii\db\Exception $e) {
            return false;
        }
    }

    /** @return CmsActiveQuery */
    public static function find()
    {
        return new CmsActiveQuery(static::class);
    }
}
