<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 02.03.2016
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\formInputs\SmartDurationInputWidget */

$widget = $this->context;
$model = $widget->model;
$this->registerCss(<<<CSS
.sx-smart-time-wrapper {
    padding-top: 3px;
    padding-bottom: 3px;
}
CSS
);
?>

<?= \yii\helpers\Html::beginTag('div', $widget->wrapperOptions); ?>

    <div style="display: none;">
        <div class="sx-real">
            <?= $element; ?>
        </div>
    </div>
    <div class="sx-not-real">
        <div class="input-group">
            <input class="form-control" type="number" step="1" style="max-width: 200px;">
            <?= \yii\helpers\Html::listBox("sx-not-real-select", $widget->defaultUnit, $widget->availableUnits, ['size' => 1, 'class' => 'form-control', 'style' => 'max-width: 70px;']) ?>
        </div>
    </div>


<?

$jsOptions = \yii\helpers\Json::encode($widget->clientOptions);
$availableUnits = \yii\helpers\Json::encode($widget->availableUnits);
$defaultUnit = \yii\helpers\Json::encode($widget->defaultUnit);


$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.SmartTimeWidget = sx.classes.Component.extend({
    
        _onDomReady: function()
        {
           var self = this;
           var availableUnits = {$availableUnits};
           var defaultUnit = {$defaultUnit};

           var normalizeUnit = function(unit) {
               if (availableUnits[unit]) {
                   return unit;
               }

               if (availableUnits[defaultUnit]) {
                   return defaultUnit;
               }

               for (var key in availableUnits) {
                   if (availableUnits.hasOwnProperty(key)) {
                       return key;
                   }
               }

               return 'sec';
           };

           var durationToSeconds = function(value, measure) {
               if (measure == 'min') {
                   return value * 60;
               } else if (measure == 'hour') {
                   return value * 3600;
               }

               return value;
           };

           var valueFromSeconds = function(value, measure) {
               if (measure == 'min') {
                   return value / 60;
               } else if (measure == 'hour') {
                   return value / 3600;
               }

               return value;
           };

           var updateStep = function(measure) {
               if (measure == 'min') {
                   self.getNotRealInput().attr("step", "0.01");
               } else if (measure == 'hour') {
                   self.getNotRealInput().attr("step", "0.0001");
               } else {
                   self.getNotRealInput().attr("step", "1");
               }
           };

           var applyNotRealValue = function() {
                var notRealVal = self.getNotRealInput().val();
                var measure = normalizeUnit(self.getNotRealSelect().val());

                self.getNotRealSelect().val(measure);
                self.getRealInput().val(durationToSeconds(notRealVal, measure));
           };
           
            this.getNotRealInput().on("keyup change", function() {
                applyNotRealValue();
            });
            
            this.getNotRealSelect().on("change", function() {
                var measure = normalizeUnit(self.getNotRealSelect().val());
                updateStep(measure);
                applyNotRealValue();
            });
            
            var startVal = this.getRealInput().val();
            if (startVal == 0) {
                var measure = normalizeUnit(defaultUnit);
                self.getNotRealSelect().val(measure);
                updateStep(measure);
            } else {
                var measure = normalizeUnit(defaultUnit);

                if (startVal >= 3600 && availableUnits['hour']) {
                    measure = 'hour';
                } else if (startVal >= 60 && availableUnits['min']) {
                    measure = 'min';
                } else if (availableUnits['sec']) {
                    measure = 'sec';
                }

                self.getNotRealInput().val(valueFromSeconds(startVal, measure));
                self.getNotRealSelect().val(measure);
                updateStep(measure);
            }
            
            
        },
        
        getRealInput: function() {
            return $(".sx-real input", this.getJWrapper());
        },
        
        getNotRealInput: function() {
            return $(".sx-not-real input", this.getJWrapper());
        },
        
        getNotRealSelect: function() {
            return $(".sx-not-real select", this.getJWrapper());
        },
        
        getJWrapper: function() {
            return $("#" + this.get('id'));
        }
    });
    new sx.classes.SmartTimeWidget({$jsOptions});
})(sx, sx.$, sx._);
JS
); ?>
<?= \yii\helpers\Html::endTag('div'); ?>
