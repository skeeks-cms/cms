<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */
namespace skeeks\cms\components\urlRules;
use skeeks\cms\App;
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
                $url = $contentElement->cmsTree->dir . DIRECTORY_SEPARATOR;
            }

            $url .= $contentElement->id . '-' . $contentElement->code . ((bool) \Yii::$app->seo->useLastDelimetrContentElements ? DIRECTORY_SEPARATOR : "");

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
        $pathInfo           = $request->getPathInfo();
        $params             = $request->getQueryParams();
        $treeNode           = null;

        if (!$pathInfo)
        {
            return false;
        }

        if (!preg_match('/\/(?<id>\d+)\-(?<code>\S+)$/i', "/" . $pathInfo, $matches))
        {
            return false;
        }

        //Если урл преобразован, редирректим по новой
        $pathInfoNormal = $this->_normalizeDir($pathInfo);
        if ($pathInfo != $pathInfoNormal)
        {
            //\Yii::$app->response->redirect(DIRECTORY_SEPARATOR . $pathInfoNormal . ($params ? '?' . http_build_query($params) : '') );
        }


        return ['cms/content-element/view', [
            'id'    => $matches['id'],
            'code'  => $matches['code']
        ]];
    }

    /**
     * Преобразование path, убираем лишние слэши, если надо добавляем последний слэш
     * @param $pathInfo
     * @return string
     */
    protected function _normalizeDir($pathInfo)
    {
        $pathInfoNormal     = $this->_filterNormalizeDir($pathInfo);

        if ((bool) \Yii::$app->seo->useLastDelimetrContentElements)
        {
            return $pathInfoNormal . DIRECTORY_SEPARATOR;
        } else
        {
            return $pathInfoNormal;
        }
    }

    /**
     * @param string $dir
     * @return string
     */
    protected function _filterNormalizeDir($dir)
    {
        $result = [];

        $data = explode(DIRECTORY_SEPARATOR, $dir);
        foreach ($data as $value)
        {
            if ($value)
            {
                $result[] = $value;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $result);
    }

}
