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

use skeeks\cms\base\components\Descriptor;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\base\Module;
use skeeks\cms\models\Site;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\Tree;
use skeeks\cms\models\TreeType;
use skeeks\cms\models\User;
use skeeks\cms\widgets\Infoblock;
use skeeks\cms\widgets\StaticBlock;
use skeeks\sx\File;
use skeeks\sx\models\IdentityMap;
use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use yii\web\View;

/**
 * Class Cms
 * @package skeeks\cms\components
 */
class Cms extends \skeeks\cms\base\Component
{
    public $staticKeySold = '';
    public $tmpModulesConfigFile;


    public function init()
    {
        parent::init();

        /**
         * Генерация SEO метатегов.
         * */
        \Yii::$app->view->on(View::EVENT_END_PAGE, function(Event $e)
        {
            \Yii::$app->seoGenerator->generateBeforeOutputPage($e->sender);
        });
    }

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
    public function renderFrontend($template, $data = [])
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

    private $_staticKey = null;
    public function getStaticKey()
    {
        if ($this->_staticKey === null)
        {
            $fileConfigSold = '';
            if (file_exists((string) $this->tmpModulesConfigFile))
            {
                $fileConfigSold = filemtime((string) $this->tmpModulesConfigFile);
            }

            $this->_staticKey = md5(implode('', [
                (string) $this->moduleCms()->getDescriptor()->getVersion(),
                $this->staticKeySold,
                Yii::getVersion(),
                'system-sold-2',
                $fileConfigSold
            ]));
        }

        return $this->_staticKey;
    }


    /**
     * @var Tree
     */
    protected $_tree = null;


    /**
     * @param Tree $tree
     * @return $this
     */
    public function setCurrentTree(Tree $tree)
    {
        $this->_tree = $tree;
        return $this;
    }

    /**
     * @return Tree
     */
    public function getCurrentTree()
    {
        return $this->_tree;
    }

    /**
     * Пройтись по всем расширениям уставноленным в проект, и сгенерировать конфиг файл.
     * @return $this
     */
    public function generateModulesConfigFile()
    {
        $config     = [];

        foreach ((array) \Yii::$app->extensions as $code => $data)
        {
            if (is_array($data['alias']))
            {
                $configTmp  = [];

                foreach ($data['alias'] as $code => $path)
                {
                    $file = new \skeeks\sx\File($path . '/configs/main.php');
                    if ($file->isExist())
                    {
                        $config[] = $file->getPath();
                    }
                    $file = new \skeeks\sx\File($path . '/config/main.php');
                    if ($file->isExist())
                    {
                        $config[] = $file->getPath();
                    }
                }
            }
        }

        if ($config)
        {

            $date = date("dd.mm.YY", time());
            $fileContent = <<<PHP
<?php
/**
 * Автоматически сгенерированные конфиг, можно просто удалить этот файл.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date {$date}
 * @since 1.0.0
 */
 return [

PHP;
            foreach ($config as $filePach)
            {
                $fileContent .= "\"" . $filePach . "\", \n";
            }
            $fileContent .= '];';

            $file = new File((string) $this->tmpModulesConfigFile);
            $file->write($fileContent);
        }

        return $this;
    }
}