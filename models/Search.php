<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.04.2015
 */
namespace skeeks\cms\models;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\User;
use Yii;

use yii\base\Model;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\data\ActiveDataProvider;

/**
 * Class Search
 * @package skeeks\cms\models
 */
class Search extends ActiveRecord
{
    /**
     * @var null|string
     */
    public $modelClassName = null;

    /**
     * @param array $modelClassName
     */
    public function __construct($modelClassName)
    {
        $this->modelClassName = $modelClassName;
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }
    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
    * @return ActiveRecord
    */
    public function getLoadedModel()
    {
        if ($this->_loadedModel === null)
        {
            $className  = $this->modelClassName;
            $this->_loadedModel = new $className();
        }

        return $this->_loadedModel;
    }

    protected $_loadedModel = null;
    protected $_dataProvider = null;

    /**
     * @return ActiveDataProvider
     */
    public function getDataProvider()
    {
        if ($this->_dataProvider === null)
        {
            $className  = $this->modelClassName;
            $query      = $className::find();

            $this->_dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }

        return $this->_dataProvider;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        if (!($this->getLoadedModel()->load($params)))
        {
            return $this->getDataProvider();
        }

        $query = $this->getDataProvider()->query;

        if ($columns = $this->getLoadedModel()->getTableSchema()->columns)
        {
            /**
             * @var \yii\db\ColumnSchema $column
             */
            foreach ($columns as $column)
            {
                if ($column->phpType == "integer")
                {
                    $query->andFilterWhere([$this->getLoadedModel()->tableName() . '.' . $column->name => $this->_loadedModel->{$column->name}]);
                } else if ($column->phpType == "string")
                {
                    $query->andFilterWhere(['like', $this->getLoadedModel()->tableName() . '.' . $column->name, $this->_loadedModel->{$column->name}]);
                }
            }
        }
        /*$query->andFilterWhere([
            'id' => $this->id,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'city', $this->city])
            ->andFilterWhere(['like', 'address', $this->address])
            ->andFilterWhere(['like', 'info', $this->info])
            ->andFilterWhere(['like', 'image', $this->image])
            ->andFilterWhere(['like', 'image_cover', $this->image_cover]);*/

        return $this->getDataProvider();
    }
}