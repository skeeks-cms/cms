<?php
/**
 * Breadcrumbs
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.01.2015
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\assets\CmsAsset;
use skeeks\cms\backend\widgets\ActiveFormBackend;
use skeeks\cms\base\Component;
use skeeks\cms\base\components\Descriptor;
use skeeks\cms\models\Site;
use skeeks\cms\models\TreeType;
use skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @property string $textCookie;
 * @property string $textPersonalData;
 * @property string $textPrivacyPolicy;
 * @property string $textCookieAlert;
 * @property string $textConsent;
 * @property string $consentCheckboxText;
 * @property string $consentUrl;
 *
 * Class LegalComponent
 * @package skeeks\cms\components
 */
class LegalComponent extends Component
{
    public $operator = "";
    public $site = "";
    public $email = "";
    public $details = "";

    public $doc_privacy_policy = "";
    /**
     * @var string
     */
    public $doc_cookie = "";
    public $doc_personal_data = "";
    /**
     * Отдельный документ «Согласие на обработку персональных данных» (/~legal-consent).
     * На него ведут галочки согласия в формах, корзине и при входе.
     * @var string
     */
    public $doc_consent = "";

    /**
     * Текст рядом с галочкой согласия. {url} — ссылка на документ согласия.
     * @var string
     */
    public $consent_checkbox_template = "";
    public $default_consent_checkbox_template = 'Я даю <a href="{url}" target="_blank" data-pjax="0">согласие на обработку персональных данных</a>';

    /**
     * Шрифты Google отдаются со своего сервера сайта (компонент localFonts).
     * @var int
     */
    public $is_local_google_fonts = 1;

    /**
     * Обязательная галочка согласия при входе и регистрации на сайте.
     * @var int
     */
    public $is_auth_consent = 1;

