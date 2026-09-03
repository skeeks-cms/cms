<?php

namespace skeeks\cms\forms;

use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use yii\base\Model;

/**
 * Selects an existing user of the current site and marks it as an employee.
 */
class CmsWorkerAddForm extends Model
{
    public $user_id;

    /** @var CmsUser|null */
    protected $_user;

    public function rules()
    {
        return [
            ['user_id', 'required'],
            ['user_id', 'integer'],
            ['user_id', 'validateUser'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'user_id' => 'Пользователь',
        ];
    }

    public function validateUser($attribute)
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $this->_user = CmsUser::find()
            ->cmsSite()
            ->andWhere([CmsUser::tableName().'.id' => (int)$this->{$attribute}])
            ->one();

        if (!$this->_user) {
            $this->addError($attribute, 'Пользователь текущего сайта не найден.');
            return;
        }

        if ($this->_user->is_company) {
            $this->addError($attribute, 'Компания не может быть добавлена как сотрудник.');
            return;
        }

        if ($this->_user->is_worker) {
            $this->addError($attribute, 'Этот пользователь уже является сотрудником.');
        }
    }

    public function addWorker()
    {
        if (!$this->_user && !$this->validate()) {
            return false;
        }

        try {
            \Yii::$app->db->transaction(function () {
                $this->_user->is_worker = 1;
                if (!$this->_user->save(true, ['is_worker'])) {
                    throw new \RuntimeException('Не удалось добавить сотрудника.');
                }

                $authManager = \Yii::$app->authManager;
                $role = $authManager->getRole(CmsManager::ROLE_WORKER);
                if (!$role) {
                    throw new \RuntimeException('Роль сотрудника не настроена.');
                }

                if (!$authManager->getAssignment($role->name, $this->_user->id)) {
                    $authManager->assign($role, $this->_user->id);
                }
            });
        } catch (\Throwable $e) {
            \Yii::error($e, self::class);
            $this->addError('user_id', $e->getMessage());
            return false;
        }

        return true;
    }
}
