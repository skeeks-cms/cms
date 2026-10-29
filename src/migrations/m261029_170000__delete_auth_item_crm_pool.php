<?php

use yii\db\Migration;

/**
 * There is no general pool (owner's decision 29.10.2026, п. 13а): the RBAC
 * permission «Общий пул клиентов» (`cms/crm-pool`) goes with its assignments
 * and its place in roles, where `rbac/init` registered it. Nothing else changes.
 */
class m261029_170000__delete_auth_item_crm_pool extends Migration
{
    public function safeUp()
    {
        $auth = \Yii::$app->authManager;
        if ($auth && ($permission = $auth->getPermission('cms/crm-pool'))) {
            $auth->remove($permission);
        }
    }

    public function safeDown()
    {
        // The pool is not brought back: a CRM client always has a main responsible.
    }
}
