===========
Quick start
===========

Работа с шаблонами
==================

Настройка
---------

Стандартным для yii2 способом, для определения пути к теме/шаблону сайта, можно путем конфигурирования компонента view.
В файле конфига проекта ``frontend/config/main.php`` отредактировать секцию ``components``

.. code-block:: php

    'view' =>
    [
        'theme' =>
        [
            'pathMap'       =>
            [
                '@app/views' =>
                [
                    '@app/templates/default',
                ],
            ]
        ],
    ],


Использование
-------------

Пути к шаблонам обычно собираюся оттакливаясь от алиаса ``@app/views`` - который выше сконфигурирован.

Пример подключения шаблона в шаблоне
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?= $this->render("@app/views/header", []); ?>


Пример глобального рендеринга шаблона
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?= \Yii::$app->views->render("@app/views/header", []); ?>


Шаблоны для писем
-----------------

Шаблоны для отправки писем из расширений лежат непосредственно в расширении в папке \mail-templates
При отправке письма идет проверка

.. code-block:: php
    \Yii::$app->mailer->view->theme->pathMap = ArrayHelper::merge(\Yii::$app->mailer->view->theme->pathMap, [
        '@app/mail' =>
        [
            '@skeeks/cms/mail-templates'
        ]
    ]);

@app - это папка текущего приложения.
Чтобы подложить для отправки свой шаблон, создайте папку mail в папке своего приложения. Положите туда свои шаблоны.


Работа с URL
============

Везде в своих проектах правильно формируйте url, на любое действие на любой раздел, на любой элемент и т.д. Это позволит избежать кучи проблем с ростом проекта. Особенно с добавлением мультиязычности на сайт.

И так, в yii2 на эту тему есть множество примеров, с ними можно ознакомиться, например тут: https://github.com/yiisoft/yii2/blob/master/docs/guide/helper-url.md

Здесь же, мы рассмотрим конкретные примеры всего что связано с базовым модулем cms

Ссылки на разделы
-----------------

Ссылки на разделы сайта, по их id параметру

.. code-block:: php

    \yii\helpers\Url::to(['/cms/tree/view', 'id' => 10])

Ссылки на разделы сайта, по их объекту модели model

.. code-block:: php

    $model = \skeeks\cms\models\CmsTree::findOne(10);
    \yii\helpers\Url::to(['/cms/tree/view', 'model' => $model])

Ссылки на разделы сайта, по их dir параметру

.. code-block:: php

    //Ссылка в раздел about
    \yii\helpers\Url::to(['/cms/tree/view', 'dir' => 'about'])

Прочие примеры с параметрами

.. code-block:: php

    //Ссылка в раздел about с параметрами
    \yii\helpers\Url::to(['/cms/tree/view', 'dir' => 'about', 'param1' => 'test1', '#' => 'test1'])

    //Абсолютная ссылка на раздел about
    \yii\helpers\Url::to(['/cms/tree/view', 'dir' => 'about'], true)

    //Абсолютная https ссылка на раздел about
    \yii\helpers\Url::to(['/cms/tree/view', 'dir' => 'about'], 'https')

    //Ссылка на вложенный раздел
    \yii\helpers\Url::to(['/cms/tree/view', 'dir' => 'about/level-2/level-3'])

Но cms поддерживает концепцию многосайтовости. Поэтому можно в параметрах указать код желаемого сайта:

.. code-block:: php

    \yii\helpers\Url::to(['/cms/tree/view', 'dir' => 'about/level-2/level-3', 'site_code' => 's2'])


Ссылки в консольном приложении
------------------------------

Об этом стоит сказать особенно. Частый случай, что в yii2 сыпятся ошибки при запуске каких либо консольных утилит. Для корректной работы ссылок, необходимо сконфигурировать компонент UrlManager в конскольном приложении.

.. code-block:: php

    'urlManager' => [
        'baseUrl'   => '',
        'hostInfo' => 'http://your-site.com'
    ]

А так же в bootstrap определить пару алиасов:

.. code-block:: php

    \Yii::setAlias('webroot', dirname(dirname(__DIR__)) . '/frontend/web');
    \Yii::setAlias('web', '');

Авторизация / Регистрация
=========================

Стандартная авторизация/регистрация
-----------------------------------

В **SkeekS CMS** уже реализован процесс авторизации, регистрации и восстановления пароля (через email).
Реализация находится в ``cms/auth`` контроллере.

Методы реализающие эти процессы:

* ``login`` — процесс авторизации
* ``register`` — процесс регистрации
* ``register-by-email`` — регистрация через email (только ajax)
* ``forget`` — запроса начала процедуры восстановления пароля
* ``reset-password`` — действие подтверждения смены пароля


Проверка текущего пользователя
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Для проверки авторизации текущего пользователя на сайте, используется стандартная конструкция yii2.

