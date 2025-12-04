<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\modules\cms\user\models\User;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_site_phone".
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property string      $url
 * @property string|null $name
 * @property string      $social_type
 * @property int         $priority
 *
 * @property CmsSite     $cmsSite
 * @property string      $iconCode
 * @property string      $iconHtml
 */
class CmsSiteSocial extends ActiveRecord
{
    const SOCIAL_INSTAGRAM = 'instagram';
    const SOCIAL_FACEBOOK = 'facebook';
    const SOCIAL_VK = 'vk';
    const SOCIAL_OK = 'odnoklassniki';
    const SOCIAL_YOUTUBE = 'youtube';
    const SOCIAL_WHATSAPP = 'whatsapp';
    const SOCIAL_TELEGRAM = 'telegram';
    const SOCIAL_OTHER = 'other';
    const SOCIAL_PINTEREST = 'pinterest';
    const SOCIAL_YANDEX = 'yandex';
    const SOCIAL_RUTUBE = 'rutube';
    const SOCIAL_DZEN = 'dzen';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site_social}}';
    }

    /**
     * @return array
     */
    static public function getSocialTypes()
    {
        return [
            self::SOCIAL_TELEGRAM  => 'Telegram',
            self::SOCIAL_WHATSAPP  => 'WatsApp',

            self::SOCIAL_YANDEX     => 'Yandex',
            self::SOCIAL_DZEN     => 'Dzen',
            self::SOCIAL_FACEBOOK  => 'Facebook',
            self::SOCIAL_INSTAGRAM => 'Instagram',
            self::SOCIAL_YOUTUBE   => 'Youtube',
            self::SOCIAL_VK        => 'Вконтакте',
            self::SOCIAL_OK        => 'Одноклассники',

            self::SOCIAL_RUTUBE        => 'Rutube',

            self::SOCIAL_PINTEREST => 'Pinterest',
            self::SOCIAL_OTHER     => 'Другое',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'url'         => 'Ссылка на профиль или сайт',
            'social_type' => 'Социальная сеть или сайт',
            'name'        => 'Название',
            'priority'    => 'Сортировка',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'name'     => 'Необязтельное поле',
            'priority' => 'Чем ниже цифра тем выше ссылка',
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'priority'], 'integer'],
            [['social_type'], 'string'],
            [['social_type'], 'required'],
            [['url'], 'required'],
            [['url', 'name'], 'string', 'max' => 255],
            [['cms_site_id', 'url'], 'unique', 'targetAttribute' => ['cms_site_id', 'url']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],

            [['url'], 'string', 'max' => 64],
            [['url'], 'url'],
        ]);
    }

    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::className(), ['id' => 'cms_site_id']);
    }


    public function getIconCode()
    {
        return in_array($this->social_type, [self::SOCIAL_OTHER]) ? 'fas fa-external-link-alt' : "fab fa-".$this->social_type;
    }
    
    public function getIconHtml()
    {
        return "<i class='{$this->iconCode}'></i>";
    }
}