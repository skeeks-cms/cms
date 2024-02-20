CHANGELOG
==============

6.2.0
-----------------
 * Обновление сохраненных фильтров
 * CMS требует для работы модуль магазина cms-shop

6.1.1
-----------------
 * Добавлен справочник стран (CmsCountry model)

6.1.0
-----------------
 * Обновление механизма EAV

6.0.3
-----------------
 * Настройка контента, выбор тип контента - не обязателен

6.0.2
-----------------
 * EAV update 
 * NOT to use $model->getRelatedProperties()->all()
 * NEED to use $model->relatedProperties;
 * \skeeks\cms\models\CmsContentElement::find() to use ->with('relatedElementProperties') for optimize

6.0.1
-----------------
 * New ajax file uploader

6.0.0
-----------------
 * Update project structure
 * Yii 3 yii/config




5.5.11
-----------------
 * Yii 2.0.24
 
5.5.10
-----------------
 * Yii 2.0.23
 
5.5.8
-----------------
 * Add main domain
 
5.5.7
-----------------
 * Cms content element is_count_views
 * Delete child elements updated
 
5.5.5
-----------------
 * Update ajax submit forms
 * Optimisations query RP

5.5.4.2
-----------------
 * Fixed CmsContentElement redirect
 * Fixed CmsLang is_active
 * Component cache key dependency lang
 
5.5.4.1
-----------------
 * CmsLang removed is_default
 
5.5.4
-----------------
 * CmsLang removed def
 * CmsLang removed active
 * CmsLang added boolean is_default
 * CmsLang added boolean is_active
 
5.5.3
-----------------
 * В настройках компонентов, можно использовать сложные формы с логикой 'elementOptions' => [ 'data' => [ 'form-reload' => 'true', ], ],
 * Валидация code RelatedProperty теперь допускает "_" 
 * Removed I18N 
 
5.5.2.1
-----------------
 * Fixed edit blocks
 
5.5.2
-----------------
 * Update "giggsey/libphonenumber-for-php": "8.8.11 || ^8.10.8"
 * Update elfinder
 * Fixed display none important
 
5.5.1
-----------------
 * Dependency update
 
5.5.0
-----------------
 * Yii 2.0.17 updated
 
5.4.0
-----------------
 * Update cms widgets
 
5.3.2
-----------------
 * Yii 2.0.16.1
 
5.3.1
-----------------
 * Fixed password change
 * Upa permission added
 
5.3.0
-----------------
 * RelatedPropertiesModel::getAttributeAsHtml($name) is new method
 * RelatedPropertiesModel::getAttributeAsText($name) is new method
 * RelatedPropertiesModel::getSmartAttribute($name) is depricated!
 * PropertyType::getAsHtml() is new method
 * PropertyType::getAsText() is new method
 * PropertyType::getStringValue() is depricated!
 * HasStorageFileMulti::relations — change default data!
 * Added ability to use files in advanced properties 
 * Updated additional properties mechanism 

5.2.11
-----------------
 * skeeks\cms\models\Tree - new method getActiveChildren();
 
5.2.10
-----------------
 * Fixed rbac

5.2.9
-----------------
 * yii2 v2.0.16
 
5.2.8
-----------------
 * New UserOnlineTriggerWidget
 * New UserOnlineWidget
 
5.2.7
-----------------
 * Fixed table exist
 
5.2.6
-----------------
 * Fixed PHP Fatal error:  Cannot use yii\base\Object as Object because 'Object' is a special class name in /.../vendor/skeeks/cms/src/behaviors/RelationalBehavior.php on line 15
 
5.2.5
-----------------
 * On CmsUser model new method findByAuthAssignments()
 
5.2.4
-----------------
 * Update icons to fontawesome for bootstrap4
 
5.2.3.1
----------------
  * Fixed
  
5.2.3
----------------
 * Fixed component settings form
 * Update email templates
 
5.2.2
----------------
 * Fixed https://github.com/skeeks-cms/cms/pull/118
 
5.2.1.1
----------------
 * Update
 
5.2.1
----------------
 * Fixed admin cms content elements
 
5.2.0
----------------
 * Updated
 
5.1.4
----------------
 * Related properties model lazy load fixed
 
5.1.3
----------------
 * Related properties model lazy load
 
5.1.2
----------------
 * Grid CSV export
 
5.1.1
----------------
 * Fixed RelationalBehavior
 
5.1.0
----------------
 * Update skeeks/yii2-sx 1.4.0
 * Update yii2 2.0.15
 * IHasInfo is deprecated
 * Fixed create new dir
 * ClusterLocal update set publicBaseUrl and rootBasePath
 * Removed skeeks\cms\Exception
 * Widget and component settings to use skeeks/yii2-form
 * Change config form interface


5.0.4
----------------
 * Fixed notices
 * Fixed create admin tree type
 * Change children elements logic
 * Cms user default username limit(1)
 * Fixed admin select tree
 
5.0.3
----------------
 * Fixed admin user search
 
5.0.2
----------------
 * Copy elements and move sections
 * "skeeks/yii2-form": "dev-master@dev"
 * Link on page with item 
 
5.0.1
----------------
 * Image and files sort
 * Ckeditor select files
 
5.0.0
----------------
 * Fixed migration
 
5.0.0-beta5
----------------
 * Fixed save element properties
 * Content elements redirrect is fixed
 * Cms user personal area
 
5.0.0-beta4
----------------
 * Fixed admin select files
 * Add widget contextData
 
5.0.0-beta3
----------------
 * Yii 2.0.13.1
 
