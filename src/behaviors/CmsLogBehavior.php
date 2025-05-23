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

use skeeks\cms\models\CmsLog;
use yii\base\Behavior;
use yii\base\Exception;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property array $noLogFields
 *
 * Class RelationalBehavior
 * @package skeeks\cms\behaviors
 */
class CmsLogBehavior extends Behavior
{
    public $parent_relation = null;
    /**
     * @var array
     */
    public $no_log_fields = [];
    /**
     * @var array
     */
    public $default_no_log_fields = [
        'id',
        'updated_at',
        'created_at',
        'updated_by',
        'created_by',
        'cms_site_id',
    ];

    /**
     * @var array
     */
    public $relation_map = [];

    /**
     * @return array
     */
    public function events()
    {
        return [
            /*BaseActiveRecord::EVENT_BEFORE_UPDATE => "_beforeUpdate",*/

            BaseActiveRecord::EVENT_AFTER_UPDATE => "_afterUpdate",
            BaseActiveRecord::EVENT_AFTER_INSERT  => "_afterInsert",
            BaseActiveRecord::EVENT_AFTER_DELETE  => "_afterDelete",
        ];
    }

    private $_start_data = [];

    public function _afterDelete($e)
    {
        $log = new CmsLog();

        if ($this->parent_relation) {

            $log->sub_model_code = $this->owner->skeeksModelCode;
            $log->sub_model_id = $this->owner->id;
            $log->model_as_text = $this->owner->asText;

            $parent = $this->owner->{$this->parent_relation};

            $log->model_code = $parent->skeeksModelCode;
            $log->model_id = $parent->id;
            $log->model_as_text = $parent->asText;

            $log->log_type = CmsLog::LOG_TYPE_UPDATE;
            $log->sub_model_log_type = CmsLog::LOG_TYPE_DELETE;

            $result = [];

            $log->data = $result;
        } else {

            $log->model_code = $this->owner->skeeksModelCode;
            $log->model_id = $this->owner->id;
            $log->model_as_text = $this->owner->asText;

            $log->log_type = CmsLog::LOG_TYPE_DELETE;
        }

        if (!$log->save()) {
            throw new Exception("Не удалось сохранить лог: ".print_r($log->errors, true));
        }
    }

    /**
     * @return array
     */
    public function getNoLogFields()
    {
        return ArrayHelper::merge((array) $this->default_no_log_fields, (array) $this->no_log_fields);
    }

    public function _afterInsert($e)
    {
        $log = new CmsLog();

        if ($this->parent_relation) {

            $log->sub_model_code = $this->owner->skeeksModelCode;
            $log->sub_model_id = $this->owner->id;
            $log->sub_model_as_text = $this->owner->asText;

            $parent = $this->owner->{$this->parent_relation};

            $log->model_code = $parent->skeeksModelCode;
            $log->model_id = $parent->id;
            $log->model_as_text = $parent->asText;

            $log->log_type = CmsLog::LOG_TYPE_UPDATE;
            $log->sub_model_log_type = CmsLog::LOG_TYPE_INSERT;


            $result = [];

            $result = $this->owner->getDataForLog();

            $log->data = $result;
        } else {

            $log->model_code = $this->owner->skeeksModelCode;
            $log->model_id = $this->owner->id;
            $log->model_as_text = $this->owner->asText;

            $log->log_type = CmsLog::LOG_TYPE_INSERT;

            $result = $this->owner->getDataForLog();

            $log->data = $result;
        }

        if (!$log->save()) {
            throw new Exception("Не удалось сохранить лог: ".print_r($log->errors, true));
        }
    }

    public function getOwnerRelationBehavior()
    {
        $behaviors = $this->owner->getBehaviors();
        if ($behaviors)
        {
            foreach ($behaviors as $key => $behavior)
            {
                if ($behavior instanceof RelationalBehavior)
                {
                    return $behavior;
                }
            }
        }

        return null;
    }

