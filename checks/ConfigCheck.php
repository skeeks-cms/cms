<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
namespace skeeks\cms\checks;
use skeeks\cms\base\CheckComponent;

/**
 * Class ConfigCheck
 * @package skeeks\cms\checks
 */
class ConfigCheck extends CheckComponent
{
    public function init()
    {
        $this->name             = \Yii::t('skeeks/cms','Checking the basic configuration of the project');
        $txt = \Yii::t('skeeks/cms','Checking the configuration of the project. Global variables, mode of development, database query cache, the cache structure of the database tables.');
        $this->description      = <<<HTML
<p>{$txt}</p>
HTML;
;
        $this->errorText    = \Yii::t('skeeks/cms','There are mistakes');
        $this->successText  = \Yii::t('skeeks/cms',"Optimally");

        parent::init();
    }


    public function run()
    {
		if (!\Yii::$app->db->enableSchemaCache)
        {
            $this->addError(\Yii::t('skeeks/cms','Cache structure of the database tables is disabled'));
        } else
        {
            $this->addSuccess(\Yii::t('skeeks/cms','Cache structure of the database tables is enabled'));
        }

        if (!\Yii::$app->db->enableQueryCache)
        {
            $this->addError(\Yii::t('skeeks/cms','Sql query cache is disabled'));
        }else
        {
            $this->addSuccess('Sql query cache is enabled');
        }


        if (YII_DEBUG)
        {
            $this->addError(\Yii::t('skeeks/cms','Enable debug mode'));
        }else
        {
            $this->addSuccess(\Yii::t('skeeks/cms','Debug mode is enabled'));
        }

        if (YII_ENV == 'prod')
        {
            $this->addSuccess(\Yii::t('skeeks/cms','Setting corresponds to the working site {prod}',['prod' => 'prod']));
        }else
        {
            $this->addError(\Yii::t('skeeks/cms','Setting does not correspond to the working site now').': ' . YII_ENV . ', '.\Yii::t('skeeks/cms','preferably').': prod');
        }
    }

}
