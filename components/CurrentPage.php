<?php
/**
 * CurrentPage
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\Site;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\TreeType;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class CurrentPage
 * @package skeeks\cms\components
 */
class CurrentPage extends \skeeks\cms\base\Component
{
    /**
     * @var Site
     */
    public $model;

    public function init()
    {
        parent::init();

        if ($this->site === null)
        {
            $serverName = \Yii::$app->getRequest()->getServerName();
            $sites = Site::getAllKeyHostName();
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

}