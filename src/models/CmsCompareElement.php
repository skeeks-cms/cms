<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.09.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\shop\models\ShopCmsContentElement;
use skeeks\cms\shop\models\ShopUser;
use skeeks\modules\cms\money\models\Currency;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "shop_favorite_product".
 *
 * @property int                   $id
 * @property int|null              $created_at
 * @property int                   $shop_user_id
 * @property int                   $cms_content_element_id
 *
 * @property ShopUser              $shopUser
 * @property CmsContentElement     $cmsContentElement
 * @property ShopCmsContentElement $shopCmsContentElement
 */
class CmsCompareElement extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_compare_element}}';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_at', 'shop_user_id', 'cms_content_element_id'], 'integer'],
            [['shop_user_id', 'cms_content_element_id'], 'required'],
            [['shop_user_id', 'cms_content_element_id'], 'unique', 'targetAttribute' => ['shop_user_id', 'cms_content_element_id']],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'shop_user_id'           => 'Пользователь',
            'cms_content_element_id' => 'Товар',
        ]);
    }


    /**
     * Gets query for [[ShopCart]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShopUser()
    {
        return $this->hasOne(ShopUser::className(), ['id' => 'shop_user_id']);
    }

    /**
     * Gets query for [[ShopProduct]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElement()
    {
        return $this->hasOne(CmsContentElement::className(), ['id' => 'cms_content_element_id']);
    }
    /**
     * Gets query for [[ShopProduct]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShopCmsContentElement()
    {
        return $this->hasOne(ShopCmsContentElement::className(), ['id' => 'cms_content_element_id']);
    }
}