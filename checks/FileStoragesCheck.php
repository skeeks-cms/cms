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
class FileStoragesCheck extends CheckComponent
{
    public function init()
    {
        $this->name             = "Проверка доступности файловых хранилищ";
        $this->description      = <<<HTML
<p>
    На сайте есть файловое хранилище.
    В него попадают все загруженные файлы.
    Так же это хранилище состоит из кластеров (отдельных серверов для хранения файлов).
    Если к сайту на подключены сервера, то при добавлении файлов, к разделам, публикациям и т.д. будет происходить с ошибками.
</p>
HTML;
;
        $this->errorText    = "Есть ошибки";
        $this->successText  = "Успешно";

        parent::init();
    }


    public function run()
    {
		if (!\Yii::$app->storage->getClusters())
        {
            $this->addError('Нет доступных серверов');
        } else
        {
            $this->addSuccess('Подключено серверов: ' . count(\Yii::$app->storage->getClusters()));
        }


        if (\Yii::$app->storage->getClusters())
        {
            foreach (\Yii::$app->storage->getClusters() as $cluster)
            {
                if ($cluster->getFreeSpacePct() > 10)
                {
                    $this->addSuccess("Сверер " . $cluster->name . ' — доступно места ' . \Yii::$app->formatter->asShortSize($cluster->getFreeSpace()) . ' (' . round($cluster->getFreeSpacePct()) . '%)');
                } else
                {
                    $this->addError("Сверер " . $cluster->name . ' — доступно места ' . \Yii::$app->formatter->asShortSize($cluster->getFreeSpace()) . ' (' . round($cluster->getFreeSpacePct()) . '%)');
                }
            }
        }
    }

}
