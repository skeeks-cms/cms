<?php
/**
 * Publication
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\CanBeLinkedToTree;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\Implode;
use skeeks\cms\models\behaviors\SeoPageName;
use skeeks\cms\models\behaviors\traits\TreeBehaviorTrait;
use skeeks\cms\models\behaviors\TreeBehavior;
use Yii;
use yii\db\ActiveQuery;
use yii\db\BaseActiveRecord;

/**
 * This is the model class for table "{{%cms_tree}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property string $description_short
 * @property string $description_full
 * @property string $files
 * @property string $code
 * @property integer $pid
 * @property string $pids
 * @property integer $level
 * @property string $dir
 * @property integer $has_children
 * @property integer $priority
 * @property string $type
 * @property integer $published_at
 * @property string $redirect
 * @property string $tree_menu_ids
 * @property string $active
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $site_code
 *
 * @property string $absoluteUrl
 * @property string $url
 *
 * @property CmsContentElement[]        $cmsContentElements
 * @property CmsContentElementTree[]    $cmsContentElementTrees
 * @property CmsTree                    $parentTree
 * @property CmsTree[]                  $parentTrees
 * @property CmsSite                    $site
 */
class Tree extends Core
{
    use TreeBehaviorTrait;
    use \skeeks\cms\models\behaviors\traits\HasFiles;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree}}';
    }


    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $result = [];

        $result[] = SeoPageName::className();
        $result[] = HasFiles::className();
        $result[] = TreeBehavior::className();
        $result[] = [
            'class' => Implode::className(),
            "fields" =>  [
                "tree_menu_ids"
            ]
        ];
        return $result;
    }

    public function init()
    {
        parent::init();

        $this->on(BaseActiveRecord::EVENT_BEFORE_INSERT, [$this, 'checksBeforeSave']);
        $this->on(BaseActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'checksBeforeSave']);
    }

    public function checksBeforeSave($event)
    {
        if (!$this->site_code)
        {
            $this->site_code = $this->parentTree->site_code;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type'              => Yii::t('app', 'Тип'),
            'redirect'          => Yii::t('app', 'Redirect'),
            'tree_menu_ids'     => Yii::t('app', 'Позиции меню'),
            'priority'          => Yii::t('app', 'Приоритет'),
            'code'              => Yii::t('app', 'Код'),
            'active'              => Yii::t('app', 'Active'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
            'meta_description' => Yii::t('app', 'Meta Description'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            ['active', 'default', 'value' => Cms::BOOL_Y],
            [['type', 'redirect'], 'string'],
            [['pid_main', 'priority'], 'integer'],
            [['tree_menu_ids'], 'safe'],
            [['code'], 'string', 'max' => 64],
            [['code'], 'unique'],
            [['name'], 'string', 'max' => 255],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string'],
            [['meta_title'], 'string', 'max' => 500],
            [['site_code'], 'string', 'max' => 5],
        ]);
    }


    /**
     * @return string
     */
    public function getAbsoluteUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->isLink())
        {
            if ($this->redirect)
            {
                return $this->redirect;
            }
        }

        if ($this->site)
        {
            if ($this->getDir())
            {
                return $this->site->getUrl() . DIRECTORY_SEPARATOR . $this->getDir() . (\Yii::$app->urlManager->suffix ? \Yii::$app->urlManager->suffix : '');
            } else {
                return $this->site->getUrl();
            }
        } else {
            if ($this->getDir()) {
                return \Yii::$app->request->getHostInfo() . DIRECTORY_SEPARATOR . $this->getDir() . (\Yii::$app->urlManager->suffix ? \Yii::$app->urlManager->suffix : '');
            } else {
                return \Yii::$app->request->getHostInfo();
            }
        }
    }

    /**
     * @return null|ModelType
     */
    public function getType()
    {
        if ($this->type)
        {
            return \Yii::$app->registeredModels->getDescriptor($this)->getTypes()->getComponent($this->type);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getParentTrees()
    {
        if ($parents = $this->findParents())
        {
            return $parents->all();
        }

        return [];
    }



    /**
     * Эта страница является ссылкой?
     *
     * @return bool
     */
    public function isLink()
    {
        return (bool) ($this->redirect);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentTree()
    {
        return $this->hasOne(static::className(), ['id' => 'pid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(CmsSite::className(), ['code' => 'site_code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::className(), ['tree_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElementTrees()
    {
        return $this->hasMany(CmsContentElementTree::className(), ['tree_id' => 'id']);
    }
}
