<?php
/**
 * RequestHelper
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 05.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\helpers;

use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\sx\traits\Entity;
use skeeks\sx\traits\InstanceObject;
use yii\base\Object;
use yii\helpers\ArrayHelper;

/**
 * Class RequestOptions
 * @package skeeks\cms\helpers
 */
class UrlHelper
{
    use Entity;

    const SYSTEM_CMS_NAME = "_sx";

    protected $_route = "";

    /**
     * @var null|UrlHelper
     */
    static public $currentUrl = null;

    /**
     * @return static
     */
    static public function getCurrent()
    {
        if (static::$currentUrl === null)
        {
            //TODO: доработать вычисление текущего роута
            static::$currentUrl = static::constructCurrent();
        }

        return static::$currentUrl;
    }

    /**
     * @return static
     */
    static public function constructCurrent()
    {
        return new static("/", \Yii::$app->request->getQueryParams());
    }

    /**
     * @param $route
     * @return $this
     */
    public function setRoute($route)
    {
        $this->_route = (string) $route;
        return $this;
    }
    /**
     * @param $route
     * @param array $data
     * @return static
     */
    static public function construct($route = "", $data = [])
    {
        return new static($route, $data);
    }

    /**
     * @param $route
     * @param array $data
     */
    public function __construct($route = "", $data = [])
    {
        $this->_route   = (string) $route;
        $this->_data    = (array) $data;
    }

    /**
     * Получить системный параметр
     * @param null|string $key
     * @param null $default
     * @return array|mixed
     */
    public function getSystem($key = null, $default = null)
    {
        $systemData = (array) $this->get(self::SYSTEM_CMS_NAME, []);
        if ($key)
        {
            return ArrayHelper::getValue($systemData, $key, $default);
        } else
        {
            return $systemData;
        }
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function setSystemParam($key, $value)
    {
        $systemData = $this->getSystem();
        $systemData[$key] = $value;

        return $this->set(self::SYSTEM_CMS_NAME, $systemData);
    }

    /**
     * @param $systemData
     * @return $this
     */
    public function setSystem($systemData = [])
    {
        return $this->set(self::SYSTEM_CMS_NAME, (array) $systemData);
    }

    /**
     * @return $this
     */
    public function setCurrentRef()
    {
        return $this->setRef(\Yii::$app->request->getUrl());
    }

    /**
     * @param $ref
     * @return $this
     */
    public function setRef($ref)
    {
        return $this->setSystemParam("ref", (string) $ref);
    }

    /**
     * @return null|string
     */
    public function getRef()
    {
        return $this->getSystem("ref", "");
    }


    /**
     * @return $this
     */
    public function enableAdmin()
    {
        return $this->set(UrlRule::ADMIN_PARAM_NAME, UrlRule::ADMIN_PARAM_VALUE);
    }

    /**
     * @return $this
     */
    public function disableAdmin()
    {
        return $this->offsetUnset(UrlRule::ADMIN_PARAM_NAME);
    }

    /**
     * @return string
     */
    public function createUrl()
    {
        return \Yii::$app->urlManager->createUrl($this->toArray());
    }

    public function toString()
    {
        return $this->createUrl();
    }
    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge([$this->_route], $this->_data);
    }
}