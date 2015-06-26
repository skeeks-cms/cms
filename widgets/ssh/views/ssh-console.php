<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.06.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\SshConsoleWidget */

$items = [];

if ($widget->enabledTabFastCmd == \skeeks\cms\components\Cms::BOOL_Y)
{
    $items[] = [
        'label' => '<i class="glyphicon glyphicon-question-sign"></i> быстрые команды',
        'encode' => false,
        'content' => $this->render('_fast-cmd', ['widget' => $this]),
        'active' => true
    ];
}

if ($widget->enabledTabHelp == \skeeks\cms\components\Cms::BOOL_Y)
{
    $items[] = [
        'label' => '<i class="glyphicon glyphicon-question-sign"></i> Помощь',
        'encode' => false,
        'content' => $this->render('_help', ['widget' => $this]),
    ];
}

if ($widget->enabledTabCmds == \skeeks\cms\components\Cms::BOOL_Y)
{
    $items[] = [
        'label' => '<i class="glyphicon glyphicon-question-sign"></i> Доступные команды CMS',
        'encode' => false,
        'content' => $this->render('_sx-cms-cmds', ['widget' => $this]),
    ];
}

if ($widget->enabledTabs != \skeeks\cms\components\Cms::BOOL_Y)
{
    $items = [];
}
?>
<div class="sx-widget-ssh-console" id="<?= $widget->id; ?>">
    <div class="sx-blocked-area">
        <iframe id="<?= $widget->iframeId; ?>" style="border: none; width: <?= $widget->consoleWidth; ?>; height: <?= $widget->consoleHeight; ?>;" data-src="<?= \skeeks\cms\helpers\UrlHelper::construct('/admin/ssh/console')->enableAdmin()->toString(); ?>"></iframe>
    </div>

    <? if ($items) : ?>
        <?= \yii\bootstrap\Tabs::widget([
            'items' => $items
        ]); ?>
    <? endif; ?>

</div>

<?
$options = $widget->getClientOptionsJson();

$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.classes.SshConsole = sx.classes.Component.extend({

            _init: function()
            {
                this.isReady = false;
                this.Blocker = null;
            },

            _initIframeConsole: function()
            {
                var self = this;

                this.IframeConsole.bind('submit', function(e, data)
                {
                    self.getBlocker().block();
                });

                this.IframeConsole.bind('error', function(e, data)
                {
                    self.getBlocker().unblock();
                });

                this.IframeConsole.bind('success', function(e, data)
                {
                    self.getBlocker().unblock();
                });

                this.trigger('ready');
                this.isReady = true;
            },

            /**
            *
            * @param callback
            * @returns {sx.classes.SshConsole}
            */
            onReady: function(callback)
            {
                if (this.isReady)
                {
                    callback();
                } else
                {
                    this.bind('ready', callback);
                }

                return this;
            },

            execute: function(cmd)
            {
                var self = this;
                this.onReady(function()
                {
                    self._execute(cmd);
                });

                return this;
            },

            _execute: function(cmd)
            {
                this.IframeConsole.input.val(cmd);
                this.IframeConsole.form.submit();

                return this;
            },

            /**
            * @returns {*|HTMLElement}
            */
            jWrapper: function()
            {
                return $("#" + this.get('id'));
            },


            /**
            *
            * @returns {null|*}
            */
            getBlocker: function()
            {
                if (!this.Blocker)
                {
                    this.Blocker = new sx.classes.Blocker(".sx-blocked-area");
                }

                return this.Blocker;
            },

            _onDomReady: function()
            {


                var self = this;
                this.IframeConsole = null;

                this.jIframe = $('#' + this.get('iframeId'));
                this.jIframe.attr('src', this.jIframe.data('src'));

                sx.Iframe = new sx.classes.Iframe(this.get('iframeId'), {
                   'autoHeight'     : false,                    //автоматически менять высоту фрейма
                   'heightTimer'    : 50000,
                   'scrolling'      : 'yes'
                });

                sx.Iframe.onSxReady(function()
                {
                    self.IframeConsole = sx.Iframe.sx.SshConsole;
                    self._initIframeConsole();
                });

            },

            _onWindowReady: function()
            {},
        });

        sx.SshConsole = new sx.classes.SshConsole($options);


    })(sx, sx.$, sx._);
JS
)
?>