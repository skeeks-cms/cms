<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Widget;
use skeeks\cms\components\Cms;
use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\traits\ValidateRulesTrait;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\base\Event;
use yii\db\BaseActiveRecord;

/**
 * This is the model class for table "{{%cms_site}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $active
 * @property string $def
 * @property integer $priority
 * @property string $code
 * @property string $lang_code
 * @property string $name
 * @property string $server_name
 * @property string $description
 *
 * @property CmsLang $cmsLang
 * @property CmsSiteDomain[] $cmsSiteDomains
 * @property CmsTree[] $cmsTrees
 */
class CmsSite extends Core
{
    use ValidateRulesTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site}}';
    }


    public function init()
    {
        parent::init();

        $this->on(BaseActiveRecord::EVENT_BEFORE_INSERT, [$this, 'afterBeforeChecks']);
        $this->on(BaseActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'afterBeforeChecks']);

    }

    /**
     * @param Event $e
     * @throws Exception
     */
    public function afterBeforeChecks(Event $e)
    {
        //Если этот элемент по умолчанию выбран, то все остальны нужно сбросить.
        if ($this->def == Cms::BOOL_Y)
        {
            static::updateAll(['def' => Cms::BOOL_N]);
        }
    }

    /**
     * @param Event $e
     * @throws Exception
     */
    /*public function afterSaveChecks(Event $e)
    {
        $tree = new Tree([
            'name' => 'Главная страница',
        ]);

        if (!$tree->save(false))
        {
            throw new Exception('Не удалось создать раздел дерева');
        }

        $this->cms_tree_id = $tree->id;
    }*/

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'active' => Yii::t('app', 'Active'),
            'def' => Yii::t('app', 'Default'),
            'priority' => Yii::t('app', 'Priority'),
            'code' => Yii::t('app', 'Code'),
            'lang_code' => Yii::t('app', 'Lang'),
            'name' => Yii::t('app', 'Name'),
            'server_name' => Yii::t('app', 'Server Name'),
            'description' => Yii::t('app', 'Description'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority'], 'integer'],
            [['code', 'lang_code', 'name'], 'required'],
            [['active', 'def'], 'string', 'max' => 1],
            [['code', 'lang_code'], 'string', 'max' => 5],
            [['name', 'server_name', 'description'], 'string', 'max' => 255],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            [['server_name'], 'validateServerName'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            ['def', 'default', 'value' => Cms::BOOL_N],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsLang()
    {
        return $this->hasOne(CmsLang::className(), ['code' => 'lang_code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteDomains()
    {
        return $this->hasMany(CmsSiteDomain::className(), ['site_code' => 'code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTrees()
    {
        return $this->hasMany(CmsTree::className(), ['site_code' => 'code']);
    }

}