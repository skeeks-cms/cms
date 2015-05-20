<?php
/**
 * CurrentSite
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\Site;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\TreeType;
use Yii;
use yii\base\Component;
use yii\console\Application;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * @method TreeType[]   getComponents()
 * @method TreeType     getComponent($id)
 *
 * Class CollectionComponents
 * @package skeeks\cms\components
 */
class CurrentSite extends Component
{
    /**
     * @var CmsSite
     */
    public $site = null;

    public function init()
    {
        parent::init();

        //TODO: Если это консольное приложение нужно предусмотреть этот момент
        if (\Yii::$app instanceof \yii\console\Application)
        {
            $this->site = false;
        }

        if ($this->site === null)
        {
            //$serverName = \Yii::$app->getRequest()->getServerName();
            //$sites = Site::getAllKeyHostName();
            $sites = [];
            if (!$sites)
            {
                $this->site = false;
            }

            if (!isset($sites[$serverName]))
            {
                $this->site = false;
            } else
            {
                $this->site = $sites[$serverName];
            }
        }
    }

    /**
     * @return CmsSite
     */
    public function get()
    {
        return $this->site;
    }

    /**
     * @param CmsSite $site
     * @return $this
     */
    public function set(CmsSite $site)
    {
        $this->site = $site;
        return $this;
    }
}