<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions;

use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use yii\helpers\Inflector;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * Class AdminModelAction
 * @package skeeks\cms\modules\admin\actions
 */
class AdminModelAction extends AdminAction
{
    public function run()
    {
        parent::run();
    }

    /**
     * @return bool
     */
    protected function beforeRun()
    {
        if (parent::beforeRun())
        {
            $this->_initBreadcrumbsData();
            $this->_initActionsData();
            $this->_initMetadata();

            return true;
        } else
        {
            return false;
        }
    }












    /**
     * Формируем данные для хлебных крошек.
     * Эти данные в layout - е будут передаваться в нужный виджет.
     * @param ActionEvent $e
     *
     * @return $this
     */
    protected function _renderBreadcrumbs(ActionEvent $e)
    {
        $currentAction = $this->_getActionFromEvent($e);
        if (!$currentAction instanceof Action || !$this->getCurrentModel())
        {
            return parent::_renderBreadcrumbs($e);
        }

        if ($this->_label)
        {
            $this->getView()->params['breadcrumbs'][] = ['label' => $this->_label, 'url' => $this->indexUrl];
        }

        $this->getView()->params['breadcrumbs'][] = ['label' => $this->getCurrentModel()->{$this->_modelShowAttribute}, 'url' => [
            $this->defaultActionModel,
            "id" => $this->getCurrentModel()->{$this->modelPkAttribute},
            UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
        ]];


        if ($this->defaultAction != $e->action->id)
        {
            $this->getView()->params['breadcrumbs'][] = $currentAction->label;
        }

        return $this;
    }


    /**
     * @param ActionEvent $e
     * @return $this
     */
    protected function _renderMetadata(ActionEvent $e)
    {
        //Если текущее действие не описано, делаем как нужно по умолчанию
        $currentAction = $this->_getActionFromEvent($e);
        if (!$currentAction instanceof Action || !$this->getCurrentModel())
        {
            return parent::_renderMetadata($e);
        }

        $actionTitle    = $currentAction->label;

        $result[] = $actionTitle;
        $result[] = $this->getCurrentModel()->{$this->_modelShowAttribute};
        $result[] = $this->_label;

        $this->getView()->title = implode(" / ", $result);
        return $this;
    }
}