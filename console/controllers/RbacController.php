<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.02.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\base\console\Controller;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use skeeks\cms\rbac\CmsManager;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Json;
use yii\rbac\Rule;

/**
 * Настройка прав доступа
 *
 *
 * Class RbacController
 * @package skeeks\cms\controllers
 */
class RbacController extends Controller
{
    /**
     * Загрузка конфига и применение правил
     */
    public function actionInit()
    {
        $this->initRbacModules();
        $this->initAdminData();
        $this->initRootAssigning();
        $this->initRootUser();
    }

    /**
     * Загрузить и посмотреть данные конфига
     */
    public function actionGetConfig()
    {
        $this->loadConfig();
    }

    public function initRootAssigning()
    {
        $roleRoot = \Yii::$app->authManager->getRole(CmsManager::ROLE_ROOT);

        foreach (\Yii::$app->authManager->getPermissions() as $permission)
        {
            $this->stdout(' assign root permisssion: ' . $permission->name);
            try
            {
                \Yii::$app->authManager->addChild($roleRoot, $permission);
                $this->stdoutN(' - success');
            } catch(\Exception $e)
            {
                $this->stdoutN(' - already exist');
            }
        };

        foreach (\Yii::$app->authManager->getRoles() as $role)
        {
            $this->stdout(' assign root role: ' . $role->name);
            try
            {
                \Yii::$app->authManager->addChild($roleRoot, $role);
                $this->stdoutN(' - success');
            } catch(\Exception $e)
            {
                $this->stdoutN(' - already exist');
            }
        };

    }


    /**
     * Получение rules, permissions adn data по всем расширениям и модулям
     */
    public function initRbacModules()
    {
        $this->stdoutN("Init rules, permissions adn data from all modules and extensions\n", Console::BOLD);

        $this->stdoutN(" 1) Loading config");

        if (!$config = $this->loadConfig())
        {
            $this->stdoutN("Start script: not found data for rbac migrations");
            die;
        }

        $this->stdoutN("2) Start migrations");
        $this->applyConfig($config);


        $this->stdoutN("3) Assigning roles, privileges, rules");
        $this->applyAssigningConfig($config);

    }


    /**
     * Применение одного правила по данным из конфига
     * @param $config
     * @return bool
     */
    protected function _applyRule($config)
    {
        if (!is_array($config))
        {
            return false;
        }

        if (!$calssName = ArrayHelper::getValue($config, 'class'))
        {
            return false;
        }

        if (!class_exists($calssName))
        {
            return false;
        }

        $rule = new $calssName;
        if (!$rule instanceof Rule)
        {
            return false;
        }

        if ($ruleExist = \Yii::$app->authManager->getRule($rule->name))
        {
            return $ruleExist;
        }

        if (\Yii::$app->authManager->add($rule))
        {
            return $rule;
        }

        return false;
    }


    /**
     * Применение одного правила по данным из конфига
     * @param $config
     * @return bool
     */
    protected function _assignRole($config)
    {
        if (!is_array($config))
        {
            return false;
        }

        if (!$name = ArrayHelper::getValue($config, 'name'))
        {
            return false;
        }

        if (!$child = ArrayHelper::getValue($config, 'child'))
        {
            return false;
        }

        if (!$role = \Yii::$app->authManager->getRole($name))
        {
            return false;
        }

        if ($childRoles = ArrayHelper::getValue($child, 'roles'))
        {
            if ($childRoles)
            {
                foreach ($childRoles as $name)
                {
                    $this->stdout(' assign child role: ' . $name);
                    if ($roleChild = \Yii::$app->authManager->getRole($name))
                    {
                        try
                        {
                            \Yii::$app->authManager->addChild($role, $roleChild);
                            $this->stdoutN(' - success');
                        } catch(\Exception $e)
                        {
                            $this->stdoutN(' - already exist');
                        }

                    }
                }
            }

        }
        if ($childPermissions = ArrayHelper::getValue($child, 'permissions'))
        {
            if ($childPermissions)
            {
                foreach ($childPermissions as $name)
                {
                    $this->stdout(' assign child permission: ' . $name);
                    if ($permissionChild = \Yii::$app->authManager->getPermission($name))
                    {
                        try
                        {
                            \Yii::$app->authManager->addChild($role, $permissionChild);
                            $this->stdoutN(' - success');
                        } catch(\Exception $e)
                        {
                            $this->stdoutN(' - already exist');
                        }

                    }
                }
            }
        }


        return $role;
    }

