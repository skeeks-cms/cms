<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\console\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentProperty2content;
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

        foreach ($controllerHelp->getCommands() as $controller) {
            $subController = \Yii::$app->createController($controller)[0];
            $actions = $controllerHelp->getActions($subController);

            if ($actions) {
                foreach ($actions as $actionId) {
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
        ini_set('memory_limit', '2048M');

        if ($files = StorageFile::find()->count()) {
            foreach (StorageFile::find()->orderBy(['id' => SORT_ASC])->each(100) as $file) {
                $this->stdout("{$file->id}");
                if ($file->deleteTmpDir()) {
                    $this->stdout(" - true\n", Console::FG_GREEN);
                } else {
                    $this->stdout(" - false\n", Console::FG_RED);
                }
            }
        }
    }


    /**
     * Tree normalization
     */
    public function actionNormalizeTree()
    {
        if (!CmsTree::find()->count()) {
            $this->stdout("Tree not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Tree normalize pids!\n", Console::FG_YELLOW);

        /**
         * @var CmsTree $tree
         */
        foreach (CmsTree::find()->orderBy(['id' => SORT_ASC])->andWhere(['level' => 0])->each(10) as $tree) {
            $this->_normalizeTreePids($tree);
        }
    }

    protected function _normalizeTreePids(CmsTree $tree)
    {
        if ($tree->level == 0) {
            $this->stdout("\tStart normalize tree: {$tree->id} - {$tree->name}\n", Console::BOLD);
            if ((int)$tree->pids != (int)$tree->id) {
                if (\Yii::$app->db->createCommand()->update(CmsTree::tableName(), [
                    'pids' => $tree->id,
                ], ['id' => $tree->id])->execute()) {
                    $this->stdout("\t{$tree->id} - {$tree->name}: is normalized\n", Console::FG_GREEN);
                } else {
                    $this->stdout("\t{$tree->id} - {$tree->name}: not save!\n", Console::FG_RED);
                    return false;
                }
            }
        } else {
            $newPids = $tree->parent->pids . "/" . $tree->id;
            if ($newPids != $tree->pids) {
                if (\Yii::$app->db->createCommand()->update(CmsTree::tableName(), [
                    'pids' => $newPids,
                ], ['id' => $tree->id])->execute()) {
                    $this->stdout("\t{$tree->id} - {$tree->name}: is normalized\n", Console::FG_GREEN);
                } else {
                    $this->stdout("\t{$tree->id} - {$tree->name}: not save!\n", Console::FG_RED);
                    return false;
                }
            }
        }

        if ($tree->children) {
            foreach ($tree->children as $tree) {
                $this->_normalizeTreePids($tree);
            }
        }
    }


    /**
     * Tree normalization
     */
    public function actionNormalizeContent()
    {
        if (!CmsContent::find()->count()) {
            $this->stdout("Content not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Tree normalize content!\n", Console::FG_YELLOW);

        /**
         * @var CmsContentProperty $cmsContentProperty
         */
        foreach (CmsContentProperty::find()->orderBy(['id' => SORT_ASC])->each(10) as $cmsContentProperty) {
            $this->stdout("\t content property: {$cmsContentProperty->name}\n", Console::FG_YELLOW);

            if (!$cmsContentProperty->content_id) {
                continue;
            }

            if (!CmsContentProperty2content::find()
                ->where(['cms_content_id' => $cmsContentProperty->content_id])
                ->andWhere(['cms_content_property_id' => $cmsContentProperty->id])
                ->exists()
            ) {
                if ((new CmsContentProperty2content([
                    'cms_content_id' => $cmsContentProperty->content_id,
                    'cms_content_property_id' => $cmsContentProperty->id
                ]))->save()) {
                    $this->stdout("\t Created: {$cmsContentProperty->name}\n", Console::FG_GREEN);
                } else {
                    $this->stdout("\t NOT Created: {$cmsContentProperty->name}\n", Console::FG_RED);
                }
            }
        }
    }

    /**
     * Deleting content items
     *
     * @param null $contentId content id
     */
    public function actionRemoveContentElements($contentId = null)
    {
        $query = CmsContentElement::find();
        if ($contentId) {
            $query->andWhere(['content_id' => $contentId]);
        }

        if (!$count = $query->count()) {
            $this->stdout("Content elements not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Found elements: {$count}!\n", Console::BOLD);

        foreach ($query->orderBy([
            'content_id' => SORT_ASC,
            'id' => SORT_ASC
        ])->each(10) as $cmsContentElement) {
            $this->stdout("\t{$cmsContentElement->id}: {$cmsContentElement->name}");

            if ($cmsContentElement->delete()) {
                $this->stdout(" - deleted\n", Console::FG_GREEN);
            } else {
                $this->stdout(" - NOT deleted\n", Console::FG_RED);
            }
        }
    }
}