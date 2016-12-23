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

use skeeks\sx\filters\string\SeoPageName as FilterSeoPageName;
use Imagine\Image\ManipulatorInterface;
use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\CanBeLinkedToTree;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\behaviors\HasTableCache;
use skeeks\cms\models\behaviors\Implode;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\behaviors\traits\HasUrlTrait;
use skeeks\cms\models\behaviors\traits\TreeBehaviorTrait;
use skeeks\cms\models\behaviors\TreeBehavior;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

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
 * @property string $code
 * @property integer $pid
 * @property string $pids
 * @property integer $level
 * @property string $dir
 * @property integer $has_children
 * @property integer $priority
 * @property string $tree_type_id
 * @property integer $published_at
 * @property string $redirect
 * @property string $tree_menu_ids
 * @property string $active
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $site_code
 * @property string $description_short_type
 * @property string $description_full_type
 * @property integer $image_full_id
 * @property integer $image_id
 * @property integer $redirect_tree_id
 * @property integer $redirect_code
 * @property string $name_hidden
 *
 *
 * @property string $view_file //version > 2.4.8
 *
 * @property string $absoluteUrl
 * @property string $url
 *
 * @property CmsStorageFile $image
 * @property CmsStorageFile $imageFull
 *
 * @property CmsTreeFile[]  $cmsTreeFiles
 * @property CmsTreeImage[] $cmsTreeImages
 * @property CmsTree        $redirectTree
 *
 * @property CmsStorageFile[] $files
 * @property CmsStorageFile[] $images
 *
 * @property CmsContentElement[]        $cmsContentElements
 * @property CmsContentElementTree[]    $cmsContentElementTrees
 * @property CmsSite                    $site
 * @property CmsSite                    $cmsSiteRelation
 * @property CmsTreeType                $treeType
 * @property CmsTreeProperty[]          $cmsTreeProperties
 *
 * @property Tree                       $parent
 * @property Tree[]                     $parents
 * @property Tree[]                     $children
 * @property Tree                       $root
 * @property Tree                       $prev
 * @property Tree                       $next
 * @property Tree                       $descendants //@version > 2.6.0
 */
