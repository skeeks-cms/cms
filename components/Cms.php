<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
namespace skeeks\cms\components;

use skeeks\cms\assets\CmsAsset;
use skeeks\cms\base\components\Descriptor;
use skeeks\cms\base\Module;
use skeeks\cms\controllers\AdminCmsContentElementController;
use skeeks\cms\events\LoginEvent;
use skeeks\cms\helpers\ComposerHelper;
use skeeks\cms\mail\Message;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsEvent;
use skeeks\cms\models\CmsExtension;
use skeeks\cms\models\CmsLang;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteDomain;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorCreateAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelRelatedPropertiesAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\events\AdminInitEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeElement;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeFile;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeListMulti;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeNumber;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeRadioList;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeSelect;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeSelectMulti;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeString;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeText;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeTextarea;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeTextInput;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeTree;
use skeeks\cms\models\Site;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\Tree;
use skeeks\cms\models\TreeType;
use skeeks\cms\models\User;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeColor;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeComboText;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeDate;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeSelectFile;
use skeeks\sx\File;
use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\base\Theme;
use yii\console\Application;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\validators\EmailValidator;
use yii\web\UploadedFile;
use yii\web\UserEvent;
use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * @property CmsSite                            $site
 * @property Tree                               $currentTree
 * @property ComposerHelper                     $composer
 * @property ComposerHelper                     $appComposer
 * @property Extension                          $extension
 *
 * @property CmsLang[]                          $languages
 *
 * @property \skeeks\cms\modules\admin\Module   $moduleAdmin
 * @property \skeeks\cms\Module                 $moduleCms
 * @property CmsLang                            $cmsLanguage
 *
 * @package skeeks\cms\components
 */
class Cms extends \skeeks\cms\base\Component
{
    /**
     * Событие срабатываемое после выполнения процесса установки или обновления cms
     */
    const EVENT_AFTER_UPDATE = 'cms.event.after.update';

