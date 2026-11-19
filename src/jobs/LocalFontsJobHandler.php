<?php
namespace skeeks\cms\jobs;

use skeeks\cms\components\LocalFonts;
use skeeks\cms\job\contracts\JobReporterInterface;
use skeeks\cms\job\exceptions\JobCancelledException;
use skeeks\cms\job\exceptions\JobPermanentException;
use skeeks\cms\job\handlers\AbstractJobHandler;
use skeeks\cms\job\runtime\JobContext;

/**
 * Скачивание локальной копии шрифтов Google (тип задания cms.local-fonts).
 *
 * Ставится из LocalFonts::queue() при показе страницы, на которой копии ещё нет.
 * payload: url — нормализованный адрес CSS Google; dir — папка копий
 * `<assets>/sx-fonts` (у консольного воркера нет asset manager веб-сайта).
 * Папка принимается, только если это `<...>/<dirName>` внутри корня проекта
 * (@root) после разрешения симлинков. Настройки сборки — из компонента
 * localFonts приложения (тот же конфиг, что у веба).
 */
class LocalFontsJobHandler extends AbstractJobHandler
{
    public function run(JobContext $context, JobReporterInterface $reporter): void
    {
        /** @var LocalFonts $fonts */
        $fonts = \Yii::$app->has('localFonts') ? clone \Yii::$app->get('localFonts') : new LocalFonts();

        $url = (string) $context->get('url', '');
        if (!$fonts->isGoogleFontsUrl($url)) {
            throw new JobPermanentException('Адрес не похож на CSS шрифтов Google.');
        }
        $fonts->setStorageDir($this->resolveDir((string) $context->get('dir', ''), $fonts->dirName));

        $reporter->setStage('download', 'Скачивание шрифтов');
        $reporter->heartbeat();
        if ($fonts->isBuilt($url)) {
            $reporter->setResult(['built' => true, 'already' => true]);
            return;
        }

        $progress = static function () use ($reporter) {
            $reporter->heartbeat();
            if ($reporter->isCancelled()) {
                throw new JobCancelledException('Скачивание шрифтов отменено.');
            }
        };
        //Свежая проверка отмены прямо перед публикацией: isCancelled() кеширует ответ на секунды.
        $beforePublish = static function () use ($context, $reporter) {
            $run = $context->getRun();
            $cancelled = $run && $run->id
                ? (bool) \skeeks\cms\job\models\CmsJobRun::find()->select(['cancel_requested_at'])->andWhere(['id' => $run->id])->scalar()
                : $reporter->isCancelled();
            if ($cancelled) {
                throw new JobCancelledException('Скачивание шрифтов отменено.');
            }
        };
        //Пауза после сбоя для задания не действует: страницы ставят его не чаще retryAfter.
        if (!$fonts->build($url, true, $progress, $beforePublish) && !$fonts->isBuilt($url)) {
            throw new \RuntimeException('Не удалось скачать шрифты: '.$url);
        }
        $reporter->countSuccess();
        $reporter->setResult(['built' => true]);
    }

    /**
     * Папка копий внутри корня проекта.
     *
     * @param string $dir
     * @param string $dirName
     * @return string
     * @throws JobPermanentException
     */
    protected function resolveDir($dir, $dirName)
    {
        $dir = rtrim($dir, '/\\');
        if ($dir === '' || basename($dir) !== $dirName) {
            throw new JobPermanentException('Неверная папка копий шрифтов.');
        }
        $rootAlias = \Yii::getAlias('@root', false);
        $root = $rootAlias ? realpath($rootAlias) : false;
        $inside = static function ($path) use ($root) {
            return $path !== false && $root !== false
                && strpos($path.DIRECTORY_SEPARATOR, rtrim($root, '/\\').DIRECTORY_SEPARATOR) === 0;
        };

        $parent = realpath(dirname($dir));
        if (!$inside($parent)) {
            throw new JobPermanentException('Папка копий шрифтов вне проекта.');
        }
        $target = $parent.DIRECTORY_SEPARATOR.$dirName;
        //Сама папка sx-fonts: симлинк не принимаем, существующая должна быть внутри проекта.
        if (is_link($target)) {
            throw new JobPermanentException('Папка копий шрифтов — символическая ссылка.');
        }
        if (file_exists($target) && (!is_dir($target) || !$inside(realpath($target)))) {
            throw new JobPermanentException('Папка копий шрифтов вне проекта.');
        }
        return $target;
    }
}
