<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\components\urlRules;

use skeeks\cms\components\Imaging;
use skeeks\cms\helpers\StringHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class UrlRuleStorageFile extends \yii\web\UrlRule
{
    public function init()
    {
        if ($this->name === null) {
            $this->name = __CLASS__;
        }
    }

    /**
     * @param \yii\web\UrlManager $manager
     * @param string              $route
     * @param array               $params
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        return false;
    }

    /**
     * @param \yii\web\UrlManager $manager
     * @param \yii\web\Request    $request
     * @return array|bool
     */
    public function parseRequest($manager, $request)
    {
        $pathInfo = $request->getPathInfo();
        $params = $request->getQueryParams();

        $extension = Imaging::getExtension($pathInfo);

        if (!$extension) {
            return false;
        }

        //В адресе должна быть пометка что это фильтр
        $strposFilter = strpos($pathInfo, "/" . Imaging::STORAGE_FILE_PREFIX);
        if (!$strposFilter) {
            return false;
        }

        return ['cms/storage-file/get-file', $params];
    }
}
