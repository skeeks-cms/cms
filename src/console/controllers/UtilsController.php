<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsSearchPhrase;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\StorageFile;
use skeeks\sx\Dir;
use Yii;
use yii\base\Event;
use yii\console\Controller;
use yii\console\controllers\HelpController;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Productivity SkeekS CMS
 *
 * @package skeeks\cms\console\controllers
 */
class UtilsController extends Controller
{
    /**
     * Получение списка всех возможных консольных команд
     * Используется в console ssh для автокомплита
     */
    public function actionAllCmd()
    {
        /**
         * @var $controllerHelp HelpController
         */
        $controllerHelp = \Yii::$app->createController('help')[0];
        $commands = $controllerHelp->getCommands();

        foreach ($controllerHelp->getCommands() as $controller)
        {
            $subController = \Yii::$app->createController($controller)[0];
            $actions = $controllerHelp->getActions($subController);

            if ($actions)
            {
                foreach ($actions as $actionId)
                {
                    $commands[] = $controller . "/" . $actionId;
                }
            }
        };

        $this->stdout(implode("\n", $commands));
    }

    /**
     * Читка всех сгенерированных миниатюр
     */
    public function actionClearAllThumbnails()
    {
        /**
         * @var $files StorageFile[]
         */
        if ($files = StorageFile::find()->all())
        {
            foreach ($files as $file)
            {
                $file->deleteTmpDir();
            }
        }
    }


    /**
     * Tree normalization
     */
    public function actionNormalizeTree()
    {
        if (!CmsTree::find()->count())
        {
            $this->stdout("Tree not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Tree normalize pids!\n", Console::FG_YELLOW);

        /**
         * @var CmsTree $tree
         */
        foreach (CmsTree::find()->orderBy(['id' => SORT_ASC])->andWhere(['level' => 0])->each(10) as $tree)
        {
            $this->_normalizeTreePids($tree);
        }
    }

    protected function _normalizeTreePids(CmsTree $tree)
    {
        if ($tree->level == 0)
        {
            $this->stdout("\tStart normalize tree: {$tree->id} - {$tree->name}\n", Console::BOLD);
            if ((int) $tree->pids != (int) $tree->id)
            {
                if (\Yii::$app->db->createCommand()->update(CmsTree::tableName(), [
                    'pids' => $tree->id,
                ], ['id' => $tree->id])->execute())
                {
                    $this->stdout("\t{$tree->id} - {$tree->name}: is normalized\n", Console::FG_GREEN);
                } else
                {
                    $this->stdout("\t{$tree->id} - {$tree->name}: not save!\n", Console::FG_RED);
                    return false;
                }
            }
        } else
        {
            $newPids = $tree->parent->pids . "/" . $tree->id;
            if ($newPids != $tree->pids)
            {
                if (\Yii::$app->db->createCommand()->update(CmsTree::tableName(), [
                    'pids' => $newPids,
                ], ['id' => $tree->id])->execute())
                {
                    $this->stdout("\t{$tree->id} - {$tree->name}: is normalized\n", Console::FG_GREEN);
                } else
                {
                    $this->stdout("\t{$tree->id} - {$tree->name}: not save!\n", Console::FG_RED);
                    return false;
                }
            }
        }

        if ($tree->children)
        {
            foreach ($tree->children as $tree)
            {
                $this->_normalizeTreePids($tree);
            }
        }
    }

}