    public $cookie_message_template = "";
    public $default_cookie_message_template = "Мы используем файлы <a href='{url_cookie}'>cookie</a>.<br>Продолжая находиться на сайте, вы соглашаетесь с этим.<br><a href='#' class='sx-trigger-allow-cookie'>Принимаю</a>";

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'        => "Правовая информация",
            'description' => 'Все что нужно для соблюдения закона 152-ФЗ',
            'image'       => [
                CmsAsset::class,
                'images/icons/admin-menu/legal.svg',
            ],
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                [
                    'operator',
                    'site',
                    'email',
                    'details',
                    'doc_privacy_policy',
                    'doc_cookie',
                    'doc_personal_data',
                    'doc_consent',
                    'consent_checkbox_template',
                    'cookie_message_template',
                ],
                'string',
            ],
            [['is_local_google_fonts', 'is_auth_consent'], 'integer'],
            [
                [
                    'email',
                ],
                'email', 'enableIDN' => true
            ],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'operator' => "Оператор",
            'site'     => "Веб сайт",
            'email'    => "E-mail",
            'details'  => "Реквизиты организации, ИП или физ. лица",

            'doc_privacy_policy' => "Политика конфиденциальности",
            'doc_cookie'         => "Политика обработки файлов cookie",
            'doc_personal_data'  => "Политика в отношении обработки персональных данных",
            'doc_consent'        => "Согласие на обработку персональных данных",

            'is_local_google_fonts'     => "Шрифты Google со своего сервера",
            'is_auth_consent'           => "Галочка согласия при входе и регистрации",
            'consent_checkbox_template' => "Текст у галочки согласия",
            'cookie_message_template'  => "Сообщение об использовании cookie",

        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'operator' => "Используется в документах, подставляется в нужные места",
            'site'     => "Используется в документах, подставляется в нужные места",
            'email'    => "Используется в документах, подставляется в нужные места",
            'details'  => "Используется в документах, подставляется в нужные места",

            'doc_privacy_policy' => "Вы можете самостоятельно сформировать этот документ или не заполняйте это поле и тогда документ будет сгенерирован автоматически.",
            'doc_cookie'         => "Вы можете самостоятельно сформировать этот документ или не заполняйте это поле и тогда документ будет сгенерирован автоматически.",
            'doc_personal_data'  => "Вы можете самостоятельно сформировать этот документ или не заполняйте это поле и тогда документ будет сгенерирован автоматически.",
            'doc_consent'        => "Отдельный документ, на который ведут галочки согласия в формах, корзине и при входе. Не заполняйте поле — документ будет сгенерирован автоматически.",

            'is_local_google_fonts'     => "Сайт фоновым заданием скачивает шрифты Google к себе и после этого отдаёт их посетителям сам: браузер посетителя не обращается к серверам Google. Пока копия не скачана, шрифты подключаются с Google, чтобы сайт всегда выглядел как обычно.",
            'is_auth_consent'           => "Обязательная галочка «Я даю согласие на обработку персональных данных» на первом шаге входа по телефону или email и на странице регистрации.",
            'consent_checkbox_template' => "{url} — ссылка на документ согласия. Если поле пустое: «Я даю согласие на обработку персональных данных».",
            'cookie_message_template'  => "{url_cookie} - ссылка на страницу документа о cookie<br>sx-trigger-allow-cookie - обязательный класс для кнопки согласия<br>",

        ]);
    }

    /**
     * @return ActiveForm
     */
    public function beginConfigForm()
    {
        return ActiveFormBackend::begin();
    }

    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        $url1 = \yii\helpers\Url::to(['/cms/legal/privacy-policy']);
        $url2 = \yii\helpers\Url::to(['/cms/legal/cookie']);
        $url3 = \yii\helpers\Url::to(['/cms/legal/personal-data']);
        $url4 = \yii\helpers\Url::to(['/cms/legal/consent']);
        return [

            'main' => [
                'class'  => FieldSet::class,
                'name'   => "Данные подставляемые в документы",
                'fields' => [


                    'operator' => [
                        'class'          => TextField::class,
                        'elementOptions' => [
                            'placeholder' => "ИП Котов Владимир Петрович",
                        ],
                    ],
                    'site'     => [
                        'class'          => TextField::class,
                        'elementOptions' => [
                            'placeholder' => "skeeks.com",
                        ],
                    ],
                    'email'    => [
                        'class'          => TextField::class,
                        'elementOptions' => [
                            'placeholder' => "legal@skeeks.com",
                        ],
                    ],
                    'details'  => [
                        'class'          => TextareaField::class,
                        'elementOptions' => [
                            'placeholder' => "ИП Иванов Иван Иванович — Индивидуальный предприниматель Иванов Иван Иванович; ОГРНИП 000000000000000, ИНН 000000000000; юридический адрес — 000000, г. Город, ул. Улица, д. 1",
                        ],
                    ],

                    [
                        'class'   => HtmlBlock::class,
                        'content' => <<<HTML
<div class="sx-surface sx-surface--raised sx-surface--padded">
    <p>Заполнив данные в полях выше, сайт сгенерирует необходимые документы для соблюдейния закона 152-ФЗ и подставит эти данные в эти документы.</p>
    <ul class="list-unstyled sx-col-menu">
        <li><a href="{$url1}" target="_blank" data-pjax="0">Политика конфиденциальности</a></li>
        <li><a href="{$url2}" target="_blank" data-pjax="0">Политика обработки файлов cookie</a></li>
        <li><a href="{$url3}" target="_blank" data-pjax="0">Политика в отношении обработки персональных данных</a></li>
        <li><a href="{$url4}" target="_blank" data-pjax="0">Согласие на обработку персональных данных</a></li>
    </ul>
</div>
HTML
                        ,
                    ],
                ],
            ],

            'site' => [
                'class'  => FieldSet::class,
                'name'   => "Защита персональных данных на сайте",
                'fields' => [
                    'is_local_google_fonts' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'is_auth_consent' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                ],
            ],

            'docs' => [
                'class'          => FieldSet::class,
                'name'           => "Документы",
                'elementOptions' => [
                    'isOpen' => false,
                ],
                'fields'         => [
                    'doc_privacy_policy' => [
                        'class'       => WidgetField::class,
                        'widgetClass' => ComboTextInputWidget::class,
                    ],
                    'doc_cookie' => [
                        'class'       => WidgetField::class,
                        'widgetClass' => ComboTextInputWidget::class,
                    ],
                    'doc_personal_data' => [
                        'class'       => WidgetField::class,
                        'widgetClass' => ComboTextInputWidget::class,
                    ],
                    'doc_consent' => [
                        'class'       => WidgetField::class,
                        'widgetClass' => ComboTextInputWidget::class,
                    ],

                ]
            ],
            'additinal' => [
                'class'          => FieldSet::class,
                'name'           => "Дополнительно",
                'elementOptions' => [
                    'isOpen' => false,
                ],
                'fields'         => [
                    'consent_checkbox_template' => [
                        'class'          => TextField::class,
                        'elementOptions' => [
                            'placeholder' => 'Я даю <a href="{url}" target="_blank" data-pjax="0">согласие на обработку персональных данных</a>',
                        ],
                    ],
                    'cookie_message_template' => [
                        'class'       => TextareaField::class,
                        'elementOptions' => [
                            'placeholder' => "Мы используем файлы <a href='{url_cookie}'>cookie</a>. Продолжая находиться на сайте, вы соглашаетесь с этим.<br><a href='#' class='sx-trigger-allow-cookie'>Принимаю</a>",
                        ],
                    ],

                ]
            ],
        ];
    }

    public $template_privacy_policy = <<<TEXT
