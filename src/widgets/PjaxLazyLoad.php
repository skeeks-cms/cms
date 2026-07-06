<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */

namespace skeeks\cms\widgets;

/**
 * @property bool isPjax;
 *
 * @package skeeks\cms\widgets
 */
class PjaxLazyLoad extends Pjax
{
    /**
     * @var int
     */
    public $delay = 200;

    /**
     * @var bool
     */
    public $enabledLoadAssets = true;


    public static $autoIdPrefix = 'PjaxLazyLoad';

    /**
     * @var string
     */
    protected $loadedAssetBundlesHeader = 'X-Pjax-Lazy-Load-Bundles';

    /**
     * @var int
     */
    public $loadedAssetBundlesCacheDuration = 3600;

    /**
     * @var bool
     */
    protected $_clientScriptRegistered = false;

    /**
     * @return string
     */
    protected function getLoadAssetsFunctionName()
    {
        return "sxPjaxLazyLoadAssets" . preg_replace('/[^a-zA-Z0-9_]/', "_", $this->id);
    }

    /**
     * @param array $additionalAssetBundles
     * @return string
     */
    protected function getLoadedAssetBundlesHeaderValue($additionalAssetBundles = [])
    {
        $assetBundles = array_values(array_unique(array_merge(
            array_keys($this->getView()->assetBundles),
            $additionalAssetBundles
        )));

        $json = json_encode($assetBundles);
        if (\Yii::$app->has('cache')) {
            $cacheKey = [self::class, 'loadedAssetBundles', md5($json)];
            if (\Yii::$app->cache->set($cacheKey, $assetBundles, $this->loadedAssetBundlesCacheDuration)) {
                return 'cache:' . md5($json);
            }
        }

        return rawurlencode($json);
    }

    /**
     * @return void
     */
    protected function removeAlreadyLoadedAssetBundles()
    {
        if (!$this->enabledLoadAssets || !$this->isPjax) {
            return;
        }

        $header = \Yii::$app->request->headers->get($this->loadedAssetBundlesHeader);
        if (!$header) {
            return;
        }

        $assetBundles = null;
        if (strpos($header, 'cache:') === 0 && \Yii::$app->has('cache')) {
            $assetBundles = \Yii::$app->cache->get([self::class, 'loadedAssetBundles', substr($header, 6)]);
        }

        if (!is_array($assetBundles)) {
            $assetBundles = json_decode(rawurldecode($header), true);
        }

        if (!is_array($assetBundles)) {
            return;
        }

        $view = $this->getView();
        foreach ($assetBundles as $assetBundle) {
            if (is_string($assetBundle) && isset($view->assetBundles[$assetBundle])) {
                unset($view->assetBundles[$assetBundle]);
            }
        }
    }

    /**
     * @return bool
     */
    public function getIsPjax() {
        return $this->requiresPjax();
    }

    /**
     * @return bool whether the current request requires pjax response from this widget
     */
    protected function requiresPjax()
    {
        if (parent::requiresPjax()) {
            return true;
        }

        $pjax = \Yii::$app->request->get('_pjax');
        return \Yii::$app->request->isAjax && $pjax && explode(' ', $pjax)[0] === '#' . $this->options['id'];
    }

    /**
     * @param string $loadedAssetBundlesHeaderValue
     * @return void
     */
    protected function applyLazyLoadClientOptions($loadedAssetBundlesHeaderValue)
    {
        if (!is_array($this->clientOptions)) {
            $this->clientOptions = [];
        }

        $headers = isset($this->clientOptions['headers']) ? (array) $this->clientOptions['headers'] : [];
        $headers['X-Pjax-Lazy-Load'] = '1';
        $headers[$this->loadedAssetBundlesHeader] = $loadedAssetBundlesHeaderValue;
        $this->clientOptions['headers'] = $headers;

        if ($this->enabledLoadAssets) {
            $loadAssetsFunctionName = $this->getLoadAssetsFunctionName();
            $this->clientOptions['dataFilter'] = new \yii\web\JsExpression(<<<JS
function(data, type) {
    if (typeof window.{$loadAssetsFunctionName} == "function") {
        return window.{$loadAssetsFunctionName}(data);
    }
    return data;
}
JS
            );
        }
    }

