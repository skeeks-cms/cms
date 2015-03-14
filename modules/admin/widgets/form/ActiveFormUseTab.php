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
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class ActiveFormUseTab
 * @package skeeks\cms\modules\admin\widgets\form
 */
class ActiveFormUseTab  extends \skeeks\cms\modules\admin\widgets\ActiveForm
{
    protected $_tabs = [];

    public function fieldSet($name, $options = [])
    {
        if (!$id = ArrayHelper::getValue($options, 'id'))
        {
            $options['id']         = "sx-form-tab-id-" . md5($name);
        }

        $this->_tabs[$id] = $name;

        return <<<HTML
        <div class="sx-form-tab tab-pane" id="{$options['id']}" data-name="{$name}" role="tabpanel">
HTML;

    }

    public function fieldSetEnd()
    {
        return <<<HTML
        </div>
HTML;

    }



    public function init()
    {
        parent::init();

        echo<<<HTML
        <div role="tabpanel" class="sx-form-tab-panel">
            <ul class="nav nav-tabs" role="tablist">
            </ul>
            <div class="tab-content">
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

        $view->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.classes.FormUseTabs = sx.classes.Component.extend({

                _init: function()
                {
                    this.activeTab = window.location.hash.replace("#","");
                },

                _onDomReady: function()
                {
                    var self = this;

                    var counter = 0;
                    $('.sx-form-tab').each(function(i,s)
                    {
                        counter = counter + 1;

                        var Jcontroll = $("<a>", {
                            'href' : '#' + $(this).attr('id'),
                            'aria-controls' : $(this).attr('id'),
                            'role' : 'tab',
                            'data-toggle' : 'tab',
                        }).append($(this).data('name'));

                        Jcontroll.on('click', function()
                        {
                            location.href = $(this).attr("href");
                        });

                        var Jli = $("<li>", {
                            'role' : 'presentation',
                            'class' : 'presentation'
                        }).append(Jcontroll);


                        if (self.activeTab)
                        {
                             if (self.activeTab == $(this).attr('id'))
                            {
                                Jli.addClass("active");
                                $(this).addClass("active");
                            }
                        } else
                        {
                            if (counter == 1)
                            {
                                Jli.addClass("active");
                                $(this).addClass("active");
                            }
                        }

                        $('.sx-form-tab-panel .nav').append(Jli);
                    });



                },

                _onWindowReady: function()
                {}
            });

            new sx.classes.FormUseTabs();

        })(sx, sx.$, sx._);
JS
);

        //$view->registerJs("jQuery('#$id').yiiActiveForm($attributes, $options);");
        echo <<<HTML
        <!--<div role="tabpanel">

          &lt;!&ndash; Nav tabs &ndash;&gt;
          <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Home</a></li>
            <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Profile</a></li>
            <li role="presentation"><a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">Messages</a></li>
            <li role="presentation"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li>
          </ul>

          &lt;!&ndash; Tab panes &ndash;&gt;
          <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="home">1</div>
            <div role="tabpanel" class="tab-pane" id="profile">2</div>
            <div role="tabpanel" class="tab-pane" id="messages">3</div>
            <div role="tabpanel" class="tab-pane" id="settings">4</div>
          </div>-->

            </div>
        </div>
HTML;

        parent::run();
    }
}