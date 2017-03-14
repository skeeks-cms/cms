CHANGELOG
==============

3.2.3
-----------------
 * Fancybox return v2.*
 * Fixed admin url
 * Fixed fancybox
 * Fixed login url
 * Uploads files for new not saved models (change \skeeks\cms\models\behaviors\HasStorageFile)

3.2.2.2
-----------------
 * Fixed fancybox
 * Url changes
 * ContentElementsCmsWidget new setting isJoinTreeMap
 * allowDeselect PropertyTypeElement
 * Documentation about working with the url

3.2.2.1
-----------------
 * Add docs
 * Fixed admin js
 * Fixed admin urls

3.2.2
-----------------
 * Removed \skeeks\cms\widgets\rbac\PermissionForRoles
 * Go to https
 * CmsContentElement added ->cmsTrees

3.2.1
-----------------
 * Updated CmsContentElementWidget
 * Updated TreeMenuCmsWidget
 * Showing links in the tree
 * Fixed admin tree
 * Fixed image urls
 * Url Rewrites
 * CmsSite added property rootCmsTree

3.2.0
-----------------
 * Delete _all dir
 * Updated cluster local
 * Good improvement widgets allows you to use begin() end() design
 * Completion form controls
 * Created skeeks\cms\widgets\Pjax Widget
 * Complete rebuilding of sections of the tree widget
 * Correction of translations
 * Update urls
 * Updated seo filter behavior
 * Completion widgets upload files
 * Update HasTrees
 * Tree url update

3.1.3
-----------------
 * Batch control properties

3.1.2
-----------------
 * Fixed a bug when editing widgets with big data.
 * Updated HasTree behavior

3.1.1.3
-----------------
 * Search for additional user properties

3.1.1.2
-----------------
 * Change log level

3.1.1.1
-----------------
 * Change related properties saver
 * CmsContentElementWidget add options

3.1.1
-----------------
 * Phone validator translate message
 * Fixed bug admin edit settings form
 * Fixed #38 [https://github.com/skeeks-cms/cms/issues/38] (Additional properties of content items)
 * Fixed #35 [https://github.com/skeeks-cms/cms/issues/35] (Errors in JavaScript code in the behavior of the widget in the admin table)
 * Fixed #37 [https://github.com/skeeks-cms/cms/issues/37] (A bug in the password change)
 * Fixed #32 [https://github.com/skeeks-cms/cms/issues/32] (Solving problems for value_enum and value_num on save additional properties.)

3.1.0.1
-----------------
 * Fixed bugs

3.1.0
-----------------
 * Fixed https://github.com/skeeks-cms/cms/issues/31
 * Changing the mechanism of additional properties \Yii::$app->cms->relatedHandlers
 * The custom properties changes
 * Translation updates
 * Fixed https://github.com/skeeks-cms/cms-admin/issues/2

3.0.2
-----------------
 * UrlManager configuration for the console application
 * i18n logs

3.0.1.8
-----------------
 * Add site and lang filters
 * Fixed search element
 * Fixed css for toolbar
 * Fixed bug

3.0.1.7
-----------------
 * Fixed bug

3.0.1.6
-----------------
 * Fixed admin urls
 * Change admin urls

3.0.1.5
-----------------
 * Fixed notices

3.0.1.4
-----------------
 * Tree full width

3.0.1.3
-----------------
 * Fixed sort grid
 * Fixed notices

3.0.1.2
-----------------
 * Fixed notices

3.0.1.1
-----------------
 * Added handling of absence js and css files
 * Generate username

3.0.1
-----------------
 * Removed UserAuthClient
 * Finalization of users
 * User admin grid
 * Fixed register bug
 * Fixed bug. http://prntscr.com/bdg9n5

3.0.0.1
-----------------
 * Fixed bug. Edit view files

3.0.0
-----------------
 * Stable release

3.0.0-rc2
-----------------
 * Revision filters
 * Fixed bugs

3.0.0-rc1
-----------------
 * Revision filters
 * Loading forms with default values
 * Adding filters in admin
 * Changes in compoWidget
 * Added phone Mask
 * Fixed admin bug
 * Fixed a serious bug

3.0.0-beta4
-----------------
 * mihaildev/yii2-elfinder: 1.1.3

3.0.0-beta3
-----------------
 * Changes in UrlHelper::construct();

3.0.0-beta2
-----------------
 * Updated CmsAccessControl

3.0.0-beta
-----------------
 * Fixed a critical bug for downloading files by unauthorized users
 * Removed @admin/views

3.0.0-alpha2
-----------------
 * Fix bugs cluster local
 * Changed dependence composer