    /**
     * Применение одного правила по данным из конфига
     * @param $config
     * @return bool
     */
    protected function _assignPermission($config)
    {
        if (!is_array($config))
        {
            return false;
        }

        if (!$name = ArrayHelper::getValue($config, 'name'))
        {
            return false;
        }

        if (!$child = ArrayHelper::getValue($config, 'child'))
        {
            return false;
        }

        if (!$permission = \Yii::$app->authManager->getPermission($name))
        {
            return false;
        }

        if ($childRoles = ArrayHelper::getValue($child, 'roles'))
        {
            if ($childRoles)
            {
                foreach ($childRoles as $name)
                {

                    $this->stdout(' assign child role: ' . $name);
                    if ($roleChild = \Yii::$app->authManager->getRole($name))
                    {
                        try
                        {
                            \Yii::$app->authManager->addChild($permission, $roleChild);
                            $this->stdoutN(' - success');
                        } catch(\Exception $e)
                        {
                            $this->stdoutN(' - already exist');
                        }
                    }
                }
            }

        }
        if ($childPermissions = ArrayHelper::getValue($child, 'permissions'))
        {
            if ($childPermissions)
            {
                foreach ($childPermissions as $name)
                {
                    $this->stdout(' assign child permission: ' . $name);
                    if ($permissionChild = \Yii::$app->authManager->getPermission($name))
                    {
                        try
                        {
                            \Yii::$app->authManager->addChild($permission, $permissionChild);
                            $this->stdoutN(' - success');
                        } catch(\Exception $e)
                        {
                            $this->stdoutN(' - already exist');
                        }
                    }
                }
            }
        }


        return $permission;
    }


    /**
     * Применение одного правила по данным из конфига
     * @param $config
     * @return bool
     */
    protected function _applyRole($config)
    {
        if (!is_array($config))
        {
            return false;
        }

        if (!$name = ArrayHelper::getValue($config, 'name'))
        {
            return false;
        }

        $description = ArrayHelper::getValue($config, 'description');

        if ($role = \Yii::$app->authManager->getRole($name))
        {
            return $role;
        }

        //Менеджер который может управлять только своими данными
        $role = \Yii::$app->authManager->createRole($name);
        $role->description = $description;

        if (\Yii::$app->authManager->add($role))
        {
            return $role;
        }

        return false;
    }

    /**
     * Применение одного правила по данным из конфига
     * @param $config
     * @return bool
     */
    protected function _applyPermission($config)
    {
        if (!is_array($config))
        {
            return false;
        }

        if (!$name = ArrayHelper::getValue($config, 'name'))
        {
            return false;
        }

        $description = ArrayHelper::getValue($config, 'description');
        $ruleName = ArrayHelper::getValue($config, 'ruleName', '');

        if ($role = \Yii::$app->authManager->getPermission($name))
        {
            return $role;
        }

        //Менеджер который может управлять только своими данными
        $role = \Yii::$app->authManager->createPermission($name);

        if ($description)
        {
            $role->description  = $description;
        }

        if ($ruleName)
        {
            $role->ruleName     = $ruleName;
        }


        if (\Yii::$app->authManager->add($role))
        {
            return $role;
        }

        return false;
    }

    /**
     * @param array $config
     */
    public function applyConfig($config = [])
    {
        if ($rules = ArrayHelper::getValue($config, 'rules'))
        {
            $this->stdoutN("Init rules: " . count($rules));

            foreach ($rules as $data)
            {
                if ($rule = $this->_applyRule($data))
                {
                    $this->stdoutN(" - success: " . $rule->name);
                } else
                {
                    $this->stdoutN(" - error config rule: " . Json::encode($data));
                }
            }
        }


        if ($roles = ArrayHelper::getValue($config, 'roles'))
        {
            $this->stdoutN("Init roles: " . count($roles));

            foreach ($roles as $data)
            {
                if ($role = $this->_applyRole($data))
                {
                    $this->stdoutN(" - success: " . $role->name);
                } else
                {
                    $this->stdoutN(" - error config role: " . Json::encode($data));
                }
            }
        }

        if ($permissions = ArrayHelper::getValue($config, 'permissions'))
        {
            $this->stdoutN("Init permissions: " . count($permissions));

            foreach ($permissions as $data)
            {
                if ($permission = $this->_applyPermission($data))
                {
                    $this->stdoutN(" - success: " . $permission->name);
                } else
                {
                    $this->stdoutN(" - error config role: " . Json::encode($data));
                }
            }
        }



    }

