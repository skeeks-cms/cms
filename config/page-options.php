<?php
/**
 * базовые глобальные опции
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
return [

    'meta_title'    =>
    [
        'name'          => 'Мета title',
        'description'   => 'Правильный title важен для seo.'
    ],

    'meta_keywords' =>
    [
        'name'          => 'Мета keywords',
        'description'   => 'Правильный keywords важен для seo.'
    ],

    'meta_description' =>
    [
        'name'          => 'Мета description',
        'description'   => 'Правильный description важен для seo.'
    ],

    'layout' =>
    [
        'name'              => 'Глобальный Шаблон Layout',
        'description'       => 'Глобальный шаблон, влияет на общее оформление страницы',
        'modelValueClass'   => '\skeeks\cms\pageOptionValues\layout\LayoutPageOptionValue'
    ],

    'action_view' =>
    [
        'name'              => 'Шаблон для отрисовки сущьности',
        'description'       => 'Шаблон отрисовки конкретной сущьности',
        'modelValueClass'   => '\skeeks\cms\pageOptionValues\actionView\ActionViewPageOptionValue'
    ],

    'infoblocks' =>
    [
        'name'              => 'Инфоблоки',
        'description'       => 'Инфоблоки, которые будут показываться на этой странице',
        'modelValueClass'   => '\skeeks\cms\pageOptionValues\infoblocks\Infoblocks'
    ],
];