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

use skeeks\cms\models\behaviors\CanBeLinkedToTree;
use skeeks\cms\models\behaviors\HasPageOptions;
use skeeks\cms\models\behaviors\Implode;
use skeeks\cms\models\behaviors\SeoPageName;
use skeeks\cms\models\behaviors\traits\TreeBehaviorTrait;
use skeeks\cms\models\behaviors\TreeBehavior;
use Yii;
use yii\db\ActiveQuery;

/**
 *
 * @property string $type
 *
 * Class Tree
 * @package skeeks\cms\models
 */
class Tree extends PageAdvanced
{
    use TreeBehaviorTrait;

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
        foreach ($behaviors as $key => $behavior) {
            if ($behavior != SeoPageName::className()) {
                $result[$key] = $behavior;
            }
        }

        $result[] = TreeBehavior::className();
        $result[] = CanBeLinkedToTree::className();
        $result[] = [
            'class' => Implode::className(),
            "fields" =>  [
                "tree_menu_ids"
            ]
        ];
        $result[HasPageOptions::className()] = HasPageOptions::className();
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
            'page_options'      => Yii::t('app', 'Page Options'),
            'tree_ids'          => Yii::t('app', 'Связан с разделами'),
            'redirect'          => Yii::t('app', 'Redirect'),
            'tree_menu_ids'     => Yii::t('app', 'Позиции меню'),
            'priority'          => Yii::t('app', 'Приоритет'),
        ]);
    }

    public $multiPageOptions;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['type'], 'string'],
            [['pid_main', 'priority'], 'integer'],
            [['page_options', 'multiPageOptions', 'tree_ids', 'tree_menu_ids', 'redirect'], 'safe'],
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios['create'] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios['update'] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }


    public function createAbsoluteUrl()
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

        $sites = Site::getAllKeyTreeId();
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
}
