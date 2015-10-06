<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.10.2015
 */

namespace skeeks\cms\modules\admin\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class BlockTitleWidget extends Widget
{
    /**
     * @var array the HTML attributes for the widget container tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = [];
    public $content = "";

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->view->registerCss(<<<CSS
.sx-admin-block-title
{
    background: #e0e8ea;
    padding: 8px 70px 10px!important;
    font-weight: bold;
    text-align: center!important;
    color: #4b6267;
    text-shadow: 0 1px #fff;
    font-size: 14px;
    margin-bottom: 5px;
}
CSS
);
        Html::addCssClass($this->options, 'sx-admin-block-title');
        echo Html::tag('div', $this->content, $this->options);
    }
}