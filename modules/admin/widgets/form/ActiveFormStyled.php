<?php
/**
 * ActiveFormStyled
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\widgets\form;

use yii\base\Model;
use yii\helpers\Html;

/**
 * Class ActiveFormUseTab
 * @package skeeks\cms\modules\admin\widgets\form
 */
class ActiveFormStyled extends \skeeks\cms\modules\admin\widgets\ActiveForm
{
    public function fieldSet($name, $options = [])
    {
        return <<<HTML
        <div class="sx-form-fieldset">
            <h3 class="sx-form-fieldset-title"><a href="#">{$name}</a></h3>
            <div class="sx-form-fieldset-content">
HTML;

    }

    public function fieldSetEnd()
    {
        return <<<HTML
            </div>
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
        $view->registerCss(<<<CSS
        .sx-form-fieldset
        {
            border-bottom: 1px dashed silver;
        }
            .sx-form-fieldset .sx-form-fieldset-title
            {
                font-weight: bold;
            }
                .sx-form-fieldset .sx-form-fieldset-title a
                {
                    font-weight: bold;
                    border-bottom: 1px dashed silver;
                    text-decoration: none;
                }

                    .sx-form-fieldset .sx-form-fieldset-title a:hover
                    {
                        text-decoration: none;
                    }

            .sx-form-fieldset .sx-form-fieldset-content
            {
                padding: 0px 20px;
            }
CSS
);
        echo <<<HTML

HTML;

        parent::run();
    }
}