<p><strong>Редакция от 19.11.2026</strong>
</p>
<h2>1. Общие положения
</h2>
<p>1.1. Настоящая Политика конфиденциальности описывает, какие сведения о посетителях получает сайт <strong>{site}</strong>, для чего они используются и как защищаются. Владелец сайта и оператор персональных данных &mdash; {operator} ({details}), далее &mdash; <strong>Оператор</strong>.
</p>
<p>1.2. Политика применяется вместе с <a href="{url_personal_data}">Политикой в отношении обработки персональных данных</a> и <a href="{url_cookie}">Политикой обработки файлов cookie</a>. Порядок обработки персональных данных определяется Федеральным законом № 152-ФЗ &laquo;О персональных данных&raquo;.
</p>
<h2>2. Какие сведения мы получаем
</h2>
<p>2.1. Сведения, которые посетитель сообщает сам, заполняя формы на сайте (заявка, заказ обратного звонка, оформление заказа, вход и регистрация, отзыв): имя, номер телефона, адрес электронной почты, адрес доставки, текст обращения и другие сведения, которые посетитель решил указать.
</p>
<p>2.2. Сведения о заказах: состав заказа, способ доставки и оплаты, история заказов в личном кабинете.
</p>
<p>2.3. Технические сведения, которые передаёт браузер: IP-адрес, данные файлов cookie, тип браузера и устройства, просмотренные страницы, источник перехода на сайт. Для их сбора и анализа может использоваться сервис Яндекс Метрика (ООО &laquo;ЯНДЕКС&raquo;).
</p>
<h2>3. Для чего мы используем сведения
</h2>
<ul>
  <li>ответить на заявку или обращение, перезвонить по просьбе посетителя;</li>
  <li>оформить, доставить и выдать заказ, принять оплату, выполнить обязательства перед покупателем;</li>
  <li>дать доступ к личному кабинету и подтвердить вход;</li>
  <li>сообщать о статусе заказа и другие сведения, связанные с обращением;</li>
  <li>анализировать посещаемость и улучшать работу сайта.</li>
</ul>
<p>Рекламные и маркетинговые сообщения направляются <strong>только при наличии отдельного согласия</strong> посетителя.
</p>
<h2>4. Кому передаются сведения
</h2>
<p>4.1. Оператор не продаёт и не передаёт сведения о посетителях третьим лицам, за исключением случаев, когда это нужно для выполнения просьбы посетителя или требуется по закону:
</p>
<ul>
  <li>службам доставки &mdash; для доставки заказа;</li>
  <li>платёжным сервисам и банкам &mdash; для приёма оплаты;</li>
  <li>сервисам отправки SMS, звонков и электронной почты &mdash; для подтверждения входа и уведомлений;</li>
  <li>сервисам веб-аналитики &mdash; в обезличенном виде, для статистики посещаемости;</li>
  <li>государственным органам &mdash; в случаях, предусмотренных законодательством РФ.</li>
