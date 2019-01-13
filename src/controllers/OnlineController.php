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
use yii\helpers\Json;
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
        $callback = \Yii::$app->request->get('callback');

        $rr = new RequestResponse();
        $rr->data['call'] = \Yii::$app->request->get('callback');
        $rr->success = true;

        $data = Json::encode($rr->toArray());
        return "{$callback}({$data})";
    }

}
