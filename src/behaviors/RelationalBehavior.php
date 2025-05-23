<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 10.10.2015
 *
 * @see https://github.com/E96/yii2-relational-behavior
 */

namespace skeeks\cms\behaviors;

use skeeks\cms\models\CmsCompany;
use yii\base\Behavior;
use yii\base\ErrorException;
use yii\base\BaseObject;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class RelationalBehavior
 * @package skeeks\cms\behaviors
 */
class RelationalBehavior extends Behavior
{
    /**
     * @var ActiveRecord
     */
    public $owner;
    /**
     * @var array обновлять только указанные связи
     */
    public $relationNames = [];

    public function attach($owner)
    {
        if (!($owner instanceof ActiveRecord)) {
            throw new ErrorException(\Yii::t('skeeks/cms', 'Owner must be instance of {yii}',
                ['yii' => 'yii\db\ActiveRecord']));
        }
        if (count($owner->getTableSchema()->primaryKey) > 1) {
            throw new ErrorException(\Yii::t('skeeks/cms',
                'RelationalBehavior doesn\'t support composite primary keys'));
        }
        parent::attach($owner);
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'saveRelations',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveRelations',

            /*ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSaveRelations',*/
        ];
    }

    public function canSetProperty($name, $checkVars = true)
    {
        $getter = 'get' . $name;
        if (method_exists($this->owner, $getter) && $this->owner->$getter() instanceof ActiveQuery) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }

    private $_oldRelations = [];
    private $_changedRelations = [];

    public function getOldRelations()
    {
        return $this->_oldRelations;
    }
    public function getChangedRelations()
    {
        return $this->_changedRelations;
    }

    public function __set($name, $value)
    {
        //Если указаны определенные связи с которыми идет работа, то проверяем
        if ($this->relationNames && !in_array($name, $this->relationNames)) {
            return;
        }

        //Перед установкой новых значений зафиксировать старые
        if (!isset($this->_oldRelations[$name])) {
            $oldModels = $this->owner->{$name};
            $this->_oldRelations[$name] = $oldModels;
        }

        if ($value == '') {
            $this->owner->populateRelation($name, []);
            return;
        }
        
        $first = false;
        if (is_array($value) && count($value) > 0) {
            foreach ($value as $key => $val)
            {
                if (!($val instanceof BaseObject)) {
                    $first = true;
                }
                
                break;
            }
        }

        if ($first || !is_array($value) && !($value instanceof BaseObject)) {
            $getter = 'get' . $name;
            /** @var ActiveQuery $query */
            $query = $this->owner->$getter();
            /* @var $modelClass ActiveRecord */
            $modelClass = $query->modelClass;
            $value = $modelClass::findAll($value);
        }

        $this->owner->populateRelation($name, $value);
    }

    public function saveRelations($event)
    {
        /** @var ActiveRecord $model */
        $model = $event->sender;
        $relatedRecords = $model->getRelatedRecords();

        foreach ($relatedRecords as $relationName => $relationRecords) {

            if ($this->relationNames && !in_array($relationName, $this->relationNames)) {
                continue;
            }

            //Необходимо проверить изменились ли записи сравнить $relationRecords с $this->_oldRelations
            $oldRelationRecords = (array) ArrayHelper::getValue($this->_oldRelations, $relationName);

            $activeQuery = $model->getRelation($relationName);
            if (!empty($activeQuery->via)) { // works only for many-to-many relation
                /* @var $viaQuery ActiveQuery */
                if ($activeQuery->via instanceof ActiveQuery) {
                    $viaQuery = $activeQuery->via;
                } elseif (is_array($activeQuery->via)) {
                    $viaQuery = $activeQuery->via[1];
                } else {
                    throw new ErrorException(\Yii::t('skeeks/cms', 'Unknown via type'));
                }
                $junctionTable = reset($viaQuery->from);
                $primaryModelColumn = array_keys($viaQuery->link)[0];
                $relatedModelColumn = reset($activeQuery->link);
                $junctionRows = [];

                $relationPks = (array) ArrayHelper::getColumn($relationRecords, array_keys($activeQuery->link)[0], false);
                $oldRelationPks = (array) ArrayHelper::getColumn($oldRelationRecords, array_keys($activeQuery->link)[0], false);

                asort($relationPks);
                asort($oldRelationPks);

                $isChange = false;
                if (array_diff($relationPks, $oldRelationPks) || array_diff($oldRelationPks, $relationPks)) {
                    $isChange = true;
                }
                //Проверить а вообще что либо изменилось ли?
                /*if ($model instanceof CmsCompany) {
                    print_r("-----------");
                    print_r($oldRelationPks);
                    print_r($relationPks);
                    var_dump($isChange);
                }*/

                if ($isChange === false) {
                    continue;
                }


                $passedRecords = count($relationPks);
                $relationPks = array_filter($relationPks);
                $savedRecords = count($relationPks);

                if ($passedRecords != $savedRecords) {
                    throw new ErrorException(\Yii::t('skeeks/cms', 'All relation records must be saved'));
                }

                $this->_changedRelations[$relationName] = $relationPks;

                foreach ($relationPks as $relationPk) {
                    $junctionRows[] = [$model->primaryKey, $relationPk];
                }
                $model->getDb()->transaction(function() use (
                    $junctionTable,
                    $primaryModelColumn,
                    $relatedModelColumn,
                    $junctionRows,
                    $model
                ) {
                    $db = $model->getDb();
                    $db->createCommand()->delete($junctionTable,
                        [$primaryModelColumn => $model->primaryKey])->execute();
                    if (!empty($junctionRows)) {
                        $db->createCommand()->batchInsert($junctionTable, [$primaryModelColumn, $relatedModelColumn],
                            $junctionRows)->execute();
                    }
                });
            }
        }

    }
}