<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.03.2015
 */
namespace skeeks\cms\components;
use skeeks\cms\assets\CmsToolbarAssets;
use skeeks\cms\helpers\UrlHelper;
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
     * Returns Yii logo ready to use in `<img src="`
     *
     * @return string base64 representation of the image
     */
    public static function getSkeeksLogo()
    {
        return 'data:image/png;base64,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';
    }

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

        $clientOptions = [
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
                    <img width="29" height="30" alt="" src="{$this->getSkeeksLogo()}">
                     <span class="label">{$version}</span>
                </a>
            </div>

            <div class="skeeks-cms-toolbar-block">
                <a href="{$adminUrl}" title="Перейти в панель администрирования"><span class="label label-info">Администрирование</span></a>
            </div>

            <div class="skeeks-cms-toolbar-block">
                <a href="{$adminUrl}" title="Перейти в панель администрирования"><img height="30" src="{$src}"/><span class="label label-info">{$username}</span></a>
            </div>

            <span class="skeeks-cms-toolbar-toggler" onclick="sx.Toolbar.close(); return false;">›</span>
        </div>

        <div id="skeeks-cms-toolbar-min">
            <a href="#" onclick="sx.Toolbar.open(); return false;" title="Открыть панель управления SkeekS Cms" id="skeeks-cms-toolbar-logo">
                <img width="29" height="30" alt="" src="{$this->getSkeeksLogo()}">
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
        if (\Yii::$app->user->can('cms.admin-access'))
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