</ul>
<h2>5. Хранение и защита
</h2>
<p>5.1. Сбор, запись, систематизация, накопление, хранение, уточнение и извлечение персональных данных граждан Российской Федерации осуществляются с использованием баз данных, находящихся на территории Российской Федерации.
</p>
<p>5.2. Оператор принимает правовые, организационные и технические меры для защиты сведений от неправомерного доступа, изменения, распространения и уничтожения.
</p>
<p>5.3. Сведения хранятся не дольше, чем этого требуют цели обработки или закон, либо до отзыва согласия.
</p>
<h2>6. Права посетителя
</h2>
<p>Посетитель вправе узнать, какие его данные обрабатываются, потребовать их уточнения, блокирования или удаления и отозвать согласие на обработку, направив обращение {contact}.
</p>
<h2>7. Заключительные положения
</h2>
<p>7.1. Оператор вправе изменять настоящую Политику. Новая редакция действует с момента её размещения на сайте.
</p>
<p>7.2. Актуальная редакция Политики постоянно доступна на сайте {site}.
</p>

TEXT;
    public $template_personal_data = <<<TEXT
<p><strong>Редакция от 19.11.2026</strong>
</p>
<p>Настоящая Политика в отношении обработки персональных данных (далее &mdash; Политика) разработана {operator} ({details}), далее &mdash; <strong>Оператор</strong>, во исполнение требований Федерального закона от 27.07.2006 № 152-ФЗ &laquo;О персональных данных&raquo; и определяет порядок и условия обработки персональных данных, получаемых при использовании сайта <strong>{site}</strong>.
</p>
<h2>1. Основные понятия
</h2>
<p>В Политике используются понятия в значении, установленном Федеральным законом № 152-ФЗ &laquo;О персональных данных&raquo;. Сайт &mdash; сайт <strong>{site}</strong> и все его страницы. Пользователь &mdash; любой посетитель сайта.
</p>
<h2>2. Принципы обработки
</h2>
<p>2.1. Обработка персональных данных осуществляется на законной и справедливой основе, ограничивается достижением конкретных заранее определённых целей и не допускает обработки данных, несовместимой с этими целями.
</p>
<p>2.2. Обрабатываются только те персональные данные, которые отвечают целям обработки; их содержание и объём соответствуют заявленным целям.
</p>
<p>2.3. Оператор не обрабатывает специальные категории персональных данных и биометрические персональные данные.
</p>
<h2>3. Субъекты, цели и состав персональных данных
</h2>
<p>3.1. Оператор обрабатывает персональные данные пользователей сайта, покупателей и лиц, обратившихся к Оператору через сайт.
</p>
<p>3.2. Цели обработки и состав данных:
</p>
<ul>
  <li><strong>обработка заявок и обращений, обратный звонок</strong> &mdash; имя, номер телефона, адрес электронной почты, текст обращения;</li>
  <li><strong>оформление, оплата, доставка и выдача заказов, исполнение договора купли-продажи</strong> &mdash; фамилия, имя, отчество, номер телефона, адрес электронной почты, адрес доставки, сведения о заказе;</li>
  <li><strong>регистрация и вход в личный кабинет</strong> &mdash; номер телефона, адрес электронной почты, пароль (хранится только в виде хеша, восстановить его нельзя), одноразовые коды подтверждения входа;</li>
  <li><strong>анализ посещаемости и улучшение работы сайта</strong> &mdash; IP-адрес, данные файлов cookie, сведения о браузере и устройстве, действия на сайте (в том числе с использованием сервиса Яндекс Метрика);</li>
  <li><strong>направление рекламных и информационных сообщений</strong> &mdash; имя, номер телефона, адрес электронной почты; только при наличии отдельного согласия пользователя.</li>
