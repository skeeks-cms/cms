<?php
/**
 * widgets
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */

return
[
    'skeeks\cms\widgets\text\Text' =>
    [
        'name'          => 'Текст',
        'description'   => 'Виджет просто выводит текст',
        'templates'     =>
        [
            'default' =>
            [
                'name' => 'Шаблон по умолчанию'
            ]
        ],
    ],

    'skeeks\cms\widgets\treeChildrens\TreeChildrens' =>
    [
        'name'          => 'Дерево разделов (вывод дочерних разделов)',
        'description'   => 'Виджет выводит нужные одразделы',

        'templates'     =>
        [
            'default' =>
            [
                'name' => 'Шаблон по умолчанию'
            ]
        ],
    ],

    'skeeks\cms\widgets\publications\Publications' =>
    [
        'name'          => 'Виджет публикаций',
        'description'   => 'Виджет выводит нужные одразделы',

        'templates'     =>
        [
            'default' =>
            [
                'name' => 'Шаблон по умолчанию'
            ]
        ],
    ],


    'skeeks\cms\widgets\publicationsAll\PublicationsAll' => [],

    'skeeks\cms\widgets\treeFixed\TreeFixed' =>
    [
        'name'          => 'Виджет фиксированного меню',
        'description'   => 'Виджет фиксированного меню',

        'templates'     =>
        [
            'default' =>
            [
                'name' => 'Шаблон по умолчанию'
            ]
        ],
    ],

    'skeeks\cms\widgets\breadcrumbs\Breadcrumbs' =>
    [
        'name'          => 'Хлебные крошки',
        'description'   => 'Виджет фиксированного меню',

        'templates'     =>
        [
            'default' =>
            [
                'name' => 'Шаблон по умолчанию'
            ],

            'project' =>
            [
                'name'      => 'Проект',
                'baseDir'   => '@app/views/widgets/breadcrumbs'
            ]
        ],
    ],

    'skeeks\cms\widgets\base\hasModels\WidgetHasModels' =>
    [
        'name'          => 'Универсальный виджет списка моделей + постраничая навигация',
        'description'   => 'Универсальный виджет списка моделей + постраничая навигация',

        'templates'     =>
        [
            'default' =>
            [
                'name' => 'Шаблон по умолчанию'
            ],
        ],
    ],
];