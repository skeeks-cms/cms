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
 * @property integer $pid_main
 * @property string $pids
 * @property integer $level
 * @property string $dir
 * @property integer $has_children
 * @property integer $main_root
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
 * @property CmsContentElement[] $cmsContentElements
 * @property CmsContentElementTree[] $cmsContentElementTrees
 * @property CmsTree $p
 * @property CmsTree[] $cmsTrees
 * @property CmsSite $site
 * @property CmsTree $pidMain
 * @property CmsTree[] $cmsTrees0
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

    /**
     * @return static
     */
    static public function findCurrentRoot()
    {
        if ($site = \Yii::$app->currentSite->get())
        {
            return self::find()->where(['id' => $site->cms_tree_id])->one();
        } else {
            return self::findDefaultRoot();
        }
    }

    /**
     * Нода по умолчанию, задается для всех сайтов проекта.
     * @return static
     */
    static public function findDefaultRoot()
    {
        return self::find()->where(['main_root' => 1])->one();
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type'              => Yii::t('app', 'Тип'),
            'pid_main'          => Yii::t('app', 'Pid main'),
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


    public function createAbsoluteUrl()
    {
        return $this->createUrl();
    }

    public function getPageUrl()
    {
        return $this->createUrl();
    }
    /**
     * @return string
     */
    public function createUrl()
    {
        if ($this->isLink())
        {
            if ($this->redirect)
            {
                return $this->redirect;
            }
        }

        //$sites = Site::getAllKeyTreeId();
        $site = '';
        if ($this->isRoot())
        {
            if (isset($sites[$this->getPidMain()]))
            {
                $site = $sites[$this->id];
            }
        } else
        {
            if (isset($sites[$this->getPidMain()]))
            {
                $site = $sites[$this->getPidMain()];
            }

        }

        $site = null;

        if ($site)
        {
            if ($this->getDir()) {

                return $site->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getDir() . (\Yii::$app->urlManager->suffix ? \Yii::$app->urlManager->suffix : '');
            } else {
                return $site->getBaseUrl();
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
     *
     * Массив id дочерних разделов
     *
     * @return array
     */
    public function fetchChildrensIds()
    {
        $ids = [];

        if ($this->hasChildrens())
        {
            if ($childrens = $this->fetchChildrens())
            {
                foreach ($childrens as $chidren)
                {
                    $ids[] = $chidren->id;
                }
            }
        }

        return $ids;
    }

    /**
     *
     * Массив id дочерних разделов включая id их подразделов
     *
     * @return array
     */
    public function fetchChildrensAllIds()
    {
        $ids = [];

        if ($this->hasChildrens())
        {
            if ($childrens = $this->fetchChildrensAll())
            {
                foreach ($childrens as $chidren)
                {
                    $ids[] = $chidren->id;
                }
            }
        }

        return $ids;
    }

    /**
     * @return $this[];
     */
    public function fetchChildrensAll()
    {
        return $this->findChildrensAll()->all();
    }

    /**
     * @return $this[];
     */
    public function fetchChildrens()
    {
        return $this->findChildrens()->all();
    }

    /**
     * @return $this[];
     */
    public function fetchParents()
    {
        if ($this->findParents())
        {
            return $this->findParents()->all();
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
     * @return bool
     */
    public function hasMainImageSrc()
    {
        $mainImage = $this->getFilesGroups()->getComponent('image');

        if ($mainImage->getFirstSrc())
        {
            return true;
        } else
        {
            return false;
        }
    }
    /**
     * @return string
     */
    public function getMainImageSrc()
    {
        $mainImage = $this->getFilesGroups()->getComponent('image');

        if ($mainImage->getFirstSrc())
        {
            return $mainImage->getFirstSrc();
        }

        return \Yii::$app->params['noimage'];
    }

    /**
     * @return array
     */
    public function getImagesSrc()
    {
        return $this->getFilesGroups()->getComponent('images')->items;
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