5.0.0-beta2
----------------
 * Fixed
 
5.0.0-beta
----------------
 * Fixed bug with saving multiple section selection.
 
5.0.0-alpha14
----------------
 * defined('ENV') or define('ENV', YII_ENV);
 
5.0.0-alpha13
----------------
 * Fixed
 
5.0.0-alpha12
----------------
 * Fixed Class 'skeeks\cms\relatedProperties\propertyTypes\PropertyTypeStorageFile' not found
 
5.0.0-alpha11
----------------
 * @bower to console app
 * Code style
 * Travis
 
5.0.0-alpha10
----------------
 * Fixed setting
 * Code style
 
5.0.0-alpha9
---------------
 * Fixed user displayName
 * New logo

5.0.0-alpha8
---------------
 * skeeks/cms-rbac ~2.2.0
 
5.0.0-alpha7
---------------
 * Change configuration authManager
 
5.0.0-alpha6
---------------
 * Renamed viewFile to view_file
 * Fixed
 
5.0.0-alpha5
---------------
 * Fixed of pgsql support
 
5.0.0-alpha4
---------------
 * Start of pgsql support

5.0.0-alpha3
---------------
 * Code style
 
5.0.0-alpha2
---------------
 * Update marketplace
 * Update agents
 * Change agents configuration
 * Use skeeks/cms-toolbar
 * Removed skeeks/cms/components/CmsToolbar
 * Fixed filters
 * Rebuild config profiling
 
5.0.0-alpha
---------------
 * Removed skeeks\cms\composer. Use skeeks/cms-composer.
 * Added default configs
 * Fixed composer versions
 * Removed APP_ENV_CONFIG_DIR
 * Removed APP_CONFIG_DIR
 * Removed COMMON_ENV_CONFIG_DIR
 * Removed COMMON_CONFIG_DIR
 * Removed console cmd cms/cache/flush-tmp-config
 * Removed Installer postUpdate
 * Removed Installer postInstall
 * Update Installer plugin
 * Removed \Yii::$app->cms->generateTmpConsoleConfig();
 * Full transition to the use of the component hiqdev/composer-config-plugin
 * Loss of compatibility with older components
 
4.0.4
---------------
 * Used https://github.com/skeeks-semenov/yii2-ya-slug.
 * Removed slug behavior https://github.com/skeeks-semenov/yii2-slug-behavior.
 * The ability to filter content properties by type
 
4.0.3.1
---------------
 * Fixed
 
4.0.3
---------------
 * Using hiqdev/composer-config-plugin
 * GenerateTmpConfigs is deprecated

4.0.2
---------------
 * Removed SeoPageName Behavior. Now used https://github.com/skeeks-semenov/yii2-slug-behavior.
 * Update to yii 2.0.13
 
4.0.1
---------------
 * Pjax backend errors hide by default
 * Change user name to first and last names
 
4.0.0.1
---------------
 * Tree property update
 * Filter by property
 * Create index m170922_023840__alter_table__cms_content_element_property
 * Update console cms/utils/clear-all-thumbnails
 * Fixed admin
 * New console cmd for delete content elements
 * Admin tree list controller
 
4.0.0
---------------
 * Fixed
 
4.0.0-rc9
---------------
 * Fixed
 
4.0.0-rc8
---------------
 * Fixed
 
4.0.0-rc7
---------------
 * Fixed
 
4.0.0-rc6
---------------
 * Fixed
 
4.0.0-rc5
---------------
 * Update
 
4.0.0-rc4
---------------
 * Update
 
4.0.0-rc3
---------------
 * Update
 
4.0.0-rc2
---------------
 * Fixed
 
4.0.0-rc
---------------
 * Up
 
4.0.0-beta3
---------------
 * Fixed errors
 
4.0.0-beta2
---------------
 * Fixed composer.json
 
4.0.0-beta
---------------
 * Dialog select elements
 
4.0.0-alpha13
---------------
 * mysq 5.7 migrate update
 
4.0.0-alpha12
---------------
 * @bower
 * Removed todos
 
4.0.0-alpha11
---------------
 * Fixed bugs

4.0.0-alpha10
---------------
 * Composer installer update
 
4.0.0-alpha9
---------------
 * Composer installer update
 * Translate
 
4.0.0-alpha8
---------------
 * Image not found http exception
 
4.0.0-alpha7
---------------
 * Enabled url normalizer by default
 
4.0.0-alpha6
---------------
 * Fixed TreeMenuCmsWidget
 
4.0.0-alpha5
---------------
 * Fixed
 
4.0.0-alpha4
---------------
 * Fixed admin php info url

4.0.0-alpha3
---------------
 * Range values
 * New related value types
 * Deleted user restrict
 * Add Properties of elements in tree
 * Change the form of editing elements
 
4.0.0-alpha2
---------------
 * new rp boolean value
 * drop list_type
 
4.0.0-alpha1
---------------
 * Fixed select file
 * Fixed rp
 
4.0.0-alpha
---------------
 * Using paulzi/yii2-auto-tree
 * Removed skeeks\cms\base\CheckComponent
 * Removed skeeks\cms\traits\ValidateRulesTrait
 * Removed skeeks\cms\Config
 * Change code folder /src
 * Url changes


### https://github.com/skeeks-cms/cms/blob/master/changelogs/CHANGELOG-V1.md
### https://github.com/skeeks-cms/cms/blob/master/changelogs/CHANGELOG-V2.md
### https://github.com/skeeks-cms/cms/blob/master/changelogs/CHANGELOG-V3.md
