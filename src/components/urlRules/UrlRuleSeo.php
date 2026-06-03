<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */

namespace skeeks\cms\components\urlRules;

use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\Tree;
use \yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Application;

/**
 * Class UrlRuleContentElement
 * @package skeeks\cms\components\urlRules
 */
class UrlRuleSeo
    extends \yii\web\UrlRule
{

    public function init()
    {
        if ($this->name === null) {
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
        if (!\Yii::$app->seo->google_file_ids) {
            return false;
        }

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

        if (!preg_match('~/?google[a-zA-Z0-9]+\.html$~', $pathInfo)) {
            return false;
        }

        foreach (\Yii::$app->seo->google_file_ids as $file_id)
        {
            if ($file_id) {
                $cmsFile = CmsStorageFile::findOne($file_id);

                if ($cmsFile) {
                    if ($cmsFile->original_name) {

                        if (strpos($pathInfo, $cmsFile->original_name) !== false) {
                            echo file_get_contents($cmsFile->rootSrc);
                            \Yii::$app->end();
                            die;
                        }
                        //rootSrc
                    }
                }

            }
        }

        return false;

        
    }


}
