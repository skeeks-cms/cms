<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2017
 */
/* @var $this \yii\web\View */
/* @var \skeeks\cms\models\forms\PasswordChangeForm $model */

$this->registerCss(<<<CSS
.sx-view-pass {
    position: absolute;
    right: 2.75rem;
    top: 2.85rem;
    cursor: pointer;
}
.sx-generate-pass {
    border-bottom: 1px dashed;
}
.sx-generate-pass:hover {
    text-decoration: none;
}
CSS
);

$passId = \yii\helpers\Html::getInputId($model, "password");

$this->registerJs(<<<JS
$("body").on("click", ".sx-view-pass", function() {
    var jInput = $("input#{$passId}");
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
    var jInput = $("input#{$passId}");
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
<h1>Смена пароля</h1>
<div class="row">
    <div class="col-12" style="max-width: 50rem;">

    <?php if (!\Yii::$app->user->identity->password_hash && \Yii::$app->cms->pass_is_need_change) : ?>
    <?php $alert = \yii\bootstrap\Alert::begin([
        'closeButton' => false,
        'id' => "no-pass",
        'options' => [
            'class' => 'alert-danger',
        ],
    ]); ?>
    <b>Внимание!</b> <br/> Для продолжения работы, придумайте свой постоянный пароль, с которым вы будете входить в систему в дальнейшем.

    <?php $alert::end(); ?>
    <?php endif; ?>

        <?php $form = \skeeks\cms\backend\widgets\ActiveFormAjaxBackend::begin([
                'clientSuccess' => new \yii\web\JsExpression(<<<JS
    function (ActiveFormAjaxSubmit) {
        if ($("#no-pass").length) {
            $("#no-pass").fadeOut();
        }
    }
JS
)
        ]); ?>


        <div style="position:relative;">
            <i class="far fa-eye sx-view-pass"></i>
            <?= $form->field($model, 'password')->passwordInput() ?>
            <div class="form-group">
                <a href="#" class="sx-generate-pass">Сгенерировать пароль</a>
            </div>
        </div>

        <?= $form->buttonsStandart($model) ?>
        <?php $form::end(); ?>
    </div>
</div>
