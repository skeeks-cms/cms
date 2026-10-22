<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\services;

use skeeks\cms\models\CmsStorageFile;
use yii\web\UploadedFile;

/**
 * Files uploaded into a form before its record is saved (editor images,
 * pasted screenshots, letter attachments). They wait in the CMS temporary
 * upload area, the ajaxfileupload module private_tmp_dir, one random
 * directory per file, and reach the storage only when the owner is saved
 * (store()). Abandoned files are removed by the daily
 * "cms.cleanup-temporary-files" job (ajaxfileupload/cleanup).
 *
 * A file is referred to by an opaque token "<prefix>-<32 hex>/<name>", never
 * by a path. Tokens are bound to the uploader's session: only that session
 * can preview, use or store the file. The binding is an owner mark in the
 * token directory (a keyed hash of the session id), not a list in the
 * session data: parallel uploads of one session never overwrite each other's
 * tokens. The daily cleanup removes the mark together with the file.
 */
class TemporaryUploads
{
    /** Token form: prefix, random key, file name without path parts. */
    public const TOKEN = '~^[a-z]{2,10}-[0-9a-f]{32}/[^/\\\\"<>]+$~u';

    /** Raster images shown in a page; everything else is only downloaded. */
    public const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /** Owner mark in the token directory; a file name never starts with a dot (safeName()). */
    private const OWNER_FILE = '.owner';

    /**
     * Saves an uploaded file into the temporary area.
     *
     * @param string $prefix short latin label of the uploader ("rt", "mail")
     * @return array token, name, size
     */
    public static function put(UploadedFile $file, string $prefix = 'rt'): array
    {
        return self::place((string)$file->name, $prefix, static fn(string $path) => $file->saveAs($path, false));
    }

    /**
     * Saves file contents obtained another way (a picture downloaded by URL)
     * into the temporary area, exactly like an uploaded file.
     *
     * @return array token, name, size
     */
    public static function putBytes(string $bytes, string $name, string $prefix = 'rt'): array
    {
        return self::place($name, $prefix, static fn(string $path) => file_put_contents($path, $bytes) === strlen($bytes));
    }

    private static function place(string $name, string $prefix, callable $write): array
    {
        if (!preg_match('/^[a-z]{2,10}$/', $prefix)) {
            throw new \InvalidArgumentException('Invalid upload prefix.');
        }
        $owner = self::owner();
        if ($owner === null) {
            throw new \RuntimeException('Нет сессии для загрузки файла.');
        }
        $key = $prefix.'-'.bin2hex(random_bytes(16));
        $name = self::safeName($name);
        $dir = self::root().DIRECTORY_SEPARATOR.$key;
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Не удалось сохранить файл.');
        }
        $path = $dir.DIRECTORY_SEPARATOR.$name;
        if (file_put_contents($dir.DIRECTORY_SEPARATOR.self::OWNER_FILE, $owner) !== strlen($owner) || !$write($path)) {
            throw new \RuntimeException('Не удалось сохранить файл.');
        }
        $token = $key.'/'.$name;

        return ['token' => $token, 'name' => $name, 'size' => (int)filesize($path)];
    }

    /** Path of a temporary file of this session, or null. */
    public static function path(string $token): ?string
    {
        $dir = self::ownDir($token);
        $path = $dir !== null ? realpath(self::root().DIRECTORY_SEPARATOR.$token) : false;
        if (!$path || dirname($path) !== $dir || !is_file($path) || is_link($path)) {
            return null;
        }

        return $path;
    }

    /** Directory of a token uploaded by this session (the file itself may be gone), or null. */
    private static function ownDir(string $token): ?string
    {
        $owner = self::isToken($token) ? self::owner() : null;
        $root = $owner !== null ? realpath(self::root()) : false;
        $dir = $root ? realpath(self::root().DIRECTORY_SEPARATOR.dirname($token)) : false;
        if (!$dir || dirname($dir) !== $root || is_link(self::root().DIRECTORY_SEPARATOR.dirname($token))) {
            return null;
        }
        $mark = $dir.DIRECTORY_SEPARATOR.self::OWNER_FILE;
        if (!is_file($mark) || is_link($mark) || !hash_equals($owner, (string)file_get_contents($mark))) {
            return null;
        }

        return $dir;
    }

    /**
     * Owner mark of the current session: the session id hashed with the
     * application secret, so the mark does not reveal the session id.
     */
    private static function owner(): ?string
    {
        if (!\Yii::$app->has('session')) {
            return null;
        }
        $session = \Yii::$app->session;
        $session->open();
        $id = (string)$session->getId();
        if ($id === '') {
            return null;
        }
        $request = \Yii::$app->has('request') ? \Yii::$app->request : null;
        $secret = $request instanceof \yii\web\Request && (string)$request->cookieValidationKey !== ''
            ? $request->cookieValidationKey : \Yii::$app->id;

        return hash_hmac('sha256', $id, (string)$secret);
    }

    public static function isToken(string $token): bool
    {
        return (bool)preg_match(self::TOKEN, $token);
    }

    /** File name of a token as uploaded: no path parts or control characters. */
    public static function name(string $token): string
    {
        return basename($token);
    }

    /** Type of a temporary file detected from its content. */
    public static function type(string $token): ?string
    {
        $path = self::path($token);

        return $path ? (string)\yii\helpers\FileHelper::getMimeType($path) : null;
    }

    public static function isImage(?string $type): bool
    {
        return in_array(strtolower((string)$type), self::IMAGE_TYPES, true);
    }

    /**
     * Moves a temporary file into the CMS storage: called when the owner is
     * saved. The token is used up.
     *
     * @param array $data extra storage file attributes (cms_site_id in queue and console code)
     */
    public static function store(string $token, array $data = []): CmsStorageFile
    {
        $path = self::path($token);
        if (!$path) {
            throw new \RuntimeException('Файл «'.self::name($token).'» устарел: загрузите его заново.');
        }
        // upload() moves the file into the storage.
        $file = \Yii::$app->storage->upload($path, array_merge(['original_name' => mb_substr(self::name($token), 0, 255)], $data));
        self::forget($token);
        if (!$file || !$file->id) {
            throw new \RuntimeException('Файл не сохранён в хранилище.');
        }

        return $file instanceof CmsStorageFile ? $file : CmsStorageFile::findOne((int)$file->id);
    }

    /**
     * Forgets a token used up by its owner, removing what is left of the
     * file. A caller that consumed the file itself (moved or deleted it) calls this too.
     */
    public static function forget(string $token): void
    {
        $dir = self::ownDir($token);
        if ($dir === null) {
            return;
        }
        $path = self::path($token);
        if ($path !== null) {
            @unlink($path);
        }
        @unlink($dir.DIRECTORY_SEPARATOR.self::OWNER_FILE);
        @rmdir($dir);
    }

    private static function root(): string
    {
        return rtrim((string)\Yii::getAlias(\Yii::$app->getModule('ajaxfileupload')->private_tmp_dir), '/\\');
    }

    private static function safeName(string $name): string
    {
        $name = trim(preg_replace('/[\x00-\x1f\/\\\\:*?"<>|]+/u', '_', basename(str_replace('\\', '/', $name))), " .");
        if ($name === '') {
            $name = 'файл';
        }
        if (mb_strlen($name) > 150) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $name = mb_substr($name, 0, 140).($extension !== '' ? '.'.mb_substr($extension, 0, 8) : '');
        }

        return $name;
    }
}