</ul>
<h2>4. Правовые основания обработки
</h2>
<ul>
  <li>согласие субъекта персональных данных на обработку его персональных данных;</li>
  <li>заключение и исполнение договора, стороной которого является субъект персональных данных;</li>
  <li>исполнение обязанностей, возложенных на Оператора законодательством Российской Федерации.</li>
</ul>
<h2>5. Порядок и условия обработки
</h2>
<p>5.1. Оператор осуществляет с персональными данными следующие действия: сбор, запись, систематизацию, накопление, хранение, уточнение (обновление, изменение), извлечение, использование, передачу (предоставление, доступ), блокирование, удаление и уничтожение.
</p>
<p>5.2. Обработка осуществляется с использованием средств автоматизации и без их использования (смешанная обработка).
</p>
<p>5.3. Сбор, запись, систематизация, накопление, хранение, уточнение и извлечение персональных данных граждан Российской Федерации осуществляются с использованием баз данных, находящихся на территории Российской Федерации.
</p>
<p>5.4. Персональные данные передаются третьим лицам только в объёме, необходимом для достижения целей обработки: службам доставки, платёжным сервисам, сервисам отправки SMS, звонков и электронной почты, сервисам веб-аналитики, а также государственным органам в случаях, предусмотренных законом.
</p>
<p>5.5. Трансграничная передача персональных данных осуществляется только при наличии правовых оснований, предусмотренных Федеральным законом № 152-ФЗ, и после уведомления уполномоченного органа, если такое уведомление требуется законом.
</p>
<h2>6. Сроки обработки и уничтожение данных
</h2>
<p>6.1. Персональные данные обрабатываются до достижения целей обработки, до отзыва согласия субъектом персональных данных или до истечения сроков хранения, установленных законодательством Российской Федерации.
</p>
<p>6.2. При достижении целей обработки или отзыве согласия персональные данные уничтожаются в сроки, установленные Федеральным законом № 152-ФЗ, если иное не предусмотрено договором или законом.
</p>
<h2>7. Права субъектов персональных данных
</h2>
<p>7.1. Субъект персональных данных вправе:
</p>
<ul>
  <li>получать сведения об обработке его персональных данных;</li>
  <li>требовать уточнения, блокирования или уничтожения персональных данных, если они неполные, устаревшие, неточные, незаконно полученные или не нужны для заявленной цели;</li>
  <li>отозвать согласие на обработку персональных данных;</li>
  <li>обжаловать действия или бездействие Оператора в уполномоченном органе по защите прав субъектов персональных данных или в суде.</li>
</ul>
<p>7.2. Запросы и отзыв согласия направляются {contact}. Оператор отвечает на запрос в течение 10 рабочих дней с даты его получения.
</p>
<h2>8. Меры по защите персональных данных
</h2>
<p>Оператор принимает необходимые правовые, организационные и технические меры для защиты персональных данных от неправомерного или случайного доступа, уничтожения, изменения, блокирования, копирования, предоставления и распространения, в том числе: ограничивает доступ к персональным данным, использует средства аутентификации и защищённые каналы связи, контролирует доступ к информационным системам и выполняет резервное копирование.
</p>
<h2>9. Заключительные положения
</h2>
<p>9.1. Оператор вправе вносить изменения в настоящую Политику. Новая редакция действует с момента её размещения на сайте.
</p>
<p>9.2. Политика находится в свободном доступе на сайте {site}.
</p>

TEXT;
    public $template_consent = <<<TEXT
<p><strong>Редакция от 19.11.2026</strong>
</p>
<p>Отмечая галочку согласия и отправляя форму на сайте <strong>{site}</strong> (заявка, заказ обратного звонка, оформление заказа, вход и регистрация), я свободно, своей волей и в своём интересе даю согласие на обработку моих персональных данных {operator} ({details}), далее &mdash; <strong>Оператор</strong>, на следующих условиях.
</p>
<h2>1. Цели обработки
</h2>
<ul>
  <li>рассмотрение моей заявки или обращения и связь со мной по ним;</li>
  <li>оформление, оплата, доставка и выдача заказа, исполнение договора купли-продажи;</li>
  <li>регистрация и вход в личный кабинет на сайте;</li>
  <li>уведомления о статусе заказа и обращения.</li>
