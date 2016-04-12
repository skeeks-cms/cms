<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.05.2015
 */
namespace skeeks\cms\modules\admin\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\events\AdminInitEvent;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
use skeeks\cms\modules\admin\filters\AccessRule;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\filters\AdminLastActivityAccessControl;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\rbac\CmsManager;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InlineAction;
use yii\base\Model;
use yii\base\Theme;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ForbiddenHttpException;

/**
 * @property string             $permissionName
 *
 * Class AdminController
 * @package skeeks\cms\modules\admin\controllers
 */
abstract class AdminController extends \yii\web\Controller
{
    const EVENT_INIT                    = 'event.adminController.init';

    const LAYOUT_EMPTY                  = 'main-empty';
    const LAYOUT_MAIN                   = 'main';

    /**
     * @var string Понятное название контроллера, будет добавлено в хлебные крошки и title страницы
     */
    public $name           = '';

    /**
     * The name of the privilege of access to this controller
     * @return string
     */
    public function getPermissionName()
    {
        return $this->getUniqueId();
    }

    /**
     * Проверка доступа к админке
     * @return array
     */
    public function behaviors()
    {
        return
        [
            //Проверка доступа к админ панели
            'adminAccess' =>
            [
                'class'         => AdminAccessControl::className(),
                'rules' =>
                [
                    [
                        'allow'         => true,
                        'roles'         =>
                        [
                            CmsManager::PERMISSION_ADMIN_ACCESS
                        ],
                    ],
                ]
            ],

            //Стандартная проверка доступности действия. Если действие заведено, в привилегиях, то проверяется наличие у пользователя
            'adminActionsAccess' =>
            [
                'class'         => AdminAccessControl::className(),
                'rules' =>
                [
                    [
                        'allow'         => true,
                        'matchCallback' => function($rule, $action)
                        {
                            if ($permission = \Yii::$app->authManager->getPermission($this->permissionName))
                            {
                                if (!\Yii::$app->user->can($permission->name))
                                {
                                    return false;
                                }
                            }

                            return true;
                        }
                    ]
                ],
            ],

            'adminLastActivityAccess' =>
            [
                'class'         => AdminLastActivityAccessControl::className(),
                'rules' =>
                [
                    [
                        'allow'         => true,
                        'matchCallback' => function($rule, $action)
                        {
                            if (\Yii::$app->user->identity->lastAdminActivityAgo > \Yii::$app->admin->blockedTime)
                            {
                                return false;
                            }

                            if (\Yii::$app->user->identity)
                            {
                                \Yii::$app->user->identity->updateLastAdminActivity();
                            }

                            return true;
                        }
                    ]
                ],
            ],
        ];
    }


    public function init()
    {
        parent::init();
        static::onceInit();

        if (!$this->name)
        {
            $this->name = \Yii::t('app','The name of the controller'); //Inflector::humanize($this->id);
        }

        \Yii::$app->trigger(self::EVENT_INIT, new AdminInitEvent([
            'name'          => self::EVENT_INIT,
            'controller'    => $this
        ]));
    }

    /**
     * @var bool
     */
    static private $_onceInit = false;

    /**
     * A one-time initialization of one scenario
     * @return bool
     */
    static public function onceInit()
    {
        if (self::$_onceInit === true)
        {
            return false;
        }

        \Yii::$app->cmsMarkeplace->info;


        if (\Yii::$app->cms->moduleAdmin->requestIsAdmin())
        {
            //TODO: Добавить возможность настройки
            \Yii::$app->view->theme = new Theme([
                'pathMap' =>
                [
                    '@app/views' =>
                    [
                        '@skeeks/cms/modules/admin/views',
                    ]
                ]
            ]);
        }

        //Если http авторизация на сайте отключена а в админке включена
        if (\Yii::$app->cms->enabledHttpAuth  == "N" && \Yii::$app->cms->enabledHttpAuthAdmin == "Y")
        {
            \Yii::$app->cms->executeHttpAuth();
        }

        self::$_onceInit = true;
    }

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $this->_initMetaData();
        $this->_initBreadcrumbsData();

        return parent::beforeAction($action);
    }

    /**
     * @return $this
     */
    protected function _initMetaData()
    {
        $data = [];
        $data[] = \Yii::$app->name;
        $data[] = $this->name;

        if ($this->action && property_exists($this->action, 'name'))
        {
            $data[] = $this->action->name;
        }
        $this->view->title = implode(" / ", $data);
        return $this;
    }

    /**
     * @return $this
     */
    protected function _initBreadcrumbsData()
    {
        $baseRoute = $this->module instanceof Application ? $this->id : ("/" . $this->module->id . "/" . $this->id);

        if ($this->name)
        {
            $this->view->params['breadcrumbs'][] = [
                'label' => $this->name,
                'url' => UrlHelper::constructCurrent()->setRoute($baseRoute. '/' . $this->defaultAction)->enableAdmin()->toString()
            ];
        }

        if ($this->action && property_exists($this->action, 'name'))
        {
             $this->view->params['breadcrumbs'][] = $this->action->name;
        }

        return $this;
    }
}