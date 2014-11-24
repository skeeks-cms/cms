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

];