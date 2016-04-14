CHANGELOG
==============

2.7.2.pre
-----------------
 * Removed I18NDb. In a separate package skeeks/cms-i18n-db
 * Disabled event ADMIN_READY
 * Updated translation functionality
 * Removed columns files_depricated in cms_tree and cms_content_element
 * Completely rewritten mechanism of personal user cabinet
 * Remove the old classes
 * Sitemap updated
 * Removed UserAction
 * Closed personal user profiles!
 * Removed skeeks\cms\models\TreeMenu
 * Rewrite admin actions
 * Removed skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelSystemAction

2.7.1.2
-----------------
 * Fixed column ids
 * Fixed user column data
 * Update log message group

2.7.1.1
-----------------
 * Fixed user edit bugs
 * Added the ability to configure the maximum and minimum display records in tables
 * Remote download link files option is enabled CURLOPT_FOLLOWLOCATION
 * Change filter element in content element grids

2.7.1
-----------------
 * Updated admin content elements in grids
 * Minimal user name length is increased
 * Adding a field in the user table of email and phone
 * Revert failed migrations
 * You can customize the grid AdminRelatedGridView
 * Disabled ajax response {test: test} to the sections and pages of content elements
 * Styling toolbar

2.7.0.3
-----------------
 * Fixed an important bug, an incorrect config cache, after the agent

2.7.0.2
-----------------
 * Fix upload errors
 * Disabling skeeks cms panels, at the time of launch and debug modules gii
 * Fix admin bugs
 * Drop user is depricated columns (city, address, info, files, status_of_life)
 * Drop restrict index in cms_storage_file

2.7.0.1
-----------------
 * Update smart content element filters
 * Fix bugs for windows

2.7.0
-----------------
 * It is ready

2.7.0.beta
-----------------
 * fix bugs

2.7.0.alpha
-----------------
 * Removed dependency yiisoft/yii2-gii
 * Removed dependency yiisoft/yii2-debug
 * Big refactoring
 * deleted references to class skeeks\cms\App
 * Added new dependency ifsnop/mysqldump-php
 * Removed skeeks\cms\components\GiiModule
 * Removed skeeks\cms\exceptions\NotConnectedToDbException
 * Removed skeeks\cms\base\Action
 * Removed skeeks\cms\base\Session
 * Removed skeeks\cms\base\DbSession
 * Removed skeeks\cms\components\CmsSettings
 * Removed skeeks\cms\console\controllers\ComposerController
 * Removed skeeks\cms\checks\MysqlDumpCheck
 * Removed skeeks\cms\checks\InstallScriptCheck
 * Removed skeeks\cms\checks\GitClientCheck
 * Major changes to work with the creation of the database dump and its recovery
 * AssetManager LinkAssets options by default false
 * Updated admin info
 * Fixed searchRelatedProperties
 * Added caching tree for multiselect
 * Added elements to favorites users
 * Removed dependency skeeks/yii2-kartik-markdown
 * Fixed a bug with the display of the content in the administrative part, an additional property with code properties

2.6.1
-----------------
 * Deleted is deprecated fields from cms_storage_file
 * Deleted class skeeks\cms\widgets\ModelStorageFileManager
 * Adding the priority clusters file storage
 * Fixed critical bug with memory consumption when displaying files in the widget select file
 * Optimized widget content items
 * Added option to obtain all the descendants of the section element http://en.cms.skeeks.com/docs/sections-tree
 * Fixed critical bug with memory consumption when displaying files in the repository. There was at moments showing a large number of elements.
 * Revision of validation of additional properties + added examples: http://en.cms.skeeks.com/docs/additional-properties-models
 * Caching data tree to build the select element
 * Revision the model related properties

2.6.0
-----------------
 * Доработка виджета ContentElementsCmsWidget — теперь более универсальный
 * Включен показ табов вложенного контента
 * Обновление requirements.php
 * Обновлена форма управления элементом контента
 * Не показываются группы разделов, если в них нет доступных или активных разделов в блоке контента.
 * В компонент CMS добавлена настройка tmpFolderScheme, описывающая временные дирриктории всех приложений
 * Отключение чистки assets файлов, в момент обновления (иногда бывают ошибки)

