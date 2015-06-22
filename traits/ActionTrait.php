<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\traits;
use yii\base\Action;
use yii\web\NotFoundHttpException;

/**
 * Class ActionTrait
 * @package skeeks\cms\traits
 */
trait ActionTrait
{
    /**
     * @var string Название действия, будет задействовано в заголовки страницы, в хлебных крошках и т.д.
     */
    public $name;

    /**
     * @var string Класс иконки
     */
    public $icon;

    /**
     * @var string Здавать вопрос перед запуском этого действия?
     */
    public $confirm = '';

    /**
     * @var string
     */
    public $method  = 'get';

    /**
     * @var string
     */
    public $request = ''; //ajax

    /**
     * @var bool Показывается в меню или нет
     */
    public $visible = true;

    /**
     * @var int приоритет виляет на сортировку
     */
    public $priority = 100;
}