    /**
     * Событие приложения (записывается в базу данных и настраивается)
     */
    const EVENT_APP = 'cms.event.app';

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Базовый модуль CMS',
        ]);
    }


    public function renderConfigForm(ActiveForm $form)
    {
        echo \Yii::$app->view->renderFile(__DIR__ . '/cms/_form.php', [
            'form'  => $form,
            'model' => $this
        ], $this);
    }

    const BOOL_Y = "Y";
    const BOOL_N = "N";

    /**
     * @var string E-Mail администратора сайта (отправитель по умолчанию).
     */
    public $adminEmail                  = 'admin@skeeks.com';

    /**
     * @var string E-Mail адрес или список адресов через запятую на который будут дублироваться все исходящие сообщения.
     */
    public $notifyAdminEmailsHidden     = '';

    /**
     * @var string E-Mail адрес или список адресов через запятую на который будут дублироваться все исходящие сообщения.
     */
    public $notifyAdminEmails           = '';

    /**
     * @var string
     */
    public $appName;

    /**
     * @var string Это изображение показывается в тех случаях, когда не найдено основное.
     */
    public $noImageUrl;

    /**
     * @var array
     */
    public $relatedProperies       = [];


    //После регистрации пользователю будут присвоены эти роли
    public $registerRoles                   = [
        CmsManager::ROLE_USER
    ];

    /**
     * @var string язык по умолчанию
     */
    public $languageCode                    = "ru";

    /**
     * @var int сбрасывать токен пароля через час
     */
    public $passwordResetTokenExpire        = 3600;

    /**
     * @var string агенты на хитах
     */
    public $enabledHitAgents                = self::BOOL_Y;

    /**
     * @var int Интервал выполенения агентов на хитах
     */
    public $hitAgentsInterval               = 60;



    /**
     * @var bool Включить http вторизацию на сайте
     */
    public $enabledHttpAuth                = self::BOOL_N;

    /**
     * @var bool Включить http авторизацию только для админки
     */
    public $enabledHttpAuthAdmin           = self::BOOL_N;

    public $httpAuthLogin                  = "";
    public $httpAuthPassword               = "";

    /**
     * Настройки дебага
     * @var string
     */
    public $debugEnabled                   = self::BOOL_N;
    public $debugAllowedIPs                = "*";
    /**
     * Настройки генератора кода
     * @var string
     */
    public $giiEnabled                   = self::BOOL_N;
    public $giiAllowedIPs                = "*";

    public $licenseKey                   = "";


    /**
     * Схема временных папок
     * Чистятся в момент нажатия на кнопку чистки временных файлов
     *
     * @var array
     */
    public $tmpFolderScheme =
    [
        'runtime' =>
        [
            '@common/runtime', '@frontend/runtime', '@console/runtime'
        ],

        'assets' =>
        [
            '@frontend/web/assets'
        ]
    ];


    /**
     * @var array Возможные шаблоны сайта
     */
    public $templatesDefault       =
    [
        'default' =>
        [
            'name'          => 'Базовый шаблон (по умолчанию)',
            'pathMap'       =>
            [
                '@app/views' =>
                [
                    '@app/templates/default',
                ],
            ]
        ]
    ];

    /**
     * @var array Возможные шаблоны сайта
     */
    public $templates       = [];
    /**
     * @var string шаблон
     */
    public $template                        = "default";


    /**
     * @var array Возможные шаблоны email
     */
    public $emailTemplatesDefault       =
    [
        'default' =>
        [
            'name'          => 'Базовый шаблон (по умолчанию)',
            'pathMap'       =>
            [
                '@app/mail' =>
                [
                    '@app/mail',
                    '@skeeks/cms/mail',
                ],
            ]
        ]
    ];
    /**
     * @var array Возможные шаблоны email
     */
    public $emailTemplates       = [];

    /**
     * @var string шаблон
     */
    public $emailTemplate                        = "default";


    /**
     * @return CmsSite
     */
    public function getSite()
    {
        return \Yii::$app->currentSite->site;
    }

    protected $_languages = null;

    public function getLanguages()
    {
        if ($this->_languages === null)
        {
            $this->_languages = CmsLang::find()->active()->indexBy('code')->all();
        }

        return (array) $this->_languages;
    }


    private static $_huck = 'Z2VuZXJhdG9y';

    public function init()
    {
        parent::init();

        //Название проекта.
        if (!$this->appName)
        {
            $this->appName = \Yii::$app->name;
        } else
        {
            \Yii::$app->name = $this->appName;
        }

        //Генерация файла с подключением настроек extensions если его нет
        if (!file_exists(AUTO_GENERATED_MODULES_FILE))
        {
            $this->generateModulesConfigFile();
        }

        //init view theme
        $this->templates = ArrayHelper::merge($this->templatesDefault, (array) $this->templates);
        foreach ($this->templates as $code => $templateData)
        {
            if ($code == $this->template)
            {
                if ($pathMap = ArrayHelper::getValue($templateData, 'pathMap'))
                {
                    if (is_array($pathMap))
                    {
                        \Yii::$app->view->theme = new Theme([
                            'pathMap' => $pathMap
                        ]);
                    }
                }
            }
        }
        //TODO: may be is depricated. While better to use '@app/views/'
        \Yii::setAlias('template', '@app/views/');

        $this->emailTemplates = ArrayHelper::merge($this->emailTemplatesDefault, (array) $this->emailTemplates);

        \Yii::$app->language = $this->languageCode;

        //Отлов событий отправки сообщений с сайта, и их модификация.
        \yii\base\Event::on(\yii\mail\BaseMailer::className(), \yii\mail\BaseMailer::EVENT_BEFORE_SEND, [$this, 'beforeSendEmail']);


        if (\Yii::$app instanceof Application)
        {
            //console init
            $this->_initConsole();
        } else
        {
            //web init
            $this->_initWeb();
        }
    }

    /**
     * Перехват отправки всех email с сайта.
     *
     * @param \yii\mail\MailEvent $event
     */
    public function beforeSendEmail(\yii\mail\MailEvent $event)
    {
        

        if ($this->notifyAdminEmailsToArray())
        {
            $event->message->setCc($this->notifyAdminEmailsToArray());
        }


        if ($emails = $this->notifyAdminEmailsHiddenToArray())
        {
            $event->message->setBcc($this->notifyAdminEmailsHiddenToArray());
        }

        /*if ($event->message instanceof Message)
        {
            if ($event->message->eventName)
            {
                $modelEvent = CmsEvent::findOne($event->message->eventName);
                if (!$modelEvent)
                {
                    $modelEvent = new CmsEvent([
                        'event_name'    => $event->message->eventName,
                        'name'          => $event->message->eventDesctiption
                    ]);

                    $modelEvent->save();
                }
            }
        }*/
    }

    /**
     * Продолжение инициализации только в случае работы console приложения
     */
    protected function _initConsole()
    {
        \Yii::$app->on(self::EVENT_AFTER_UPDATE, function(Event $e)
        {
            $this->_installAgents();
        });
    }

    /**
     * Продолжение инициализации только в случае работы web приложения
     */
    protected function _initWeb()
    {
        if (!$this->noImageUrl)
        {
            $this->noImageUrl = CmsAsset::getAssetUrl('img/image-not-found.jpg');
        }

        if ($this->enabledHttpAuth == self::BOOL_Y)
        {
            $this->executeHttpAuth();
        }

        //Выполнение агентов на хитах, должны быть  включены в настройка, нужна system.
        if ($this->enabledHitAgents == self::BOOL_Y)
        {
            $key = 'Agents';
            Yii::beginProfile("Enabled agents on the hits");
                $data = \Yii::$app->cache->get($key);
                if ($data === false)
                {
                    Yii::beginProfile("Execute");
                        $result = \Yii::$app->console->execute("cd " . ROOT_DIR . '; php yii cms/utils/agents-execute;');
                        \Yii::$app->cache->set($key, '1', (int) $this->hitAgentsInterval);
                    Yii::endProfile("Execute");
                }
            Yii::endProfile("Enabled agents on the hits");
        }


        /**
         * Генерация SEO метатегов.
         * */
        \Yii::$app->view->on(View::EVENT_BEGIN_PAGE, function(Event $e)
        {
            if (!\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
            {
                \Yii::$app->response->getHeaders()->setDefault('X-Powered-CMS', $this->moduleCms->descriptor->toString());

                /**
                 * @var $view View
                 */
                $view = $e->sender;
                if (!isset($view->metaTags[self::$_huck]))
                {
                    $view->registerMetaTag([
                        "name"      => base64_decode(self::$_huck),
                        "content"   => $this->moduleCms->descriptor->toString()
                    ], self::$_huck);
                }
            }
        });



        \Yii::$app->user->on(\yii\web\User::EVENT_AFTER_LOGIN, function (UserEvent $e)
        {
            $e->identity->logged_at = \Yii::$app->formatter->asTimestamp(time());
            $e->identity->save(false);

            if ($this->moduleAdmin()->requestIsAdmin())
            {
                \Yii::$app->user->identity->updateLastAdminActivity();
            }
        });
    }


    public function executeHttpAuth()
    {
        if (!isset($_SERVER['PHP_AUTH_PW']))
        {
            $this->_authentificateHttp();
        }

        if(!@$_SERVER['PHP_AUTH_PW'])
        {
            $this->_authentificateHttp();
        }

        else
        {
            if (@$_SERVER['PHP_AUTH_USER'] != $this->httpAuthLogin || @$_SERVER['PHP_AUTH_PW'] != $this->httpAuthPassword)
            {
                $this->_authentificateHttp();
            }
        }
    }

    /**
     *
     */
    protected function _authentificateHttp()
    {
        Header("WWW-Authenticate: Basic realm=\"SkeekS CMS\"");
        Header("HTTP/1.0 401 Unauthorized");
        echo "SkeekS CMS Authorization required.";
        exit;
    }

    protected function _installAgents()
    {
        //Вставка агентов
        if (!CmsAgent::find()->where(['name' => 'cms/db/refresh'])->one())
        {
            ( new CmsAgent([
                'name'              => 'cms/db/refresh',
                'description'       => 'Инвалидация кэша структуры таблиц',
                'agent_interval'    => 3600*3, //раз в три часа
                'next_exec_at'      => \Yii::$app->formatter->asTimestamp(time()) + 3600*3,
                'is_period'         => Cms::BOOL_N
            ]) )->save();
        }

        if (!CmsAgent::find()->where(['name' => 'cms/cache/flush-runtimes'])->one())
        {
            ( new CmsAgent([
                'name'              => 'cms/cache/flush-runtimes',
                'description'       => 'Чистка временных диррикторий',
                'agent_interval'    => 3600*24,
                'next_exec_at'      => \Yii::$app->formatter->asTimestamp(time()) + 3600*24,
                'is_period'         => Cms::BOOL_N
            ]) )->save();
        }

        if (!CmsAgent::find()->where(['name' => 'cms/db/dump'])->one())
        {
            ( new CmsAgent([
                'name'              => 'cms/db/dump',
                'description'       => 'Бэкап базы данных',
                'agent_interval'    => 3600*24, //раз в три часа
                'next_exec_at'      => \Yii::$app->formatter->asTimestamp(time()) + 3600*24,
                'is_period'         => Cms::BOOL_N
            ]) )->save();
        }

        if (!CmsAgent::find()->where(['name' => 'cms/utils/clear-search-phrase'])->one())
        {
            ( new CmsAgent([
                'name'              => 'cms/utils/clear-search-phrase',
                'description'       => 'Чистка поисковых запросов',
                'agent_interval'    => 3600*24, //раз в сутки
                'next_exec_at'      => \Yii::$app->formatter->asTimestamp(time()) + 3600*24,
                'is_period'         => Cms::BOOL_N
            ]) )->save();
        }
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['adminEmail', 'noImageUrl', 'notifyAdminEmails', 'notifyAdminEmailsHidden', 'appName', 'template', 'languageCode'], 'string'],
            [['adminEmail'], 'email'],
            [['adminEmail'], 'email'],
            [['emailTemplate'], 'string'],
            [['passwordResetTokenExpire'], 'integer', 'min' => 300],
            [['hitAgentsInterval'], 'integer', 'min' => 60],
            [['enabledHitAgents'], 'string'],
            [['registerRoles'], 'safe'],
            [['enabledHttpAuthAdmin'], 'string'],
            [['enabledHttpAuth'], 'string'],
            [['httpAuthLogin'], 'string'],
            [['httpAuthPassword'], 'string'],
            [['debugEnabled'], 'string'],
            [['debugAllowedIPs'], 'string'],
            [['giiEnabled'], 'string'],
            [['giiAllowedIPs'], 'string'],
            [['licenseKey'], 'string'],

            [['notifyAdminEmails'], function($attribute)
            {
                if ($emails = $this->notifyAdminEmailsToArray())
                {
                    foreach ($emails as $email)
                    {
                        $validator = new EmailValidator();

                        if (!$validator->validate($email, $error))
                        {
                            $this->addError($attribute, $email . ' — некорректный email адрес');
                            return false;
                        }
                    }
                }

            }],

            [['notifyAdminEmailsHidden'], function($attribute)
            {
                if ($emails = $this->notifyAdminEmailsHiddenToArray())
                {
                    foreach ($emails as $email)
                    {
                        $validator = new EmailValidator();

                        if (!$validator->validate($email, $error))
                        {
                            $this->addError($attribute, $email . ' — некорректный email адрес');
                            return false;
                        }
                    }
                }

            }],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'adminEmail'                => 'Основной Email Администратора сайта',
            'notifyAdminEmailsHidden'   => 'Email адреса уведомлений (скрытая копия)',
            'notifyAdminEmails'         => 'Email адреса уведомлений',
            'noImageUrl'                => 'Изображение заглушка',
            'appName'                   => 'Название проекта',
            'template'                  => 'Шаблон',
            'templates'                 => 'Возможные шаблон',
            'emailTemplate'             => 'Шаблон для email',
            'languageCode'              => 'Язык по умолчанию',
            'passwordResetTokenExpire'  => 'Инвалидировать токен пароля через час',
            'enabledHitAgents'          => 'Выполнение агентов на хитах',
            'hitAgentsInterval'         => 'Интервал выполнения агентов на хитах',
            'registerRoles'             => 'При регистрации добавлять в группу',
            'enabledHttpAuth'           => 'Использовать http авторизацию на сайте',
            'enabledHttpAuthAdmin'      => 'Использовать http авторизацию в административной части',
            'httpAuthLogin'             => 'Логин',
            'httpAuthPassword'          => 'Пароль',
            'debugEnabled'              => 'Включение режима отладки',
            'debugAllowedIPs'           => 'Включение режима отладки для ip адресов',
            'giiEnabled'                => 'Генератор кода включен',
            'giiAllowedIPs'             => 'Включение генератора кода для ip адресов',
            'licenseKey'                => 'Лицензионный ключ',
        ]);
    }

    /**
     * @return string
     */
    public function logo()
    {
        return 'data:image/png;base64,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';
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
     * @return User
     * @throws Exception
     */
    public function getUserClassName()
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

        return $userClassName;
    }


    /**
     *
     * Вернутся модули только которые instanceof skeeks\cms\Module
     * При этом происходит загрузка всех зарегистрированных модулей приложения, это не очень оптимально.
     * Используется для админки, только если срабатывает роут админки, в сайтовой части данной неоптимальности нет.
     *
     * @return Module[]
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
     * @param $template
     * @param $data
     * @return string
     */
    public function renderFrontend($template, $data = [])
    {
        return \Yii::$app->view->renderFile(\Yii::getAlias("@frontend/views/") . $template, $data);
    }


    /**
     * TODO: is depricated
     * @return null|\skeeks\cms\modules\admin\Module
     */
    static public function moduleAdmin()
    {
        return \Yii::$app->getModule("admin");
    }

    /**
     * TODO: is depricated
     * @return null|\skeeks\cms\Module
     */
    static public function moduleCms()
    {
        return \Yii::$app->getModule("cms");
    }


    /**
     * @return null|\skeeks\cms\modules\admin\Module
     */
    public function getModuleAdmin()
    {
        return \Yii::$app->getModule("admin");
    }

    /**
     * @return null|\skeeks\cms\Module
     */
    public function getModuleCms()
    {
        return \Yii::$app->getModule("cms");
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
     *
     * Взять все установленные расширения, и вернуть пути к конфигам
     *
     * @param string|array $fileName
     * @return array
     */
    public function findConfigFiles($fileName = '/config/main.php')
    {
        $config     = [];

        $fileNames = [];
        if (is_string($fileName))
        {
            $fileNames[] = $fileName;
        } else if (is_array($fileName))
        {
            $fileNames = $fileName;
        }

        foreach ((array) \Yii::$app->extensions as $code => $data)
        {
            if (is_array($data['alias']))
            {
                $configTmp  = [];

                foreach ($data['alias'] as $code => $path)
                {
                    foreach ($fileNames as $fileName)
                    {
                        $file = new \skeeks\sx\File($path . $fileName);
                        if ($file->isExist())
                        {
                            $config[] = $file->getPath();
                        }
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Пройтись по всем расширениям уставноленным в проект, и сгенерировать конфиг файл.
     * @return bool
     */
    public function generateModulesConfigFile()
    {
        $configs            = $this->findConfigFiles(['/config/main.php']);
        $configsConsole     = $this->findConfigFiles(['/config/main-console.php']);

        if ($configs || $configsConsole)
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
            $fileContent .= "'web' => [\n";

            foreach ($configs as $filePach)
            {
                $fileContent .= "\"" . $filePach . "\", \n";
            }
            $fileContent .= "],\n";

            $fileContent .= "'console' => [\n";

            foreach ($configsConsole as $filePach)
            {
                $fileContent .= "\"" . $filePach . "\", \n";
            }
            $fileContent .= "]\n";

$fileContent .= '];';

            $file = new File(AUTO_GENERATED_MODULES_FILE);
            $file->write($fileContent);
        }

        $file = new File(AUTO_GENERATED_MODULES_FILE);
        return $file->isExist();

    }


    /**
     * @return array
     */
    public function notifyAdminEmailsToArray()
    {
        return $this->notifyAdminEmails ? explode(",", $this->notifyAdminEmails) : [];
    }

    /**
     * @return array
     */
    public function notifyAdminEmailsHiddenToArray()
    {
        return $this->notifyAdminEmailsHidden ? explode(",", $this->notifyAdminEmailsHidden) : [];
    }

    /**
     * Да/нет
     * @return array
     */
    public function booleanFormat()
    {
        return [
            self::BOOL_Y => Yii::t('yii', 'Yes', [], \Yii::$app->formatter->locale),
            self::BOOL_N => Yii::t('yii', 'No', [], \Yii::$app->formatter->locale)
        ];
    }


    /**
     * Базовые типы свойств
     * @return array
     */
    public function basePropertyTypes()
    {
        return [
            PropertyTypeText::className()           => (new PropertyTypeText)->name,
            PropertyTypeNumber::className()         => (new PropertyTypeNumber)->name,
            PropertyTypeList::className()           => (new PropertyTypeList)->name,
            PropertyTypeFile::className()           => (new PropertyTypeFile)->name,
            PropertyTypeTree::className()           => (new PropertyTypeTree)->name,
            PropertyTypeElement::className()        => (new PropertyTypeElement)->name,
        ];
    }

    /**
     * Пользовательские типы свойств.
     * @return array
     */
    public function userPropertyTypes()
    {
        $fromConfig = [];

        if ((array) $this->relatedProperies)
        {
            foreach ((array) $this->relatedProperies as $userPropertyClass)
            {
                //TODO: добавить проверки
                $fromConfig[$userPropertyClass] = (new $userPropertyClass)->name;
            }
        }

        return (array) ArrayHelper::merge([
            UserPropertyTypeDate::className() => (new UserPropertyTypeDate())->name,
            UserPropertyTypeComboText::className() => (new UserPropertyTypeComboText())->name,
            UserPropertyTypeColor::className() => (new UserPropertyTypeColor())->name,
            UserPropertyTypeSelectFile::className() => (new UserPropertyTypeSelectFile())->name
        ], (array) $fromConfig);
    }

    /**
     * Все типы свойств
     * @return array
     */
    public function allPropertyTypes()
    {
        return array_merge($this->basePropertyTypes(), $this->userPropertyTypes());
    }


    /**
     * @return Extension
     */
    public function getExtension()
    {
        return CmsExtension::getInstance('skeeks/cms');
    }

    /**
     * @return ComposerHelper
     */
    public function getComposer()
    {
        $extension = CmsExtension::getInstance('skeeks/cms');
        return $extension->composer;
    }

    /**
     * @return ComposerHelper
     */
    public function getAppComposer()
    {
        $composerFile = ROOT_DIR . "/composer.json";
        if (file_exists($composerFile))
        {
            $data = file_get_contents($composerFile);
            $data = Json::decode($data);

            return new ComposerHelper([
                'data' => (array) $data
            ]);
        }

        return new ComposerHelper();
    }


    /**
     * @return array|null|CmsLang
     */
    public function getCmsLanguage()
    {
        return CmsLang::find()->where(['code' => $this->languageCode])->one();
    }
}