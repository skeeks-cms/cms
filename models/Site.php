<?php
/**
 * StaticBlock
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\Exception;
use skeeks\cms\exceptions\NotFoundDb;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasRef;
use Yii;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property int $cms_tree_id
 * @property string $host_name
 * @property string $name
 * @property string $description
 * @method string getName()
 *
 * Class Publication
 * @package skeeks\cms\models
 */
class Site extends Core
{
    static public $sites = null;
    static public $sitesKeyTreeId = null;
    static public $sitesKeyHostName = null;

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

        $this->on(BaseActiveRecord::EVENT_BEFORE_INSERT, [$this, 'beforeInsertSite']);

    }

    /**
     * @param Event $e
     * @throws Exception
     */
    public function beforeInsertSite(Event $e)
    {
        $tree = new Tree([
            'name' => 'Главная страница',
        ]);

        if (!$tree->save(false))
        {
            throw new Exception('Не удалось создать раздел дерева');
        }

        $this->cms_tree_id = $tree->id;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                "class"  => behaviors\Serialize::className(),
                'fields' => ['params']
            ],
        ]);
    }


    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios['create'] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios['update'] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['host_name', 'cms_tree_id'], 'required'],
            [['host_name'], 'validateHost'],
            [['host_name'], 'unique'],
            [['description', 'name', 'host_name'], 'string'],
            [['params'], 'safe'],
        ]);
    }



    public function validateHost($attribute)
    {
        if(!preg_match('/^[а-яa-z0-9.]{3,255}$/', $this->$attribute))
        {
            $this->addError($attribute, 'Используйте только буквы в нижнем регистре и цифры. Пример site.ru (3-255 символов)');
        }
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return  array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'host_name' => 'Название хоста (домена)',
            'description' => "Описание",
            'name' => Yii::t('app', 'Name'),
            'params' => Yii::t('app', 'Params'),
            'cms_tree_id' => Yii::t('app', 'Tree Id'),
        ]);
    }


    /**
     * @return static[];
     */
    static public function getAll()
    {
        try
        {
            if (static::$sites === null)
            {
                static::$sites = static::find()->all();
            }

        } catch (\yii\db\Exception $e)
        {
            if (!\Yii::$app->db->getTableSchema(static::tableName(), true))
            {
                throw new NotFoundDb();
            }
        }


        return static::$sites;
    }

    /**
     * @return static[];
     */
    static public function getAllKeyTreeId()
    {
        if (static::$sitesKeyTreeId === null)
        {
            $models = static::getAll();
            if ($models)
            {
                foreach ($models as $model)
                {
                    static::$sitesKeyTreeId[$model->cms_tree_id] = $model;
                }
            } else
            {
                static::$sitesKeyTreeId = [];
            }
        }

        return static::$sitesKeyTreeId;
    }

    /**
     * @return static[];
     */
    static public function getAllKeyHostName()
    {
        if (static::$sitesKeyHostName === null)
        {
            $models = static::getAll();
            if ($models)
            {
                foreach ($models as $model)
                {
                    static::$sitesKeyHostName[$model->host_name] = $model;
                }
            } else
            {
                static::$sitesKeyHostName = [];
            }
        }

        return static::$sitesKeyHostName;
    }


    /**
     * @param $id
     * @return static
     */
    static public function findById($id)
    {
        return static::find()->where(['id' => (int) $id])->one();
    }

    /**
     * @param $host_name
     * @return static
     */
    static public function findByHostName($host_name)
    {
        return static::find()->where(['host_name' => (string) $host_name])->one();
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->primaryKey ? 'site-' . $this->primaryKey : '';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name ? $this->name : $this->host_name;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return '//' . $this->host_name;
    }
}