    /**
     * Данные по атрибуетам для лога
     *
     * @param array $attributes
     * @return array
     */
    public function getDataForLog(array $attributes = [])
    {
        $data = $this->owner->toArray($attributes);

        if ($this->noLogFields) {
            foreach ($this->noLogFields as $key) {
                ArrayHelper::remove($data, $key);
            }
        }

        $result = [];

        foreach ($data as $key => $value)
        {
            $newValueText = $value;
            $relationCode = ArrayHelper::getValue((array) $this->relation_map, $key);

            if ($relationCode) {
                $newValueText = (string) $this->owner->{$relationCode};
            }

            $result[$key] = [
                'name' => $this->owner->getAttributeLabel($key),
                'value' => $value,
                'as_text' => $newValueText,
            ];
        }

        $model = $this->owner;

        if ($relationBehavor = $this->getOwnerRelationBehavior()) {

            if ($relationBehavor->relationNames) {

                $relatedRecords = $model->getRelatedRecords();

                foreach ($relationBehavor->relationNames as $relationName)
                {
                    //Если нужны не все а только определенные атрибуты
                    if ($attributes) {
                        //Если relation не в аттрибутах то не трогать ее
                        if (!in_array($relationName, $attributes)) {
                            continue;
                        }
                    }

                    $valueData = (array) ArrayHelper::getValue($relatedRecords, $relationName);

                    $valueDataFormated = (array) ArrayHelper::map($valueData, "id", function($object) {
                        return (string) $object;
                    });

                    $result[$relationName] = [
                        'name' => $this->owner->getAttributeLabel($relationName),
                        'value' => implode(", ", array_keys($valueDataFormated)),
                        'as_text' => implode(", ", $valueDataFormated),
                    ];
                }
            }

        }

        return $result;
    }