2.6.0-alpha1
-----------------
 * Адаптация под composer 1.0.0-beta1
 * Изменен путь по умолчанию в AUTO_GENERATED_MODULES_FILE
 * Удалена зависимость от kartik-v/yii2-widget-touchspin (теперь используем нативные возможности)
 * Удален класс skeeks\cms\components\imaging\validators\AllowExtension
 * Удален класс skeeks\cms\validators\HasBehaviorsOr
 * Удален класс skeeks\cms\validators\HasBehavior
 * Доработано поведение HasTrees (теперь не удаляются все связи при каждом сохранении)
 * Удален класс skeeks\cms\filters\NormalizeDir
 * Удален класс skeeks\cms\validators\db\IsNewRecord
 * Удален класс skeeks\cms\validators\db\NotNewRecord
 * Удален класс skeeks\cms\validators\db\NotSame
 * Удален класс skeeks\cms\validators\db\IsSame
 * Удалена папка docs (документация на сайте dev.cms.skeeks.com)
 * Удален класс skeeks\cms\panels\ViewsPanel
 * Удален класс skeeks\cms\base\behaviors\ActiveRecord
 * Удален класс skeeks\cms\base\behaviors\Controller
 * Удален класс skeeks\cms\components\CollectionComponents
 * Удален класс skeeks\cms\traits\HasComponentConfigFormTrait
 * Удален класс skeeks\cms\models\ModelDescriptor
 * Удален класс skeeks\cms\models\ComponentModel
 * Удален класс skeeks\cms\models\ActionViewModel
 * Удален класс skeeks\cms\base\db\ActiveRecord
 * Чиста кода от Notice предупреждений

