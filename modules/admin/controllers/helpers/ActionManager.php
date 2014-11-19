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
namespace skeeks\cms\modules\admin\controllers\helpers;
use skeeks\cms\Exception;
use skeeks\cms\modules\admin\controllers\AdminController;
use yii\base\Behavior;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
class ActionManager extends Behavior
{
    public $actions         = [];

    /**
     * @throws Exception
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @param Component $owner
     */
    public function attach($owner)
    {
        if (!$owner instanceof AdminController)
        {
            throw new Exception("Данный менеджер предназначен для работы с контроллерами дочерними от: " . AdminController::className());
        }

        parent::attach($owner);
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllowActions()
    {
        $baseData = [
            "controller" => $this->owner
        ];

        $result = [];

        if ($this->actions)
        {
            foreach ($this->actions as $code => $actionData)
            {
                $result[$code] = $actionData;

                if (isset($actionData["rules"]))
                {
                    if (is_string($actionData["rules"]))
                    {
                        $rules = [$actionData["rules"]];
                    } else if (is_array($actionData["rules"]))
                    {
                        $rules = $actionData["rules"];
                    } else
                    {
                        throw new Exception("rules должны быть array|string");
                    }

                    foreach ($rules as $ruleData)
                    {
                        if (is_string($ruleData))
                        {
                            $test = $ruleData;
                            $ruleData = [];
                            $ruleData["class"] = $test;
                        }

                        //TODO: не хватает проверок, кучи, добавить
                        if (is_array($ruleData))
                        {
                            $class  = $ruleData["class"];
                            unset($ruleData["class"]);
                            $params = $ruleData;
                            $ruleObject = new $class(array_merge($params, $baseData));
                        } else
                        {
                            throw new Exception("rules должны быть array|string");
                        }

                        /**
                         * @var ActionRule $ruleObject
                         */
                        if (!$ruleObject->isAllow())
                        {
                            unset($result[$code]);
                        }
                    }


                }
            }
        }


        $lastResult = [];
        if ($result)
        {
            foreach ($result as $code => $actionData)
            {
                if (!ArrayHelper::getValue($actionData, 'priority', 0))
                {
                    unset($result[$code]);
                    $lastResult[$code] = $actionData;
                }
            }

            $lastResult = array_merge($lastResult, $result);
        }


        return $lastResult;
    }

    /**
     * @param $code
     * @return array|bool
     */
    public function getActionData($code)
    {
        if (isset($this->actions[$code]))
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
     * @param $code
     * @return bool|Action
     */
    public function getAction($code)
    {
        if ($data = $this->getActionData($code))
        {
            $data["code"] = $code;
            $data["controller"] = $this->owner;
            return new Action($data);
        }

        return false;
    }


}