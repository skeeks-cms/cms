<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */
namespace skeeks\cms\components\urlRules;
use skeeks\cms\App;
use skeeks\cms\filters\NormalizeDir;
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
    /**
     *
     * Добавлять слэш на конце или нет
     *
     * @var bool
     */
    public $useLastDelimetr = true;

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
            $id = (int) ArrayHelper::getValue($params, 'id');
            if (!$id)
            {
                return false;
            }

            /**
             * @var $contentElement CmsContentElement
             */
            if (!$contentElement = ArrayHelper::getValue(self::$models, $id))
            {
                $contentElement     = CmsContentElement::findOne(['id' => $id]);
                self::$models[$id]  = $contentElement;
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

            $url .= $contentElement->id . '-' . $contentElement->code . ($this->useLastDelimetr ? DIRECTORY_SEPARATOR : "");

            if (isset($params['code']))
            {
                unset($params['code']);
            };

            if (isset($params['id']))
            {
                unset($params['id']);
            };

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

        //Если урл преобразован, редирректим по новой
        $pathInfoNormal = $this->_normalizeDir($pathInfo);
        if ($pathInfo != $pathInfoNormal)
        {
            \Yii::$app->response->redirect(DIRECTORY_SEPARATOR . $pathInfoNormal . ($params ? '?' . http_build_query($params) : '') );
        }

        $pathInfo = "/" . $pathInfo;

        if (!preg_match('/\/(?<id>\d+)\-(?<code>\S+)$/i', $pathInfo, $matches))
        {
            return false;
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
        $filter             = new NormalizeDir();
        $pathInfoNormal     = $filter->filter($pathInfo);

        if ($this->useLastDelimetr)
        {
            return $pathInfoNormal . DIRECTORY_SEPARATOR;
        } else
        {
            return $pathInfoNormal;
        }
    }

}
