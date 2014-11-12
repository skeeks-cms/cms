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
        'label'         => 'Текст',
        'description'   => 'Виджет просто выводит текст',
        'templates'     =>
        [
            'default' =>
            [
                'label' => 'Шаблон по умолчанию'
            ]
        ],
        'enabled'       => true
    ],

    'skeeks\cms\widgets\infoblocks\Infoblocks' =>
    [
        'label'         => 'Список инфоблоков',
        'description'   => 'Виджет который содержит в себе другие инфоблоки',
        'templates'     =>
        [
            'default' =>
            [
                'label' => 'Шаблон по умолчанию'
            ]
        ],
        'enabled'       => true
    ]
];