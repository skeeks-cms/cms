<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.09.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\behaviors\RelationalBehavior;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\modules\cms\money\models\Currency;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "shop_favorite_product".
 *
 * @property int                 $id
 * @property int|null            $created_at
 * @property int|null            $created_by
 * @property string              $name
 * @property string              $response
 * @property int                 $is_active
 * @property int                 $priority
 *
 * @property CmsContentElement[] $contentElements
 * @property CmsTree[]           $trees
 */
class CmsFaq extends ActiveRecord
{
    use HasLogTrait;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [


            RelationalBehavior::class => [
                'class'         => RelationalBehavior::class,
                'relationNames' => [
                    'contentElements',
                    'trees',
                ],
            ],

            CmsLogBehavior::class => [
                'class'        => CmsLogBehavior::class,
                /*'relation_map' => [
                    'cms_company_status_id' => 'status',
                ],*/
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_faq}}';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [['response'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['name', 'response'], 'required'],

            ['priority', 'default', 'value' => 500],
            ['is_active', 'default', 'value' => 1],

            [['trees', 'contentElements'], 'safe'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name'            => 'Вопрос',
            'is_active'        => 'Показывается?',
            'response'        => 'Ответ',
            'priority'        => 'Сортировка',
            'trees'           => 'Разделы',
            'contentElements' => 'Контент',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTrees()
    {
        return $this->hasMany(CmsTree::class,
            ['id' => 'cms_tree_id'])->viaTable("cms_faq2tree", ['cms_faq_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContentElements()
    {
        return $this->hasMany(CmsContentElement::class,
            ['id' => 'cms_content_element_id'])->viaTable("cms_faq2content_element", ['cms_faq_id' => 'id']);
    }


}