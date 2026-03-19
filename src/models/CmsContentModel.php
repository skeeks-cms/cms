<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.09.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;

/**
 *
 * @property integer             $id
 * @property integer             $created_at
 *
 * @property CmsContentElement[] $cmsContentElements
 */
class CmsContentModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_model}}';
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::class, ['cms_content_model_id' => 'id']);
    }

}