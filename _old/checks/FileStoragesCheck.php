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
        $this->name             = \Yii::t('skeeks/cms',"Check availability file-storages");
        $txt = \Yii::t('skeeks/cms','The site has a file storage. It contains all downloaded files. It also consists of a storage cluster (separate servers for file storage). If the site is not connected to the servers, then when you add files to the sections, publications, etc. errors will occur.');
        $this->description      = <<<HTML
<p>
    {$txt}
</p>
HTML;
;
        $this->errorText    = \Yii::t('skeeks/cms',"There are mistakes");
        $this->successText  = \Yii::t('skeeks/cms',"Successfully");

        parent::init();
    }


    public function run()
    {
		if (!\Yii::$app->storage->getClusters())
        {
            $this->addError(\Yii::t('skeeks/cms','No available servers'));
        } else
        {
            $this->addSuccess(\Yii::t('skeeks/cms','Connected servers').': ' . count(\Yii::$app->storage->getClusters()));
        }


        if (\Yii::$app->storage->getClusters())
        {
            foreach (\Yii::$app->storage->getClusters() as $cluster)
            {
                if ($cluster->getFreeSpacePct() > 10)
                {
                    $this->addSuccess(\Yii::t('skeeks/cms',"Server {server} {d} available space",['server' => $cluster->name, 'd' => '—']).' ' . \Yii::$app->formatter->asShortSize($cluster->getFreeSpace()) . ' (' . round($cluster->getFreeSpacePct()) . '%)');
                } else
                {
                    $this->addError(\Yii::t('skeeks/cms',"Server {server} {d} available space",['server' => $cluster->name, 'd' => '—']).' ' . \Yii::$app->formatter->asShortSize($cluster->getFreeSpace()) . ' (' . round($cluster->getFreeSpacePct()) . '%)');
                }
            }
        }
    }

}
