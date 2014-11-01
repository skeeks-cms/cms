<?php
/**
 * ActionManager
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 01.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\descriptors;
use yii\base\Component;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
class ActionManager extends Component
{
    public $actions    = [];

    /**
     * @param Action $action
     * @return $this
     */
    public function registerAction(Action $action)
    {
        $this->actions[$action->code] = $action;
        return $this;
    }

    /**
     * @param $code
     * @return Action|bool
     */
    public function getAction($code)
    {
        if (isset($this->actions))
        {
            return $this->actions[$code];
        }

        return false;
    }

    /**
     * @param $code
     * @return bool
     */
    public function hasAction($code)
    {
        return isset($this->actions[$code]);
    }


    /**
     * @param string $code
     * @param array $data
     * @return $this
     */
    public function createAction($code, array $data = [])
    {
        $this->registerAction(Action::create($code, $data));
        return $this;
    }

}