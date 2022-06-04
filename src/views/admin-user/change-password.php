<?php
/**
 * @var $this yii\web\View
 * @var $controller \skeeks\cms\backend\controllers\BackendModelController
 * @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm
 *
 */
$controller = $this->context;
$action = $controller->action;
$this->registerCss(<<<CSS
.sx-view-pass {
    position: absolute;
    right: 24px;
    top: 36px;
    cursor: pointer;
}
.sx-generate-pass {
    border-bottom: 1px dashed;
}
CSS
);
$this->registerJs(<<<JS
$("body").on("click", ".sx-view-pass", function() {
    var jInput = $("input#sx-pass");
    var jIcon = $(this);
    
    if (jInput.attr("type") == 'password') {
        jInput.attr('type', 'text');
        jIcon.addClass("fa-eye-slash");
        jIcon.removeClass("fa-eye");
    } else {
        jInput.attr('type', 'password');
        jIcon.addClass("fa-eye");
        jIcon.removeClass("fa-eye-slash");
    }
});

$("body").on("click", ".sx-generate-pass", function() {
    var jInput = $("input#sx-pass");
    var jIcon = $(".sx-view-pass");
    
    var pass = password_generator(8);
    
    jInput.attr('type', 'text');
    jInput.val(pass);
    jIcon.addClass("fa-eye-slash");
    jIcon.removeClass("fa-eye");
    
    return false;
});

function gen_password(len){
    var password = "";
    var symbols = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!№;%:?*()_+=";
    for (var i = 0; i < len; i++){
        password += symbols.charAt(Math.floor(Math.random() * symbols.length));     
    }
    return password;
}

function password_generator( len ) {
    var length = (len)?(len):(10);
    var string = "abcdefghijklmnopqrstuvwxyz"; //to upper 
    var numeric = '0123456789';
    var punctuation = '!@#$%^&*()_+~`|}{[]\:;?><,./-=';
    var password = "";
    var character = "";
    var crunch = true;
    while( password.length<length ) {
        entity1 = Math.ceil(string.length * Math.random()*Math.random());
        entity2 = Math.ceil(numeric.length * Math.random()*Math.random());
        entity3 = Math.ceil(punctuation.length * Math.random()*Math.random());
        hold = string.charAt( entity1 );
        hold = (password.length%2==0)?(hold.toUpperCase()):(hold);
        character += hold;
        character += numeric.charAt( entity2 );
        character += punctuation.charAt( entity3 );
        password = character;
    }
    password=password.split('').sort(function(){return 0.5-Math.random()}).join('');
    return password.substr(0,len);
}
JS
);
?>
<? $form = \skeeks\cms\backend\widgets\ActiveFormBackend::begin(); ?>
<div style="position:relative;">
    <i class="far fa-eye sx-view-pass"></i>
    <?= $form->field($dm, 'password')->passwordInput([
        'id' => 'sx-pass'
    ]) ?>
    <div class="form-group">
    <a href="#" class="sx-generate-pass">Сгенерировать пароль</a>
    </div>
</div>


<?php echo $form->errorSummary([$dm]); ?>
<?= $form->buttonsStandart($dm, $action->buttons); ?>


<? if ($is_saved) : ?>
    <?php
    $submitBtn = \Yii::$app->request->post('submit-btn');
    $this->registerJs(<<<JS
    sx.Window.openerWidgetTriggerEvent('model-update', {
        'submitBtn' : '{$submitBtn}'
    });
JS
    ); ?>
<? endif; ?>

<? $form::end(); ?>


