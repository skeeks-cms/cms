<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\console\controllers;

use skeeks\cms\components\storage\ClusterLocal;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentProperty2content;
use skeeks\cms\models\CmsSearchPhrase;
use skeeks\cms\models\CmsStorageFile;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUserPhone;
use skeeks\cms\models\StorageFile;
use skeeks\cms\shop\models\ShopCmsContentElement;
use skeeks\cms\Skeeks;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\controllers\HelpController;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Productivity SkeekS CMS
 *
 * @package skeeks\cms\console\controllers
 */
class TelephonyController extends Controller
{
    public function actionUploadRecords()
    {
        $q = CmsTelephonyCall::find()
            ->andWhere(['cms_record_file_id' => null])
            ->andWhere(['is not', 'record_url', null])
            ->andWhere(['in', 'status', CmsTelephonyCall::STATUS_ANSWERED])
            ->orderBy(['id' => SORT_DESC])
        ;
        $count = $q->count();

        $this->stdout("Записей: {$count}\n");

        /**
         * @var $call CmsTelephonyCall
         */
        foreach ($q->each(100) as $call)
        {
            $this->stdout("{$call->id}\n");
            $call->provider->handler->saveRecord($call);
        }
    }
}