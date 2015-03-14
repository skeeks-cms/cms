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


    /**
        Получение только доступного меню
     * @return array
     */
    public function getAllowData()
    {
        $groups = [];

        foreach (\Yii::$app->extensions as $code => $data)
        {
            if ($data['alias'])
            {
                foreach ($data['alias'] as $code => $path)
                {
                    $adminMenuFile = $path . '/config/admin/menu.php';
                    if (file_exists($adminMenuFile))
                    {
                                                var_dump($adminMenuFile);

                        $menuGroups = (array) include_once $adminMenuFile;
                        $this->groups = ArrayHelper::merge($this->groups, $menuGroups);
                    }

                }
            }
        }

        foreach ($this->groups as $groupCode => $groupData)
        {
            if ($groupData['items'])
            {
                $items = [];
                foreach ($groupData['items'] as $itemCode => $itemData)
                {
                    $permissionCode = \Yii::$app->cms->moduleAdmin()->getPermissionCode($itemData['url'][0]);
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
