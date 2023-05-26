<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 11.03.2017
 */

namespace skeeks\cms\components;

use skeeks\cms\backend\BackendComponent;
use yii\helpers\Url;
use yii\web\Application;

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

    public function _run()
    {
        \Yii::$app->on(Application::EVENT_BEFORE_ACTION, function () {
            //Для работы с системой управления сайтом, будем требовать от пользователя реальные данные
            if (\Yii::$app->user->isGuest === false) {
                if (!in_array(\Yii::$app->controller->action->uniqueId, [
                    'cms/upa-personal/password',
                ])) {
                    $user = \Yii::$app->user->identity;
                    if (!$user->password_hash && \Yii::$app->cms->pass_is_need_change) {
                        \Yii::$app->response->redirect(Url::to(['/cms/upa-personal/password']));
                    }
                }
            }

        });

        return parent::_run();
    }
}