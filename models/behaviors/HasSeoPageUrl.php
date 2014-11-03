<?php
/**
 * HasSeoPageUrl
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 03.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

/**
 * Class HasSeoPageUrl
 * @package skeeks\cms\models\behaviors
 */
class HasSeoPageUrl extends HasPageUrl
{
    /**
     * @var string
     */
    public $viewPageTemplate                    = "module/controller/view";
    public $seoPageNameAttribute                = "seo_page_name";

    /**
     * @return string
     */
    public function createUrl()
    {
        return \Yii::$app->urlManager->createUrl([$this->viewPageTemplate, $this->seoPageNameAttribute => $this->owner->{$this->pkName}]);
    }

    public function createAbsoluteUrl()
    {
        return \Yii::$app->urlManager->createAbsoluteUrl([$this->viewPageTemplate, $this->seoPageNameAttribute => $this->owner->{$this->seoPageNameAttribute}]);
    }

    /**
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl();
    }


}