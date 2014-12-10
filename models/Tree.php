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

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\behaviors\HasAdultStatus;
use skeeks\cms\models\behaviors\HasPageOptions;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\SeoPageName;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
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
    use \skeeks\cms\models\behaviors\traits\HasPageOptions;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $result = [];
        foreach ($behaviors as $key => $behavior)
        {
            if ($behavior != SeoPageName::className())
            {
                $result[$key] = $behavior;
            }
        }

        $result[] = TreeBehavior::className();
        $result[HasPageOptions::className()] = HasPageOptions::className();
        $result[TimestampPublishedBehavior::className()] = TimestampPublishedBehavior::className();
        return $result;
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree}}';
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type' => Yii::t('app', 'Tree type'),
            'pid_main' => Yii::t('app', 'Pid main'),
            'page_options' => Yii::t('app', 'Page Options'),
            'published_at'  => Yii::t('app', 'Дата публикации'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['type'], 'string'],
            [['pid_main', 'published_at'], 'integer'],
            [['page_options', 'multiPageOptions'], 'safe'],
        ]);
    }

    /**
     * @return string
     */
    public function createUrl()
    {
        $sites = Site::getAllKeyTreeId();
        if ($this->isRoot())
        {
            $site = $sites[$this->id];
        } else
        {
            $site = $sites[$this->getPidMain()];
        }

        if ($site)
        {
            if ($this->getDir())
            {

                return  $site->getBaseUrl() .  DIRECTORY_SEPARATOR . $this->getDir() . (\Yii::$app->urlManager->suffix ? \Yii::$app->urlManager->suffix : '');
            } else
            {
                return  $site->getBaseUrl();
            }
        } else
        {
            if ($this->getDir())
            {
                return  \Yii::$app->request->getHostInfo() . DIRECTORY_SEPARATOR . $this->getDir() . (\Yii::$app->urlManager->suffix ? \Yii::$app->urlManager->suffix : '');
            } else
            {
                return  \Yii::$app->request->getHostInfo();
            }

        }
    }

    public function createAbsoluteUrl()
    {
        return $this->createUrl();
    }


    /**
     * Нода по умолчанию, задается для всех сайтов проекта.
     * @return static
     */
    static public function findDefaultRoot()
    {
        return self::find()->where(['main_root' => 1])->one();
    }

    /**
     * @return static
     */
    static public function findCurrentRoot()
    {
        if ($site = \Yii::$app->currentSite->get())
        {
            return self::find()->where(['id' => $site->cms_tree_id])->one();
        } else
        {
            return self::findDefaultRoot();
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



}
