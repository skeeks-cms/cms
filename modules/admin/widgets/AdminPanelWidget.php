<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.09.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use yii\base\Widget;
use yii\helpers\Url;

/**
 * Class AdminPanelWidget
 * @package skeeks\cms\modules\admin\widgets
 */
class AdminPanelWidget extends Widget
{
    public $cssClass;
    public $name;
    public $content;
    public $buttons;

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        echo <<<HTML
<div class="panel panel-primary sx-panel sx-panel-content {$this->cssClass}" id="{$this->id}">
    <div class="panel-heading sx-no-icon">
        <div class="pull-left">
            <h2>
                {$this->name}
            </h2>
        </div>
        <div class="panel-actions">
            {$this->buttons}
        </div>
    </div><!-- End .panel-heading -->
    <div class="panel-body">
        <div class="panel-content">
            {$this->content}

HTML;

    }

    /**
     * Runs the widget.
     * This registers the necessary javascript code and renders the form close tag.
     * @throws InvalidCallException if `beginField()` and `endField()` calls are not matching
     */
    public function run()
    {

        echo <<<HTML
        </div>
    </div>
</div>
HTML;

    }
}