</ul>
<h2>2. Перечень персональных данных
</h2>
<p>Фамилия, имя, отчество; номер телефона; адрес электронной почты; адрес доставки; сведения о заказе; текст обращения и иные сведения, которые я указал в форме; при входе и регистрации — пароль (в виде хеша) и одноразовые коды подтверждения.
</p>
<h2>3. Действия с персональными данными и способы обработки
</h2>
<p>Сбор, запись, систематизация, накопление, хранение, уточнение (обновление, изменение), извлечение, использование, передача (предоставление, доступ) службам доставки, платёжным сервисам и сервисам отправки SMS, звонков и электронной почты в объёме, необходимом для указанных целей, блокирование, удаление и уничтожение. Обработка осуществляется с использованием средств автоматизации и без их использования.
</p>
<h2>4. Срок действия и отзыв согласия
</h2>
<p>4.1. Согласие действует до достижения целей обработки либо до его отзыва.
</p>
<p>4.2. Я могу отозвать согласие, направив Оператору заявление {contact}. После получения отзыва Оператор прекращает обработку и уничтожает персональные данные в сроки, установленные Федеральным законом № 152-ФЗ &laquo;О персональных данных&raquo;, если иное не предусмотрено законом.
</p>
<p>4.3. Порядок обработки персональных данных описан в <a href="{url_personal_data}">Политике в отношении обработки персональных данных</a>.
</p>

TEXT;
    public $template_cookie = <<<TEXT
<p><strong>Редакция от 01.05.2025</strong>
</p>
<h2>1. Общие положения
</h2>
<p>1.1. Настоящая Политика обработки файлов cookie (далее &mdash; <strong>Политика</strong>) определяет порядок и условия использования файлов cookie при посещении и использовании интернет-сайтов, расположенных на домене <strong>{site}</strong>, владельцем которых является {operator} ({details}), далее &mdash; <strong>Оператор</strong>.
  <br />
  <br />
  {operator} {details}
</p>
<p>1.2. Файлы cookie используются Оператором для обеспечения корректной работы Сайта, анализа пользовательской активности и улучшения качества сервисов.
</p>
<p>1.3. Настоящая Политика носит <strong>информационный характер</strong> и применяется совместно с Политикой обработки персональных данных.
</p>
<h2>2. Термины и определения
</h2>
<p>В настоящей Политике используются термины в значении, установленном законодательством Российской Федерации, в том числе Федеральным законом № 152-ФЗ &laquo;О персональных данных&raquo;.
</p>
<h2>3. Виды используемых файлов cookie
</h2>
<p>Оператор использует следующие категории файлов cookie:
</p>
<p><strong>3.1. Обязательные cookie</strong>
</p>
<p>Используются для обеспечения корректной работы Сайта, безопасности и доступности его функционала.
  <br />
  <strong>Не требуют согласия Пользователя.</strong>
</p>
<p><strong>3.2. Аналитические cookie</strong>
</p>
<p>Используются для сбора обезличенной информации о том, как Пользователь взаимодействует с Сайтом (например, посещаемые страницы, продолжительность сессии).
  <br />
  Используются <strong>только при наличии согласия Пользователя</strong>.
</p>
<p><strong>3.3. Маркетинговые cookie</strong>
</p>
<p>Используются для показа релевантной рекламы, проведения ретаргетинга и оценки эффективности рекламных кампаний.
  <br />
  Используются <strong>только при наличии согласия Пользователя</strong>.
</p>
<h2>4. Состав обрабатываемых данных
</h2>
<p>4.1. Файлы cookie могут содержать обезличенную техническую информацию, включая:
</p>
<ul>
  <li>IP-адрес;</li>
  <li>тип и версию браузера;</li>
  <li>сведения об устройстве;</li>
  <li>данные о действиях Пользователя на Сайте;</li>
  <li>идентификаторы cookie.</li>
