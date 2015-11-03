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
        $this->name             = \Yii::t('app','Checking the basic configuration of the project');
        $txt = \Yii::t('app','Checking the configuration of the project. Global variables, mode of development, database query cache, the cache structure of the database tables.');
        $this->description      = <<<HTML
<p>{$txt}</p>
HTML;
;
        $this->errorText    = \Yii::t('app','There are mistakes');
        $this->successText  = \Yii::t('app',"Optimally");

        parent::init();
    }


    public function run()
    {
		if (!\Yii::$app->db->enableSchemaCache)
        {
            $this->addError(\Yii::t('app','Cache structure of the database tables is disabled'));
        } else
        {
            $this->addSuccess(\Yii::t('app','Cache structure of the database tables is enabled'));
        }

        if (!\Yii::$app->db->enableQueryCache)
        {
            $this->addError(\Yii::t('app','Sql query cache is disabled'));
        }else
        {
            $this->addSuccess('Sql query cache is enabled');
        }


        if (YII_DEBUG)
        {
            $this->addError(\Yii::t('app','Enable debug mode'));
        }else
        {
            $this->addSuccess(\Yii::t('app','Debug mode is enabled'));
        }

        if (YII_ENV == 'prod')
        {
            $this->addSuccess(\Yii::t('app','Setting corresponds to the working site {prod}',['prod' => 'prod']));
        }else
        {
            $this->addError(\Yii::t('app','Setting does not correspond to the working site now').': ' . YII_ENV . ', '.\Yii::t('app','preferably').': prod');
        }
    }

}
