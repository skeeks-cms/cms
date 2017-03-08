<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */
namespace skeeks\cms\traits;

/**
 * @property $name;
 * @property $icon;
 * @property $image;
 *
 * Class THasUrl
 * @package skeeks\cms\traits
 */
trait THasUrl
{
    /**
     * @var string
     */
    protected $_url = '';

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param string|array $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }
}