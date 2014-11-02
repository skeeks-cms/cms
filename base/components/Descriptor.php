<?php
/**
 * Descriptor
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 29.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\base\components;

use \skeeks\sx\Entity;
use \skeeks\sx\Version;
use \yii\base\Component;

/**
 * Class Descriptor
 * @package skeeks\cms\base\components
 */
class Descriptor
    extends Component
{
    public $version                 = "1.0.0";
    public $startDevelopmentDate    = "2010-01-01";

    public $name            = "";
    public $description     = "";
    public $keywords        = "";
    public $homepage        = "http://www.skeeks.com/";
    public $license         = "BSD-3-Clause";

    public $support         =
    [
        /*"issues"    =>  "http://www.skeeks.com/",
        "wiki"      =>  "http://cms.skeeks.com/wiki/",
        "source"    =>  "http://git.skeeks.com/skeeks/yii2-app"*/
    ];

    public $companies       =
    [
        [
            "name"      =>  "SkeekS",
            "emails"    => ["info@skeeks.com", "support@skeeks.com"],
            "phones"    => ["+7 (495) 722-28-73"],
            "sites"     => ["skeeks.com"]
        ]
    ];


    public $authors       =
    [
        [
            "name"      => "Semenov Alexander",
            "emails"    => ["semenov@skeeks.com"],
            "phones"    => ["+7 (495) 722-28-73"]
        ],

        [
            "name"      => "Semenov Alexander",
            "emails"    => ["semenov@skeeks.com"],
            "phones"    => ["+7 (495) 722-28-73"]
        ],
    ];

    public $admin       =
    [
        "enabled"       => true,
        "name"          => "",

        "items"         =>
        [
            /*"user" =>
                [
                    "label"     => "Управление пользователями",
                    "route"     => "cms/test-admin",
                    "priority"  => 10,
                ],

            "user-group" =>
                [
                    "label"     => "Управление группами",
                    "route"     => "cms/user-group-admin",
                    "priority"  => 5,
                ]*/
        ]
    ];



    /**
     * @var Version
     */
    protected $_version = null;


    /**
     * @return Version
     */
    public function getVersion()
    {
        if ($this->_version == null)
        {
            $this->_version = new Version($this->version);
        }

        return $this->_version;
    }


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
        return (string) "@ " . \Yii::$app->getFormatter()->asDate($this->startDevelopmentDate, "y") . "-" . \Yii::$app->getFormatter()->asDate(time(), "y") .  "; " . $this->toString();
    }



    /**
     * @return array
     */
    public function getAdminItems()
    {
        if (isset($this->admin["items"]))
        {
            return (array) $this->admin["items"];
        }

        return [];
    }
}