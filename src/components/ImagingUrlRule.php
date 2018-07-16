<?php
/**
 * ImagingUrlRule
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\helpers\StringHelper;
use skeeks\sx\File;

/**
 * Class Storage
 * @package skeeks\cms\components
 */
class ImagingUrlRule
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
        $pathInfo = $request->getPathInfo();
        $params = $request->getQueryParams();

        $sourceOriginalFile = File::object($pathInfo);
        $extension = $sourceOriginalFile->getExtension();

        if (!$extension) {
            return false;
        }

        if (!in_array(StringHelper::strtolower($extension), (array)\Yii::$app->imaging->extensions)) {
            return false;
        }
        
        return ['cms/imaging/process', $params];
    }
}