class Tree extends Core
{
    use HasRelatedPropertiesTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree}}';
    }

    const PRIORITY_STEP = 100; //Шаг приоритета
    const PIDS_DELIMETR = "/"; //Шаг приоритета


    public function behaviors()
    {
        $behaviors = parent::behaviors();

        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFile::className() =>
            [
                'class'     => HasStorageFile::className(),
                'fields'    => ['image_id', 'image_full_id']
            ],

            HasStorageFileMulti::className() =>
            [
                'class'         => HasStorageFileMulti::className(),
                'relations'     => ['images', 'files']
            ],

            Implode::className() =>
            [
                'class' => Implode::className(),
                "fields" =>  [
                    "tree_menu_ids"
                ]
            ],

            "implode_tree" =>
            [
                'class' => Implode::className(),
                "fields" =>  ["pids"],
                "delimetr" => self::PIDS_DELIMETR,
            ],

            HasRelatedProperties::className() =>
            [
                'class' => HasRelatedProperties::className(),
                'relatedElementPropertyClassName'   => CmsTreeProperty::className(),
                'relatedPropertyClassName'          => CmsTreeTypeProperty::className(),
            ],
        ]);
    }

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_INSERT, [$this, 'beforeSaveTree']);
        $this->on(self::EVENT_BEFORE_UPDATE, [$this, 'beforeSaveTree']);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterUpdateTree']);
        $this->on(self::EVENT_BEFORE_DELETE, [$this, 'beforeDeleteTree']);
        $this->on(self::EVENT_AFTER_DELETE, [$this, 'afterDeleteTree']);
    }


    /**
     * Если есть дети для начала нужно удалить их всех
     * @param Event $event
     * @throws \Exception
     */
    public function beforeDeleteTree(Event $event)
    {
        if ($this->children)
        {
            foreach ($this->children as $childNode)
            {
                $childNode->delete();
            }
        }
    }

    /**
     * После удаления нужно родителя пересчитать
     * @param Event $event
     */
    public function afterDeleteTree(Event $event)
    {
        if ($this->parent)
        {
            $this->parent->processNormalize();
        }
    }

    /**
     * Изменился код
     * @param AfterSaveEvent $event
     */
    public function afterUpdateTree(AfterSaveEvent $event)
    {
        if ($event->changedAttributes)
        {
            //Если изменилось название seo_page_name
            if (isset($event->changedAttributes['code']))
            {
                $event->sender->processNormalize();
            }
        }
    }

    /**
     * Проверки и дополнения перед сохранением раздела
     * @param $event
     */
    public function beforeSaveTree($event)
    {
        if (!$this->site_code)
        {
            if ($this->parent)
            {
                $this->site_code = $this->parent->site_code;
            }
        }

        if (!$this->tree_type_id)
        {
            if ($this->parent && $this->parent->treeType)
            {
                if ($this->parent->treeType->defaultChildrenTreeType)
                {
                    $this->tree_type_id = $this->parent->treeType->defaultChildrenTreeType->id;
                } else
                {
                    $this->tree_type_id = $this->parent->tree_type_id;
                }
            }
        }


        //Если не заполнено название, нужно сгенерить
        if (!$this->name)
        {
            $this->generateName();
        }

        if (!$this->code)
        {
            $this->generateCode();
        }
    }



    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('skeeks/cms', 'ID'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'published_at' => Yii::t('skeeks/cms', 'Published At'),
            'published_to' => Yii::t('skeeks/cms', 'Published To'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'tree_type_id'              => Yii::t('skeeks/cms', 'Type'),
            'redirect'          => Yii::t('skeeks/cms', 'Redirect'),
            'tree_menu_ids'     => Yii::t('skeeks/cms', 'Menu Positions'),
            'priority'          => Yii::t('skeeks/cms', 'Priority'),
            'code'              => Yii::t('skeeks/cms', 'Code'),
            'active'              => Yii::t('skeeks/cms', 'Active'),
            'meta_title'        => Yii::t('skeeks/cms', 'Meta Title'),
            'meta_keywords'         => Yii::t('skeeks/cms', 'Meta Keywords'),
            'meta_description'  => Yii::t('skeeks/cms', 'Meta Description'),
            'description_short' => Yii::t('skeeks/cms', 'Description Short'),
            'description_full' => Yii::t('skeeks/cms', 'Description Full'),
            'description_short_type' => Yii::t('skeeks/cms', 'Description Short Type'),
            'description_full_type' => Yii::t('skeeks/cms', 'Description Full Type'),
            'image_id' => Yii::t('skeeks/cms', 'Main Image (announcement)'),
            'image_full_id' => Yii::t('skeeks/cms', 'Main Image'),
            'images' => Yii::t('skeeks/cms', 'Images'),
            'files' => Yii::t('skeeks/cms', 'Files'),
            'redirect_tree_id' => Yii::t('skeeks/cms', 'Redirect Section'),
            'redirect_code' => Yii::t('skeeks/cms', 'Redirect Code'),
            'name_hidden' => Yii::t('skeeks/cms', 'Hidden Name'),
            'view_file' => Yii::t('skeeks/cms', 'Template'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['description_short', 'description_full'], 'string'],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            [['redirect_code'], 'default', 'value' => 301],
            [['redirect_code'], 'in', 'range' => [301, 302]],
            [['redirect'], 'string'],
            [['name_hidden'], 'string'],
            [['priority', 'tree_type_id', 'image_id', 'image_full_id', 'redirect_tree_id', 'redirect_code'], 'integer'],
            [['tree_menu_ids'], 'safe'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 255],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string'],
            [['meta_title'], 'string', 'max' => 500],
            [['site_code'], 'string', 'max' => 15],
            [['pid', 'code'], 'unique', 'targetAttribute' => ['pid', 'code'], 'message' => \Yii::t('skeeks/cms','For this subsection of the code is already in use.')],
            [['pid', 'code'], 'unique', 'targetAttribute' => ['pid', 'code'], 'message' => \Yii::t('skeeks/cms','The combination of Code and Pid has already been taken.')],

            ['description_short_type', 'string'],
            ['description_full_type', 'string'],
            ['description_short_type', 'default', 'value' => "text"],
            ['description_full_type', 'default', 'value' => "text"],
            ['view_file', 'string', 'max' => 128],
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRedirectTree()
    {
        return $this->hasOne(CmsTree::className(), ['id' => 'redirect_tree_id']);
    }

    /**
     *
     * Корневые разделы дерева.
     *
     * @return ActiveQuery
     */
	static public function findRoots()
	{
		return static::find()->where(['level' => 0])->orderBy(["priority" => SORT_ASC]);
	}


    /**
     * @return string
     */
    public function getUrl($scheme = false, $params = [])
    {
        if ($params)
        {
            $params = ArrayHelper::merge(['/cms/tree/view', 'model' => $this], $params);
        } else
        {
            $params = ['/cms/tree/view', 'model' => $this];
        }

        return Url::to(['/cms/tree/view', 'model' => $this], $scheme);
    }

    /**
     * @return string
     */
    public function getAbsoluteUrl($params = [])
    {
        return $this->getUrl(true, $params);
    }

    /**
     * @return CmsSite
     */
    public function getSite()
    {
        //return $this->hasOne(CmsSite::className(), ['code' => 'site_code']);
        return CmsSite::getByCode($this->site_code);
    }

    /**
     * @return ActiveQuery
     */
    public function getCmsSiteRelation()
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeProperties()
    {
        return $this->hasMany(CmsTreeProperty::className(), ['element_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTreeType()
    {
        return $this->hasOne(CmsTreeType::className(), ['id' => 'tree_type_id']);
    }


    /**
     * @version > 2.4.9.1
     * Все возможные свойства связанные с моделью
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    /*public function getRelatedProperties()
    {
        return $this->treeType->cmsTreeTypeProperties;
    }*/

    /**
     *
     * @version > 2.4.9.1
     * Все возможные свойства связанные с моделью
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRelatedProperties()
    {
        return $this->hasMany(CmsTreeTypeProperty::className(), ['tree_type_id' => 'id'])
                    ->via('treeType')->orderBy(['priority' => SORT_ASC]);
        //return $this->cmsContent->cmsContentProperties;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(StorageFile::className(), ['id' => 'image_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFullImage()
    {
        return $this->hasOne(StorageFile::className(), ['id' => 'image_full_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsTreeImages');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsTreeFiles');
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeFiles()
    {
        return $this->hasMany(CmsTreeFile::className(), ['tree_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeImages()
    {
        return $this->hasMany(CmsTreeImage::className(), ['tree_id' => 'id']);
    }














        //Работа с деревом

    /**
     * @param null $depth
     * @return array
     */
    public function getParentsIds($depth = null)
    {
        return (array) $this->pids;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(static::className(), ['id' => 'pid']);
    }

    /**
     *
     * To get root of a node:
     *
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoot()
    {
        $tableName = $this->tableName();
        $id = $this->getParentsIds();
        if ($id && is_array($id))
        {
            $id = $id[0];
        }

        $query = $this->find()
            ->andWhere(["{$tableName}.[[" . $this->primaryKey()[0] . "]]" => $id]);

        $query->multiple = false;
        return $query;
    }

    /**
     * @param int|null $depth
     * @return \yii\db\ActiveQuery
     * @throws Exception
     */
    public function getParents($depth = null)
    {
        $tableName = $this->tableName();
        $ids = $this->getParentsIds($depth);
        $query = $this->find()
            ->andWhere(["{$tableName}.[[" . $this->primaryKey()[0] . "]]" => $ids]);
        $query->multiple = true;
        return $query;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        $result = $this->hasMany($this->className(), ["pid" => "id"]);
        $result->orderBy(["priority" => SORT_ASC]);

        return $result;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDescendants()
    {
        $pidsAll = implode("/", $this->pids) . "/" . $this->id . "/%";

        $expression = new Expression("`pids` LIKE '{$pidsAll}'");

        $query = static::find()
            ->andWhere([
                'or',
                $expression,
                ['pid' => $this->id],
            ])
            ->orderBy([
                "level"     => SORT_ASC,
                "priority"  => SORT_ASC
            ]);

        //$query->primaryModel = $this;
        $query->multiple = true;

        return $query;
    }


    /**
     * @return \yii\db\ActiveQuery
     * @throws NotSupportedException
     */
    public function getPrev()
    {
        $tableName = $this->tableName();
        $query = $this->find()
            ->andWhere([
                'and',
                ["{$tableName}.[[pid]]" => $this->pid],
                ['<', "{$tableName}.[[priority]]", $this->priority],
            ])
            ->orderBy(["{$tableName}.[[priority]]" => SORT_ASC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws NotSupportedException
     */
    public function getNext()
    {
        $tableName = $this->tableName();
        $query = $this->find()
            ->andWhere([
                'and',
                ["{$tableName}.[[pid]]" => $this->pid],
                ['>', "{$tableName}.[[priority]]", $this->priority],
            ])
            ->orderBy(["{$tableName}.[[priority]]" => SORT_ASC])
            ->limit(1);
        $query->multiple = false;
        return $query;
    }







        //Манипуляции с деревом


    /**
     * Автоматическая генерация названия раздела
     * @return $this
     */
    public function generateName()
    {
        $lastTree = $this->find()->orderBy(["id" => SORT_DESC])->one();
        $this->setAttribute("name", "pk-" . $lastTree->primaryKey);

        return $this;
    }

    /**
     * Автоматическая генерация code по названию
     * @return $this
     */
    public function generateCode()
    {
        if ($this->isRoot())
        {
            $this->setAttribute("code", null);
        } else
        {
            $filter         = new FilterSeoPageName();
            $filter->maxLength = \Yii::$app->cms->tree_max_code_length;

            $this->code     = $filter->filter($this->name);

            $matches = [];
            //Роутинг элементов нужно исключить
            if (preg_match('/(?<id>\d+)\-(?<code>\S+)$/i', $this->code, $matches))
            {
                $this->code = "s" . $this->code;
            }

            if (!$this->isValidCode())
            {
                $this->code    = $filter->filter($this->code . "-" . substr(md5(uniqid() . time()), 0, 4));

                if (!$this->isValidCode())
                {
                    $this->generateCode();
                }
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function isValidCode()
    {
        $parent = $this->parent;
        if (!$parent)
        {
            return true;
        }

        $find   = $parent->getChildren()
            ->where([
                "code" => $this->code,
                'pid' => $this->pid
            ]);

        if (!$this->isNewRecord)
        {
            $find->andWhere([
                "!=", 'id', $this->id
            ]);
        }

        if ($find->one())
        {
            return false;
        }

        return true;
    }

    /**
     *
     * Обновление всего дерева ниже, и самого элемента.
     * Если найти всех рутов дерева и запустить этот метод, то дерево починиться в случае поломки
     * правильно переустановятся все dir, pids и т.д.
     *
     * @return $this
     */
    public function processNormalize()
    {
        //Если это новая несохраненная сущьность, ничего делать не надо
        if ($this->isNewRecord)
        {
            return $this;
        }

        if (!$this->pid)
        {
            $this->setAttribute("dir", null);
            $this->save(false);
        }
        else
        {
            $this->setAttributesForFutureParent($this->parent);
            $this->save(false);
        }


        //Берем детей на один уровень ниже
        if ($this->children)
        {
            $this->save(false);

            foreach ($this->children as $childModel)
            {
                $childModel->processNormalize();
            }
        }

        return $this;
    }


    /**
     * Установка атрибутов если родителем этой ноды будет новый, читаем родителя, и обновляем необходимые данные у себя
     *
     * @param Tree $parent
     * @return $this
     */
    public function setAttributesForFutureParent(Tree $parent)
    {
        //Родитель должен быть уже сохранен
        if ($parent->isNewRecord)
        {
            throw new Exception('Родитель дольжен быть сохранен');
        }

        if ($this->id == $parent->id)
        {
            throw new Exception('Родитель не может быть вложен в родителя');
        }

        $newPids     = $parent->pids;
        $newPids[]   = $parent->primaryKey;

        $this->setAttribute("level",     ($parent->level + 1));
        $this->setAttribute('pid',       $parent->primaryKey);
        $this->setAttribute("pids",      $newPids);


        if (!$this->name)
        {
            $this->generateName();
        }

        if (!$this->code)
        {
            //Просто генерируем pageName
            $this->generateCode();
        }

        if ($parent->dir)
        {
            $this->setAttribute("dir",       $parent->dir . Tree::PIDS_DELIMETR . $this->code);
        } else
        {
            $this->setAttribute("dir",       $this->code);
        }

        return $this;
    }


    /**
     * Создание дочерней ноды
     *
     * @param Tree $target
     * @return Tree
     * @throws Exception
     * @throws \skeeks\sx\validate\Exception
     */
    public function processCreateNode(Tree $target)
    {
        //Текущая сущьность должна быть уже сохранена
        if ($this->isNewRecord)
        {
            throw new Exception('Текущая сущьность должна быть уже сохранена');
        }
        //Новая сущьность должна быть еще не сохранена
        if (!$target->isNewRecord)
        {
            throw new Exception('Новая сущьность должна быть еще не сохранена');
        }

        //Установка атрибутов будущему ребенку
        $target->setAttributesForFutureParent($this);
        if (!$target->save(false))
        {
            throw new Exception(\Yii::t('skeeks/cms',"Failed to create the child element:  ") . Json::encode($target->attributes));
        }

        $this->save(false);

        return $target;
    }


    /**
     * Процесс вставки ноды одна в другую.
     * Можно вставлять как уже сохраненную модель с дочерними элементами, так и еще не сохраненную.
     *
     * @param Tree $target
     * @return $this
     * @throws Exception
     * @throws \skeeks\sx\validate\Exception
     */
    public function processAddNode(Tree $target)
    {
        //Текущая сущьность должна быть уже сохранена, и не равна $target
        if ($this->isNewRecord)
        {
            throw new Exception('Для начала нужно сохранить');
        }

        if ($this->id == $target->id)
        {
            throw new Exception('Должны быть разными');
        }


        //Если раздел который мы пытаемся добавить новый, то у него нет детей и он
        if ($target->isNewRecord)
        {
            $this->processCreateNode($target);
            return $this;
        }
        else
        {
            $target->setAttributesForFutureParent($this);
            if (!$target->save(false))
            {
                throw new Exception(\Yii::t('skeeks/cms',"Unable to move: ") . Json::encode($target->attributes));
            }

            $this->processNormalize();
        }

        return $this;
    }






    //TODO: is depricated 2.4
    /**
     * @return bool
     */
    public function isRoot()
    {
        return (bool) ($this->level == 0);
    }

    //TODO: is depricated 2.3.3
    /**
     * Найти непосредственных детей ноды
     * @return ActiveQuery
     */
	/*public function findChildrensAll()
	{
        $pidString = implode('/', $this->pids) . "/" . $this->primaryKey;

		return $this->find()
            ->andWhere(['like', 'pids', $pidString . '%', false])
            ->orderBy(["priority" => SORT_ASC]);
	}*/

}



