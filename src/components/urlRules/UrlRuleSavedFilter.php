<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */

namespace skeeks\cms\components\urlRules;

use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsSavedFilter;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\Tree;
use \yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Application;

/**
 * Class UrlRuleContentElement
 * @package skeeks\cms\components\urlRules
 */
class UrlRuleSavedFilter
    extends \yii\web\UrlRule
{

    public function init()
    {
        if ($this->name === null) {
            $this->name = __CLASS__;
        }
    }

    /**
     * //Это можно использовать только в коротких сценариях, иначе произойдет переполнение памяти
     * @var array
     */
    static public $models = [];

    /**
     * @param \yii\web\UrlManager $manager
     * @param string $route
     * @param array $params
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        if ($route == 'cms/saved-filter/view') {
            $savedFilter = $this->_getElement($params);

            if (!$savedFilter) {
                return false;
            }

            $url = '';

            $cmsTree = ArrayHelper::getValue($params, 'cmsTree');
            ArrayHelper::remove($params, 'cmsTree');
            $cmsSite = ArrayHelper::getValue($params, 'cmsSite', null);
            ArrayHelper::remove($params, 'cmsSite');

            if ($cmsTree) {
                $url = $cmsTree->dir . "/";
            }
            $url .= $savedFilter->code . '-f' . $savedFilter->id;


            if (strpos($url, '//') !== false) {

                $url = preg_replace('#/+#', '/', $url);
            }

            /**
             * @see parent::createUrl()
             */
            if ($url !== '') {
                $url .= ($this->suffix === null ? $manager->suffix : $this->suffix);
            }

            /**
             * @see parent::createUrl()
             */
            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                $url .= '?' . $query;
            }

            //Раздел привязан к сайту, сайт может отличаться от того на котором мы сейчас находимся
            if (!$cmsSite) {
                $siteClass = \Yii::$app->skeeks->siteClass;
                $cmsSite = $siteClass::getById($savedFilter->cms_site_id);
                //$cmsSite = $contentElement->cmsSite;
            }

            if ($cmsSite) {
                if ($cmsSite->cmsSiteMainDomain) {
                    return $cmsSite->url . '/' . $url;
                }
            }

            return $url;
        }

        return false;
    }

    /**
     *
     * @param $params
     * @return bool|CmsContentElement
     */
    protected function _getElement(&$params)
    {
        $id = (int)ArrayHelper::getValue($params, 'id');
        $savedFilter = ArrayHelper::getValue($params, 'model');

        if (!$id && !$savedFilter) {
            return false;
        }

        if ($savedFilter && $savedFilter instanceof CmsSavedFilter) {
            if (\Yii::$app instanceof Application) {
                self::$models[$savedFilter->id] = $savedFilter;
            }
        } else {
            /**
             * @var $savedFilter CmsSavedFilter
             */
            if (!$contentElement = ArrayHelper::getValue(self::$models, $id)) {
                $contentElement = CmsSavedFilter::findOne(['id' => $id]);
                if (\Yii::$app instanceof Application) {
                    self::$models[$id] = $contentElement;
                }
            }
        }

        ArrayHelper::remove($params, 'id');
        ArrayHelper::remove($params, 'code');
        ArrayHelper::remove($params, 'model');

        return $contentElement;
    }

    /**
     * @param \yii\web\UrlManager $manager
     * @param \yii\web\Request $request
     * @return array|bool
     */
    public function parseRequest($manager, $request)
    {
        if ($this->mode === self::CREATION_ONLY) {
            return false;
        }

        if (!empty($this->verb) && !in_array($request->getMethod(), $this->verb, true)) {
            return false;
        }

        $pathInfo = $request->getPathInfo();
        if ($this->host !== null) {
            $pathInfo = strtolower($request->getHostInfo()) . ($pathInfo === '' ? '' : '/' . $pathInfo);
        }


        $params = $request->getQueryParams();
        $suffix = (string)($this->suffix === null ? $manager->suffix : $this->suffix);
        $treeNode = null;

        if (!$pathInfo) {
            return false;
        }

        if ($suffix) {
            $pathInfo = substr($pathInfo, 0, strlen($pathInfo) - strlen($suffix));
        }

        if (preg_match('/\/(?<code>\S+)\-f(?<id>\d+)$/i', "/" . $pathInfo, $matches)) {
            return [
                'cms/saved-filter/view', [
                    'id' => $matches['id'],
                    'code' => $matches['code']
                ]
            ];
        }

        return false;
    }


}
