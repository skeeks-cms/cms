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
    public $viewPageTemplate    = "module/controller/view";
    public $pkName              = "seo_page_name";

    /**
     * @return string
     */
    public function createUrl()
    {
        return \Yii::$app->urlManager->createUrl([$this->viewPageTemplate, $this->pkName => $this->owner->{$this->pkName}]);
    }

    public function createAbsoluteUrl()
    {
        return \Yii::$app->urlManager->createAbsoluteUrl([$this->viewPageTemplate, $this->pkName => $this->owner->{$this->pkName}]);
    }

    /**
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl();
    }
}