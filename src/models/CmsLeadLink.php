<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use yii\helpers\ArrayHelper;

/**
 * Website or social network link of a lead. Uses the link types of
 * {@see CmsCompanyLink} so it moves to the company created from the lead.
 *
 * @property int         $id
 * @property int         $cms_lead_id
 * @property string      $link_type
 * @property string      $url
 * @property string|null $name
 * @property int         $sort
 *
 * @property CmsLead     $cmsLead
 * @property string      $linkTypeAsText
 */
class CmsLeadLink extends ActiveRecord
{
    use HasLogTrait;

    public static function tableName()
    {
        return '{{%cms_lead_link}}';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'log' => [
                'class' => CmsLogBehavior::class,
                'parent_relation' => 'cmsLead',
                'relation_map' => [
                    'link_type' => 'linkTypeAsText',
                ],
            ],
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_lead_id', 'sort'], 'integer'],
            [['cms_lead_id', 'url'], 'required'],
            [['url', 'name'], 'filter', 'filter' => 'trim', 'skipOnEmpty' => true],
            [['url', 'name'], 'string', 'max' => 255],
            [['url'], 'url', 'defaultScheme' => 'https'],
            [['name'], 'default', 'value' => null],
            [['link_type'], 'default', 'value' => CmsCompanyLink::TYPE_OTHER],
            [['link_type'], 'in', 'range' => array_keys(CmsCompanyLink::getLinkTypes())],
            [['url'], 'unique', 'targetAttribute' => ['cms_lead_id', 'url'], 'message' => 'Такая ссылка уже добавлена к лиду.'],
            [['cms_lead_id'], 'exist', 'targetClass' => CmsLead::class, 'targetAttribute' => 'id'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_lead_id' => 'Лид',
            'url'         => 'Ссылка на соцсеть или сайт',
            'link_type'   => 'Социальная сеть или сайт',
            'name'        => 'Название',
            'sort'        => 'Сортировка',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'name' => 'Необязательное поле',
        ]);
    }

    public function getLinkTypeAsText(): string
    {
        return (string)ArrayHelper::getValue(CmsCompanyLink::getLinkTypes(), $this->link_type);
    }

    public function getCmsLead()
    {
        return $this->hasOne(CmsLead::class, ['id' => 'cms_lead_id']);
    }

    public function asText()
    {
        return $this->url;
    }
}
