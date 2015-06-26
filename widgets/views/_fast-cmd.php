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
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii help'); return false;">php yii help</a> - посмотреть хелп</p>
        </div>
        <div class="col-lg-4">
            <h2>Обновление и установка</h2>
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii cms/update'); return false;">php yii cms/update</a> - обновление проекта</p>
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/apply-migrations'); return false;">cms/db/apply-migrations</a> - установка миграций</p>
        </div>
        <div class="col-lg-4">
            <h2>Утилиты</h2>
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii cms/composer/status'); return false;">php yii cms/composer/status</a> - модифицировалось ли ядро</p>
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/dump-list'); return false;">cms/db/dump-list</a> - список бэкапов базы</p>
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/db-refresh'); return false;">cms/db/db-refresh</a> - сброс кэша структуры таблиц</p>
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/utils/clear-runtimes'); return false;">cms/utils/clear-runtimes</a> - чистка временных данных</p>
            <p><a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/utils/clear-assets'); return false;">cms/utils/clear-assets</a> - чистка временных css и js</p>
        </div>
    </div>
</div>
