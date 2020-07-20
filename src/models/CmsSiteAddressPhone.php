<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\validators\PhoneValidator;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_site_phone".
 *
 * @property int $id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int $cms_site_id
 * @property string $value
 * @property string|null $name
 * @property int $priority
 *
 * @property CmsSiteAddress $cmsSiteAddress
 */
class CmsSiteAddressPhone extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site_address_phone}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'value' => 'Email',
            'name' => 'Название',
            'priority' => 'Сортировка',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'name' => 'Необязтельное поле, можно дать название этому email',
            'priority' => 'Чем ниже цифра тем выше email',
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_address_id', 'priority'], 'integer'],
            [['cms_site_address_id', 'value'], 'required'],
            [['value', 'name'], 'string', 'max' => 255],
            [['cms_site_address_id', 'value'], 'unique', 'targetAttribute' => ['cms_site_address_id', 'value']],
            [['cms_site_address_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSiteAddress::class, 'targetAttribute' => ['cms_site_address_id' => 'id']],

            [['value'], 'string', 'max' => 64],
            [['value'], PhoneValidator::class],
        ]);
    }

    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteAddress()
    {
        return $this->hasOne(CmsSiteAddress::className(), ['id' => 'cms_site_address_id']);
    }
}