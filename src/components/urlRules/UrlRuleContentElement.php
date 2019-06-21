<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */

namespace skeeks\cms\components\urlRules;

use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\Tree;
use \yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class UrlRuleContentElement
 * @package skeeks\cms\components\urlRules
 */
class UrlRuleContentElement
    extends \yii\web\UrlRule
{

    public function init()
    {
        if ($this->name === null) {
            $this->name = __CLASS__;
        }
    }

    static public $models = [];

    /**
     * @param \yii\web\UrlManager $manager
     * @param string $route
     * @param array $params
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        if ($route == 'cms/content-element/view') {
            $contentElement = $this->_getElement($params);

            if (!$contentElement) {
                return false;
            }

            $url = '';

            $cmsTree = ArrayHelper::getValue($params, 'cmsTree');
            ArrayHelper::remove($params, 'cmsTree');
            //We need to build on what that particular section of the settings
            if (!$cmsTree) {
                $cmsTree = $contentElement->cmsTree;
            }

            if ($cmsTree) {
                $url = $cmsTree->dir . "/";
            }

            $url .= $contentElement->id . '-' . $contentElement->code;


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
            if ($cmsTree && $cmsTree->site) {
                //TODO:: добавить проверку текущего сайта. В случае совпадения возврат локального пути
                if ($cmsTree->site->cmsSiteMainDomain) {
                    return $cmsTree->site->url . '/' . $url;
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
        $contentElement = ArrayHelper::getValue($params, 'model');

        if (!$id && !$contentElement) {
            return false;
        }

        if ($contentElement && $contentElement instanceof CmsContentElement) {
            self::$models[$contentElement->id] = $contentElement;
        } else {
            /**
             * @var $contentElement CmsContentElement
             */
            if (!$contentElement = ArrayHelper::getValue(self::$models, $id)) {
                $contentElement = CmsContentElement::findOne(['id' => $id]);
                self::$models[$id] = $contentElement;
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

        if (!preg_match('/\/(?<id>\d+)\-(?<code>\S+)$/i', "/" . $pathInfo, $matches)) {
            return false;
        }


        return [
            'cms/content-element/view',
            [
                'id' => $matches['id'],
                'code' => $matches['code']
            ]
        ];
    }


}
