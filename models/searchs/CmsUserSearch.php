<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\models\searchs;

use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\CmsUser;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class CmsUserSearch extends CmsUser
{
    public $created_at_from;
    public $created_at_to;

    public $updated_at_from;
    public $updated_at_to;

    public $auth_at_from;
    public $auth_at_to;

    public $has_image;

    public $q;

    public $role;

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['section', 'integer'],
            ['created_at_from', 'integer'],
            ['created_at_to', 'integer'],
            ['updated_at_from', 'integer'],
            ['updated_at_to', 'integer'],
            ['auth_at_from', 'integer'],
            ['auth_at_to', 'integer'],
            ['has_image', 'integer'],
            ['q', 'string'],
            ['role', 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [

            'created_at_from' => \Yii::t('skeeks/cms', 'Created date (from)'),
            'created_at_to' => \Yii::t('skeeks/cms', 'Created (up to)'),

            'updated_at_from' => \Yii::t('skeeks/cms', 'Updated time (from)'),
            'updated_at_to' => \Yii::t('skeeks/cms', 'Updated time (up to)'),

            'auth_at_from' => \Yii::t('skeeks/cms', 'The last authorization (up from)'),
            'auth_at_to' => \Yii::t('skeeks/cms', 'The last authorization (up to)'),

            'has_image' => \Yii::t('skeeks/cms', 'Image'),

            'q' => \Yii::t('skeeks/cms', 'Search'),
            'role' => \Yii::t('skeeks/cms', 'Role'),
        ]);
    }

    public function search($params)
    {
        $tableName = $this->tableName();

        $activeDataProvider = new ActiveDataProvider([
            'query' => static::find()
        ]);

        if (!($this->load($params)))
        {
            return $activeDataProvider;
        }
        /**
         * @var $query ActiveQuery
         */
        $query = $activeDataProvider->query;

        //Standart
        if ($columns = $this->getTableSchema()->columns)
        {
            /**
             * @var \yii\db\ColumnSchema $column
             */
            foreach ($columns as $column)
            {
                if ($column->phpType == "integer")
                {
                    $query->andFilterWhere([$this->tableName() . '.' . $column->name => $this->{$column->name}]);
                } else if ($column->phpType == "string")
                {
                    $query->andFilterWhere(['like', $this->tableName() . '.' . $column->name, $this->{$column->name}]);
                }
            }
        }


        if ($this->created_at_from)
        {
            $query->andFilterWhere([
                '>=', $this->tableName() . '.created_at', \Yii::$app->formatter->asTimestamp(strtotime($this->created_at_from))
            ]);
        }

        if ($this->created_at_to)
        {
            $query->andFilterWhere([
                '<=', $this->tableName() . '.created_at', \Yii::$app->formatter->asTimestamp(strtotime($this->created_at_to))
            ]);
        }


        if ($this->updated_at_from)
        {
            $query->andFilterWhere([
                '>=', $this->tableName() . '.updated_at', \Yii::$app->formatter->asTimestamp(strtotime($this->updated_at_from))
            ]);
        }

        if ($this->updated_at_to)
        {
            $query->andFilterWhere([
                '<=', $this->tableName() . '.created_at', \Yii::$app->formatter->asTimestamp(strtotime($this->updated_at_to))
            ]);
        }



        if ($this->auth_at_from)
        {
            $query->andFilterWhere([
                '>=', $this->tableName() . '.logged_at', \Yii::$app->formatter->asTimestamp(strtotime($this->auth_at_from))
            ]);
        }

        if ($this->auth_at_to)
        {
            $query->andFilterWhere([
                '<=', $this->tableName() . '.logged_at', \Yii::$app->formatter->asTimestamp(strtotime($this->auth_at_to))
            ]);
        }


        if ($this->has_image)
        {
            $query->andFilterWhere([
                '>', $this->tableName() . '.image_id', 0
            ]);
        }


        if ($this->q)
        {
            $query->andFilterWhere([
                'or',
                ['like', $this->tableName() . '.name', $this->q],
                ['like', $this->tableName() . '.email', $this->q],
                ['like', $this->tableName() . '.phone', $this->q],
                ['like', $this->tableName() . '.username', $this->q],
            ]);
        }

        if ($this->role)
        {
            $query->innerJoin('auth_assignment', 'auth_assignment.user_id = cms_user.id');

            $query->andFilterWhere([
                'auth_assignment.item_name' => $this->role
            ]);
        }

        return $activeDataProvider;
    }


    /**
     * Returns the list of attribute names.
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return ArrayHelper::merge(parent::attributes(), $names);
    }
}
