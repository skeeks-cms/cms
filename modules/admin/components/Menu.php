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
use skeeks\cms\modules\admin\helpers\AdminMenuItem;
use skeeks\cms\modules\admin\helpers\MenuItem;
use yii\base\Component;
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
        \Yii::beginProfile('admin-menu');

        if ($this->isLoaded)
        {
            return (array) $this->groups;
        }


        $paths[] = \Yii::getAlias('@common/config/admin/menu.php');
        $paths[] = \Yii::getAlias('@app/config/admin/menu.php');

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

        foreach ($paths as $path)
        {
            if (file_exists($path))
            {
                $menuGroups = (array) include_once $path;
                $this->groups = ArrayHelper::merge($this->groups, $menuGroups);
            }
        }

        ArrayHelper::multisort($this->groups, 'priority');

        $this->isLoaded = true;

        \Yii::endProfile('admin-menu');

        return (array) $this->groups;
    }


    /**
     * @return AdminMenuItem[]
     */
    public function getItems()
    {
        $result = [];

        if ($data = $this->getData())
        {
            $result = AdminMenuItem::createItems($data);
            ArrayHelper::multisort($result, 'priority');
        }

        return $result;
    }
}
