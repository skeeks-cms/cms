<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\user\UserAction;
use skeeks\cms\helpers\RequestResponse;
use yii\web\Controller;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class OnlineController extends Controller
{
    /**
     * @return RequestResponse
     */
    public function actionTrigger()
    {
        $rr = new RequestResponse();
        $rr->success = true;
        return $rr;
    }

}
