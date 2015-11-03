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
            $this->addError(\Yii::t('app','Sql query cache disabled'));
        }else
        {
            $this->addSuccess('Кэш запросов sql включен');
        }


        if (YII_DEBUG)
        {
            $this->addError('Включен режим отладки');
        }else
        {
            $this->addSuccess('Режим отладки выключен');
        }

        if (YII_ENV == 'prod')
        {
            $this->addSuccess('Окружение соовтествует рабочему сайту prod');
        }else
        {
            $this->addError('Окружение не соовтествует рабочему сайту сейчас: ' . YII_ENV . ', желательно: prod');
        }
    }

}
