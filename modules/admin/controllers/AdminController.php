<?php
/**
 * Это самый базовый контроллер админки, все контроллеры, сторонних модулей и приложения, должны быть дочерними от этого контроллера.
 *
 * 1) Закрываем путь всем неавторизованным пользователям
 * 2) Определяем layout админки
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;

use skeeks\cms\App;
use yii\base\ActionEvent;
use yii\web\Controller;
use yii\filters\AccessControl;

/**
 * Class AdminController
 * @package skeeks\cms\modules\admin\controllers
 */
abstract class AdminController extends Controller
{
    /**
     * @var string понятное название контроллера, будет добавлено в хлебные крошки и title страницы
     */
    protected $_label = null;

    /**
     * @var array
     */
    protected $_actions =
    [
        /*"index" =>
        [
            "label" => "Список",
        ],

        "create" =>
        [
            "label" => "Добавить",
        ],*/
    ];

    public function behaviors()
    {
        return
        [
            'access' =>
            [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        $this->_ensure();

        $this->layout = App::moduleAdmin()->layout;

        $this->on(self::EVENT_BEFORE_ACTION, [$this, "_renderActions"]);
    }

    protected function _renderActions(ActionEvent $e)
    {
        $this->getView()->params["actions"] = "rendered";
        /*print_r($this->view->params["actions"]);die;
        print_r($e->action->id);die;
        print_r($this->_actions);die;*/
    }

    protected function _ensure()
    {}
}