</ul>
<p>4.2. Оператор <strong>не использует файлы cookie для установления личности Пользователя</strong>.
</p>
<h2>5. Цели использования файлов cookie
</h2>
<p>Файлы cookie используются в следующих целях:
</p>
<ul>
  <li>обеспечение корректной и безопасной работы Сайта;</li>
  <li>анализ пользовательской активности;</li>
  <li>улучшение функционала и удобства использования Сайта;</li>
  <li>проведение аналитических и статистических исследований;</li>
  <li>показ персонализированной рекламы (при согласии Пользователя).</li>
</ul>
<h2>6. Условия обработки и срок хранения
</h2>
<p>6.1. Обработка файлов cookie осуществляется с использованием средств автоматизации.
</p>
<p>6.2. Срок хранения файлов cookie определяется их типом и может составлять от времени сессии до срока, установленного настройками браузера Пользователя.
</p>
<h2>7. Использование сторонних сервисов
</h2>
<p>7.1. Для анализа посещаемости и поведения Пользователей Оператор может использовать сторонние аналитические сервисы (например, <strong>Яндекс Метрика</strong>).
</p>
<p>7.2. Использование таких сервисов осуществляется <strong>при наличии согласия Пользователя</strong> и на основании договоров с соответствующими сервисами.
</p>
<p>7.3. Трансграничная передача данных возможна <strong>только в случаях, предусмотренных законодательством РФ</strong>, и при наличии правовых оснований.
</p>
<h2>8. Управление файлами cookie
</h2>
<p>8.1. Пользователь может в любой момент изменить настройки использования cookie, включая отказ от аналитических и маркетинговых cookie, через настройки своего браузера (запрет или удаление файлов cookie).
</p>
<p>8.2. Отключение обязательных cookie может привести к некорректной работе Сайта.
</p>
<h2>9. Заключительные положения
</h2>
<p>9.1. Оператор вправе вносить изменения в настоящую Политику.
</p>
<p>9.2. Актуальная редакция Политики размещается в открытом доступе на Сайте.
</p>

