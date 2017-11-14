<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */

namespace skeeks\cms\base;

/**
 * @property string $copyright
 * @property string $powered
 *
 * Class ComponentDescriptor
 * @package skeeks\cms\base
 */
class ComponentDescriptor
    extends \yii\base\Component
{
    public $version = "1.0.0";
    public $startDevelopmentDate = "2010-01-01";

    public $name = "SkeekS CMS";
    public $description = "";
    public $keywords = [];
    public $homepage = "https://cms.skeeks.com/";
    public $license = "BSD-3-Clause";

    public $support =
        [
            /*"issues"    =>  "http://www.skeeks.com/",
            "wiki"      =>  "http://cms.skeeks.com/wiki/",
            "source"    =>  "http://git.skeeks.com/skeeks/yii2-app"*/
        ];

    public $companies =
        [
            [
                "name" => "SkeekS",
                "emails" => ["info@skeeks.com", "support@skeeks.com"],
                "phones" => ["+7 (495) 722-28-73"],
                "sites" => ["skeeks.com"]
            ]
        ];


    public $authors =
        [
            [
                "name" => "Semenov Alexander",
                "emails" => ["semenov@skeeks.com"],
                "phones" => ["+7 (495) 722-28-73"]
            ],

            [
                "name" => "Semenov Alexander",
                "emails" => ["semenov@skeeks.com"],
                "phones" => ["+7 (495) 722-28-73"]
            ],
        ];


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }


    /**
     * @return string
     */
    public function toString()
    {
        return $this->name . " (" . $this->version . ")";
    }

    /**
     * @return string
     */
    public function getCopyright()
    {
        return (string)"@ " . \Yii::$app->getFormatter()->asDate($this->startDevelopmentDate,
                "y") . "-" . \Yii::$app->getFormatter()->asDate(time(), "y") . "; " . $this->toString();
    }

    /**
     * @return string
     */
    public function getPovered()
    {
        return 'Разработка <a href="' . $this->homepage . '" rel="external">' . $this->name . '</a>';
    }

}