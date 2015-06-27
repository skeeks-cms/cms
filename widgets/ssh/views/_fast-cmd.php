<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.06.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\SshConsoleWidget */
?>
<div class="sx-fast-cmd sx-blocked-area">
    <div class="row">
        <div class="col-lg-4">
            <h2>Help</h2>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii help'); return false;">
                    php yii help
                    <small>
                        (посмотреть хелп)
                    </small>
                </a>
            </p>
        </div>
        <div class="col-lg-4">
            <h2>Обновление и установка</h2>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii cms/update'); return false;">
                    php yii cms/update
                    <small>
                        (обновление проекта)
                    </small>
                </a>
            </p>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/apply-migrations'); return false;">
                    cms/db/apply-migrations
                    <small>
                        (установка миграций)
                    </small>
                </a>
            </p>
        </div>
        <div class="col-lg-4">
            <h2>Утилиты</h2>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii cms/composer/status'); return false;">
                    php yii cms/composer/status
                    <small>
                        (смотреть модицикации ядра)
                    </small>
                </a>
            </p>


            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/utils/clear-runtimes'); return false;">
                    cms/utils/clear-runtimes
                    <small>
                        (чистка временных данных)
                    </small>
                </a>
            </p>

            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/utils/clear-assets'); return false;">
                    cms/utils/clear-assets
                    <small>
                        (чистка временных css и js)
                    </small>
                </a>
            </p>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-3">
            <h2>Работа с базой данных</h2>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/dump-list'); return false;">
                    cms/db/dump-list
                    <small>
                        (список бэкапов базы)
                    </small>
                </a>
            </p>

            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/db-refresh'); return false;">
                    cms/db/db-refresh
                    <small>
                        (сброс кэша структуры таблиц)
                    </small>
                </a>
            </p>
        </div>
    </div>
</div>
