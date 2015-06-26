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
    <iframe style="border: none; width: <?= $widget->consoleWidth; ?>; height: <?= $widget->consoleHeight; ?>;" src="<?= \skeeks\cms\helpers\UrlHelper::construct('/admin/ssh/console')->enableAdmin()->toString(); ?>"></iframe>

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
            {},

            _onDomReady: function()
            {},

            _onWindowReady: function()
            {},

            execute: function(cmd)
            {

            },

            /**
            * @returns {*|HTMLElement}
            */
            jWrapper: function()
            {
                return $("#" + this.get('id'));
            },
        });

        sx.SshConsole = new sx.classes.SshConsole($options);


    })(sx, sx.$, sx._);
JS
)
?>