<?php
/**
 * Storage
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\components;
use skeeks\cms\App;
use \yii\base\InvalidConfigException;
/**
 * Class Storage
 * @package skeeks\cms\components
 */
class UrlRule
    extends \yii\web\UrlRule
{
    /**
     * Месторасположения админки
     * @var string
     */
    public $adminPrefix = '~sx';

    public function init()
    {
        if ($this->name === null)
        {
            $this->name = __CLASS__;
        }

        if (!$this->adminPrefix)
        {
            throw new InvalidConfigException("Некорректно сконфигурирован модуль admin для skeeks cms");
        }
    }

    /**
     * @param \yii\web\UrlManager $manager
     * @param string $route
     * @param array $params
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        if (!isset($params["namespace"]))
        {
            return false;
        }

        if ($params["namespace"] != "admin")
        {
            return false;
        }

        unset($params["namespace"]);

        $url = $this->adminPrefix . "/" . $route;
        if (!empty($params) && ($query = http_build_query($params)) !== '')
        {
            $url .= '?' . $query;
        }

        return $url;
    }

    /**
     * @param \yii\web\UrlManager $manager
     * @param \yii\web\Request $request
     * @return array|bool
     */
    public function parseRequest($manager, $request)
    {
        $pathInfo       = $request->getPathInfo();
        $params         = $request->getQueryParams();
        $firstPrefix    = substr($pathInfo, 0, strlen($this->adminPrefix));

        if ($firstPrefix == $this->adminPrefix)
        {
            $route = str_replace($this->adminPrefix, "", $pathInfo);
            if (!$route)
            {
                $route = "admin/index";
            }

            $params["namespace"] = "admin";
            return [$route, $params];
            //return ["cms/admin-user", $params];
        }

        return false;  // this rule does not apply
    }
}
