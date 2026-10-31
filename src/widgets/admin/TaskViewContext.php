<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use yii\base\BaseObject;
use yii\db\ActiveQuery;

/**
 * What every task view («Задачи», «Сроки», «Календарь», «Гант») shows: whose tasks and what search.
 *
 * In the task section the viewer chooses among the employees they manage (GET `executor_id`, themself by
 * default; an administrator — anyone); in an employee's card the employee is fixed and there is no choice.
 * GET `q` searches the task name, number, project, company and client.
 */
class TaskViewContext extends BaseObject
{
    /** @var CmsUser whose tasks are shown */
    public $user;

    /** @var CmsUser[] employees the viewer may choose; empty — no choice (an employee's card) */
    public $users = [];

    /** @var string search */
    public $q = '';

    /** Relations every task row draws: loaded at once instead of a query per task. */
    public const ROW_RELATIONS = ['cmsProject.cmsImage', 'cmsCompany.cmsImage', 'cmsUser', 'schedules'];

    /**
     * The context of the task section: the chosen employee among those the viewer manages.
     */
    public static function fromRequest(): self
    {
        $request = \Yii::$app->request;
        $me = \Yii::$app->user->identity;
        $users = [(int)$me->id => $me];
        foreach (CmsUser::find()->managedBy()->andWhere([CmsUser::tableName().'.is_active' => 1])->all() as $user) {
            $users[(int)$user->id] = $user;
        }
        uasort($users, static function (CmsUser $a, CmsUser $b) use ($me) {
            if ((int)$a->id === (int)$me->id || (int)$b->id === (int)$me->id) {
                return (int)$a->id === (int)$me->id ? -1 : 1;
            }
            return strcmp((string)$a->shortDisplayName, (string)$b->shortDisplayName);
        });
        $executorId = (int)$request->get('executor_id', $me->id);

        return new static([
            'user'  => $users[$executorId] ?? $me,
            'users' => count($users) > 1 ? array_values($users) : [],
            'q'     => trim((string)$request->get('q', '')),
        ]);
    }

    /**
     * The context of an employee's card: the employee is fixed.
     */
    public static function forUser(CmsUser $user): self
    {
        return new static(['user' => $user, 'q' => trim((string)\Yii::$app->request->get('q', ''))]);
    }

    /** Narrows a task query by the search. */
    public function applySearch(ActiveQuery $query): ActiveQuery
    {
        if ($this->q === '') {
            return $query;
        }
        $q = $this->q;
        $table = CmsTask::tableName();
        $condition = ['or',
            ['like', $table.'.name', $q],
            ['cms_project_id' => CmsProject::find()->select('id')->andWhere(['like', 'name', $q])],
            ['cms_company_id' => CmsCompany::find()->select('id')->andWhere(['like', 'name', $q])],
            ['cms_user_id' => CmsUser::find()->select(CmsUser::tableName().'.id')->andWhere(['or',
                ['like', CmsUser::tableName().'.first_name', $q],
                ['like', CmsUser::tableName().'.last_name', $q],
            ])],
        ];
        if (ctype_digit($q)) {
            $condition[] = [$table.'.id' => (int)$q];
        }

        return $query->andWhere($condition);
    }
}
