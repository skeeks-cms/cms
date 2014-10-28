<?php
/**
 * App
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms;

use skeeks\sx\models\IdentityMap;
/**
 * Class App
 * @package skeeks\cms
 */
class App
{
    /**
     * @var null
     */
    static protected $_instance = null;

    /**
     * @return static
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance))
        {
            self::$_instance = new static();
        }

        return self::$_instance;
    }

    /**
     * @return App
     */
    static public function instance()
    {
        return static::getInstance();
    }


    protected function __construct()
    {}


    /**
     * @var IdentityMap
     */
    protected $_entityMap = null;
    /**
     * @return IdentityMap
     */
    public function getEntityMap()
    {
        if (null === $this->_entityMap)
        {
            $this->_entityMap = new IdentityMap();
        }

        return $this->_entityMap;
    }


    /**
     * @return \yii\web\User|\common\models\User|null
     */
    static public function getUser()
    {
        if (\Yii::$app->user->isGuest)
        {
            return null;
        }

        return \Yii::$app->user->identity;
    }

    /**
     * @return mixed|\yii\web\User
     */
    static public function getAuth()
    {
        return \Yii::$app->user;
    }


    /**
     * @return mixed|\yii\web\User
     */
    static public function auth()
    {
        return static::getAuth();
    }

    /**
     * @return \common\models\User|\yii\web\User
     */
    static public function user()
    {
        return static::getUser();
    }

    /**
     * @param $template
     * @param $data
     * @return string
     */
    static public function renderFrontend($template, $data)
    {
        return \Yii::$app->view->renderFile(\Yii::getAlias("@frontend/views/") . $template, $data);
    }
}