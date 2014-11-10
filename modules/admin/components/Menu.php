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
use skeeks\cms\base\Component;

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
}
