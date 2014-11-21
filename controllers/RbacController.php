<?php
/**
 * Найти все модули, у них попросить все возможные привилегии
 *
 * TODO: добавить возможность генерации
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\App;
use skeeks\cms\base\console\Controller;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use Yii;
use yii\helpers\Console;

class RbacController extends Controller
{
    public function actionInit()
    {
        $this->startTool();
        $this->stdout("Cms Rbac init\n");
        $this
            ->_initBaseData()
            ->_initAdminData()
            ->_initRootUser()
        ;
    }

    /**
     * Автоматическая генерация
     * @return $this
     */
    protected function _initRootUser()
    {
        $this->stdout("Init root user \n", Console::BOLD);

        $root = User::findByUsername('root');
        $aManager = \Yii::$app->authManager;
        if ($root && $aManager->getRole('root'))
        {
            $aManager->assign($aManager->getRole('root'), $root->primaryKey);
        }

        return $this;
    }

    /**
     * Автоматическая генерация
     * @return $this
     */
    protected function _initAdminData()
    {
        $this->stdout("Init admin data\n", Console::BOLD);
        $auth = Yii::$app->authManager;

        foreach (\Yii::$app->adminMenu->groups as $group)
        {
            foreach ($group['items'] as $itemData)
            {
                /**
                 * @var $controller \yii\web\Controller
                 */
                list($controller, $route) = \Yii::$app->createController($itemData['url'][0]);

                if ($controller)
                {
                    if ($controller instanceof AdminController)
                    {
                        $permissionCode = App::moduleAdmin()->getPermissionCode($controller->getUniqueId());

                        //Привилегия доступу к админке
                        if (!$adminAccess = $auth->getPermission($permissionCode))
                        {
                            $adminAccess = $auth->createPermission($permissionCode);
                            $adminAccess->description = 'Админка | ' . $controller->getLabel();
                            $auth->add($adminAccess);

                            if ($root = $auth->getRole('root'))
                            {
                                $auth->addChild($root, $adminAccess);
                            }
                        }


                        if ($actionManager = $controller->getBehavior('actionManager'))
                        {
                            if ($actions = $actionManager->actions)
                            {
                                foreach ($actions as $actionCode => $actionData)
                                {
                                    $permissionCode = App::moduleAdmin()->getPermissionCode($controller->getUniqueId() . '/' . $actionCode);

                                    //Привилегия доступу к админке
                                    if (!$adminAccess = $auth->getPermission($permissionCode))
                                    {
                                        $adminAccess = $auth->createPermission($permissionCode);
                                        $adminAccess->description = 'Админка | ' . $controller->getLabel() . ' | ' . $actionData['label'];
                                        $auth->add($adminAccess);

                                        if ($root = $auth->getRole('root'))
                                        {
                                            $auth->addChild($root, $adminAccess);
                                        }
                                    }

                                    $permissionCode = App::moduleAdmin()->getPermissionCodeOwn($permissionCode);
                                    if (!$adminAccessOwn = $auth->getPermission($permissionCode))
                                    {
                                        $rule = new AuthorRule();

                                        $adminAccessOwn = $auth->createPermission($permissionCode);
                                        $adminAccessOwn->description = 'Админка | ' . $controller->getLabel() . ' | ' . $actionData['label'] . ' (только свои записи)';
                                        $adminAccessOwn->ruleName = $rule->name;
                                        $auth->add($adminAccessOwn);
                                        $auth->addChild($adminAccessOwn, $adminAccess);

                                        if ($root = $auth->getRole('root'))
                                        {
                                            $auth->addChild($root, $adminAccessOwn);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    protected function _initBaseData()
    {
        $this->stdout("Init base data\n", Console::BOLD);

        $auth = Yii::$app->authManager;

        // add the rule
        $ruleAuthor = new AuthorRule();
        if (!$ruleAuthor = $auth->getRule($ruleAuthor->name))
        {
            $ruleAuthor = new AuthorRule();
            $auth->add($ruleAuthor);
        }
        $ruleAuthor = $auth->getRule($ruleAuthor->name);

        //Привилегия доступу к админке
        if (!$adminAccess = $auth->getPermission('cms.admin-access'))
        {
            $adminAccess = $auth->createPermission('cms.admin-access');
            $adminAccess->description = 'Доступ к админке';
            $auth->add($adminAccess);
        }

        if (!$user = $auth->getRole('user'))
        {
            //Пользователь без доступа к админке
            $user = $auth->createRole('user');
            $user->description = 'Пользователь';
            $auth->add($user);
        }

        if (!$manager = $auth->getRole('manager'))
        {
            //Менеджер который может управлять только своими данными
            $manager = $auth->createRole('manager');
            $manager->description = 'Менеджер (только свои записи)';
            $auth->add($manager);
            $auth->addChild($manager, $adminAccess);
        }

        if (!$managerAdvansed = $auth->getRole('managerAdvansed'))
        {
            //Менеджер который может управлять всеми данными
            $managerAdvansed = $auth->createRole('managerAdvansed');
            $managerAdvansed->description = 'Менеджер расширенный (все записи)';
            $auth->add($managerAdvansed);
            $auth->addChild($managerAdvansed, $manager);
            $auth->addChild($managerAdvansed, $adminAccess);
        }

        if (!$admin = $auth->getRole('admin'))
        {
            //Админ обладает расширенными возможностями
            $admin = $auth->createRole('admin');
            $admin->description = 'Администратор';
            $auth->add($admin);
            $auth->addChild($admin, $user);
            $auth->addChild($admin, $manager);
            $auth->addChild($admin, $managerAdvansed);
            $auth->addChild($admin, $adminAccess);
        }

        if (!$root = $auth->getRole('root'))
        {
            //Админ обладает расширенными возможностями
            $root = $auth->createRole('root');
            $root->description = 'Суперпользователь';
            $auth->add($root);
            $auth->addChild($root, $user);
            $auth->addChild($root, $manager);
            $auth->addChild($root, $admin);
            $auth->addChild($root, $managerAdvansed);
            $auth->addChild($root, $adminAccess);
        }

        return $this;
    }
}