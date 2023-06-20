<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\validators\PhoneValidator;
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
 * @property string      $value
 * @property string      $onlyNumber
 * @property string|null $name
 * @property int         $priority
 *
 * @property CmsSite     $cmsSite
 * @property CmsUser     $createdBy
 * @property CmsUser     $updatedBy
 */
class CmsSitePhone extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site_phone}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'value'    => 'Телефон',
            'name'     => 'Название',
            'priority' => 'Сортировка',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'name'     => 'Необязтельное поле, можно дать название этому номеру телефона',
            'priority' => 'Чем ниже цифра тем выше телефон',
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
            [['value'], 'required'],
            [['value', 'name'], 'string', 'max' => 255],
            [['cms_site_id', 'value'], 'unique', 'targetAttribute' => ['cms_site_id', 'value']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],

            [['value'], 'string', 'max' => 64],
            [['value'], PhoneValidator::class],


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

    /**
     * @return string
     */
    public function getOnlyNumber()
    {
        if ($this->value) {
            return self::onlyNumber($this->value);
        }

        return '';
    }

    /**
     * @param string $formatedPhone
     * @return string
     */
    static public function onlyNumber(string $formatedPhone): string
    {
        $formatedPhone = str_replace("-", "", $formatedPhone);
        //$formatedPhone = str_replace("+", "", $formatedPhone);
        $formatedPhone = str_replace(" ", "", $formatedPhone);
        $formatedPhone = str_replace("&nbsp;", "", $formatedPhone);
        return $formatedPhone;
    }
}