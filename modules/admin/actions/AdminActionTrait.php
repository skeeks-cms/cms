<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions;
use yii\base\Action;
use yii\web\NotFoundHttpException;
/**
 * Основные свойства, которымы обладает каждое дейсвие админки.
 *
 * Class AdminActionTrait
 * @package skeeks\cms\modules\admin\actions
 */
trait AdminActionTrait
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
}