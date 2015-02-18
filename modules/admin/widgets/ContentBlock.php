<?php
/**
 * ContentBlock
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.02.2015
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets;
use yii\base\Widget;


/**
 * Class ContentBlock
 * @package skeeks\cms\modules\admin\widgets
 */
class ContentBlock
    extends Widget
{

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        $breadcrumbs = \yii\widgets\Breadcrumbs::widget([
                    'homeLink' => ['label' => \Yii::t("yii", "Home"), 'url' => [
                        'admin/index',
                        'namespace' => 'admin'
                    ]],
                    'links' => isset(\Yii::$app->view->params['breadcrumbs']) ? \Yii::$app->view->params['breadcrumbs'] : [],
                ]);

        $alert = \skeeks\cms\modules\admin\widgets\Alert::widget();
        $actions = \Yii::$app->view->params['actions'];

        echo <<<HTML
        <div class="main">
            <div class="col-lg-12">
                <div class="panel panel-primary sx-panel">
                    <div class="panel-heading sx-no-icon">
                        <h2>
                            {$breadcrumbs}
                        </h2>
                        <div class="panel-actions"></div>
                    </div><!-- End .panel-heading -->
                    <div class="panel-body">
                            <div class="panel-content-before">
                                {$actions}
                            </div>
                            <div class="panel-content">
                                    {$alert}

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
    </div>
</div>
HTML;

    }
}