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
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentProperty2content;
use skeeks\cms\models\CmsSearchPhrase;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUser;
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
 * Class UpdateController
 * @package skeeks\cms\console\controllers
 */
class UpdateController extends Controller
{
    /**
     * Update user name to first and last name
     */
    public function actionUserNameToFirstName()
    {
        ini_set("memory_limit", "1G");

        if (!CmsUser::find()->count()) {
            $this->stdout("Content not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("1. Cms user name normalize!\n", Console::FG_YELLOW);

        /**
         * @var CmsUser $cmsUser
         */
        foreach (CmsUser::find()->orderBy(['id' => SORT_ASC])->each(10) as $cmsUser) {
            $this->stdout("\t User: {$cmsUser->id}\n", Console::FG_YELLOW);

            if (!$cmsUser->_to_del_name) {
                $this->stdout("\t NOT found property: _to_del_name\n", Console::FG_YELLOW);
                continue;
            }

            $name = $cmsUser->_to_del_name;
            $nameData = explode(" ", $name);

            if (isset($nameData[0])) {
                $cmsUser->first_name = trim($nameData[0]);
            }

            if (isset($nameData[1])) {
                $cmsUser->last_name = trim($nameData[1]);
            }

            if (isset($nameData[2])) {
                $cmsUser->patronymic = trim($nameData[2]);
            }


            if ($cmsUser->save()) {
                $this->stdout("\t Updated name: {$cmsUser->name}\n", Console::FG_GREEN);
            } else {
                $this->stdout("\t NOT updated name: {$cmsUser->id}\n", Console::FG_RED);
            }
        }
    }


    /**
     *
     */
    public function actionContentPropertyResave()
    {
        ini_set("memory_limit", "1G");

        if (!$count = CmsContentElement::find()->count()) {
            $this->stdout("Content elements not found!\n", Console::BOLD);
            return;
        }

        $this->stdout("Content elements found: {$count}\n", Console::BOLD);

        /**
         * @var $element CmsContentElement
         */
        foreach (CmsContentElement::find()
                     //->orderBy(['id' => SORT_ASC])
                     ->each(10) as $element) {
            $this->stdout("\t Element: {$element->id}", Console::FG_YELLOW);

            if ($element->relatedPropertiesModel->save()) {
                $this->stdout(" - saved\n", Console::FG_GREEN);
            } else {
                $this->stdout(" - NOT saved\n", Console::FG_RED);
            }
        }


    }
}