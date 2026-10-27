<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\forms;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsUser;
use yii\base\Model;

/**
 * Optional values for bulk task editing.
 *
 * An empty attribute means that the corresponding task value must be kept.
 */
class CmsTaskBulkEditForm extends Model
{
    public $executor_id;
    public $plan_duration;
    public $fact_duration;
    public $cms_company_id;
    public $cms_user_id;
    public $cms_project_id;
    /** @var int|null the deal (service) of the tasks; empty keeps each task's deal */
    public $cms_deal_id;

    public function rules()
    {
        return [
            [
                [
                    'executor_id',
                    'plan_duration',
                    'fact_duration',
                    'cms_company_id',
                    'cms_user_id',
                    'cms_project_id',
                    'cms_deal_id',
                ],
                'filter',
                'filter' => function ($value) {
                    return $value === '' ? null : $value;
                },
            ],
            [['executor_id', 'cms_company_id', 'cms_user_id', 'cms_project_id', 'cms_deal_id'], 'integer', 'min' => 1],
            [['plan_duration', 'fact_duration'], 'integer', 'min' => 0],
            [['executor_id'], 'validateExecutor'],
            [['cms_company_id'], 'validateCompany'],
            [['cms_user_id'], 'validateClient'],
            [['cms_project_id'], 'validateProject'],
            [['cms_deal_id'], 'validateDeal'],
            [['cms_company_id'], 'validateRelation', 'skipOnEmpty' => false],
            [['executor_id'], 'validateChanges', 'skipOnEmpty' => false],
        ];
    }

    public function attributeLabels()
    {
        return [
            'executor_id' => 'Исполнитель',
            'plan_duration' => 'Время на выполнение задачи',
            'fact_duration' => 'Длительность для отчета',
            'cms_company_id' => 'Компания',
            'cms_user_id' => 'Клиент',
            'cms_project_id' => 'Проект',
            'cms_deal_id' => 'Услуга',
        ];
    }

    /**
     * The deal must be visible to the employee and belong to the chosen company,
     * client or project company. Tasks then follow the deal's company (see the
     * controller), as the single task form does.
     */
    public function validateDeal($attribute)
    {
        if (!$this->hasValue($this->{$attribute}) || $this->hasErrors($attribute)) {
            return;
        }

        $deal = CmsDeal::find()->forManager()->andWhere([CmsDeal::tableName().'.id' => (int)$this->{$attribute}])->one();
        if (!$deal) {
            $this->addError($attribute, 'Услуга недоступна.');
            return;
        }

        if ($this->hasValue($this->cms_company_id)) {
            if ((int)$deal->cms_company_id !== (int)$this->cms_company_id) {
                $this->addError($attribute, 'Услуга должна относиться к выбранной компании.');
            }
        } elseif ($this->hasValue($this->cms_user_id)) {
            if ($deal->cms_company_id || (int)$deal->cms_user_id !== (int)$this->cms_user_id) {
                $this->addError($attribute, 'Услуга должна быть оформлена на выбранного клиента.');
            }
        } elseif ($this->hasValue($this->cms_project_id)) {
            $project = CmsProject::findOne((int)$this->cms_project_id);
            if ($project && $project->cms_company_id && (int)$project->cms_company_id !== (int)$deal->cms_company_id) {
                $this->addError($attribute, 'Услуга должна относиться к компании проекта.');
            }
        }
    }

    public function hasRelationChange()
    {
        return $this->hasValue($this->cms_company_id)
            || $this->hasValue($this->cms_user_id)
            || $this->hasValue($this->cms_project_id);
    }

    public function validateExecutor($attribute)
    {
        if (!$this->hasValue($this->{$attribute}) || $this->hasErrors($attribute)) {
            return;
        }

        if (!CmsUser::find()->isWorker()->andWhere(['id' => (int)$this->{$attribute}])->exists()) {
            $this->addError($attribute, 'Исполнитель недоступен.');
        }
    }

    public function validateCompany($attribute)
    {
        if (!$this->hasValue($this->{$attribute}) || $this->hasErrors($attribute)) {
            return;
        }

        if (!CmsCompany::find()->forManager()->andWhere(['id' => (int)$this->{$attribute}])->exists()) {
            $this->addError($attribute, 'Компания недоступна.');
        }
    }

    public function validateClient($attribute)
    {
        if (!$this->hasValue($this->{$attribute}) || $this->hasErrors($attribute)) {
            return;
        }

        if (!CmsUser::find()->forManager()->andWhere(['id' => (int)$this->{$attribute}])->exists()) {
            $this->addError($attribute, 'Клиент недоступен.');
        }
    }

    public function validateProject($attribute)
    {
        if (!$this->hasValue($this->{$attribute}) || $this->hasErrors($attribute)) {
            return;
        }

        $query = CmsProject::find()
            ->forManager()
            ->andWhere(['id' => (int)$this->{$attribute}]);

        if ($this->hasValue($this->cms_company_id)) {
            $query->andWhere(['cms_company_id' => (int)$this->cms_company_id]);
        }

        if (!$query->exists()) {
            $message = $this->hasValue($this->cms_company_id)
                ? 'Проект должен относиться к выбранной компании.'
                : 'Проект недоступен.';
            $this->addError($attribute, $message);
        }
    }

    public function validateRelation($attribute)
    {
        if ($this->hasValue($this->cms_user_id)
            && ($this->hasValue($this->cms_company_id) || $this->hasValue($this->cms_project_id))) {
            $this->addError($attribute, 'Выберите один тип связи: компанию, клиента или проект.');
        }
    }

    public function validateChanges($attribute)
    {
        foreach ([
            'executor_id',
            'plan_duration',
            'fact_duration',
            'cms_company_id',
            'cms_user_id',
            'cms_project_id',
            'cms_deal_id',
        ] as $changeAttribute) {
            if ($this->hasValue($this->{$changeAttribute})) {
                return;
            }
        }

        $this->addError($attribute, 'Укажите хотя бы одно значение для редактирования.');
    }

    private function hasValue($value)
    {
        return $value !== null && $value !== '';
    }
}
