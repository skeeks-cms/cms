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
                'img/legal.jpg',
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
                    'cookie_message_template',
                ],
                'string',
            ],
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
                            'placeholder' => "ИП Семенов Александр Сергеевич — Индивидуальный предприниматель Семенов Александр Сергеевич; ОГРНИП 316504400051472, ИНН 400403130765; юридический адрес — 141501, Московская область, г. Солнечногорск, Молодежный проезд, д. 3, кв. 103",
                        ],
                    ],

                    [
                        'class'   => HtmlBlock::class,
                        'content' => <<<HTML
<div class="sx-block">
    <p>Заполнив данные в полях выше, сайт сгенерирует необходимые документы для соблюдейния закона 152-ФЗ и подставит эти данные в эти документы.</p>
    <ul class="list-unstyled sx-col-menu">
        <li><a href="{$url1}" target="_blank" data-pjax="0">Политика конфиденциальности</a></li>
        <li><a href="{$url2}" target="_blank" data-pjax="0">Политика обработки файлов cookie</a></li>
        <li><a href="{$url2}" target="_blank" data-pjax="0">Политика в отношении обработки персональных данных</a></li>
    </ul>
</div>
HTML
                        ,
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

                ]
            ],
            'additinal' => [
                'class'          => FieldSet::class,
                'name'           => "Дополнительно",
                'elementOptions' => [
                    'isOpen' => false,
                ],
                'fields'         => [
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
<p>(Политика в отношении обработки персональных данных)
</p>
<h2>1. Общие положения
</h2>
<p>1.1. Настоящая Политика определяет порядок обработки и защиты персональных данных, осуществляемой {operator} ({details}), далее &mdash; <strong>Оператор</strong>.
</p>
<p>1.2. Политика разработана в соответствии с Федеральным законом № 152-ФЗ &laquo;О персональных данных&raquo; и применяется ко всем персональным данным, получаемым Оператором при использовании сайта <strong>{site}</strong> и Платформы SkeekS CMS.
</p>
<h2>2. Категории субъектов и персональных данных
</h2>
<p>2.1. Оператор обрабатывает персональные данные следующих категорий субъектов:
</p>
<ul>
  <li>пользователи сайта;</li>
  <li>Заказчики Платформы.</li>
</ul>
<p>2.2. Оператор обрабатывает следующие персональные данные:
</p>
<ul>
  <li>фамилия, имя, отчество;</li>
  <li>адрес электронной почты;</li>
  <li>номер телефона;</li>
  <li>иные данные, предоставленные субъектом персональных данных добровольно.</li>
</ul>
<p>2.3. Также осуществляется обработка обезличенных данных (IP-адрес, cookie, данные аналитики).
</p>
<h2>3. Цели обработки персональных данных
</h2>
<p>Персональные данные обрабатываются в целях:
</p>
<ul>
  <li>заключения, исполнения и прекращения договоров;</li>
  <li>регистрации и предоставления доступа к Платформе;</li>
  <li>идентификации пользователя;</li>
  <li>оказания технической и клиентской поддержки;</li>
  <li>направления сервисных уведомлений;</li>
  <li>улучшения работы сайта и Платформы.</li>
</ul>
<p>Направление рекламных и маркетинговых сообщений осуществляется <strong>только при наличии отдельного согласия</strong>.
</p>
<h2>4. Правовые основания обработки
</h2>
<p>Оператор обрабатывает персональные данные на основании:
</p>
<ul>
  <li>согласия субъекта персональных данных;</li>
  <li>заключения и исполнения договора;</li>
  <li>требований законодательства РФ.</li>
</ul>
<h2>5. Условия обработки
</h2>
<p>5.1. Обработка персональных данных осуществляется с использованием средств автоматизации и/или без их использования.
</p>
<p>5.2. Хранение персональных данных осуществляется с использованием баз данных, находящихся на территории Российской Федерации.
</p>
<p>5.3. Персональные данные обрабатываются до достижения целей обработки либо до отзыва согласия, если иное не предусмотрено законом.
</p>
<h2>6. Персональные данные третьих лиц (SaaS-модель)
</h2>
<p>6.1. В случае обработки персональных данных клиентов и пользователей сайтов Заказчиков:
</p>
<ul>
  <li>Заказчик является оператором персональных данных;</li>
  <li>Оператор осуществляет обработку персональных данных <strong>по поручению Заказчика</strong> исключительно в целях предоставления функционала Платформы.</li>
</ul>
<p>6.2. Оператор не использует персональные данные третьих лиц в собственных целях.
</p>
<h2>7. Меры по защите персональных данных
</h2>
<p>Оператор принимает необходимые правовые, организационные и технические меры для защиты персональных данных от неправомерного доступа, утраты, изменения и распространения.
</p>
<h2>8. Права субъектов персональных данных
</h2>
<p>Субъект персональных данных имеет право:
</p>
<ul>
  <li>получать информацию об обработке своих персональных данных;</li>
  <li>требовать уточнения, блокирования или уничтожения персональных данных;</li>
  <li>отозвать согласие на обработку персональных данных.</li>
</ul>
<h2>9. Трансграничная передача
</h2>
<p>Трансграничная передача персональных данных осуществляется <strong>только при наличии правовых оснований</strong>, предусмотренных законодательством РФ, и при необходимости использования соответствующих сервисов.
</p>
<h2>10. Заключительные положения
</h2>
<p>10.1. Оператор вправе вносить изменения в настоящую Политику.
</p>
<p>10.2. Актуальная редакция Политики размещается в открытом доступе на сайте Оператора.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
</p>

TEXT;
    public $template_personal_data = <<<TEXT
<p>Настоящая Политика в отношении обработки персональных данных (далее &mdash; Политика) разработана {operator} ({details}), далее &mdash; Оператор, в соответствии с Федеральным законом № 152-ФЗ &laquo;О персональных данных&raquo;.
</p>
<p>Политика определяет порядок обработки и защиты персональных данных, получаемых Оператором при использовании сайта и Платформы SkeekS CMS.
</p>
<h2>1. Общие положения
</h2>
<p>1.1. Оператор осуществляет обработку персональных данных на принципах законности, справедливости, соразмерности и конфиденциальности.
</p>
<p>1.2. Политика применяется ко всем персональным данным, которые Оператор получает от пользователей сайта, Заказчиков и иных лиц в рамках использования Платформы.
</p>
<p>1.3. Оператор не осуществляет обработку специальных категорий персональных данных, а также биометрических персональных данных.
</p>
<h2>2. Основные понятия
</h2>
<p>В Политике используются понятия в значении, установленном Федеральным законом № 152-ФЗ &laquo;О персональных данных&raquo;.
</p>
<h2>3. Цели обработки персональных данных
</h2>
<p>Оператор обрабатывает персональные данные в следующих целях:
</p>
<ul>
  <li>заключение, исполнение и прекращение гражданско-правовых договоров;</li>
  <li>регистрация пользователей и предоставление доступа к Платформе;</li>
  <li>аутентификация и подтверждение действий пользователей;</li>
  <li>обработка обращений, запросов и заявок;</li>
  <li>предоставление технической и клиентской поддержки;</li>
  <li>направление сервисных и информационных уведомлений;</li>
  <li>улучшение качества работы сайта и Платформы.</li>
</ul>
<p>Направление рекламных и маркетинговых сообщений осуществляется только при наличии отдельного согласия субъекта персональных данных.
</p>
<h2>4. Категории персональных данных и субъектов
</h2>
<p>4.1. Оператор обрабатывает следующие персональные данные:
</p>
<ul>
  <li>фамилия, имя, отчество;</li>
  <li>адрес электронной почты;</li>
  <li>номер телефона;</li>
  <li>иные данные, предоставленные пользователем добровольно.</li>
</ul>
<p>4.2. Также осуществляется сбор обезличенных данных (cookies, IP-адрес, данные статистики) с использованием сервисов интернет-аналитики.
</p>
<h2>5. Правовые основания обработки
</h2>
<p>Оператор обрабатывает персональные данные на следующих правовых основаниях:
</p>
<ul>
  <li>согласие субъекта персональных данных;</li>
  <li>заключение и исполнение договора;</li>
  <li>требования законодательства Российской Федерации.</li>
</ul>
<h2>6. Условия и способы обработки
</h2>
<p>6.1. Обработка персональных данных осуществляется с использованием средств автоматизации и/или без их использования.
</p>
<p>6.2. Оператор осуществляет следующие действия с персональными данными:
  <br />
  сбор, запись, систематизацию, накопление, хранение, уточнение, использование, блокирование, удаление и уничтожение.
</p>
<p>6.3. Хранение персональных данных осуществляется с использованием баз данных, находящихся на территории Российской Федерации.
</p>
<h2>7. Персональные данные третьих лиц
</h2>
<p>7.1. В случае обработки персональных данных клиентов и пользователей сайтов Заказчиков:
</p>
<ul>
  <li>Заказчик является оператором персональных данных;</li>
  <li>Оператор осуществляет обработку персональных данных по поручению Заказчика исключительно в целях предоставления функционала Платформы.</li>
</ul>
<p>7.2. Оператор не использует персональные данные третьих лиц в собственных целях.
</p>
<h2>8. Меры по защите персональных данных
</h2>
<p>Оператор принимает необходимые правовые, организационные и технические меры для защиты персональных данных, включая:
</p>
<ul>
  <li>ограничение доступа к персональным данным;</li>
  <li>использование средств аутентификации;</li>
  <li>защиту информационных систем;</li>
  <li>контроль доступа и резервирование технических ресурсов.</li>
</ul>
<h2>9. Права субъектов персональных данных
</h2>
<p>Субъект персональных данных имеет право:
</p>
<ul>
  <li>получать информацию об обработке его персональных данных;</li>
  <li>требовать уточнения, блокирования или уничтожения персональных данных;</li>
  <li>отозвать согласие на обработку персональных данных.</li>
</ul>
<h2>10. Сроки обработки и уничтожение данных
</h2>
<p>Персональные данные обрабатываются до достижения целей обработки либо до отзыва согласия, если иное не предусмотрено законодательством РФ.
</p>
<h2>11. Заключительные положения
</h2>
<p>11.1. Оператор вправе вносить изменения в настоящую Политику.
</p>
<p>11.2. Актуальная редакция Политики размещается в свободном доступе на сайте Оператора.
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
<p>8.1. Пользователь может в любой момент изменить настройки использования cookie, включая отказ от аналитических и маркетинговых cookie, через настройки браузера или интерфейс cookie-баннера на Сайте.
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
     * @return string
     */
    public function getTextCookie()
    {
        if ($this->doc_cookie) {
            return (string) $this->doc_cookie;
        } else {
            $text = $this->template_cookie;
            $text = str_replace("{operator}", $this->operator, $text);
            $text = str_replace("{site}", $this->site, $text);
            $text = str_replace("{email}", $this->email, $text);
            $text = str_replace("{details}", $this->details, $text);
            return $text;
        }
    }
    /**
     * @return string
     */
    public function getTextPersonalData()
    {
        if ($this->doc_personal_data) {
            return (string) $this->doc_personal_data;
        } else {
            $text = $this->template_personal_data;
            $text = str_replace("{operator}", $this->operator, $text);
            $text = str_replace("{site}", $this->site, $text);
            $text = str_replace("{email}", $this->email, $text);
            $text = str_replace("{details}", $this->details, $text);
            return $text;
        }
    }
    /**
     * @return string
     */
    public function getTextPrivacyPolicy()
    {
        if ($this->doc_privacy_policy) {
            return (string) $this->doc_privacy_policy;
        } else {
            $text = $this->template_privacy_policy;
            $text = str_replace("{operator}", $this->operator, $text);
            $text = str_replace("{site}", $this->site, $text);
            $text = str_replace("{email}", $this->email, $text);
            $text = str_replace("{details}", $this->details, $text);
            return $text;
        }
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
    background: white;
    padding: 1rem;
    z-index: 99;
    opacity: 1;
    border-radius: var(--base-radius);
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