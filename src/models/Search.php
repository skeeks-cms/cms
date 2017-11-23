<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.04.2015
 */

namespace skeeks\cms\models;

use yii\base\Component;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

/**
 * @property ActiveRecord $loadedModel
 * @property ActiveDataProvider $dataProvider
 *
 * Class Search
 * @package skeeks\cms\models
 */
class Search extends Component
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

    protected $_loadedModel = null;
    protected $_dataProvider = null;

    /**
     * @return ActiveRecord
     */
    public function getLoadedModel()
    {
        if ($this->_loadedModel === null) {
            $className = $this->modelClassName;
            $this->_loadedModel = new $className();
        }

        return $this->_loadedModel;
    }

    /**
     * @return ActiveDataProvider
     */
    public function getDataProvider()
    {
        if ($this->_dataProvider === null) {
            $className = $this->modelClassName;

            $this->_dataProvider = new ActiveDataProvider([
                'query' => $className::find(),
            ]);
        }

        return $this->_dataProvider;
    }

    /**
     * @param $params
     * @return ActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     */
    public function search($params)
    {
        if (!($this->loadedModel->load($params))) {
            return $this->dataProvider;
        }

        $query = $this->dataProvider->query;

        if ($columns = $this->loadedModel->getTableSchema()->columns) {
            /**
             * @var \yii\db\ColumnSchema $column
             */
            foreach ($columns as $column) {
                if ($column->phpType == "integer") {
                    $query->andFilterWhere([$this->loadedModel->tableName() . '.' . $column->name => $this->loadedModel->{$column->name}]);
                } else {
                    if ($column->phpType == "string") {
                        $query->andFilterWhere([
                            'like',
                            $this->loadedModel->tableName() . '.' . $column->name,
                            $this->loadedModel->{$column->name}
                        ]);
                    }
                }
            }
        }

        return $this->dataProvider;
    }
}