    public function applyAssigningConfig($config)
    {
        if ($roles = ArrayHelper::getValue($config, 'roles'))
        {
            $this->stdoutN("Assining roles: " . count($roles));

            foreach ($roles as $data)
            {
                if ($role = $this->_assignRole($data))
                {
                    $this->stdoutN(" - success assigned: " . $role->name);
                }
            }
        }


        if ($permissions = ArrayHelper::getValue($config, 'permissions'))
        {
            $this->stdoutN("Assining permissions: " . count($roles));

            foreach ($permissions as $data)
            {
                if ($permission = $this->_assignPermission($data))
                {
                    $this->stdoutN(" - success assigned: " . $permission->name);
                }
            }
        }
    }


    /**
     * Сканирование всех расширений и модулей и получение правил для rbac миграций
     * @return array
     */
    public function loadConfig()
    {
        $this->stdoutN("Scan all extensions");

        $config = [];

        foreach (\Yii::$app->extensions as $code => $data)
        {
            if ($data['alias'])
            {
                foreach ($data['alias'] as $code => $path)
                {
                    $permisssionsFile = $path . '/config/permissions.php';

                    if (file_exists($permisssionsFile))
                    {
                        $this->stdout(" - " . $permisssionsFile);

                        $cfg = (array) include $permisssionsFile;
                        if ($cfg)
                        {
                            $config = ArrayHelper::merge($config, $cfg);
                            $this->stdout(" (rules: " . count(ArrayHelper::getValue($cfg, 'rules', [])) . ';');
                            $this->stdout(" roles: " . count(ArrayHelper::getValue($cfg, 'roles', [])) . ';');
                            $this->stdout(" permissions: " . count(ArrayHelper::getValue($cfg, 'permissions', [])) . ';)');
                            $this->stdoutN('');
                        } else
                        {
                            $this->stdoutN(" (is empty data)");
                        }
                    }
                }
            }
        }

        $this->stdout("All config is ready");

        $this->stdout(" (rules: " . count(ArrayHelper::getValue($config, 'rules', [])) . ';');
        $this->stdout(" roles: " . count(ArrayHelper::getValue($config, 'roles', [])) . ';');
        $this->stdout(" permissions: " . count(ArrayHelper::getValue($config, 'permissions', [])) . ';)');
        $this->stdoutN('');

        return $config;
    }





































     /**
     * Автоматическая генерация
     * @return $this
     */
    protected function initRootUser()
    {
        $this->stdout("Init root user \n", Console::BOLD);

        $root = User::findByUsername('root');
        $aManager = \Yii::$app->authManager;
        if ($root && $aManager->getRole(CmsManager::ROLE_ROOT))
        {
            if (!$aManager->getAssignment(CmsManager::ROLE_ROOT, $root->primaryKey))
            {
                $aManager->assign($aManager->getRole(CmsManager::ROLE_ROOT), $root->primaryKey);
            }
        }

        return $this;
    }


    public function initAdminData()
    {
        $auth = Yii::$app->authManager;

        foreach (\Yii::$app->adminMenu->getData() as $group)
        {
            if (is_array($itemData))
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
                                $permissionCode = \Yii::$app->cms->moduleAdmin()->getPermissionCode($controller->getUniqueId());

                                //Привилегия доступу к админке
                                if (!$adminAccess = $auth->getPermission($permissionCode))
                                {
                                    $adminAccess = $auth->createPermission($permissionCode);
                                    $adminAccess->description = 'Администрирование | ' . $controller->name;
                                    $auth->add($adminAccess);
                                }
                            }
                        }
                }

            }

        }

        return $this;
    }
}
