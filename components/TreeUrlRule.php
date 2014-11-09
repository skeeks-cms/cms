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
use skeeks\cms\models\Tree;
use \yii\base\InvalidConfigException;
/**
 * Class Storage
 * @package skeeks\cms\components
 */
class TreeUrlRule
    extends \yii\web\UrlRule
{
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

        //Проверить для начала зарегистрирован ли текущий сайт в базе данных, если да то нужно главнй раздел взять для него, если нет тогда по умолчанию для всех сайтов.
        $mainRoot = Tree::findDefaultRoot();
        if (!$mainRoot)
        {
            return false;
        }

        if ($mainRoot->isRoot())
        {
            $treeNode           = Tree::find()->where([$mainRoot->dirAttrName => $pathInfo])->one();
        }

        if ($treeNode)
        {
            $params['model']        = $treeNode;
            return ['cms/tree/view', $params];
        } else
        {
            return false;
        }
    }
}
