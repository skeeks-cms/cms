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
use skeeks\cms\models\User;
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
     * @return \yii\web\User|\common\models\User|models\User|null
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
     * @return \yii\web\User
     */
    static public function auth()
    {
        return static::getAuth();
    }

    /**
     * @return \common\models\User|\yii\web\User|models\User
     */
    static public function user()
    {
        return static::getUser();
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws Exception
     */
    static public function findUser()
    {
        /**
         * @var $userClassName User
         */
        $userClassName = \Yii::$app->user->identityClass;

        if (!class_exists($userClassName))
        {
            throw new Exception("Не правильно сконфигурирован компонент user, класс пользователя не найден");
        }

        if (!is_subclass_of($userClassName, User::className()))
        {
            throw new Exception("Пользовательский класс должен быть наследован от базового skeeks cms класса: " . User::className());
        }

        return $userClassName::find();
    }


        /**
         * Удобный и быстрый доступ к модулям
         */

    /**
     * @return null|\skeeks\cms\modules\admin\Module
     */
    static public function moduleAdmin()
    {
        return \Yii::$app->getModule("admin");
    }

    /**
     * @return null|\skeeks\cms\CmsModule
     */
    static public function moduleCms()
    {
        return \Yii::$app->getModule("cms");
    }

    /**
     *
     * Вернутся модули только которые instanceof skeeks\cms\Module
     * При этом происходит загрузка всех зарегистрированных модулей приложения, это не очень оптимально.
     * Используется для админки, только если срабатывает роут админки, в сайтовой части данной неоптимальности нет.
     *
     * @return array
     */
    static public function getModules()
    {
        $result = [];
        $allModules = array_keys(\Yii::$app->getModules());
        if ($allModules)
        {
            foreach ($allModules as $key)
            {
                $moduleObject = \Yii::$app->getModule($key);

                if ($moduleObject instanceof Module)
                {
                    $result[$key] = $moduleObject;
                }
            }
        }

        return $result;
    }


    /**
     * @var components\Descriptor
     */
    protected static $_descriptor = null;

    /**
     * @return components\Descriptor
     */
    static public function getDescriptor()
    {
        if (self::$_descriptor === null)
        {
             self::$_descriptor = new components\Descriptor((array) \Yii::$app->params["descriptor"]);
        }

        return self::$_descriptor;
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