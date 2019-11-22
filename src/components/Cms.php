<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */

namespace skeeks\cms\components;

use skeeks\cms\assets\CmsAsset;
use skeeks\cms\base\Module;
use skeeks\cms\helpers\ComposerHelper;
use skeeks\cms\helpers\FileHelper;
use skeeks\cms\models\CmsExtension;
use skeeks\cms\models\CmsLang;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\Site;
use skeeks\cms\models\Tree;
use skeeks\cms\models\TreeType;
use skeeks\cms\modules\admin\widgets\formInputs\OneImage;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeBool;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeElement;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeFile;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeListMulti;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeNumber;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeRadioList;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeSelect;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeSelectMulti;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeStorageFile;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeString;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeText;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeTextarea;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeTextInput;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeTree;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeColor;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeComboText;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeDate;
use skeeks\cms\relatedProperties\userPropertyTypes\UserPropertyTypeSelectFile;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use Yii;
use yii\base\Event;
use yii\base\InvalidParamException;
use yii\console\Application;
use yii\helpers\ArrayHelper;
use yii\web\UserEvent;
use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * @property CmsSite                          $site
 * @property Tree                             $currentTree
 *
 * @property CmsLang[]                        $languages
 *
 * @property string                           $version
 * @property string                           $homePage
 * @property string                           $cmsName
 *
 * @property \skeeks\cms\modules\admin\Module $moduleAdmin
 * @property \skeeks\cms\Module               $moduleCms
 * @property CmsLang                          $cmsLanguage
 * @property PropertyType[]                   $relatedHandlers
 * @property array                            $relatedHandlersDataForSelect
 *
 * @package skeeks\cms\components
 */
class Cms extends \skeeks\cms\base\Component
{
    /**
     * Разршение на доступ к персональной части
     */
    const UPA_PERMISSION = 'cms-upa-permission';

    const BOOL_Y = "Y";
    const BOOL_N = "N";

    private static $_huck = 'Z2VuZXJhdG9y';
    /**
     * @var string E-Mail администратора сайта (отправитель по умолчанию).
     */
    public $adminEmail = 'admin@skeeks.com';
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
    public $registerRoles = [
        CmsManager::ROLE_USER,
    ];

    /**
     * Авторизация на сайте разрешена только с проверенными email
     * @var bool
     */
    public $auth_only_email_is_approved = 0;


    //После регистрации пользователю будут присвоены эти роли
    /**
     * @var string язык по умолчанию
     */
    public $languageCode = "";
    /**
     * @var int Reset password token one hour later
     */
    public $passwordResetTokenExpire = 3600;
    /**
     * @var int
     */
    public $tree_max_code_length = 64;
    /**
     * @var int
     */
    public $element_max_code_length = 128;

