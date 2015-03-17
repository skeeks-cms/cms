<?php
/**
 * Строит меню в админке
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 10.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\components;
use skeeks\cms\App;
use skeeks\cms\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class Menu
 * @package skeeks\cms\modules\admin\components
 */
class Menu
    extends Component
{
    public $groups =
    [
        /*
         * Example
        'admin' =>
        [
            'label'     => 'Для разработчика',
            'priority'  => 0,

            'items' =>
            [
                [
                    "label"     => "Генератор кода",
                    "url"       => ["admin/gii"],
                ],

                [
                    "label"     => "Удаление и чистка",
                    "url"       => ["admin/clear"],
                ],
                [
                    "label"     => "Работа с базой данных",
                    "url"       => ["admin/db"],
                ],
            ]
        ]*/
    ];

    public $isLoaded = false;

    public function getData()
    {
        if ($this->isLoaded)
        {
            return (array) $this->groups;
        }

        foreach (\Yii::$app->extensions as $code => $data)
        {
            if ($data['alias'])
            {
                foreach ($data['alias'] as $code => $path)
                {
                    $adminMenuFile = $path . '/config/admin/menu.php';
                    if (file_exists($adminMenuFile))
                    {
                        $menuGroups = (array) include_once $adminMenuFile;
                        $this->groups = ArrayHelper::merge($this->groups, $menuGroups);
                    }
                }
            }
        }

        $this->isLoaded = true;
        return (array) $this->groups;
    }

    /**
        Получение только доступного меню
     * @return array
     */
    public function getAllowData()
    {
        foreach ($this->getData() as $groupCode => $groupData)
        {
            if ($groupData['items'])
            {
                $items = [];
                foreach ($groupData['items'] as $itemCode => $itemData)
                {
                    /**
                     * @var $controller \yii\web\Controller
                     */
                    list($controller, $route) = \Yii::$app->createController($itemData['url'][0]);

                    if (!$controller)
                    {
                        continue;
                    }

                    $permissionCode = \Yii::$app->cms->moduleAdmin()->getPermissionCode($controller->getUniqueId());
                    if ($permission = \Yii::$app->authManager->getPermission($permissionCode))
                    {
                        if (\Yii::$app->user->can($permission->name))
                        {
                            $items[$itemCode] = $itemData;
                        }
                    } else
                    {
                        $items[$itemCode] = $itemData;
                    }
                }

                if ($items)
                {
                    $groupData['items'] = $items;
                    $groups[$groupCode] = $groupData;
                }
            }
        }

        return $groups;
    }
}
