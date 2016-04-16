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
        if ($this->name === null)
        {
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
        if ($route == 'cms/content-element/view')
        {
            $suffix             = (string)($this->suffix === null ? $manager->suffix : $this->suffix);

            $id                     = (int) ArrayHelper::getValue($params, 'id');
            $contentElement         = ArrayHelper::getValue($params, 'model');

            if (!$id && !$contentElement)
            {
                return false;
            }

            if ($contentElement && $contentElement instanceof CmsContentElement)
            {
                self::$models[$contentElement->id] = $contentElement;
            } else
            {
                /**
                 * @var $contentElement CmsContentElement
                 */
                if (!$contentElement = ArrayHelper::getValue(self::$models, $id))
                {
                    $contentElement     = CmsContentElement::findOne(['id' => $id]);
                    self::$models[$id]  = $contentElement;
                }
            }



            if (!$contentElement)
            {
                return false;
            }

            $url = '';

            if ($contentElement->cmsTree)
            {
                $url = $contentElement->cmsTree->dir . "/";
            }

            //$url .= $contentElement->id . '-' . $contentElement->code . ((bool) \Yii::$app->seo->useLastDelimetrContentElements ? DIRECTORY_SEPARATOR : "");
            $url .= $contentElement->id . '-' . $contentElement->code . $suffix;

            ArrayHelper::remove($params, 'id');
            ArrayHelper::remove($params, 'code');
            ArrayHelper::remove($params, 'model');

            if (!empty($params) && ($query = http_build_query($params)) !== '')
            {
                $url .= '?' . $query;
            }

            return $url;
        }

        return false;
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

        $pathInfo           = $request->getPathInfo();
        if ($this->host !== null) {
            $pathInfo = strtolower($request->getHostInfo()) . ($pathInfo === '' ? '' : '/' . $pathInfo);
        }


        $params             = $request->getQueryParams();
        $suffix             = (string)($this->suffix === null ? $manager->suffix : $this->suffix);
        $treeNode           = null;

        if (!$pathInfo)
        {
            return false;
        }

        if (!preg_match('/\/(?<id>\d+)\-(?<code>\S+)$/i', "/" . $pathInfo, $matches))
        {
            return false;
        }


        return ['cms/content-element/view', [
            'id'    => $matches['id'],
            'code'  => $matches['code']
        ]];
    }


}
