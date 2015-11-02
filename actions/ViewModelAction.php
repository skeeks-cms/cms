<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */
namespace skeeks\cms\actions;


use skeeks\cms\App;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\Tree;
use Yii;
use yii\base\Action;
use yii\base\ActionEvent;
use yii\base\Exception;
use yii\base\Model;
use yii\base\UserException;
use yii\web\Controller;
use yii\web\HttpException;

/**
 * Стандартное действие для отображения одной сущьности, используется для панели редактирования в сайтовой части.
 *
 * Class ViewModelAction
 * @package skeeks\cms\actions
 */
abstract class ViewModelAction extends Action
{
    /**
     * @var string the view file to be rendered. If not set, it will take the value of [[id]].
     * That means, if you name the action as "error" in "SiteController", then the view name
     * would be "error", and the corresponding view file would be "views/site/error.php".
     */
    public $view;

    /**
     * @var Model|null
     */
    public $model = null;

    /**
     * @var
     */
    public $callback;

    /**
     * @var bool включение/отключение стандартной обработки seo meta tags
     */
    public $enabledStandartMetaData = true;


    /**
     * Runs the action
     *
     * @return string result content
     */
    public function run($model)
    {
        /*$this->controller->on(Controller::EVENT_BEFORE_ACTION, function(ActionEvent $event)
        {
            if ($event->action == $this->id)
            {

            }
        });*/

        $this->model = $model;
        return $this->_go();
    }

    /**
     * @return string
     */
    protected function _go()
    {
        if (!$this->model)
        {
            throw new HttpException(404);
        }

        /*if (!in_array($this->_model->status, [HasStatus::STATUS_ACTIVE, HasStatus::STATUS_INACTIVE]))
        {
            throw new HttpException(404);
        }*/

        if ($this->enabledStandartMetaData)
        {
            $this->initStandartMetaData();
        }

        if ($this->callback)
        {
            if (!is_callable($this->callback))
            {
                throw new InvalidConfigException(\Yii::t('app','{cb} should be a valid callback.',['cb' => '"' . get_class($this) . '::callback"' ]));
            }

            $result = call_user_func($this->callback, $this);

            if ($result)
            {
                return $result;
            }
        }

        if (Yii::$app->getRequest()->getIsAjax() && !\Yii::$app->request->isPjax)
        {
            return "test: test";
        } else
        {
            return $this->controller->render($this->view ?: $this->id, [
                'model' => $this->model
            ]);
        }
    }

    /**
     * Установка метаданных страницы
     * @return $this
     */
    public function initStandartMetaData()
    {
        $model = $this->model;

        if ($title = $model->meta_title)
        {
            $this->controller->getView()->title = $title;
        } else
        {
            if (isset($model->name))
            {
                $this->controller->getView()->title = $model->name;
            }
        }

        if ($meta_keywords = $model->meta_keywords)
        {
            $this->controller->view->registerMetaTag([
                "name"      => 'keywords',
                "content"   => $meta_keywords
            ], 'keywords');
        }

        if ($meta_descripption = $model->meta_description)
        {
            $this->controller->view->registerMetaTag([
                "name"      => 'description',
                "content"   => $meta_descripption
            ], 'description');
        }
        else
        {
            if (isset($model->name))
            {
                $this->controller->view->registerMetaTag([
                    "name"      => 'description',
                    "content"   => $model->name
                ], 'description');
            }
        }

        return $this;
    }
}