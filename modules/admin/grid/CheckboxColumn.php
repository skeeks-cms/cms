<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.07.2015
 */
namespace skeeks\cms\modules\admin\grid;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class CheckboxColumn
 * @package skeeks\cms\modules\admin\grid
 */
class CheckboxColumn extends \yii\grid\CheckboxColumn
{
    protected function renderHeaderCellContent()
    {
        $this->checkboxOptions = ArrayHelper::merge(['class' => 'sx-admin-grid-checkbox'], $this->checkboxOptions);

        $id = $this->grid->options['id'];
        $jsOptions = [
            'gridId' => $id
        ];

        $jsOptionsString = Json::encode($jsOptions);

        $this->grid->getView()->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.classes.CheckboxAdmin = sx.classes.Component.extend({

                _onDomReady: function()
                {

                    $('.select-on-check-all').on('click', function()
                    {
                        _.delay(function()
                        {
                            $('.sx-admin-grid-checkbox').each(function()
                            {
                                if ( $(this).is(":checked") )
                                {
                                    $(this).closest("tr").addClass("sx-active");
                                } else
                                {
                                    $(this).closest("tr").removeClass("sx-active");
                                }

                            });

                        }, 100);

                    });

                    $('.sx-admin-grid-checkbox').on('click', function()
                    {
                        if ( $(this).is(":checked") )
                        {
                            $(this).closest("tr").addClass("sx-active");
                        } else
                        {
                            $(this).closest("tr").removeClass("sx-active");
                        }
                    });
                },

                _onWindowReady: function()
                {}
            });

            new sx.classes.CheckboxAdmin({$jsOptionsString});
        })(sx, sx.$, sx._);
JS
);

        return parent::renderHeaderCellContent();
    }
}