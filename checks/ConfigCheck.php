<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
namespace skeeks\cms\checks;
use skeeks\cms\base\CheckComponent;
use skeeks\sx\String;

/**
 * Class ConfigCheck
 * @package skeeks\cms\checks
 */
class ConfigCheck extends CheckComponent
{
    public function init()
    {
        $this->name             = "Проверка основной конфигурации проекта";
        $this->description      = <<<HTML
<p>
Осуществляется проверка конфигуркции проекта. Глобальные переменные, режим разработки, кэш запросов базы данных, кэш структуры таблиц базы данных.
</p>
HTML;
;
        $this->errorText    = "Есть ошибки";
        $this->successText  = "Оптимально";

        parent::init();
    }


    public function run()
    {
		if (!\Yii::$app->db->enableSchemaCache)
        {
            $this->addError('Кэш структуры таблиц БД отключен');
        } else
        {
            $this->addSuccess('Кэш структуры таблиц БД включен');
        }

        if (!\Yii::$app->db->enableQueryCache)
        {
            $this->addError('Кэш запросов sql отключен');
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