    /**
     * @param string $loadedAssetBundlesHeaderValue
     * @return void
     */
    protected function registerLazyLoadScript($loadedAssetBundlesHeaderValue)
    {
        $loadAssetsJs = "";
        $loadAssetsFunctionName = $this->getLoadAssetsFunctionName();
        $headersJs = \yii\helpers\Json::htmlEncode([
            'X-Pjax-Lazy-Load' => '1',
            $this->loadedAssetBundlesHeader => $loadedAssetBundlesHeaderValue,
        ]);

        if ($this->enabledLoadAssets) {
            $loadAssetsJs = <<<JS
        var normalizeAssetUrl = function(url) {
            var a = document.createElement("a");
            a.href = url;
            return a.href;
        };

        var isAssetExists = function(selector, attr, url) {
            var normalizedUrl = normalizeAssetUrl(url);
            var result = false;
            $(selector).each(function() {
                if (normalizeAssetUrl($(this).attr(attr)) == normalizedUrl) {
                    result = true;
                    return false;
                }
            });
            return result;
        };

        window.{$loadAssetsFunctionName} = function(contents) {
            var assets = $("<div>");
            if (typeof contents == "string") {
                assets.append($.parseHTML(contents, document, true));
            } else {
                assets.append($(contents).clone());
            }

            assets.find("link[rel='stylesheet'][href]").each(function() {
                var href = $(this).attr("href");
                if (!isAssetExists("link[rel='stylesheet'][href]", "href", href)) {
                    $(this).clone().appendTo("head");
                }
                $(this).remove();
            });

            assets.find("script[src]").each(function() {
                var src = $(this).attr("src");
                if (!window.sxPjaxLazyLoadAssets) {
                    window.sxPjaxLazyLoadAssets = {};
                }

                var normalizedSrc = normalizeAssetUrl(src);
                if (window.sxPjaxLazyLoadAssets[normalizedSrc] || isAssetExists("script[src]", "src", src)) {
                    window.sxPjaxLazyLoadAssets[normalizedSrc] = true;
                    $(this).remove();
                    return;
                }

                var loaded = true;
                $.ajax({
                    url: src,
                    dataType: "script",
                    cache: true,
                    async: false
                }).fail(function() {
                    loaded = false;
                });
                if (loaded) {
                    window.sxPjaxLazyLoadAssets[normalizedSrc] = true;
                }
                $(this).remove();
            });

            if (window.jQuery && window.sx && window.sx.$ && window.jQuery.fn && window.sx.$.fn) {
                var jQueryPlugins = ["pagination"];
                for (var i = 0; i < jQueryPlugins.length; i++) {
                    var pluginName = jQueryPlugins[i];
                    if (window.jQuery.fn[pluginName] && !window.sx.$.fn[pluginName]) {
                        window.sx.$.fn[pluginName] = window.jQuery.fn[pluginName];
                    }
                }
            }

            return assets.html();
        };

JS;
        }

        \Yii::$app->view->registerJs(<<<JS
    (function($) {
        {$loadAssetsJs}

    setTimeout(function() {
        $.pjax.reload("#{$this->id}", {
            'timeout': $this->timeout,
            'headers': {$headersJs},
            'dataFilter': function(data, type) {
                if (typeof window.{$loadAssetsFunctionName} == "function") {
                    return window.{$loadAssetsFunctionName}(data);
                }
                return data;
            },
            async: true
        });
        $.pjax.xhr = null;
    }, $this->delay);
    })(jQuery);
JS
        );
    }

    /**
     * Registers client scripts when the page already knows the full asset bundle list.
     */
    public function registerClientScript()
    {
        if ($this->_clientScriptRegistered) {
            return;
        }
        $this->_clientScriptRegistered = true;

        if ($this->isPjax) {
            parent::registerClientScript();
            return;
        }

        $this->getView()->on(\yii\web\View::EVENT_END_BODY, function () {
            $loadedAssetBundlesHeaderValue = $this->getLoadedAssetBundlesHeaderValue([
                \yii\widgets\PjaxAsset::class,
            ]);
            $this->applyLazyLoadClientOptions($loadedAssetBundlesHeaderValue);
            $this->registerLazyLoadScript($loadedAssetBundlesHeaderValue);
            parent::registerClientScript();
        });
    }

    /**
     * @return string|void
     */
    public function run()
    {
        if ($this->isPjax) {
            $this->removeAlreadyLoadedAssetBundles();
        }

        parent::run();
    }
}
