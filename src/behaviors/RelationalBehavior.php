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

    public function __set($name, $value)
    {
        if ($value == '') {
            $this->owner->populateRelation($name, []);
            return;
        }

        if (is_array($value) && count($value) > 0 && !($value[0] instanceof BaseObject) ||
            !is_array($value) && !($value instanceof BaseObject)
        ) {
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
                $relationPks = ArrayHelper::getColumn($relationRecords, array_keys($activeQuery->link)[0], false);
                $passedRecords = count($relationPks);
                $relationPks = array_filter($relationPks);
                $savedRecords = count($relationPks);
                if ($passedRecords != $savedRecords) {
                    throw new ErrorException(\Yii::t('skeeks/cms', 'All relation records must be saved'));
                }
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