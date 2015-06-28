<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.06.2015
 */
namespace skeeks\cms\helpers;
use skeeks\cms\components\imaging\Filter;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * @property string $name
 * @property string $description
 * @property string $homepage
 * @property string $type
 * @property string $license
 * @property array  $support
 * @property array  $keywords
 * @property array  $authors
 * @property array  $require
 *
 * Class ComposerHelper
 * @package skeeks\cms\helpers
 */
class ComposerHelper extends Component
{
    /**
     * @var array
     */
    public $data = [];
    
    public function init()
    {}

    /**
     * @return string
     */
    public function getName()
    {
        return (string) ArrayHelper::getValue($this->data, 'name');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return (string) ArrayHelper::getValue($this->data, 'description');
    }

    /**
     * @return array
     */
    public function getKeywords()
    {
        return (array) ArrayHelper::getValue($this->data, 'keywords');
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        return (string) ArrayHelper::getValue($this->data, 'homepage');
    }

    /**
     * @return string
     */
    public function getType()
    {
        return (string) ArrayHelper::getValue($this->data, 'type');
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return (string) ArrayHelper::getValue($this->data, 'license');
    }

    /**
     * @return array
     */
    public function getSupport()
    {
        return (array) ArrayHelper::getValue($this->data, 'support');
    }

    /**
     * @return array
     */
    public function getAuthors()
    {
        return (array) ArrayHelper::getValue($this->data, 'authors');
    }

    /**
     * @return array
     */
    public function getRequire()
    {
        return (array) ArrayHelper::getValue($this->data, 'require');
    }
}