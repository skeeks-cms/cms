<?php
/**
 * ViewModelAction
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\actions;


use skeeks\cms\App;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\Tree;
use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\Model;
use yii\base\UserException;
use yii\web\HttpException;

/**
 * Class ErrorAction
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
    protected $_model = null;

    /**
     * @var
     */
    public $callback;

    /**
     * Runs the action
     *
     * @return string result content
     */
    public function run($model)
    {
        $this->_model = $model;
        return $this->_go();
    }

    /**
     * @return string
     */
    protected function _go()
    {

        if (!$this->_model)
        {
            throw new HttpException(404);
        }

        \Yii::$app->pageOptions->setValuesFromModel($this->_model);

        if (!in_array($this->_model->status, [HasStatus::STATUS_ACTIVE, HasStatus::STATUS_INACTIVE]))
        {
            throw new HttpException(404);
        }


        $this
            ->_initMetaData()
            ->_initTypes()
            ->_initGlobalLayout()
            ->_initActionView()
        ;



        if ($this->callback)
        {
            if (!is_callable($this->callback)) {
                throw new InvalidConfigException('"' . get_class($this) . '::callback" should be a valid callback.');
            }

            $result = call_user_func($this->callback, $this);

            if ($result)
            {
                return $result;
            }
        }

        if (Yii::$app->getRequest()->getIsAjax())
        {
            return "test: test";
        } else
        {
            return $this->controller->render($this->view ?: $this->id, [
                'model' => $this->_model
            ]);
        }
    }


    /**
     * Установка глобального layout-a
     * @return $this
     */
    protected function _initActionView()
    {
        if ($value = \Yii::$app->pageOptions->getComponent('action_view')->getValue()->value)
        {
            $this->view = $value;
        }

        return $this;
    }

    /**
     * Установка глобального layout-a
     * @return $this
     */
    protected function _initTypes()
    {
        if (!isset($this->_model->type))
        {
            return $this;
        }

        if (!$this->_model->type)
        {
            return $this;
        }

        if (!$type = \Yii::$app->registeredModels->getDescriptor($this->_model)->getTypes()->getComponent($this->_model->type))
        {
            return $this;
        }


        if ($layout = $type->getLayout())
        {
            $this->controller->layout = $layout->path;
        }

        if ($type->actionView)
        {
            if ($actionView = \Yii::$app->registeredModels->getDescriptor($this->_model)->getActionViews()->getComponent($type->actionView))
            {
                $this->view = $actionView->id;
            }
        }


        return $this;
    }


    /**
     * Установка глобального layout-a
     * @return $this
     */
    protected function _initGlobalLayout()
    {
        if ($value = \Yii::$app->pageOptions->getComponent('layout')->getValue()->value)
        {
            $this->controller->layout = $value;
        }

        return $this;
    }


    /**
     * Установка метаданных страницы
     * @return $this
     */
    protected function _initMetaData()
    {
        $model = $this->_model;

        if ($title = \Yii::$app->pageOptions->getComponent('meta_title')->getValue()->value)
        {
            $this->controller->getView()->title = $title;
        } else
        {
            if (isset($model->name))
            {
                $this->controller->getView()->title = $model->name;
            }
        }

        if ($meta_keywords = \Yii::$app->pageOptions->getComponent('meta_keywords')->getValue()->value)
        {
            $this->controller->view->registerMetaTag([
                "name"      => 'keywords',
                "content"   => $meta_keywords
            ], 'keywords');
        }

        /*else
        {
            if (isset($model->name))
            {
                $this->controller->view->registerMetaTag([
                    "name"      => 'keywords',
                    "content"   => $model->name
                ], 'keywords');
            }
        }*/


        if ($meta_descripption = \Yii::$app->pageOptions->getComponent('meta_description')->getValue()->value)
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

    /**
     * @return null|Model
     */
    public function getModel()
    {
        return $this->_model;
    }
}