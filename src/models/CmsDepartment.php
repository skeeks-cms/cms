<?php

namespace skeeks\cms\models;

use paulzi\adjacencyList\AdjacencyListBehavior;
use paulzi\autotree\AutoTreeTrait;
use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\RelationalBehavior;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "cms_contractor".
 *
 * @property int             $id
 * @property int|null        $created_by
 * @property int|null        $created_at
 * @property string          $name Название
 * @property int|null        $worker_id Руководитель отдела
 * @property int             $sort
 *
 * @property CmsUser         $supervisor Руководитель отдела
 * @property CmsUser[]       $workers Сотрудники отдела
 *
 * @property string          $fullName
 *
 * @property CmsDepartment   $parent
 * @property CmsDepartment[] $parents
 * @property CmsDepartment[] $children
 * @property CmsDepartment[] $activeChildren
 * @property CmsDepartment   $root
 * @property CmsDepartment   $prev
 * @property CmsDepartment   $next
 * @property CmsDepartment[] $descendants
 *
 */
class CmsDepartment extends ActiveRecord
{
    use AutoTreeTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_department';
    }

    /*public function init()
    {
        $this->on(self::EVENT_BEFORE_INSERT, [$this, '_beforeInsert']);
    }*/

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        return ArrayHelper::merge(parent::behaviors(), [

            [
                'class' => RelationalBehavior::class,
            ],

            [
                'class'           => AdjacencyListBehavior::class,
                'parentAttribute' => 'pid',
                'sortable'        => [
                    'sortAttribute' => 'sort',
                ],
            ],

        ]);
    }

    /*public function _beforeInsert()
    {
        if ($this->pid) {
            $this->appendTo($this->parent);
        }
    }*/


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'created_at'], 'integer'],


            [['name'], 'string', 'max' => 255],
            [['name'], 'required'],

            [['worker_id'], 'integer'],

            [['sort'], 'integer'],
            [['sort'], 'default', 'value' => 100],

            [['pid'], 'integer'],

            [['workers'], 'safe'],

        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name'      => 'Название',
            'worker_id' => 'Руководитель отдела',
            'pid'       => 'Родительский отдел',
            'sort'      => 'Сортировка',
            'workers'   => 'Сотрудники',
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupervisor()
    {
        return $this->hasOne(CmsUser::class, ['id' => 'worker_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsDepartment2workers()
    {
        return $this->hasMany(CmsDepartment2worker::class, ['cms_department_id' => 'id'])
            ->from(['cmsDepartment2workers' => CmsDepartment2worker::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkers()
    {
        return $this->hasMany(CmsUser::class, ['id' => 'worker_id'])
            ->via('cmsDepartment2workers');;
    }

    /**
     * @param string $glue
     *
     * @return string
     */
    public function getFullName($glue = " / ")
    {
        $paths = [];

        if ($this->parents) {
            foreach ($this->parents as $parent) {
                $paths[] = $parent->name;
            }
        }

        $paths[] = $this->name;

        return implode($glue, $paths);
    }
}