    public function _afterUpdate(AfterSaveEvent $e) {

        $attrs = (array) $e->changedAttributes;

        /**
         * @var $relationBehavior RelationalBehavior
         */
        $relationBehavior = $this->getOwnerRelationBehavior();
        $changedRelations = [];
        $relations = [];

        $model = $this->owner;
        $relatedRecords = [];

        if ($relationBehavior) {
            if ($changedRelations = $relationBehavior->getChangedRelations()) {

                $relatedRecords = $model->getRelatedRecords();
                $oldRelatedRecords = $relationBehavior->getOldRelations();

                $attrs = ArrayHelper::merge($attrs, $changedRelations);
            }
        }

        if ($attrs) {

            $result = [];

            if ($this->noLogFields) {
                foreach ($this->noLogFields as $key) {
                    ArrayHelper::remove($attrs, $key);
                }
            }

            if ($attrs) {
                foreach ($attrs as $key => $value)
                {
                    if ($changedRelations && in_array($key, array_keys($changedRelations))) {

                        $newValue = (array) ArrayHelper::getValue($relatedRecords, $key);
                        $oldValue = (array) ArrayHelper::getValue($oldRelatedRecords, $key);

                        $oldValueDataFormated = (array) ArrayHelper::map($oldValue, "id", function($object) {
                            return (string) $object;
                        });

                        $valueDataFormated = (array) ArrayHelper::map($newValue, "id", function($object) {
                            return (string) $object;
                        });

                        $result[$key] = [
                            'name' => $this->owner->getAttributeLabel($key),

                            'value' => implode(", ", array_keys($valueDataFormated)),
                            'as_text' => implode(", ", $valueDataFormated),

                            'old_value' => implode(", ", array_keys($oldValueDataFormated)),
                            'old_as_text' => implode(", ", $oldValueDataFormated),
                        ];

                    } else {
                        $newValue = $this->owner->getAttribute($key);

                        $newValueText = $newValue;
                        $relationCode = ArrayHelper::getValue((array) $this->relation_map, $key);
                        if ($relationCode) {
                            $newValueText = (string) $this->owner->{$relationCode};
                        }

                        if ($newValue != $value) {

                            $result[$key] = [

                                'value' => $newValue,
                                'as_text' => $newValueText,

                                'old_value' => $value,
                                'old_as_text' => $value,

                                'name' => $this->owner->getAttributeLabel($key),
                            ];
                        }
                    }

                }

                if ($result)
                {
                    $log = new CmsLog();

                    if ($this->parent_relation) {

                        $log->sub_model_code = $this->owner->skeeksModelCode;
                        $log->sub_model_id = $this->owner->id;
                        $log->sub_model_as_text = $this->owner->asText;

                        $parent = $this->owner->{$this->parent_relation};

                        $log->model_code = $parent->skeeksModelCode;
                        $log->model_id = $parent->id;
                        $log->model_as_text = $parent->asText;

                        $log->log_type = CmsLog::LOG_TYPE_UPDATE;
                        $log->sub_model_log_type = CmsLog::LOG_TYPE_UPDATE;

                    } else {
                        $log->model_code = $this->owner->skeeksModelCode;
                        $log->model_id = $this->owner->id;
                        $log->model_as_text = $this->owner->asText;

                        $log->log_type = CmsLog::LOG_TYPE_UPDATE;
                    }

                    $log->data = $result;


                    if (!$log->save()) {
                        throw new Exception("Не удалось сохранить лог: ".print_r($log->errors, true));
                    }
                }
            }

        }
    }
    /*public function _afterUpdate(AfterSaveEvent $e) {

        $attrs = (array) $e->changedAttributes;

        /**
         * @var $relationBehavior RelationalBehavior
        $relationBehavior = $this->getOwnerRelationBehavior();
        $changedRelations = [];
        $relations = [];

        $model = $this->owner;
        $relatedRecords = [];

        if ($relationBehavior) {
            if ($changedRelations = $relationBehavior->getChangedRelations()) {

                $relatedRecords = $model->getRelatedRecords();
                $oldRelatedRecords = $relationBehavior->getOldRelations();

                $attrs = ArrayHelper::merge($attrs, $changedRelations);
            }
        }

        if ($attrs) {

            $result = [];

            if ($this->no_log_fields) {
                foreach ($this->no_log_fields as $key) {
                    ArrayHelper::remove($attrs, $key);
                }
            }

            if ($attrs) {
                foreach ($attrs as $key => $value)
                {
                    if ($changedRelations && in_array($key, array_keys($changedRelations))) {

                        $newValue = (array) ArrayHelper::getValue($relatedRecords, $key);
                        $oldValue = (array) ArrayHelper::getValue($oldRelatedRecords, $key);


                        $result[$key] = [
                            'old' => implode(", ", ArrayHelper::map($oldValue, "id", function($object) {
                                return (string) $object;
                            })),
                            'new' => implode(", ", ArrayHelper::map($newValue, "id", function($object) {
                                return (string) $object;
                            })),
                            'name' => $this->owner->getAttributeLabel($key),
                        ];

                    } else {
                        $newValue = $this->owner->getAttribute($key);

                        $newValueText = $newValue;
                        $relationCode = ArrayHelper::getValue((array) $this->relation_map, $key);
                        if ($relationCode) {
                            $newValueText = (string) $this->owner->{$relationCode};
                        }

                        if ($newValue != $value) {

                            $result[$key] = [
                                'old' => $value,
                                'new' => $newValueText,
                                'name' => $this->owner->getAttributeLabel($key),
                            ];
                        }
                    }

                }

                if ($result)
                {
                    $log = new CmsLog();

                    if ($this->parent_relation) {

                        $log->sub_model_code = $this->owner->skeeksModelCode;
                        $log->sub_model_id = $this->owner->id;
                        $log->sub_model_as_text = $this->owner->asText;

                        $parent = $this->owner->{$this->parent_relation};

                        $log->model_code = $parent->skeeksModelCode;
                        $log->model_id = $parent->id;
                        $log->model_as_text = $parent->asText;

                        $log->log_type = CmsLog::LOG_TYPE_UPDATE;
                        $log->sub_model_log_type = CmsLog::LOG_TYPE_UPDATE;

                    } else {
                        $log->model_code = $this->owner->skeeksModelCode;
                        $log->model_id = $this->owner->id;
                        $log->model_as_text = $this->owner->asText;

                        $log->log_type = CmsLog::LOG_TYPE_UPDATE;
                    }

                    $log->data = $result;

                    if (!$log->save()) {
                        throw new Exception("Не удалось сохранить лог: ".print_r($log->errors, true));
                    }
                }
            }

        }
    }*/
}