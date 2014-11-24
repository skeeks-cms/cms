<?php
/**
 * Cms
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\Site;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\TreeType;
use skeeks\cms\widgets\Infoblock;
use skeeks\cms\widgets\StaticBlock;
use skeeks\sx\models\IdentityMap;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class Cms
 * @package skeeks\cms\components
 */
class Cms extends \skeeks\cms\base\Component
{
    /**
     * @return \yii\web\User|\common\models\User|models\User|null
     */
    static public function getAuthUser()
    {
        if (\Yii::$app->user->isGuest)
        {
            return null;
        }

        return \Yii::$app->user->identity;
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
     * @var Descriptor
     */
    protected static $_descriptor = null;

    /**
     * @return Descriptor
     */
    static public function getDescriptor()
    {
        if (self::$_descriptor === null)
        {
             self::$_descriptor = new Descriptor((array) \Yii::$app->params["descriptor"]);
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


    /**
     * @return null|\skeeks\cms\modules\admin\Module
     */
    static public function moduleAdmin()
    {
        return \Yii::$app->getModule("admin");
    }

    /**
     * @return null|\skeeks\cms\Module
     */
    static public function moduleCms()
    {
        return \Yii::$app->getModule("cms");
    }



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
     *
     * Виджет статический блок
     *
     * @param string $code
     * @param string $defaultValue
     * @param array $options
     * @return string
     */
    public function widgetStaticBlock($code, $defaultValue = '', $options = [])
    {
        return StaticBlock::widget(ArrayHelper::merge($options, [
            'code'      => (string) $code,
            'default'   => (string) $defaultValue
        ]));
    }

    /**
     *
     * Вызов инфоблока
     *
     * @param string|int $id
     * @param array $config
     * @param array $options
     * @return string
     */
    public function widgetInfoblock($id, $config = [], $options = [])
    {
        return Infoblock::widget(ArrayHelper::merge($options, [
            'id'        => $id,
            'config'    => $config
        ]));
    }

}