<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.03.2015
 */
namespace skeeks\cms\components;
use skeeks\cms\actions\ViewModelAction;
use skeeks\cms\assets\CmsToolbarAssets;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\rbac\CmsManager;
use yii\base\BootstrapInterface;
use yii\helpers\Json;
use yii\web\Application;
use yii\web\View;

use \Yii;

/**
 * Class CmsToolbar
 * @package skeeks\cms\components
 */
class CmsToolbar extends \skeeks\cms\base\Component implements BootstrapInterface
{
    /**
     * @var array the list of IPs that are allowed to access this module.
     * Each array element represents a single IP filter which can be either an IP address
     * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
     * The default value is `['127.0.0.1', '::1']`, which means the module can only be accessed
     * by localhost.
     */
    public $allowedIPs = ['*'];


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        // delay attaching event handler to the view component after it is fully configured
        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $app->getView()->on(View::EVENT_END_BODY, [$this, 'renderToolbar']);
        });
    }

    /**
     * Renders mini-toolbar at the end of page body.
     *
     * @param \yii\base\Event $event
     */
    public function renderToolbar($event)
    {
        if (!$this->checkAccess() || Yii::$app->getRequest()->getIsAjax()) {
            return;
        }

        $version = \Yii::$app->cms->moduleCms()->getDescriptor()->getVersion();
        $homePage = \Yii::$app->cms->moduleCms()->getDescriptor()->homepage;

        $adminUrl = UrlHelper::construct('')->enableAdmin()->toString();
        $src = \Yii::$app->cms->getAuthUser()->getAvatarSrc();
        $username = \Yii::$app->cms->getAuthUser()->username;
        $logoSrc = \Yii::$app->cms->logo();
        $profileEditUrl = UrlHelper::construct('cms/admin-profile/update')->enableAdmin();

        $actionEdit = '';


        if (is_subclass_of(\Yii::$app->controller->action, ViewModelAction::className()))
        {
            if ($model = \Yii::$app->controller->action->getModel())
            {

                if ($descriptor = \Yii::$app->registeredModels->getDescriptor($model->className()))
                {
                    if ($descriptor->adminControllerRoute)
                    {
                        $urlEdit = UrlHelper::construct($descriptor->adminControllerRoute . '/update', ['id' => $model->id])->enableAdmin()
                            ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                            //->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL, 'true')
                            ;

                        \Yii::$app->view->registerJs(<<<JS
    sx.classes.ModelEdit = sx.classes.Component.extend({

        _init: function()
        {
            this.window = new sx.classes.Window(this.get('update-url'));
            this.window.bind('close', function()
            {
                //sx.notify.info('Страница сейчас будет перезагружена');

                _.defer(function()
                {
                     window.location.reload();
                });
            });

            this.window.open();
        },

        _onDomReady: function()
        {},

        _onWindowReady: function()
        {}
    });
JS
);
                        $actionEdit = <<<HTML
                        <div class="skeeks-cms-toolbar-block">
                            <a href="{$urlEdit}" onclick="new sx.classes.ModelEdit({'update-url' : '{$urlEdit}'}); return false;" title="Редактировать">
                                 <span class="label">Редактировать</span>
                            </a>
                        </div>
HTML;
                    }

                }
            }


        }


        $clientOptions = [
            'logo-src'          => \Yii::$app->cms->logo(),
            'cms-version'       => $version,
            'cms-link'          => $homePage,
            'container-id'      => 'skeeks-cms-toolbar',
            'container-min-id'  => 'skeeks-cms-toolbar-min',
        ];

        $clientOptionsJson = Json::encode($clientOptions);



        echo <<<HTML

        <div id="skeeks-cms-toolbar" class="skeeks-cms-toolbar-top hidden-print">
            <div class="skeeks-cms-toolbar-block title">
                <a href="{$homePage}" title="Текущая версия SkeekS Cms {$version}" target="_blank">
                    <img width="29" height="30" alt="" src="{$logoSrc}">
                     <span class="label">{$version}</span>
                </a>
            </div>

            <div class="skeeks-cms-toolbar-block">
                <a href="{$adminUrl}" title="Перейти в панель администрирования"><span class="label label-info">Администрирование</span></a>
            </div>

            <div class="skeeks-cms-toolbar-block">
                <a href="{$profileEditUrl}" title="Это вы, перейти к редактированию свох данных"><img height="30" src="{$src}" style="margin-left: 5px;"/> <span class="label label-info">{$username}</span></a>
            </div>
            {$actionEdit}
            <span class="skeeks-cms-toolbar-toggler" onclick="sx.Toolbar.close(); return false;">›</span>
        </div>

        <div id="skeeks-cms-toolbar-min">
            <a href="#" onclick="sx.Toolbar.open(); return false;" title="Открыть панель управления SkeekS Cms" id="skeeks-cms-toolbar-logo">
                <img width="29" height="30" alt="" src="{$logoSrc}">
            </a>
            <span class="skeeks-cms-toolbar-toggler" onclick="sx.Toolbar.open(); return false;">‹</span>
        </div>
HTML;

        //echo '<div id="skeeks-cms-toolbar" style="display:none"></div>';

        /* @var $view View */
        $view = $event->sender;
        CmsToolbarAssets::register($view);

        $view->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.Toolbar = new sx.classes.SkeeksToolbar({$clientOptionsJson});
        })(sx, sx.$, sx._);
JS
);
    }

    /**
     * Checks if current user is allowed to access the module
     * @return boolean if access is granted
     */
    protected function checkAccess()
    {
        if (\Yii::$app->user->can(CmsManager::PERMISSION_ADMIN_ACCESS) && \Yii::$app->user->can(CmsManager::PERMISSION_CONTROLL_PANEL))
        {
            if (!\Yii::$app->cms->moduleAdmin()->requestIsAdmin())
            {
                return true;
            }
        }
        /*$ip = Yii::$app->getRequest()->getUserIP();

        foreach ($this->allowedIPs as $filter) {
            if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))) {
                return true;
            }
        }
        Yii::warning('Не разрешено запускать панель с этого ip ' . $ip, __METHOD__);*/
        return false;
    }
}