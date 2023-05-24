<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 11.03.2017
 */

namespace skeeks\cms\components;

use skeeks\cms\backend\BackendComponent;
use yii\base\Theme;

/**
 * Class UpaBackendComponent
 * @package skeeks\cms\upa
 */
class UpaBackendComponent extends BackendComponent
{
    /**
     * @var string
     */
    public $controllerPrefix = "upa";

    /**
     * @var array
     */
    public $urlRule = [
        'urlPrefix' => '~upa',
    ];

    /*protected $_menu = [
        'data' => [
            'personal' =>
            [
                'name' => 'Настройки',

                'items' => [
                    [
                        'url'   => ['/personal-info/index'],
                        'name'   => 'Личные настройки',
                    ],
                ],
            ],
        ]
    ];*/
}