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
     * Подчиненные руководителя: сотрудники отделов, которыми он руководит, и всех их подотделов,
     * а также руководители этих подотделов. Сам руководитель в список не входит.
     *
     * Единственное место этого правила: User::getSubordinates() и через него все forManager().
     *
     * @param int $leaderId
     * @return int[]
     */
    public static function subordinateIds(int $leaderId): array
    {
        if ($leaderId <= 0) {
            return [];
        }

        $rows = (new \yii\db\Query())
            ->select(['id', 'pid', 'worker_id'])
            ->from(static::tableName())
            ->all(static::getDb());

        $children = [];
        $queue = [];
        foreach ($rows as $row) {
            $children[(int)$row['pid']][] = $row;
            if ((int)$row['worker_id'] === $leaderId) {
                $queue[] = (int)$row['id'];
            }
        }

        $result = [];
        $departmentIds = [];
        while ($queue) {
            $departmentId = array_shift($queue);
            if (isset($departmentIds[$departmentId])) {
                continue;
            }
            $departmentIds[$departmentId] = $departmentId;

            foreach ($children[$departmentId] ?? [] as $child) {
                if ($child['worker_id']) {
                    $result[(int)$child['worker_id']] = (int)$child['worker_id'];
                }
                $queue[] = (int)$child['id'];
            }
        }

        if ($departmentIds) {
            $workerIds = (new \yii\db\Query())
                ->select(['worker_id'])
                ->from(CmsDepartment2worker::tableName())
                ->andWhere(['cms_department_id' => array_values($departmentIds)])
                ->column(static::getDb());
            foreach ($workerIds as $workerId) {
                $result[(int)$workerId] = (int)$workerId;
            }
        }

        unset($result[$leaderId]);
        sort($result);

        return $result;
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