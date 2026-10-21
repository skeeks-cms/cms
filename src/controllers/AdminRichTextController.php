<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendController;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\services\RemoteImageDownloader;
use skeeks\cms\services\TemporaryUploads;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Images pasted or dropped into RichTextInputWidget. They go to the CMS
 * temporary upload area, not to the storage: RichTextBehavior stores them
 * when the record is saved. Only the uploading session can preview them.
 */
class AdminRichTextController extends BackendController
{
    /** The largest image accepted by the editor. */
    public const MAX_SIZE = 15 * 1024 * 1024;

    public function init()
    {
        $this->name = 'Редактор текста';
        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ADMIN_ACCESS;

        parent::init();
    }

    public function actionUpload()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        if (!\Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException('Используйте POST.');
        }
        $file = UploadedFile::getInstanceByName('file');
        if (!$file || $file->error !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Картинка не загрузилась: возможно, она слишком большая.'];
        }
        if ($file->size > self::MAX_SIZE) {
            return ['success' => false, 'message' => 'Картинка больше '.(self::MAX_SIZE / 1024 / 1024).' МБ.'];
        }
        $type = (string)\yii\helpers\FileHelper::getMimeType($file->tempName);
        if (!TemporaryUploads::isImage($type)) {
            return ['success' => false, 'message' => 'В текст можно вставить только картинку: PNG, JPEG, GIF или WebP.'];
        }
        $upload = TemporaryUploads::put($file, 'rt');

        return [
            'success' => true,
            'token' => $upload['token'],
            'name' => $upload['name'],
            'size' => $upload['size'],
            'sizeText' => \Yii::$app->formatter->asShortSize($upload['size']),
            'image' => true,
            'url' => self::fileUrl($upload['token']),
        ];
    }

    /**
     * A picture of text pasted from another site (POST url): downloaded into
     * the temporary area as if it were chosen from a disk, so the saved text
     * keeps our copy instead of a link to someone else's server.
     */
    public function actionImport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        if (!\Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException('Используйте POST.');
        }
        try {
            $image = RemoteImageDownloader::download(trim((string)\Yii::$app->request->post('url')));
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $upload = TemporaryUploads::putBytes($image['bytes'], $image['name'], 'rt');

        return [
            'success' => true,
            'token' => $upload['token'],
            'name' => $upload['name'],
            'size' => $upload['size'],
            'image' => true,
            'url' => self::fileUrl($upload['token']),
        ];
    }

    /** A temporary image of this session (?t=token), for the editor preview. */
    public function actionFile($t = '')
    {
        $path = TemporaryUploads::path((string)$t);
        $type = $path ? (string)\yii\helpers\FileHelper::getMimeType($path) : '';
        if (!$path || !TemporaryUploads::isImage($type)) {
            throw new NotFoundHttpException('Файл не найден или устарел.');
        }
        \Yii::$app->response->headers->set('X-Content-Type-Options', 'nosniff');
        \Yii::$app->response->headers->set('Cache-Control', 'private, max-age=86400');

        return \Yii::$app->response->sendFile($path, TemporaryUploads::name((string)$t), ['inline' => true, 'mimeType' => $type]);
    }

    public static function fileUrl(string $token): string
    {
        return \yii\helpers\Url::to(['/cms/admin-rich-text/file', 't' => $token]);
    }
}
