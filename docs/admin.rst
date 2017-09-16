=========
CMS Admin
=========

Административная часть сайта реализована компонентом ``skeeks/cms-admin``
По умолчанию CMS уже сдержит этот компонент.

Настройка и конфигурирование
----------------------------

Компонент админки подключается и настраивается стандартным способом в проект.

Пример конфигурирования
~~~~~~~~~~~~~~~~~~~~~~~

В файле конфига проекта ``frontend/config/main.php`` отредактировать секцию ``components``

.. code-block:: php

    'backendAdmin' =>
    [
        'class'             => '\skeeks\cms\admin\AdminComponent',
        'controllerPrefix'  => 'admin',
        'urlRule'           => [
            'urlPrefix' => '~sx' //Префикс админки, то есть путь к админке сайта может быть любой
        ],
        'allowedIPs' => [
            '91.219.167.252',
              '93.186.50.*'
        ],
        'on beforeRun' => function($event) {
            \Yii::$app->httpBasicAuth->verify();
        }
    ],


Доступные настройки
~~~~~~~~~~~~~~~~~~~

allowedIPs
""""""""""
Разрешенный массив ip адресов, по умолчанию ``['*']``


Меню
----

Административное меню формируется путем слияния конфигов всех установленных расширений и конфига проекта.

* ``@skeeks/cms/config/admin/menu.php``
* ``@skeeks/cms-admin/config/admin/menu.php``
* ``@all-other-extensions/config/admin/menu.php``
* ``@common/config/admin/menu.php``


Формат
~~~~~~

В конечном виде конфиг меню представляет собой один большой массив с элементами

.. code-block:: php

    [
        'users' =>
        [
            'label'     => \Yii::t('skeeks/cms', 'Users'),
            'priority'  => 200,
            'enabled'   => true,
            "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],

            'items' =>
            [
                [
                    "label"     => \Yii::t('skeeks/cms',"User management"),
                    "url"       => ["cms/admin-user"],
                    "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],
                    'priority'  => 0
                ],

                //....
            ]
        ],
    ],

Каждый элемент массива может содержать следующие опции:

* **label** — Название пункта меню
* **priority** — Порядок чем меньше тем выше пункт
* **enabled** — Показывается или не показывается
* **img** — Картинка (массив [Asset, 'путь к файлу'])
* **url** — URL массив который будет передан в ``yii\helpers\Url::to()``;


Создание контроллера
--------------------

В проекте
~~~~~~~~~

Ссылка на контроллер: ``Url::to(['/admin-competition'])``

Создать файл контроллера: ``frontend/controllers/AdminCompetitionController.php``

.. code-block:: php

    namespace frontend\controllers;
    use frontend\modules\competition\models\Competition;

    class AdminCompetitionController extends \skeeks\cms\modules\admin\controllers\AdminModelEditorController
    {
        public function init()
        {
            $this->name                     = \Yii::t('app', 'Конкурсы');
            $this->modelShowAttribute       = "name";
            $this->modelClassName           = Competition::className();

            parent::init();
        }
    }

Создать файл шаблона: ``frontend/views/admin-competition/index.php``


.. code-block:: php

    <? $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>

    <?php echo $this->render('_search', [
        'searchModel'   => $searchModel,
        'dataProvider'  => $dataProvider
    ]); ?>

    <?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
        'dataProvider'  => $dataProvider,
        'filterModel'   => $searchModel,
        'adminController'   => $controller,
        'pjax'              => $pjax,
        'columns' => [
            'name',

        ],
    ]); ?>

    <? $pjax::end(); ?>

Создать файл для фильтров и поиска: ``frontend/views/admin-competition/_search.php``


.. code-block:: php

    <?
        $filter = new \yii\base\DynamicModel([
            'id',
        ]);
        $filter->addRule('id', 'integer');

        $filter->load(\Yii::$app->request->get());

        if ($filter->id)
        {
            $dataProvider->query->andWhere(['id' => $filter->id]);
        }
    ?>
    <? $form = \skeeks\cms\modules\admin\widgets\filters\AdminFiltersForm::begin([
            'action' => '/' . \Yii::$app->request->pathInfo,
        ]); ?>

        <?= $form->field($searchModel, 'name')->setVisible(); ?>

    <? $form::end(); ?>

Создать файл для редактирования элементов: ``frontend/views/admin-competition/_form.php``


.. code-block:: php

    <?php $form = ActiveForm::begin(); ?>
    <?php  ?>

    <?= $form->fieldSet( \Yii::t('skeeks/form2/app', 'General information'))?>
        <?= $form->field($model, 'name')->textInput(); ?>
        <?= $form->field($model, 'description')->textInput(); ?>
        <?= $form->field($model, 'is_active')->checkbox(); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>
    <?php ActiveForm::end(); ?>


Создание контроллера
~~~~~~~~~~~~~~~~~~~~

