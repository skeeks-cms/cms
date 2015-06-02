<?php
/**
 * GridView
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.02.2015
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\components\Cms;
use skeeks\cms\grid\GridViewPjaxTrait;
use skeeks\cms\modules\admin\traits\GridViewSortableTrait;
use skeeks\cms\modules\admin\widgets\gridView\GridViewSettings;
use skeeks\cms\traits\HasComponentConfigFormTrait;
use skeeks\cms\traits\HasComponentDbSettingsTrait;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\jui\Sortable;
use yii\web\JsExpression;

/**
 * Расширенный грид, с настройками.
 *
 * @property GridViewSettings $settings
 *
 * Class GridView
 * @package skeeks\cms\modules\admin\widgets
 */
class GridViewHasSettings extends GridView
{
    /**
     * @var string the layout that determines how different sections of the list view should be organized.
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{errors}`: the filter model error summary. See [[renderErrors()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
     * - `{settings-btn}`: the settings. See [[renderSettings()]].
     */
    public $layout = "{summary}\n{items}\n<div class='pull-left'>{pager}</div><div class='pull-right'>{settings}</div>";

    /**
     * @var array
     */
    public $settingsData = [];

    public function init()
    {
        parent::init();

        $settingsData =
        [
            //namespace настроек по умолчанию.
            'namespace' => \Yii::$app->controller->action->getUniqueId()
        ];

        $settingsData       = ArrayHelper::merge($settingsData, (array) $this->settingsData);
        $this->_settings    = new GridViewSettings($settingsData);


        if ($this->settings->enabledPjaxPagination == Cms::BOOL_Y)
        {
            $this->enabledPjax = true;
        } else
        {
            $this->enabledPjax = false;
        }

        $this->initDataProvider();
    }

    /**
     * @var GridViewSettings
     */
    protected $_settings;

    /**
     * @return GridViewSettings
     */
    public function getSettings()
    {
        return $this->_settings;
    }

    /**
     * @param string $name
     * @return bool|string
     */
    public function renderSection($name)
    {
        switch ($name) {
            case "{settings}":
                return $this->renderSettings();
            default:
                return parent::renderSection($name);
        }
    }

    /**
     * @return string
     */
    public function renderSettings()
    {
        $gridEditSettings = [
            'url'           => (string) $this->settings->getEditUrl(),
            'enabledPjax'   => $this->enabledPjax,
            'pjax'          => $this->pjax
        ];

        $gridEditSettings = Json::encode($gridEditSettings);
        return '<div class="sx-grid-settings">' . Html::a('<i class="glyphicon glyphicon-cog"></i>', $this->settings->getEditUrl(), [
            'class' => 'btn btn-default btn-sm',
            'onclick' => new JsExpression(<<<JS
            new sx.classes.GridEditSettings({$gridEditSettings}); return false;
JS
)
        ]) . "</div>";

    }


    public function registerAsset()
    {
        parent::registerAsset();

        $this->view->registerCss(<<<CSS
        .sx-grid-settings
        {
            margin: 20px 0;
        }
CSS
);
        $this->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.classes.GridEditSettings = sx.classes.Component.extend({

                _init: function()
                {
                    var self = this;

                    this.Window = new sx.classes.Window(this.get('url'));

                    this.Window.bind('close', function(e, data)
                    {
                        self.reload();
                    });

                    this.Window.open();
                },

                reload: function()
                {
                    if (this.get('enabledPjax'))
                    {
                        var id = null;
                        var pjax = this.get('pjax');
                        if (pjax.options)
                        {
                            id = pjax.options.id;
                        }

                        if (id)
                        {
                            $.pjax.reload('#' + id, {});
                            return this;
                        }

                    }

                    window.location.reload();
                    return this;
                },

                _onDomReady: function()
                {},

                _onWindowReady: function()
                {}
            });
        })(sx, sx.$, sx._);
JS
);
    }
    public function initDataProvider()
    {
        $this->dataProvider;

        $this->dataProvider->getPagination()->defaultPageSize   = $this->settings->pageSize;
        $this->dataProvider->getPagination()->pageParam         = $this->settings->pageParamName;

        if ($this->settings->orderBy)
        {
            $this->dataProvider->getSort()->defaultOrder =
            [
                $this->settings->orderBy => (int) $this->settings->order
            ];
        }

        return $this;
    }

}