    /**
     * Время последней активности когда считается что пользователь онлайн
     * @var int
     */
    public $userOnlineTime = 60; //1 минута

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
                    '@frontend/runtime',
                    '@console/runtime',
                ],

            'assets' =>
                [
                    '@frontend/web/assets',
                ],
        ];
    protected $_languages = null;
    /**
     * @var Tree
     */
    protected $_tree = null;
    private $_relatedHandlers = [];


    public function renderConfigForm(ActiveForm $form)
    {
        echo \Yii::$app->view->renderFile(__DIR__.'/cms/_form.php', [
            'form'  => $form,
            'model' => $this,
        ], $this);
    }
    /**
     * @return CmsSite
     */
    public function getSite()
    {
        return \Yii::$app->currentSite->site;
    }
    public function getLanguages()
    {
        if ($this->_languages === null) {
            $this->_languages = CmsLang::find()->active()->indexBy('code')->all();
        }

        return (array)$this->_languages;
    }
    public function init()
    {
        parent::init();

        //Название проекта.
        if (!$this->appName) {
            $this->appName = \Yii::$app->name;
        } else {
            \Yii::$app->name = $this->appName;
        }

        //Язык
        if ($this->languageCode) {
            \Yii::$app->language = $this->languageCode;
        } else {
            $this->languageCode = \Yii::$app->language;
        }

        $this->relatedHandlers = ArrayHelper::merge([
            PropertyTypeText::className()   => [
                'class' => PropertyTypeText::className(),
            ],
            PropertyTypeNumber::className() => [
                'class' => PropertyTypeNumber::className(),
            ],
            PropertyTypeBool::className()   => [
                'class' => PropertyTypeBool::className(),
            ],
            PropertyTypeList::className()   => [
                'class' => PropertyTypeList::className(),
            ],

            PropertyTypeTree::className()           => [
                'class' => PropertyTypeTree::className(),
            ],
            PropertyTypeElement::className()        => [
                'class' => PropertyTypeElement::className(),
            ],
            PropertyTypeStorageFile::className()    => [
                'class' => PropertyTypeStorageFile::className(),
            ],
            UserPropertyTypeDate::className()       => [
                'class' => UserPropertyTypeDate::className(),
            ],
            UserPropertyTypeComboText::className()  => [
                'class' => UserPropertyTypeComboText::className(),
            ],
            UserPropertyTypeColor::className()      => [
                'class' => UserPropertyTypeColor::className(),
            ],
            UserPropertyTypeSelectFile::className() => [
                'class' => UserPropertyTypeSelectFile::className(),
            ],

        ], $this->relatedHandlers);

        if (\Yii::$app instanceof Application) {

        } else {


            //web init
            if (!$this->noImageUrl) {
                $this->noImageUrl = CmsAsset::getAssetUrl('img/image-not-found.jpg');
            }

            \Yii::$app->view->on(View::EVENT_BEGIN_PAGE, function (Event $e) {
                if (!\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
                    \Yii::$app->response->getHeaders()->setDefault('X-Powered-CMS',
                        $this->cmsName." {$this->homePage}");

                    /**
                     * @var $view View
                     */
                    $view = $e->sender;
                    if (!isset($view->metaTags[self::$_huck])) {
                        $view->registerMetaTag([
                            "name"    => base64_decode(self::$_huck),
                            "content" => $this->cmsName." — {$this->homePage}",
                        ], self::$_huck);
                    }

                    if (!isset($view->metaTags['cmsmagazine'])) {
                        $view->registerMetaTag([
                            "name"    => 'cmsmagazine',
                            "content" => "7170fe3a42c6f80cd95fd8bce765333d",
                        ], 'cmsmagazine');
                    }
                }
            });

            \Yii::$app->user->on(\yii\web\User::EVENT_AFTER_LOGIN, function (UserEvent $e) {
                $e->identity->logged_at = \Yii::$app->formatter->asTimestamp(time());
                $e->identity->save(false);

                if (\Yii::$app->admin->requestIsAdmin) {
                    \Yii::$app->user->identity->updateLastAdminActivity();
                }
            });
        }
    }
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['adminEmail', 'noImageUrl', 'appName', 'languageCode'], 'string'],
            [['adminEmail'], 'email'],
            [['adminEmail'], 'email'],
            [['registerRoles'], 'safe'],
            [['tree_max_code_length'], 'integer'],
            [['element_max_code_length'], 'integer'],
            [['auth_only_email_is_approved'], 'integer'],
        ]);
    }
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'adminEmail'                  => 'Основной Email Администратора сайта',
            'noImageUrl'                  => 'Изображение заглушка',
            'appName'                     => 'Название проекта',
            'languageCode'                => 'Язык по умолчанию',
            'registerRoles'               => 'При регистрации добавлять в группу',
            'tree_max_code_length'        => 'Максимальная длинна кода (url) разделов',
            'element_max_code_length'     => 'Максимальная длинна кода (url) элементов',
            'auth_only_email_is_approved' => 'Разрешить авторизацию на сайте только с подтвержденными email?',
        ]);
    }
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'adminEmail'                  => 'E-Mail администратора сайта. Этот email будет отображаться как отправитель, в отправленных письмах с сайта.',
            'noImageUrl'                  => 'Это изображение показывается в тех случаях, когда не найдено основное.',
            'registerRoles'               => 'Так же после созданию пользователя, ему будут назначены, выбранные группы.',
            'auth_only_email_is_approved' => 'Если эта опция включена то пользователь, который не подтвердил свой email не сможет авторизоваться на сайте.',
        ]);
    }
    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'template' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [
                    'appName',
                    'adminEmail',
                    'noImageUrl' => [
                        'class'       => WidgetField::class,
                        'widgetClass' => OneImage::class,
                    ],
                ],
            ],

            'lang' => [
                'class'  => FieldSet::class,
                'name'   => 'Языковые настройки',
                'fields' => [
                    'languageCode' => [
                        'class' => SelectField::class,
                        'items' => \yii\helpers\ArrayHelper::map(
                            \skeeks\cms\models\CmsLang::find()->active()->all(),
                            'code',
                            'name'
                        ),
                    ],
                ],
            ],

            'auth' => [
                'class'  => FieldSet::class,
                'name'   => 'Авторизация',
                'fields' => [
                    'registerRoles'               => [
                        'class'    => SelectField::class,
                        'multiple' => true,
                        'items'    => \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description'),
                    ],
                    'auth_only_email_is_approved' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                ],
            ],

            'section' => [
                'class'  => FieldSet::class,
                'name'   => 'Разделы',
                'fields' => [
                    'tree_max_code_length',
                ],
            ],

            'element' => [
                'class'  => FieldSet::class,
                'name'   => 'Элементы',
                'fields' => [
                    'element_max_code_length',
                ],
            ],

            'access' => [
                'class'  => FieldSet::class,
                'name'   => 'Доступ',
                'fields' => [
                    'access' => [
                        'class'   => HtmlBlock::class,
                        'content' => function (HtmlBlock $htmlBlock) {
                            $content = \yii\bootstrap\Alert::widget([
                                'options' => [
                                    'class' => 'alert-warning',
                                ],
                                'body'    => "<b>Внимание!</b> Права доступа сохраняются в режиме реального времени. Так же эти настройки не зависят от сайта или пользователя.",
                            ]);

                            $content .= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
                                'content' => "Файлы",
                            ]);
                            $content .= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
                                'permissionName' => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                                'label'          => 'Доступ к личным файлам',
                            ]);
                            $content .= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
                                'permissionName' => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
                                'label'          => 'Доступ к общим файлам',
                            ]);
                            $content .= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
                                'permissionName' => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES,
                                'label'          => 'Доступ ко всем файлам',
                            ]);

                            return $content;
                        },
                    ],
                ],
            ],

        ];
    }
    /**
     * @return string
     */
    public function logo()
    {
        return 'data:image/png;base64,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';
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
     * @return bool
     * @deprecated
     */
    public function generateTmpConfig()
    {
        $configs = FileHelper::findExtensionsFiles(['/config/main.php']);
        $configs = array_unique(array_merge(
            [
                \Yii::getAlias('@skeeks/cms/config/main.php'),
            ], $configs
        ));

        $result = [];
        foreach ($configs as $filePath) {
            $fileData = (array)include $filePath;
            $result = \yii\helpers\ArrayHelper::merge($result, $fileData);
        }

        if (!file_exists(dirname(TMP_CONFIG_FILE_EXTENSIONS))) {
            mkdir(dirname(TMP_CONFIG_FILE_EXTENSIONS), 0777, true);
        }

        $string = var_export($result, true);
        file_put_contents(TMP_CONFIG_FILE_EXTENSIONS, "<?php\n\nreturn $string;\n");

        // invalidate opcache of extensions.php if exists
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(TMP_CONFIG_FILE_EXTENSIONS, true);
        }

        return file_exists(TMP_CONFIG_FILE_EXTENSIONS);
    }
    /**
     * Да/нет
     * @return array
     */
    public function booleanFormat()
    {
        return [
            self::BOOL_Y => Yii::t('yii', 'Yes', [], \Yii::$app->formatter->locale),
            self::BOOL_N => Yii::t('yii', 'No', [], \Yii::$app->formatter->locale),
        ];
    }
    /**
     * @return array|null|CmsLang
     */
    public function getCmsLanguage()
    {
        return CmsLang::find()->where(['code' => \Yii::$app->language])->one();
    }
    /**
     * @return array
     */
    public function getRelatedHandlersDataForSelect()
    {
        $baseTypes = [];
        $userTypes = [];
        if ($this->relatedHandlers) {
            foreach ($this->relatedHandlers as $id => $handler) {
                if ($handler instanceof PropertyTypeBool || $handler instanceof PropertyTypeText || $handler instanceof PropertyTypeNumber || $handler instanceof PropertyTypeList
                    || $handler instanceof PropertyTypeFile || $handler instanceof PropertyTypeTree || $handler instanceof PropertyTypeElement || $handler instanceof PropertyTypeStorageFile
                ) {
                    $baseTypes[$handler->id] = $handler->name;
                } else {
                    $userTypes[$handler->id] = $handler->name;
                }
            }
        }

        return [
            \Yii::t('skeeks/cms', 'Base types')   => $baseTypes,
            \Yii::t('skeeks/cms', 'Custom types') => $userTypes,
        ];
    }
    /**
     * @return PropertyType[] list of handlers.
     */
    public function getRelatedHandlers()
    {
        $handlers = [];
        foreach ($this->_relatedHandlers as $id => $handler) {
            $handlers[$id] = $this->getRelatedHandler($id);
        }

        return $handlers;
    }
    /**
     * @param array $handlers list of handlers
     */
    public function setRelatedHandlers(array $handlers)
    {
        $this->_relatedHandlers = $handlers;
    }
    /**
     * @param string $id service id.
     * @return PropertyType auth client instance.
     * @throws InvalidParamException on non existing client request.
     */
    public function getRelatedHandler($id)
    {
        if (!array_key_exists($id, $this->_relatedHandlers)) {
            throw new InvalidParamException("Unknown auth property type '{$id}'.");
        }
        if (!is_object($this->_relatedHandlers[$id])) {
            $this->_relatedHandlers[$id] = $this->createRelatedHandler($id, $this->_relatedHandlers[$id]);
        }

        return $this->_relatedHandlers[$id];
    }
    /**
     * Creates auth client instance from its array configuration.
     * @param string $id auth client id.
     * @param array  $config auth client instance configuration.
     * @return PropertyType auth client instance.
     */
    protected function createRelatedHandler($id, $config)
    {
        $config['id'] = $id;

        return \Yii::createObject($config);
    }
    /**
     * Checks if client exists in the hub.
     * @param string $id client id.
     * @return boolean whether client exist.
     */
    public function hasRelatedHandler($id)
    {
        return array_key_exists($id, $this->_relatedHandlers);
    }


    /**
     * @return string
     */
    public function getVersion()
    {
        return (string)ArrayHelper::getValue(\Yii::$app->extensions, 'skeeks/cms.version');
    }

    /**
     * @return string
     */
    public function getHomePage()
    {
        return "https://cms.skeeks.com";
    }
    /**
     * @return string
     */
    public function getCmsName()
    {
        return "SkeekS CMS";
    }
}