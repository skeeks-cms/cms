<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.03.2015
 */
namespace skeeks\cms\components;
use skeeks\cms\actions\ViewModelAction;
use skeeks\cms\assets\CmsToolbarAsset;
use skeeks\cms\assets\CmsToolbarAssets;
use skeeks\cms\assets\CmsToolbarFancyboxAsset;
use skeeks\cms\exceptions\NotConnectedToDbException;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\helpers\Tree;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\rbac\CmsManager;
use yii\base\BootstrapInterface;
use yii\base\ViewEvent;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
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
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Панель быстрого управления',
        ]);
    }

    /**
     * Файл с формой настроек, по умолчанию
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'cmsToolbar/_form.php';
    }


    /**
     * @var array the list of IPs that are allowed to access this module.
     * Each array element represents a single IP filter which can be either an IP address
     * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
     * The default value is `['127.0.0.1', '::1']`, which means the module can only be accessed
     * by localhost.
     */
    public $allowedIPs = ['*'];


    public $infoblocks = [];


    const EDIT_MODE     = 'edit';
    const NO_EDIT_MODE  = 'no-edit';

    public $mode                            = self::NO_EDIT_MODE;
    public $isOpen                          = Cms::BOOL_N;
    public $enabled                         = 1;
    public $enableFancyboxWindow            = 0;

    public $infoblockEditBorderColor        = "red";


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['mode', 'infoblockEditBorderColor', 'isOpen'], 'string'],
            [['enabled', 'enableFancyboxWindow'], 'integer'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'enabled'                       => 'Активность панели управления',
            'mode'                          => 'Режим редактирования',
            'isOpen'                        => 'Открыта',
            'enableFancyboxWindow'          => 'Включить диалоговые онка панели (Fancybox)',
            'infoblockEditBorderColor'      => 'Цвет рамки вокруг инфоблока',
        ]);
    }


    public $viewFiles = [];

    public function init()
    {
        parent::init();

        /*\Yii::$app->view->on(View::EVENT_AFTER_RENDER, function(ViewEvent $e)
        {
            if (\Yii::$app->cmsToolbar->isEditMode() && \Yii::$app->cmsToolbar->enabled)
            {
                $id = "sx-view-render-md5" . md5($e->viewFile);
                if (in_array($id, $this->viewFiles))
                {
                    return;
                }

                $this->viewFiles[$id] = $id;

                $e->sender->registerJs(<<<JS
new sx.classes.toolbar.EditViewBlock({'id' : '{$id}'});
JS
);
                $e->output = Html::tag('div', $e->output,
                [
                    'class'     => 'skeeks-cms-toolbar-edit-view-block',
                    'id'        => $id,
                    'title'     => "Двойной клик по блоку откроек окно управлния настройками",
                    'data'      =>
                    [
                        'id'            => $id,
                        'config-url'    => $e->viewFile
                    ]
                ]);
            }
        });*/
    }

    public function enableEditMode()
    {
        $userSettings           = CmsComponentSettings::createByComponentUserId($this, \Yii::$app->user->id);
        $userSettings->setSettingValue('mode', self::EDIT_MODE);

        if (!$userSettings->save())
        {
            $rr->message = 'Не удалось сохранить настройки';
            $rr->success = false;
            return $rr;
        }

        $this->invalidateCache();

        return $this;
    }

    public function disableEditMode()
    {
        $userSettings           = CmsComponentSettings::createByComponentUserId($this, \Yii::$app->user->id);
        $userSettings->setSettingValue('mode', self::NO_EDIT_MODE);

        if (!$userSettings->save())
        {
            $rr->message = 'Не удалось сохранить настройки';
            $rr->success = false;
            return $rr;
        }

        $this->invalidateCache();

        return $this;
    }

    /**
     * @return $this
     */
    public function triggerEditMode()
    {
        if ($this->isEditMode())
        {
            $this->disableEditMode();
        } else
        {
            $this->enableEditMode();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isEditMode()
    {
        return (bool) ($this->mode == self::EDIT_MODE);
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

    public $inited = false;

    /**
     * Установка проверок один раз.
     * Эти проверки могут быть запущены при отрисовке первого виджета.
     */
    public function initEnabled()
    {
        if ($this->inited)
        {
            return;
        }

        if (!$this->enabled)
        {
            return;
        }

        if (\Yii::$app->user->isGuest)
        {
            $this->enabled = false;
            return;
        }

        if (!$this->checkAccess() || Yii::$app->getRequest()->getIsAjax())
        {
            $this->enabled = false;
            return;
        }
    }

    /**
     * Renders mini-toolbar at the end of page body.
     *
     * @param \yii\base\Event $event
     */
    public function renderToolbar($event)
    {
        $this->initEnabled();

        if (!$this->enabled)
        {
            return;
        }

        $editModel = null;
        $urlEditModel = "";
        $urlUserEdit = UrlHelper::construct('cms/admin-profile/update')->enableAdmin()->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true');
        if (is_subclass_of(\Yii::$app->controller->action, ViewModelAction::className()))
        {
            if ($editModel = \Yii::$app->controller->action->model)
            {
                $adminControllerRoute = '';

                if ($editModel instanceof CmsContentElement)
                {
                    $adminControllerRoute = 'cms/admin-cms-content-element';
                } else if ($editModel instanceof \skeeks\cms\models\Tree)
                {
                    $adminControllerRoute = 'cms/admin-tree';
                } else if ($editModel instanceof User)
                {
                    $adminControllerRoute = 'cms/admin-user';
                }

                if ($adminControllerRoute)
                {

                    /**
                     * @var $controller AdminModelEditorController
                     */
                    $controller = \Yii::$app->createController($adminControllerRoute)[0];

                    $urlEditModel = UrlHelper::construct($adminControllerRoute . '/update', [$controller->requestPkParamName => $editModel->{$controller->modelPkAttribute}])->enableAdmin()
                        ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                        //->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL, 'true')
                        ;
                }

            }
        }


        $clientOptions = [
            'infoblockSettings'             => [
                'border' =>
                [
                    'color' => $this->infoblockEditBorderColor
                ]
            ],
            'container-id'                  => 'skeeks-cms-toolbar',
            'container-min-id'              => 'skeeks-cms-toolbar-min',
            'isOpen'                        => (bool) ($this->isOpen == Cms::BOOL_Y),
            'backend-url-triggerEditMode'   => UrlHelper::construct('cms/toolbar/trigger-edit-mode')->toString(),
            'backend-url-triggerIsOpen'     => UrlHelper::construct('cms/toolbar/trigger-is-open')->toString()
        ];

        //echo '<div id="skeeks-cms-toolbar" style="display:none"></div>';

        /* @var $view View */
        $view = $event->sender;
        CmsToolbarAsset::register($view);

        if ($this->enableFancyboxWindow)
        {
            CmsToolbarFancyboxAsset::register($view);
        }

        echo $view->render('@skeeks/cms/views/cms-toolbar', [
            'clientOptions'     => $clientOptions,
            'urlEditModel'      => $urlEditModel,
            'editModel'         => $editModel,
            'urlUserEdit'         => $urlUserEdit,
            'urlSettings'         => UrlHelper::construct('cms/admin-settings')->enableAdmin()->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
        ]);
    }

    /**
     * Checks if current user is allowed to access the module
     * @return boolean if access is granted
     */
    protected function checkAccess()
    {
        //\Yii::$app->user->can(CmsManager::PERMISSION_ADMIN_ACCESS) version > 2.0.13
        if (\Yii::$app->user->can(CmsManager::PERMISSION_CONTROLL_PANEL))
        {
            if (!\Yii::$app->cms->moduleAdmin()->requestIsAdmin() && (!\Yii::$app->controller instanceof AdminController) )
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