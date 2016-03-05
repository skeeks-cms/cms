<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */
namespace skeeks\cms\components\urlRules;
use skeeks\cms\App;
use skeeks\cms\exceptions\NotConnectedToDbException;
use skeeks\cms\models\Tree;
use \yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class UrlRuleTree
 * @package skeeks\cms\components\urlRules
 */
class UrlRuleTree
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
        if ($route == 'cms/tree/view')
        {
            $id          = (int) ArrayHelper::getValue($params, 'id');
            $treeModel   = ArrayHelper::getValue($params, 'model');

            if (!$id && !$treeModel)
            {
                return false;
            }

            if ($treeModel && $treeModel instanceof Tree)
            {
                $tree = $treeModel;
                self::$models[$treeModel->id] = $treeModel;
            } else
            {
                if (!$tree = ArrayHelper::getValue(self::$models, $id))
                {
                    $tree = Tree::findOne(['id' => $id]);
                    self::$models[$id] = $tree;
                }
            }

            if (!$tree)
            {
                return false;
            }

            if ($tree->dir)
            {
                $url = $tree->dir . ((bool) \Yii::$app->seo->useLastDelimetrTree ? DIRECTORY_SEPARATOR : "") . (\Yii::$app->urlManager->suffix ? \Yii::$app->urlManager->suffix : '');
            } else
            {
                $url = "";
            }

            ArrayHelper::remove($params, 'id');
            ArrayHelper::remove($params, 'model');

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
            //\Yii::$app->response->redirect(DIRECTORY_SEPARATOR . $pathInfoNormal . ($params ? '?' . http_build_query($params) : '') );
        }

        return $this->_go($pathInfoNormal);
    }

    protected function _go($normalizeDir = null)
    {
        if (\Yii::$app->seo->useLastDelimetrTree)
        {
            $normalizeDir = substr($normalizeDir, 0, (strlen($normalizeDir) - 1));
        }


        try
        {
            $dependency = new TagDependency([
                'tags'      =>
                [
                    (new Tree())->getTableCacheTag(),
                ],
            ]);


            if (!$normalizeDir) //главная страница
            {
                $treeNode = Tree::getDb()->cache(function ($db) {
                    return Tree::find()->where([
                        "site_code"         => \Yii::$app->cms->site->code,
                        "level"             => 0,
                    ])->one();
                }, null, $dependency);

                /*$treeNode = Tree::find()->where([
                    "site_code"         => \Yii::$app->cms->site->code,
                    "level"             => 0,
                ])->one();*/


            } else //второстепенная страница
            {
                /*$treeNode = Tree::getDb()->cache(function ($db) {
                    return Tree::find()->where([
                        (new Tree())->dirAttrName       => $normalizeDir,
                        "site_code"                     => \Yii::$app->cms->site->code,
                    ])->one();
                }, null, $dependency);*/

                $treeNode = Tree::find()->where([
                    "dir"                           => $normalizeDir,
                    "site_code"                     => \Yii::$app->cms->site->code,
                ])->one();
            }
        } catch (Exception $e)
        {
            if (in_array($e->getCode(), NotConnectedToDbException::$invalidConnectionCodes))
            {
                throw new NotConnectedToDbException;
            }
        } catch (\yii\base\InvalidConfigException $e)
        {
            throw new NotConnectedToDbException;
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
        $pathInfoNormal     = $this->_filterNormalizeDir($pathInfo);

        if ((bool) \Yii::$app->seo->useLastDelimetrTree)
        {
            return $pathInfoNormal . "/";
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