2.5.0
-----------------
 * Переделана сборка формы базовых CMS компонентов, виджетов, теперь используют интерфейс ConfigFormInterface
 * Изменено управление типами свойств
 * Добавлен ConfigFormInterface
 * Удален виджет формы yandex карт, вместо этого разработан целый пакет skeeks/cms-ya-map (https://github.com/skeeks-cms/cms-ya-map)
 * yi2-faker и yii2-codeception убраны из зависимостей

2.4.10
-----------------
 * Дорботка i18n
 * Мелкие доработки
 * Доработка управления пользователями
 * В 2 раза уменьшено количество запросов в DB
 * Оптимизация запросов и производительности
 * Модель relatedProperty — dynamic model
 * Исправлен баг создания дополнительных свойств
 * Отключено хранение переводов в базе по умолчанию
 * Доработка виджета показа элементов контента (исправлен баг с учетом подразделов)

2.4.9
-----------------
 * Мгновенная загрузка меню в нужном месте.
 * Дополнительные свойства разделов объеденены в одну форму.
 * Объединение дополнительныйх свойств в элементах контента в одну форму.
 * Изменение колонок в элементах контента по умолчанию
 * Обновлен стиль форм
 * Новый функционал зависимостей между элементами контента
 * Изменение логики открытых пунктов меню
 * Рекурсивное построение меню, бесконечной вложенности
 * Доработка формы, в настройках элемента привязки к контенту
 * Fixed max-width for images
 * Отключение панели SkeekS CMS в действиях файлового менеджера (во фрейме)
 * Улучшено управление разделами (двойной клик, перемещение разделов, внешний вид)
 * Возможность задавать собственный шаблон любому разделу
 * Исправлен маленький баг верстки футера
 * Добавлена возможность менять сортирвку рабочих столов
 * Доработка виджета элементов контента (появились кнопки на список и создание элементов)
 * Изменился порядок пунктов меню по умолчанию
 * Изменилась конка пунктов меню по умолчанию
 * Исправлены небольшие опечатки

2.4.8
-----------------
 * Исправлены баги построения меню
 * Исправлены мелкие баги
 * Исправлены формы некоторых виджетов
 * Новая настройка доступа для управленя рабочими столами
 * Виджет рабочего стола "Место на диске"
 * Виджет рабочего стола "Краткая информация"
 * Виджет рабочего стола "Информация о CMS"
 * Виджет рабочего стола "Элементы контента"
 * Подчищены use
 * Удалены неспользуемые классы
 * Возможность создавать и управлять рабочими столами, возможность добавления виджетов на них
 * Виджет для отрисовки панели в административной части
 * Добавлен файл для проверки окружения сайта requirements.php
 * Исправлено дублирование шаблона по умолчанию в настройках проекта
 * Исправлены некоторые параметры доступа
 * Изменены приоритеты построения меню
 * Исправлена логика работы открытия и закрытия меню
 * Обновлен размер иконок и сами иконки
 * Обновлено оформление меню в административной части
 * Изменена логика открытия и закрытия блоков в административной части
 * Обновлено оформление хлебных крошек в административной части

2.4.7
-----------------
 * Отключена возможность обновления и установки решений через административную часть
 * Добавлена команда php yii cms/db/first-dump-restore

2.4.6.6
-----------------
 * Обновление скрипта обновления проектов

2.4.6.5
-----------------
 * Небольшие правки

2.4.6.4
-----------------
 * Новая настройка СЕО компонента — счетчики. Теперь есть возможность задавать коды счетчики для всех сайтов в одном компоненте \Yii::$app->seo->countersContent

2.4.6.3
-----------------
 * Логируются более понятные ошибки с запросами несуществующих картинок
 * Исправлен баг с сохранением пустых свойств

2.4.6.2
-----------------
 * Редактирование элемента в таблице по двойному клику на строку.
 * Новые элементы форм
 * Добавлена авторизация через Facebook
 * Незначительные доработки

2.4.6.1
-----------------
 * Данные для селекта типов элемента контента (с группами)
 * Разработка нового элеманта формы, для выбора элемента контента CmsContentElementInput
 * Изменения по динамически собираемым полям к формам
 * Изменились докстринги

2.4.6
-----------------
 * Доработка хлебных крошек
 * Добавлена возможность управления переводоми хранящимися в базе данных

2.4.5.2
-----------------
 * Добавлена возможность управления доступом к различным элементам контента
 * Удобное управление группами пользователей (убрали непонятную влкадку привелегии)
 * Дополнительная информация в таблице пользователей.
 * Доработка отображения пользователя в таблицах.
 * Доработка создания пользователей. Генерация необходимых хэшей.

2.4.5.1
-----------------
 * Доработка регистрации по email
 * Доработка getSmartValue
 * Фильтрация по динамически создаваемым свойствам элементов (админ часть)
 * Исправлен баг, с редирректами
 * Исправлен баг, доступа к элементам контента через панель быстрого управления

2.4.5
-----------------
 * Добавлена возможность выбора типа разделов в виджете меню
 * Исправлен баг с созданием разделов и рактиногом совпадающим с элементами
 * Добавлена возможность привязки изображений к сайтам, и языку.

2.4.4.7
-----------------
 * fix install migration bugs

2.4.4.6
-----------------
 * fix install bugs

2.4.4.5
-----------------
 * No image default update
 * New console tool [php yii cms/utils/clear-all-thumbnails] - чистка всех сгенерированных миниатюр

2.4.4.4
-----------------
 * Исправлены баг формирования meta title

2.4.4.3
-----------------
 * Исправлены ошибки в процессе установки

2.4.4.2
-----------------
  * добавлены настройки шаблонов seo данных в контенте
  * добавлены настройки шаблонов seo данных в контенте
  * добавлена вкладка настроек доступа к контенту
  * fix admin total size
  * Удаление устаревших классов, рефакторинг

2.4.4.1
-----------------
  * Испралвение ошибок доступа
  * Исправление ошибки управления привилегиями

2.4.4
-----------------
  * Возможность управления шаблонами в режиме редактирования

2.4.2
-----------------
  * Исправлены недочеты в getSmartAttribute
  * Оптимизация запросов построения меню

2.4.1.3
-----------------
  * В виджете элементов контента, добавлены with по умолчанию
  * Оптимизация сборки URL к элементу контента (хорошее ускорение)

2.4.1.2
-----------------
  * Добавлена возможность кэширования виджета с элементами контента
  * Исправлена отправка скрытой копии email

2.4.1.1
-----------------
  * продолжение интернациализации проекта
  * fix bugs
  * Убрана зависимость от функции system

2.4.1
-----------------
  * Доработан процесс обновления
  * Доработан процесс установки
  * Увеличены кнопки действий в системе администрирования
  * Доработка панели быстрого управления сайтом
  * Отладка процесса обновления
  * Исправлена верстка административной части

2.4.0.2
-----------------
  * Добавлено скрытое название в разделах (удобно использовать в мультиязычных деревьях)
  * Расширены настройки перенаправлений в разделах

2.4.0.1
-----------------
  * Немного переводов
  * Новый console tool cms/init
  * Добавлены новые группы переводов в модуле cms

2.4.0
-----------------
  * В быструю панель редактирования сайта, добавлен функционал чистки кэша
  * Исправлено переключение настроек с сохранением режима диалогового окна
  * Указание раздела по умолчанию в настройка типов разделов
  * Дополнительные возможности настройки шаблонов по умолчанию для разделов и контента
  * Полный рефакторинг модели дерева, удалено поведение TreeBehavior, теперь все методы в самой моделе.
  * Рефакторинг управления деревом разделов (removed is depricated методы: \skeeks\cms\models\Tree hasChildrens, findChildrens, getPid, swapPriorities, )
  * Для исключения путаницы сортировка везде включена по умолчанию по возрастанию приоритета.
  * Добавлены настройки к типам контента, можно указать основной раздел привязки. А так же кореневой раздел.
  * Добавлена древовидность в управлении контентом
  * Увеличен максимальный уровень вложенности меню
  * Увеличен размер шрифта в меню амдинистративной части
  * Обновлен режим редактирования, появились дополнительные возможности исправлены баги.
  * У сайта убрано поле язык (язык задается в настройках компонента, для любого из сайтов)
  * Исправлена адресация
  * Исправлен учет настройки добавления слэша на конце разделов.
  * Запуск проекта на стандартном хостинге nic.ru — выявил некоторые ошибки. Исправлено.
  * Доработка Thumbnail фильтра. Теперь можно не передавать один из параметров (ширину или высоту). В этом случае второй параметр будет вычислен автоматически, согласно пропорциям изображения.
  * Исправлен элемент управления временем в полях с датой
  * Доработка авторизации через социальные сети
  * Небольшие изменения
  * Добавлена возможность переключения языка интерфейса
  * Рефаторинг чистки кэша
  * Доработка авторизации через социальные сети
  * Имзенение логики рендеринга действий контроллера
  * Доработка авторизации через социальные сети
 
### https://github.com/skeeks-cms/cms/blob/master/CHANGELOG-OLD.md
