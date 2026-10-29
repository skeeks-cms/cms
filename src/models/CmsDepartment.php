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

    /** @var array|null the tree of one web request (tree()) */
    private static ?array $tree = null;

    /**
     * The departments and their employees, read once per web request (audit F10:
     * every rights check of a feed read them again): rows (id, pid, worker_id),
     * workers by department, departments by worker. Saving or deleting a
     * department or its employee drops it (resetTree()); the console and workers
     * read it every time, as their data may change under them.
     *
     * @return array{rows: array[], workers: int[][], departments: int[][]}
     */
    public static function tree(): array
    {
        if (self::$tree !== null && \skeeks\cms\crm\CrmAccess::keepsAnswers()) {
            return self::$tree;
        }
        $rows = (new \yii\db\Query())
            ->select(['id', 'pid', 'worker_id'])
            ->from(static::tableName())
            ->all(static::getDb());
        $workers = [];
        $departments = [];
        foreach ((new \yii\db\Query())->select(['cms_department_id', 'worker_id'])->from(CmsDepartment2worker::tableName())->all(static::getDb()) as $link) {
            $workers[(int)$link['cms_department_id']][] = (int)$link['worker_id'];
            $departments[(int)$link['worker_id']][] = (int)$link['cms_department_id'];
        }

        return self::$tree = ['rows' => $rows, 'workers' => $workers, 'departments' => $departments];
    }

    /** The tree changed (a department, its head or its employees): the next check reads it again. */
    public static function resetTree(): void
    {
        self::$tree = null;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        static::resetTree();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        static::resetTree();
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

        $tree = static::tree();
        $rows = $tree['rows'];

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

        foreach ($departmentIds as $departmentId) {
            foreach ($tree['workers'][$departmentId] ?? [] as $workerId) {
                $result[$workerId] = $workerId;
            }
        }

        unset($result[$leaderId]);
        sort($result);

        return $result;
    }

    /**
     * Руководители сотрудника: обратное правило к subordinateIds() на том же дереве.
     * L — руководитель X, если X есть в subordinateIds(L): руководители отделов, в которых
     * X работает, и всех их родительских отделов, а для руководителя отдела — руководители
     * родительских отделов. Сам сотрудник в список не входит.
     *
     * @param int $workerId
     * @return int[]
     */
    public static function leaderIdsOf(int $workerId): array
    {
        if ($workerId <= 0) {
            return [];
        }

        $tree = static::tree();
        $rows = $tree['rows'];
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int)$row['id']] = $row;
        }

        $start = $tree['departments'][$workerId] ?? [];
        foreach ($rows as $row) {
            // A head is a subordinate of the heads above his department, not of himself.
            if ((int)$row['worker_id'] === $workerId && $row['pid']) {
                $start[] = (int)$row['pid'];
            }
        }

        $result = [];
        $seen = [];
        foreach ($start as $departmentId) {
            while ($departmentId && isset($byId[$departmentId]) && !isset($seen[$departmentId])) {
                $seen[$departmentId] = true;
                $leaderId = (int)$byId[$departmentId]['worker_id'];
                if ($leaderId) {
                    $result[$leaderId] = $leaderId;
                }
                $departmentId = (int)$byId[$departmentId]['pid'];
            }
        }

        unset($result[$workerId]);
        sort($result);

        return array_values($result);
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