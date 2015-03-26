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
    'skeeks\cms\widgets\text\Text' => [],
    'skeeks\cms\widgets\treeChildrens\TreeChildrens' => [],
    'skeeks\cms\widgets\publications\Publications' => [],
    'skeeks\cms\widgets\publicationsAll\PublicationsAll' => [],
    'skeeks\cms\widgets\treeFixed\TreeFixed' => [],
    'skeeks\cms\widgets\treeList\TreeList' => [],
    'skeeks\cms\widgets\breadcrumbs\Breadcrumbs' =>
    [
        'templates'     =>
        [
            'project' =>
            [
                'name'      => 'Проект',
                'baseDir'   => '@app/views/widgets/breadcrumbs'
            ]
        ],
    ],
/*
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
    ],*/
];