TEXT;

    /**
     * Подставляет данные оператора и ссылки в шаблон документа.
     *
     * {operator}, {site}, {email}, {details} — поля компонента;
     * {contact} — «на адрес электронной почты …» или «по контактам, указанным на сайте»;
     * {url_cookie}, {url_personal_data}, {url_privacy_policy}, {url_consent} — ссылки на документы.
     *
     * @param string $text
     * @return string
     */
    public function fillTemplate($text)
    {
        $site = (string) $this->site;
        if (!$site && \Yii::$app->has('request') && \Yii::$app->request instanceof \yii\web\Request) {
            $site = (string) \Yii::$app->request->hostName;
        }

        $contact = $this->email
            ? "на адрес электронной почты ".$this->email
            : "по контактам, указанным на сайте";

        return strtr((string) $text, [
            "{operator}"           => (string) $this->operator,
            "{site}"               => $site,
            "{email}"              => (string) $this->email,
            "{details}"            => (string) $this->details,
            "{contact}"            => $contact,
            "{url_cookie}"         => Url::to(['/cms/legal/cookie']),
            "{url_personal_data}"  => Url::to(['/cms/legal/personal-data']),
            "{url_privacy_policy}" => Url::to(['/cms/legal/privacy-policy']),
            "{url_consent}"        => Url::to(['/cms/legal/consent']),
        ]);
    }

    /**
     * @return string
     */
    public function getTextCookie()
    {
        return $this->doc_cookie ? (string) $this->doc_cookie : $this->fillTemplate($this->template_cookie);
    }
    /**
     * @return string
     */
    public function getTextPersonalData()
    {
        return $this->doc_personal_data ? (string) $this->doc_personal_data : $this->fillTemplate($this->template_personal_data);
    }
    /**
     * @return string
     */
    public function getTextPrivacyPolicy()
    {
        return $this->doc_privacy_policy ? (string) $this->doc_privacy_policy : $this->fillTemplate($this->template_privacy_policy);
    }
    /**
     * Отдельный документ согласия на обработку персональных данных.
     * @return string
     */
    public function getTextConsent()
    {
        return $this->doc_consent ? (string) $this->doc_consent : $this->fillTemplate($this->template_consent);
    }
    /**
     * Ссылка на документ согласия.
     * @return string
     */
    public function getConsentUrl()
    {
        return Url::to(['/cms/legal/consent']);
    }
    /**
     * HTML-текст для подписи к галочке согласия (без самой галочки).
     * @return string
     */
    public function getConsentCheckboxText()
    {
        $template = $this->consent_checkbox_template ? $this->consent_checkbox_template : $this->default_consent_checkbox_template;
        return str_replace("{url}", $this->consentUrl, $template);
    }
    /**
     * Обязательная галочка согласия для публичной формы (вход, регистрация).
     * Проверка — браузерная (required), как у остальных галочек согласия.
     *
     * @param string $id уникальный id галочки на странице
     * @return string
     */
    public function renderConsentCheckbox($id)
    {
        return \yii\helpers\Html::tag('div',
            \yii\helpers\Html::checkbox('legal_consent', false, ['id' => $id, 'value' => 1, 'required' => true, 'class' => 'sx-legal-consent-input'])
            .' '.\yii\helpers\Html::tag('label', $this->consentCheckboxText, ['for' => $id, 'class' => 'sx-legal-consent-label', 'style' => 'display: inline; margin-bottom: 0;']),
            ['class' => 'form-group sx-legal-consent']
        );
    }
    /**
     * @return string
     */
    public function getTextCookieAlert()
    {
        $template = $this->cookie_message_template ? $this->cookie_message_template : $this->default_cookie_message_template;

        $url = Url::to(['/cms/legal/cookie']);

        $text = str_replace("{url_cookie}", $url, $template);
        return $text;
    }

    public function registerAssets()
    {
        //Мы используем файлы <a href='{url_cookie}'>cookie</a>. Продолжая находиться на сайте, вы соглашаетесь с этим.<br><a href='#' class='sx-trigger-allow-cookie'>Принимаю</a>

        //Если уже согласен на обработку cookie то ничего не делать
        $data = ArrayHelper::getValue($_COOKIE, 'sx__legal__is_allow');
        if ($data) {
            return false;
        }
        $legalText = $this->textCookieAlert;

        \Yii::$app->view->registerCss(<<<CSS
.sx-legal-cookie:hover {
    opacity: 1;
}
.sx-legal-cookie {
    position: fixed;
    left: 2rem;
    bottom: 2rem;
    font-size: 0.8rem;
    color: var(--sx-color-text, #262626);
    border: 1px solid var(--sx-color-border, #dedede);
    background: var(--sx-color-surface-raised, #fff);
    box-shadow: var(--sx-shadow-panel, 0 3px 8px rgba(0, 0, 0, .16));
    padding: 1rem;
    z-index: 99;
    opacity: 1;
    border-radius: var(--sx-radius-panel, var(--base-radius, 8px));
}
.sx-legal-cookie a {
    color: var(--sx-color-accent, #2067b0);
}
.sx-legal-cookie a:hover,
.sx-legal-cookie a:focus {
    color: var(--sx-color-accent-hover, #195891);
}
@media (max-width: 768px) {
    .sx-legal-cookie {
        bottom: 5rem;
    }
    .mm-opening .sx-legal-cookie {
        display: none;
    }
}


CSS
        );

        \Yii::$app->view->registerJs(<<<JS
sx.classes.LegalCookie = sx.classes.Component.extend({

    _init: function()
    {
        this.cookie = new sx.classes.Cookie("legal");
    },
    
    _onDomReady: function()
    {
        var self = this;
        
        var is_allow = this.cookie.get("is_allow");
        if (is_allow != 1) {
            var jAlert = $("<div>", {
                'class' : "sx-legal-cookie"
            }).append("{$legalText}");
            
            $("body").append(jAlert);
        }
        
        $("body").on("click", ".sx-trigger-allow-cookie", function() {
            self.cookie.set("is_allow", 1);
            $(this).closest(".sx-legal-cookie").fadeOut();
            
            return false; 
        });
    }
    
});

sx.LogalCookie = new sx.classes.LegalCookie();
JS
            );
    }
}