3.0.0-alpha1
-----------------
 * Rewrote the logic configuration files download
 * To use \Yii::$app->langugae
 * Removed rbac. In a separate package skeeks/cms-rbac
 * Removed skeeks\cms\helpers\Request
 * Begain using kartik-v/yii2-datecontrol
 * Changed menu
 * Removed ssh. In a separate package skeeks/cms-ssh-console
 * Removed yiisoft/yii2-swiftmailer dependency
 * Removed skeeks/cms/base/Module
 * Removed dbDump. In a separate package skeeks/cms-db-dumper
 * Removed marketplace. In a separate package skeeks/cms-marketplace
 * Cancel use cms base controller
 * Fixed email submit and templates
 * Removed mailer. In a separate package skeeks/cms-mailer
 * Fixed set unsafe attributes in the base component
 * Removed global const BACKUP_DIR
 * Removed global const COMMON_RUNTIME_DIR
 * Removed global const APP_DIR
 * Removed global const SKEEKS_DIR
 * Removed global const GETENV_POSSIBLE_NAMES
 * Removed global const ENABLED_MODULES_CONF
 * Removed mailer. In a separate package skeeks/cms-mailer
 * Removed Cms::moduleCms()
 * Removed Cms::moduleAdmin()
 * Removed templates. In a separate package skeeks/cms-view
 * Configs updated
 * Removed admin. In a separate package skeeks/cms-admin
 * Removed AdminController::EVENT_INIT
 * Renamed i18N component
 * Removed Cms::TRIGGER_AFTER_UPDATE
 * Removed agents. In a separate package skeeks/cms-agent
 * Removed class ConnectToDbForm
 * Removed class DbDsnHelper
 * Removed StatusColumn class
 * Fixed serious bug not allowing to use configuration files function. For example: on beforeRequest => function ($e){};
 * Removed captcha
 * Removed class skeeks\cms\base\console\Controller
 * Removed authclient. In a separate package skeeks/cms-authclient
 * Change component connect admin menu
 * Removed http auth
 * Removed DescriptionFullColumn, DescriptionShortColumn
 * Removed _ide/_back
 * Removed seo. In a separate package skeeks/cms-seo
 * Removed some settings in seo component
 * Rewritten classes urlRules

2.7.2-alpha2
-----------------
 * i18n update
 * Removed cmsSearch. In a separate package skeeks/cms-search
 * Remove the old properties of CMS components
 * Agent Management Settings moved to the config
 * \Yii::setAlias('template', '@app/views/'); is removed
 * Correcting typos

2.7.2.alpha
-----------------
 * Removed is deprecated
 * Rewritten cmsToolbar
 * Rewritten view actions
 * Removed I18NDb. In a separate package skeeks/cms-i18n-db
 * Disabled event ADMIN_READY
 * Updated translation functionality
 * Removed columns files_depricated in cms_tree and cms_content_element
 * Completely rewritten mechanism of personal user cabinet
 * Remove the old classes
 * Sitemap updated
 * Removed UserAction
 * Closed personal user profiles!
 * Removed skeeks\cms\models\TreeMenu
 * Rewrite admin actions
 * Removed skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelSystemAction

2.7.1.2
-----------------
 * Fixed column ids
 * Fixed user column data
 * Update log message group

2.7.1.1
-----------------
 * Fixed user edit bugs
 * Added the ability to configure the maximum and minimum display records in tables
 * Remote download link files option is enabled CURLOPT_FOLLOWLOCATION
 * Change filter element in content element grids

2.7.1
-----------------
 * Updated admin content elements in grids
 * Minimal user name length is increased
 * Adding a field in the user table of email and phone
 * Revert failed migrations
 * You can customize the grid AdminRelatedGridView
 * Disabled ajax response {test: test} to the sections and pages of content elements
 * Styling toolbar

2.7.0.3
-----------------
 * Fixed an important bug, an incorrect config cache, after the agent

2.7.0.2
-----------------
 * Fix upload errors
 * Disabling skeeks cms panels, at the time of launch and debug modules gii
 * Fix admin bugs
 * Drop user is depricated columns (city, address, info, files, status_of_life)
 * Drop restrict index in cms_storage_file

2.7.0.1
-----------------
 * Update smart content element filters
 * Fix bugs for windows

2.7.0
-----------------
 * It is ready

2.7.0.beta
-----------------
 * fix bugs

2.7.0.alpha
-----------------
 * Removed dependency yiisoft/yii2-gii
 * Removed dependency yiisoft/yii2-debug
 * Big refactoring
 * deleted references to class skeeks\cms\App
 * Added new dependency ifsnop/mysqldump-php
 * Removed skeeks\cms\components\GiiModule
 * Removed skeeks\cms\exceptions\NotConnectedToDbException
 * Removed skeeks\cms\base\Action
 * Removed skeeks\cms\base\Session
 * Removed skeeks\cms\base\DbSession
 * Removed skeeks\cms\components\CmsSettings
 * Removed skeeks\cms\console\controllers\ComposerController
 * Removed skeeks\cms\checks\MysqlDumpCheck
 * Removed skeeks\cms\checks\InstallScriptCheck
 * Removed skeeks\cms\checks\GitClientCheck
 * Major changes to work with the creation of the database dump and its recovery
 * AssetManager LinkAssets options by default false
 * Updated admin info
 * Fixed searchRelatedProperties
 * Added caching tree for multiselect
 * Added elements to favorites users
 * Removed dependency skeeks/yii2-kartik-markdown
 * Fixed a bug with the display of the content in the administrative part, an additional property with code properties

2.6.1
-----------------
 * Deleted is deprecated fields from cms_storage_file
 * Deleted class skeeks\cms\widgets\ModelStorageFileManager
 * Adding the priority clusters file storage
 * Fixed critical bug with memory consumption when displaying files in the widget select file
 * Optimized widget content items
 * Added option to obtain all the descendants of the section element http://en.cms.skeeks.com/docs/sections-tree
 * Fixed critical bug with memory consumption when displaying files in the repository. There was at moments showing a large number of elements.
 * Revision of validation of additional properties + added examples: http://en.cms.skeeks.com/docs/additional-properties-models
 * Caching data tree to build the select element
 * Revision the model related properties
 
### https://github.com/skeeks-cms/cms/blob/master/CHANGELOG-V2.md
### https://github.com/skeeks-cms/cms/blob/master/CHANGELOG-V1.md
