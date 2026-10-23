<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\services\TemporaryUploads;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Files attached to a form before it is saved (AttachmentsInputWidget). They
 * wait in the CMS temporary upload area, bound to the uploader's session,
 * and reach the storage only when the form's record is saved. Any signed-in
 * user: administration and personal cabinets (UPA) use the same field; what
 * a user may save is checked by the action that saves the record.
 */
class TemporaryUploadController extends Controller
{
    /** The largest attached file. */
    public const MAX_SIZE = 50 * 1024 * 1024;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['attach' => ['POST']],
            ],
        ];
    }

    public function actionAttach()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!$file || $file->error !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Файл не загрузился: возможно, он слишком большой.'];
        }
        if ($file->size > self::MAX_SIZE) {
            return ['success' => false, 'message' => 'Файл «'.$file->name.'» больше '.(self::MAX_SIZE / 1024 / 1024).' МБ.'];
        }
        $upload = TemporaryUploads::put($file, 'att');

        return [
            'success' => true,
            'token' => $upload['token'],
            'name' => $upload['name'],
            'size' => $upload['size'],
            'sizeText' => \Yii::$app->formatter->asShortSize($upload['size']),
        ];
    }
}