.. code-block:: php

    if (\Yii::$app->user->isGuest)
    {
        //Пользователь неавторизован
    } else
    {
        //Пользователь авторизован можно запросить его данные
        print_r(\Yii::$app->user->identity->toArray());
    }

Ссылки на авторизацию
~~~~~~~~~~~~~~~~~~~~~

Как получить ссылку на действия связанные с авторизацией

.. code-block:: php

    echo \yii\helpers\Url::to(['cms/auth/login']);
    echo \yii\helpers\Url::to(['cms/auth/register']);
    echo \yii\helpers\Url::to(['cms/auth/forget']);

Еще один вариант через хелпер SkeekS CMS

.. code-block:: php

    echo \skeeks\cms\helpers\UrlHelper::construct('cms/auth/login')->setCurrentRef()


Форма авторизации
~~~~~~~~~~~~~~~~~

Эту форму можно вставить в любое место на сайте, работает через ajax.

.. code-block:: php

    $model = new \skeeks\cms\models\forms\LoginFormUsernameOrEmail();

    <?php $form = skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
        'action' => \skeeks\cms\helpers\UrlHelper::construct('cms/auth/login')->setCurrentRef()->toString(),
        'validationUrl' => \skeeks\cms\helpers\UrlHelper::construct('cms/auth/login')->setSystemParam(\skeeks\cms\helpers\RequestResponse::VALIDATION_AJAX_FORM_SYSTEM_NAME)->toString()
    ]); ?>
        <?= $form->field($model, 'identifier') ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <?= $form->field($model, 'rememberMe')->checkbox() ?>

        <div class="form-group">
            <?= \yii\helpers\Html::submitButton("<i class=\"glyphicon glyphicon-off\"></i> Войти", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>

    <?php skeeks\cms\base\widgets\ActiveFormAjaxSubmit::end(); ?>


Форма регистрации
~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php $form = ActiveForm::begin([
                    'action' => UrlHelper::construct('cms/auth/register-by-email')->toString(),
                    'validationUrl' => UrlHelper::construct('cms/auth/register-by-email')->setSystemParam(\skeeks\cms\helpers\RequestResponse::VALIDATION_AJAX_FORM_SYSTEM_NAME)->toString(),
                    'afterValidateCallback' => <<<JS
        function(jForm, ajaxQuery)
        {
            var handler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
                'blockerSelector' : '#' + jForm.attr('id'),
                'enableBlocker' : true,
            });

            handler.bind('success', function()
            {
                _.delay(function()
                {
                    $('#sx-login').click();
                }, 2000);
            });
        }
    JS

                ]); ?>
        <?= $form->field($model, 'email') ?>

        <div class="form-group">
            <?= Html::submitButton("<i class=\"glyphicon glyphicon-off\"></i> Зарегистрироваться", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>

    <?php ActiveForm::end(); ?>



Форма восстановления пароля
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php $form = ActiveForm::begin([
        'action' => UrlHelper::construct('cms/auth/forget')->toString(),
        'validationUrl' => UrlHelper::construct('cms/auth/forget')->setSystemParam(\skeeks\cms\helpers\RequestResponse::VALIDATION_AJAX_FORM_SYSTEM_NAME)->toString()
    ]); ?>
        <?= $form->field($model, 'identifier') ?>

        <div class="form-group">
            <?= Html::submitButton("Отправить", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>

    <?php ActiveForm::end(); ?>



Компоненты CMS
==============




Виджеты CMS
===========

Виджеты cms наследуются от базвого виджета: ``skeeks\cms\base\Widget``

Преимущество их работы, состоит в том, что их можно редактировать в "**Панеле быстрого управления сайтом**".

skeeks\\cms\\cmsWidgets\\text\\TextCmsWidget
--------------------------------------------

Предназначен для редактирования блоков текста или html кода

Пример использования:

.. code-block:: php

    <? echo \skeeks\cms\cmsWidgets\text\TextCmsWidget::widget([
        'namespace' => 'unique-settings-code',
        'text' => 'Edited text'
    ]); ?>

.. code-block:: php

    <? echo \skeeks\cms\cmsWidgets\text\TextCmsWidget::widget([
        'namespace' => 'unique-settings-code',
        'text' => <<<HTML
    <p class="cl-gray ">
        Edited text
    </p>
    HTML
    ]); ?>

.. code-block:: php

    <? \skeeks\cms\cmsWidgets\text\TextCmsWidget::begin([
        'namespace' => 'unique-settings-code',
    ]); ?>
    <p class="cl-gray ">
        Edited text
    </p>
    <? \skeeks\cms\cmsWidgets\text\TextCmsWidget::end(); ?>


.. code-block:: php

    <? \skeeks\cms\cmsWidgets\text\TextCmsWidget::beginWidget('unique-settings-code'); ?>
    <p class="cl-gray ">
        Edited text
    </p>
    <? \skeeks\cms\cmsWidgets\text\TextCmsWidget::end(); ?>


skeeks\\cms\\cmsWidgets\\treeMenu\\TreeMenuCmsWidget
----------------------------------------------------

Данный виджет, чаще всего предназначен для построения меню на сайте. При чем как главного меню, так и второстепенного. Добиться этого можно путем манипулации с его параметрами и способом вызова. Так же, виджет может подойти для вывода подразделов определенного раздела сайта (например основные разделы услуг, на главную страницу сайта).

Пример использования
~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?= \skeeks\cms\cmsWidgets\treeMenu\TreeMenuCmsWidget::widget([
        'namespace' => 'top-site-menu',
        'viewFile' => '@app/views/widgets/TreeMenuCmsWidget/top-site-menu',
        'label' => 'Title menu',
        'level' => '1',
        'enabledRunCache' => \skeeks\cms\components\Cms::BOOL_N,
    ]); ?>

Пример содержимого файла: ``@app/views/widgets/TreeMenuCmsWidget/top-site-menu``

.. code-block:: php

    <?php
    /* @var $this   yii\web\View */
    /* @var $widget \skeeks\cms\cmsWidgets\treeMenu\TreeMenuCmsWidget */
    /* @var $trees  \skeeks\cms\models\Tree[] */
    ?>
    <ul class="menu">
        <? if ($trees = $widget->activeQuery->all()) : ?>
            <? foreach ($trees as $tree) : ?>
                <?= $this->render("menu-top-item", [
                    "widget" => $widget,
                    "model" => $tree,
                ]); ?>
            <? endforeach; ?>
        <? endif; ?>
    </ul>

Пример содержимого файла: ``@app/views/widgets/TreeMenuCmsWidget/menu-top-item​``

.. code-block:: php

    <?php
    /* @var $this   yii\web\View */
    /* @var $widget \skeeks\cms\cmsWidgets\treeMenu\TreeMenuCmsWidget */
    /* @var $model   \skeeks\cms\models\Tree */
    $dir = $model->dir;
    if ($model->redirect_tree_id) {
        $dir = $model->redirectTree->dir;
    };
    $activeClass = '';
    if (strpos(\Yii::$app->request->pathInfo, $dir) !== false) {
        $activeClass = ' active';
    }
    ?>
    <li>
        <a href="<?= $model->url; ?>" title="<?= $model->name; ?>" class="<?= $activeClass; ?>">
            <?= $model->name; ?>
        </a>
    </li>


Пример с переопределением настроек
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <? $widget = \skeeks\cms\cmsWidgets\treeMenu\TreeMenuCmsWidget::beginWidget('menu-top-1', [
      'viewFile' => '@app/views/widgets/TreeMenuCmsWidget/menu-top',
      'label' => 'Верхнее меню',
      'level' => '1',
      'enabledRunCache' => \skeeks\cms\components\Cms::BOOL_N,
    ]); ?>
        <?
        //Переопределение шаблона, то есть не важно что укажут в настройках виджета, шаблон все равно будет использоваться этот!
        $widget->viewFile = '@app/views/widgets/TreeMenuCmsWidget/menu-top-2';
        //Изменение запроса
        $widget->activeQuery->andWhere(['code' => 'dostavka']);
        ?>
    <? \skeeks\cms\cmsWidgets\treeMenu\TreeMenuCmsWidget::end(); ?>

Второстепенное меню каталога
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Например, при хождении по каталогу, необходимо чтобы подразделы каталога, отображались всегда слева. Для этого можно опереться на параметр текущего раздела сайта, и передать его в один из параметров виджета в качестве ``treePid``. В этом случае, в выборке нужных разделов будет всегда участвовать условие родительского раздела. И при этом это условие будет перекрывать настройки указанные администратором через админку, а значит администратор не сможет сломать виджет, но при этом сможет поменять некоторые параметры.

.. code-block:: php

    <? $widget = \skeeks\cms\cmsWidgets\treeMenu\TreeMenuCmsWidget::beginWidget('menu-top-1', [
      'viewFile' => '@app/views/widgets/TreeMenuCmsWidget/menu-top',
      'label' => 'Вложенное меню',
      'enabledRunCache' => \skeeks\cms\components\Cms::BOOL_N,
    ]); ?>
        <?
        //Если задан текущий раздел, и у него есть достаточная вложенность
        if (\Yii::$app->cms->currentTree && \Yii::$app->cms->currentTree->parent && isset(\Yii::$app->cms->currentTree->parents[1]))
        {
            $currentParentTree = \Yii::$app->cms->currentTree->parents[1];
            $widget->treePid    = $currentParentTree->id; //Пере определние параметра родительского раздела

            $widget->initActiveQuery(); //Применение новых настроек виджета
        }
        ?>
    <? \skeeks\cms\cmsWidgets\treeMenu\TreeMenuCmsWidget::end(); ?>
