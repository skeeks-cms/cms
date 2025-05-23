<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\validators\PhoneValidator;
use yii\helpers\ArrayHelper;
/**
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_company_id
 * @property string      $url
 * @property string|null $name
 * @property string      $link_type
 * @property int         $sort
 *
 * @property CmsCompany  $cmsCompany
 */
class CmsCompanyLink extends ActiveRecord
{
    use HasLogTrait;

    const TYPE_INSTAGRAM = 'instagram';
    const TYPE_FACEBOOK = 'facebook';
    const TYPE_VK = 'vk';
    const TYPE_OK = 'odnoklassniki';
    const TYPE_YOUTUBE = 'youtube';
    const TYPE_WHATSAPP = 'whatsapp';
    const TYPE_TELEGRAM = 'telegram';
    const TYPE_OTHER = 'other';
    const TYPE_PINTEREST = 'pinterest';

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            CmsLogBehavior::class => [
                'class'           => CmsLogBehavior::class,
                'parent_relation' => 'cmsCompany',
                'relation_map' => [
                    'link_type' => 'linkTypeAsText',
                ],
            ],
        ]);
    }

    public function getLinkTypeAsText()
    {
        return (string) ArrayHelper::getValue(self::getLinkTypes(), $this->link_type);
    }
    /**
     * @return array
     */
    static public function getLinkTypes()
    {
        return [
            self::TYPE_FACEBOOK  => 'Facebook',
            self::TYPE_INSTAGRAM => 'Instagram',
            self::TYPE_YOUTUBE   => 'Youtube',
            self::TYPE_VK        => 'Вконтакте',
            self::TYPE_OK        => 'Одноклассники',
            self::TYPE_TELEGRAM  => 'Telegram',
            self::TYPE_WHATSAPP  => 'WatsApp',
            self::TYPE_PINTEREST => 'Pinterest',
            self::TYPE_OTHER     => 'Другое',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_company_link}}';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_company_id', 'sort'], 'integer'],
            [['cms_company_id', 'url'], 'required'],
            [['name'], 'string', 'max' => 255],

            [
                ['cms_company_id', 'url'],
                'unique',
                'targetAttribute' => ['cms_company_id', 'url'],
                //'message' => 'Этот email уже занят'
            ],

            [['name'], 'default', 'value' => null],

            [['url'], 'required'],
            [['url', 'name'], 'string', 'max' => 255],
            [['url'], 'string', 'max' => 255],
            [['url'], 'url'],

            [['link_type'], 'string'],
            [['link_type'], 'default', 'value' => self::TYPE_OTHER],

        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'url'       => 'Ссылка на соцсеть или сайт',
            'link_type' => 'Социальная сеть или сайт',
            'name'      => 'Название',
            'priority'  => 'Сортировка',

            'cms_company_id' => "Компания",
            'sort'           => "Сортировка",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'name'     => 'Необязтельное поле',
            'sort' => 'Чем ниже цифра тем выше ссылка',
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
        return $this->url;
    }
}