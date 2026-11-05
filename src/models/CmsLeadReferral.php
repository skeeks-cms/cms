<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\crm\LeadReferrals;
use skeeks\cms\query\CmsActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * An item of the site list «Откуда узнали» (crm\LeadReferrals): «Яндекс Директ»,
 * «2ГИС», «Рекомендация»… The client keeps the list himself; the rules fill the
 * deal field automatically.
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property string      $name
 * @property string|null $code
 * @property int         $sort
 * @property int         $is_active
 * @property string|null $match_rules
 *
 * @property CmsLead[]   $leads
 */
class CmsLeadReferral extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_lead_referral}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_site_id'], 'default', 'value' => static function () {
                return \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
            }],
            [['name', 'code', 'match_rules'], 'filter', 'filter' => static fn($value) => $value === null ? null : trim((string)$value)],
            [['code', 'match_rules'], 'default', 'value' => null],
            [['code'], 'filter', 'filter' => static fn($value) => $value === null || $value === '' ? null : mb_strtolower($value)],
            [['sort'], 'default', 'value' => 100],
            [['is_active'], 'default', 'value' => 1],
            [['name', 'cms_site_id'], 'required'],
            [['cms_site_id', 'sort'], 'integer'],
            [['is_active'], 'boolean'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 64],
            [['code'], 'match', 'pattern' => '/^[a-z0-9_-]+$/', 'message' => 'Код — латинские буквы, цифры, «_» и «-».'],
            [['match_rules'], 'string', 'max' => 5000],
            [['match_rules'], function ($attribute) {
                $error = LeadReferrals::validateRules((string)$this->$attribute);
                if ($error !== null) {
                    $this->addError($attribute, $error);
                }
            }],
            [['name'], 'unique', 'targetAttribute' => ['cms_site_id', 'name'], 'message' => 'Такой пункт уже есть.'],
            [['code'], 'unique', 'targetAttribute' => ['cms_site_id', 'code'], 'message' => 'Такой код уже есть.'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => 'Название',
            'code' => 'Код',
            'sort' => 'Порядок',
            'is_active' => 'Показывать в списке',
            'match_rules' => 'Как определять автоматически',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'name' => 'Как пункт видят менеджеры: «Яндекс Директ», «2ГИС», «Вывеска / проходил мимо».',
            'code' => 'Необязательно. Нужен для интеграций и API, например yandex_direct.',
            'sort' => 'Пункты идут в списке по возрастанию числа.',
            'is_active' => 'Выключенный пункт не предлагается в новых сделках, но остаётся у старых и в отчёте.',
            'match_rules' => 'Одно правило на строку, все условия строки должны совпасть. Примеры:'
                ."\nutm_source=yandex utm_medium=cpc — реклама Директа по меткам;"
                ."\nreferrer=2gis.ru — переход с сайта 2ГИС (учитывается, только если меток нет);"
                ."\nchannel=mailing — сделка из рассылки;"
                ."\nphone=+7 900 000-00-00 — звонок на этот номер (коллтрекинг)."
                ."\nНесколько значений — через запятую, * — любое значение. Пусто — пункт выбирают только вручную.",
        ]);
    }

    public function getLeads()
    {
        return $this->hasMany(CmsLead::class, ['cms_lead_referral_id' => 'id']);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        if ($this->getLeads()->exists()) {
            $this->addError('name', 'Этот пункт уже указан у сделок. Удалить его нельзя — выключите его.');
            return false;
        }

        return true;
    }

    /**
     * Active items of a site in list order.
     *
     * @return CmsLeadReferral[]
     */
    public static function activeForSite(?int $siteId = null): array
    {
        return static::find()
            ->cmsSite($siteId ?? (\Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : 0))
            ->andWhere(['is_active' => 1])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /**
     * All items of a site in list order (the report keeps inactive ones).
     *
     * @return CmsLeadReferral[]
     */
    public static function forSite(?int $siteId = null): array
    {
        return static::find()
            ->cmsSite($siteId ?? (\Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : 0))
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /** @return CmsActiveQuery */
    public static function find()
    {
        return new CmsActiveQuery(static::class);
    }
}
