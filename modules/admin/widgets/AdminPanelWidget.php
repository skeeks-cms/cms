<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.09.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class AdminPanelWidget
 * @package skeeks\cms\modules\admin\widgets
 */
class AdminPanelWidget extends Widget
{
    /**
     * Widget options
     *
     *  'class' => 'sx-dashboard-widget',
        'data'      =>
        [
            'id' => 1
        ],
     * @var array
     */
    public $options = [];



    /**
     * Widget color scheme
     *
     * panel-primary
     * panel-success
     * panel-danger
     *
     * @var string
     */
    public $color = 'panel-primary';

    /**
     * Panel heading options
     *
     * @var array
     */
    public $headingOptions = [
        'class' => 'panel-heading'
    ];


    /**
     * Panel body options
     *
     * @var array
     */
    public $bodyOptions = [
        'class' => 'panel-body'
    ];


    /**
     * @var Название панели
     */
    public $name;
    /**
     * @var Содержимое
     */
    public $content;

    /**
     * @var Кнопки действий
     */
    public $actions;

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        Html::addCssClass($this->options, ['panel', 'sx-panel', $this->color]);

        $options = ArrayHelper::merge($this->options, [
            'id'    => $this->id,
        ]);

        echo Html::beginTag('div', $options);

            echo Html::beginTag('div', $this->headingOptions);

echo<<<HTML

                <div class="pull-left">
                    <h2>
                        {$this->name}
                    </h2>
                </div>
                <div class="panel-actions panel-hidden-actions">
                    {$this->actions}
                </div>
HTML;

            echo Html::endTag('div');

            echo Html::beginTag('div', $this->bodyOptions);

                echo '<div class="panel-content">' . $this->content;

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
HTML;

            echo Html::endTag('div');
        echo Html::endTag('div');

    }
}