<?php
/**
 * Action
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 01.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers\helpers;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use yii\base\Component;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
class Action extends Component
{
    public $code            = "";

    /**
     * @var AdminController
     */
    public $controller      = "";

    public $label   = "";

    public $priority    = null;
    public $method      = null;
    public $confirm     = null;

    public $icon     = null;
    public $img      = null;

    public $rules       = [];

    /**
     * @param AdminController $controller
     * @param string $code
     * @param array $data
     * @return static
     */
    static public function create(AdminController $controller, $code, array $data = [])
    {
        return new static(array_merge($data, [
            "controller"    => $controller,
            "code"          => $code
        ]));
    }

    /**
     * Добавить правило
     * @param $rule
     */
    public function appendRule($rule)
    {
        $this->rules = array_merge($this->rules, $rule);
    }



    /**
     * @return bool
     */
    public function isOpenNewWindow()
    {
        if ($this->controller instanceof AdminModelEditorController)
        {
            if ($model = $this->controller->getModel())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getNewWindowName()
    {
        if ($this->controller instanceof AdminModelEditorController)
        {
            if ($model = $this->controller->getModel())
            {
                return $this->controller->id . $model->id;
            }
        }

        return $this->controller->id . $this->code;
    }



    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->controller->module instanceof \skeeks\cms\Module)
        {
            $route = $this->controller->module->id . '/' . $this->controller->id . '/' . $this->code;
        } else
        {
            $route = $this->controller->id . '/' . $this->code;
        }

        $url = UrlHelper::constructCurrent()->setRoute($route)
            ->set(UrlRule::ADMIN_PARAM_NAME, UrlRule::ADMIN_PARAM_VALUE);

        if ($this->controller instanceof AdminModelEditorController)
        {
            if ($model = $this->controller->getModel())
            {
                $url->set('id', $model->id);
            }
        }

        return $url->toString();
    }


}