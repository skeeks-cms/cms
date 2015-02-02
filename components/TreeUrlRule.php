<?php
/**
 * TreeUrlRule
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;
use skeeks\cms\App;
use skeeks\cms\filters\NormalizeDir;
use skeeks\cms\models\Tree;
use \yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class Storage
 * @package skeeks\cms\components
 */
class TreeUrlRule
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

    /**
     * @param \yii\web\UrlManager $manager
     * @param string $route
     * @param array $params
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        if ($route == 'cms/tree/view')
        {
            $id = (int) ArrayHelper::getValue($params, 'id');
            if (!$id)
            {
                return false;
            }

            $tree = Tree::findOne(['id' => $id]);
            if (!$tree)
            {
                return false;
            }

            $url = $tree->getPageUrl();

            unset($params['id']);
            /*if ($url !== '') {
                $url .= ($this->suffix === null ? $manager->suffix : $this->suffix);
            }*/

            if (!empty($params) && ($query = http_build_query($params)) !== '') {
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
            return $this->_go();
        }

        //Если урл преобразован, редирректим по новой
        $pathInfoNormal = $this->_normalizeDir($pathInfo);
        if ($pathInfo != $pathInfoNormal)
        {
            \Yii::$app->response->redirect(DIRECTORY_SEPARATOR . $pathInfoNormal . ($params ? '?' . http_build_query($params) : '') );
        }

        return $this->_go($pathInfoNormal);
    }

    protected function _go($normalizeDir = null)
    {
        if ($this->useLastDelimetr)
        {
            $normalizeDir = substr($normalizeDir, 0, (strlen($normalizeDir) - 1));
        }

        if (!$normalizeDir) //главная страница
        {
            $treeNode = Tree::findCurrentRoot();
            if (!$treeNode)
            {
                return false;
            }

        } else //второстепенная страница
        {
            $treeRoot = Tree::findCurrentRoot();
            if (!$treeRoot)
            {
                return false;
            }

            $treeNode           = Tree::find()->where([
                $treeRoot->dirAttrName      => $normalizeDir,
                $treeRoot->pidMainAttrName  => $treeRoot->id,
            ])->one();

        }

        if ($treeNode)
        {
            \Yii::$app->cms->setCurrentTree($treeNode);

            $params['id']        = $treeNode->id;
            return ['cms/tree/view', $params];
        } else
        {
            return false;
        }
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
