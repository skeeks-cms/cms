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
                        (<?=\Yii::t('app','see help')?>)
                    </small>
                </a>
            </p>
        </div>
        <div class="col-lg-4">
            <h2><?=\Yii::t('app','Update and Installation')?></h2>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii cms/update'); return false;">
                    php yii cms/update
                    <small>
                        (<?=\Yii::t('app','updating project')?>)
                    </small>
                </a>
            </p>

            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii cms/update --dry-run'); return false;">
                    php yii cms/update --dry-run
                    <small>
                        (<?=\Yii::t('app','simulation of update')?>)
                    </small>
                </a>
            </p>

        </div>
        <div class="col-lg-4">
            <h2><?=\Yii::t('app','Utilities')?></h2>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('php yii cms/composer/status'); return false;">
                    php yii cms/composer/status
                    <small>
                        (<?=\Yii::t('app','watch the kernel modification')?>)
                    </small>
                </a>
            </p>


            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/utils/clear-runtimes'); return false;">
                    cms/utils/clear-runtimes
                    <small>
                        (<?=\Yii::t('app','clearing temporary data')?>)
                    </small>
                </a>
            </p>

            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/utils/clear-assets'); return false;">
                    cms/utils/clear-assets
                    <small>
                        (<?=\Yii::t('app','Cleaning temporary {css} and {js}',['css' => 'css', 'js' => 'js'])?>)
                    </small>
                </a>
            </p>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-3">
            <h2><?=\Yii::t('app','Work to database')?></h2>
            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/dump-list'); return false;">
                    cms/db/dump-list
                    <small>
                        (<?=\Yii::t('app','list of database backups')?>)
                    </small>
                </a>
            </p>

            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/db-refresh'); return false;">
                    cms/db/db-refresh
                    <small>
                        (<?=\Yii::t('app','reset cache of table structure')?>)
                    </small>
                </a>
            </p>

            <p>
                <a href="#" class="btn btn-default" onclick="sx.SshConsole.execute('cms/db/apply-migrations'); return false;">
                    cms/db/apply-migrations
                    <small>
                        (<?=\Yii::t('app','installing migration')?>)
                    </small>
                </a>
            </p>
        </div>
    </div>
</div>
