<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\components\urlRules;

use skeeks\cms\components\Imaging;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class UrlRuleImagePreview extends \yii\web\UrlRule
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

        /*$sourceOriginalFile = File::object($pathInfo);
        $extension = $sourceOriginalFile->getExtension();*/

        $extension = Imaging::getExtension($pathInfo);

        //Если нет разрешения файла, то правило не срабатывает
        if (!$extension) {
            return false;
        }

        //preview картинки срабатывают только для определенных расширений
        $imaging = \Yii::$app->imaging;
        if (!$imaging->isAllowExtension($extension)) {
            return false;
        }

        //В адресе должна быть пометка что это фильтр
        $strposFilter = strpos($pathInfo, "/" . Imaging::THUMBNAIL_PREFIX);
        if (!$strposFilter) {
            return false;
        }

        return ['cms/image-preview/process', $params];
    }
}
