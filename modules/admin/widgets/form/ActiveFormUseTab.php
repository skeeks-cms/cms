<?php
/**
 * ActiveFormUseTab
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\widgets\form;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\validators\db\IsNewRecord;
use skeeks\sx\validate\Validate;
use yii\base\Model;
use yii\helpers\Html;

/**
 * Class ActiveFormUseTab
 * @package skeeks\cms\modules\admin\widgets\form
 */
class ActiveFormUseTab extends \skeeks\cms\modules\admin\widgets\ActiveForm
{
    protected $_tabs = [];

    public function tabRun($name)
    {
        $this->_tabs[] = $name;
        $counter = count($this->_tabs);

        return <<<HTML
        <div class="sx-form-tab tab-pane active" id="sx-form-tab-id-{$counter}" role="tabpanel">
HTML;

    }

    public function tabEnd()
    {
        return <<<HTML
        </div>
HTML;

    }


    /**
     * Runs the widget.
     * This registers the necessary javascript code and renders the form close tag.
     * @throws InvalidCallException if `beginField()` and `endField()` calls are not matching
     */
    public function run()
    {
        $view = $this->getView();
        //$view->registerJs("jQuery('#$id').yiiActiveForm($attributes, $options);");
        echo <<<HTML
        <div role="tabpanel">

          <!-- Nav tabs -->
          <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Home</a></li>
            <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Profile</a></li>
            <li role="presentation"><a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">Messages</a></li>
            <li role="presentation"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="home">1</div>
            <div role="tabpanel" class="tab-pane" id="profile">2</div>
            <div role="tabpanel" class="tab-pane" id="messages">3</div>
            <div role="tabpanel" class="tab-pane" id="settings">4</div>
          </div>

        </div>
HTML;

        parent::run();
    }
}