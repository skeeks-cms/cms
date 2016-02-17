<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.09.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use yii\base\Widget;
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class AdminPanelWidget
 * @package skeeks\cms\modules\admin\widgets
 */
class AdminPanelWidget extends Widget
{
    public $options = [];

    public $name;
    public $content;
    public $buttons;

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        $classses = "panel panel-primary sx-panel sx-panel-content";
        if ($class = ArrayHelper::getValue($this->options, 'class'))
        {
            $classses .= " " . $class;
        }

        $options = ArrayHelper::merge($this->options, [
            'id'    => $this->id,
            'class' => $classses,
        ]);

        echo Html::beginTag('div', $options);

        echo <<<HTML
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
HTML;

        echo Html::endTag('div');

    }
}