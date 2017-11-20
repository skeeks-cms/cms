-- Converted by db_converter
START TRANSACTION;
SET standard_conforming_strings=off;
SET escape_string_warning=off;
SET CONSTRAINTS ALL DEFERRED;

CREATE TABLE "auth_assignment" (
    "item_name" varchar(128) NOT NULL,
    "user_id" integer NOT NULL,
    "created_at" integer DEFAULT NULL,
    PRIMARY KEY ("item_name","user_id")
);

INSERT INTO "auth_assignment" VALUES ('root',1,1439037301);
CREATE TABLE "auth_item" (
    "name" varchar(128) NOT NULL,
    "type" integer NOT NULL,
    "description" text ,
    "rule_name" varchar(128) DEFAULT NULL,
    "data" text ,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    PRIMARY KEY ("name")
);

INSERT INTO "auth_item" VALUES ('',2,'Управление шаблоном',NULL,NULL,1504194697,1504194697),('admin',1,'Администратор',NULL,NULL,1439037301,1439037301),('admin/admin-permission',2,'Администрирование | Управление привилегиями',NULL,NULL,1439297538,1439297538),('admin/admin-role',2,'Администрирование | Управление ролями',NULL,NULL,1439297538,1439297538),('admin/checker',2,'Администрирование | Проверка системы',NULL,NULL,1439297538,1439297538),('admin/clear',2,'Администрирование | Удаление временных файлов',NULL,NULL,1439297538,1439297538),('admin/db',2,'Администрирование | Удаление временных файлов',NULL,NULL,1439297538,1439297538),('admin/email',2,'Администрирование | Тестирование отправки email сообщений с сайта',NULL,NULL,1439297538,1439297538),('admin/gii',2,'Администрирование | Генератор кода',NULL,NULL,1439297538,1439297538),('admin/index',2,'Администрирование | Рабочий стол',NULL,NULL,1443689676,1443689676),('admin/info',2,'Администрирование | Информация о системе',NULL,NULL,1439297538,1439297538),('admin/ssh',2,'Администрирование | Ssh консоль',NULL,NULL,1439297538,1439297538),('approved',1,'Подтвержденный пользователь',NULL,NULL,1443647312,1443647312),('cms.admin-access',2,'Доступ к системе администрирования',NULL,NULL,1439037301,1439037301),('cms.admin-access/update',2,'Редактировать',NULL,NULL,1510340312,1510340312),('cms.admin-access/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1510340312,1510340312),('cms.admin-dashboards-edit',2,'Access to edit dashboards',NULL,NULL,1455882084,1455882084),('cms.controll-panel-access',2,'Доступ к панеле управления сайтом',NULL,NULL,1439037301,1439037301),('cms.edit-view-files',2,'The ability to edit view files',NULL,NULL,1447185422,1447185422),('cms.elfinder-additional-files',2,'Доступ ко всем файлам',NULL,NULL,1439037301,1439037301),('cms.elfinder-common-public-files',2,'Доступ к общим публичным файлам',NULL,NULL,1439037301,1439037301),('cms.elfinder-user-files',2,'Доступ к личным файлам',NULL,NULL,1439037301,1439037301),('cms.model-create',2,'Возможность создания записей',NULL,NULL,1439037301,1439037301),('cms.model-delete',2,'Удаление записей',NULL,NULL,1439037301,1439037301),('cms.model-delete-own',2,'Удаление своих записей','isAuthor',NULL,1439037301,1439037301),('cms.model-update',2,'Обновление данных записей',NULL,NULL,1439037301,1439037301),('cms.model-update-advanced',2,'Обновление дополнительных данных записей',NULL,NULL,1439037301,1439037301),('cms.model-update-advanced-own',2,'Обновление дополнительных данных своих записей','isAuthor',NULL,1439037301,1439037301),('cms.model-update-own',2,'Обновление данных своих записей','isAuthor',NULL,1439037301,1439037301),('cms.user-full-edit',2,'The ability to manage user groups',NULL,NULL,1457470067,1457470067),('cms/admin-clear',2,'Удаление временных файлов',NULL,NULL,1504194295,1504194295),('cms/admin-clear/index',2,'Чистка временных данных',NULL,NULL,1504194358,1504194358),('cms/admin-cms-agent',2,'Администрирование | Управление агентами',NULL,NULL,1439297538,1439297538),('cms/admin-cms-content',2,'Администрирование | Управление контентом',NULL,NULL,1443696551,1443696551),('cms/admin-cms-content-element',2,'Администрирование | Элементы',NULL,NULL,1443700128,1443700128),('cms/admin-cms-content-element__1',2,'Администрирование | Публикации',NULL,NULL,1443699185,1443699185),('cms/admin-cms-content-element__1/activate-multi',2,'Активировать',NULL,NULL,1510087622,1510087622),('cms/admin-cms-content-element__1/change-tree-multi',2,'Основной раздел',NULL,NULL,1510087623,1510087623),('cms/admin-cms-content-element__1/change-trees-multi',2,'Дополнительные разделы',NULL,NULL,1510087623,1510087623),('cms/admin-cms-content-element__1/create',2,'Добавить',NULL,NULL,1510087622,1510087622),('cms/admin-cms-content-element__1/delete',2,'Удалить',NULL,NULL,1504194594,1504194594),('cms/admin-cms-content-element__1/delete-multi',2,'Удалить',NULL,NULL,1510087622,1510087622),('cms/admin-cms-content-element__1/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194597,1504194597),('cms/admin-cms-content-element__1/inActivate-multi',2,'Деактивировать',NULL,NULL,1510087622,1510087622),('cms/admin-cms-content-element__1/index',2,'Список',NULL,NULL,1510087622,1510087622),('cms/admin-cms-content-element__1/rp',2,'Свойства',NULL,NULL,1510087623,1510087623),('cms/admin-cms-content-element__1/update',2,'Редактировать',NULL,NULL,1504194594,1504194594),('cms/admin-cms-content-element__1/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194594,1504194594),('cms/admin-cms-content-element__2',2,'Администрирование | Слайды',NULL,NULL,1443697223,1443697223),('cms/admin-cms-content-element__2/activate-multi',2,'Активировать',NULL,NULL,1510329298,1510329298),('cms/admin-cms-content-element__2/change-tree-multi',2,'Основной раздел',NULL,NULL,1510329299,1510329299),('cms/admin-cms-content-element__2/change-trees-multi',2,'Дополнительные разделы',NULL,NULL,1510329299,1510329299),('cms/admin-cms-content-element__2/create',2,'Добавить',NULL,NULL,1510329298,1510329298),('cms/admin-cms-content-element__2/delete',2,'Удалить',NULL,NULL,1504194601,1504194601),('cms/admin-cms-content-element__2/delete-multi',2,'Удалить',NULL,NULL,1510329298,1510329298),('cms/admin-cms-content-element__2/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194601,1504194601),('cms/admin-cms-content-element__2/inActivate-multi',2,'Деактивировать',NULL,NULL,1510329299,1510329299),('cms/admin-cms-content-element__2/index',2,'Список',NULL,NULL,1510329298,1510329298),('cms/admin-cms-content-element__2/rp',2,'Свойства',NULL,NULL,1510329299,1510329299),('cms/admin-cms-content-element__2/update',2,'Редактировать',NULL,NULL,1504194600,1504194600),('cms/admin-cms-content-element__2/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194601,1504194601),('cms/admin-cms-content-element__3',2,'Администрирование | Услуги',NULL,NULL,1445806952,1445806952),('cms/admin-cms-content-element__3/activate-multi',2,'Активировать',NULL,NULL,1510087640,1510087640),('cms/admin-cms-content-element__3/change-tree-multi',2,'Основной раздел',NULL,NULL,1510087640,1510087640),('cms/admin-cms-content-element__3/change-trees-multi',2,'Дополнительные разделы',NULL,NULL,1510087640,1510087640),('cms/admin-cms-content-element__3/create',2,'Добавить',NULL,NULL,1510087640,1510087640),('cms/admin-cms-content-element__3/delete',2,'Удалить',NULL,NULL,1504194607,1504194607),('cms/admin-cms-content-element__3/delete-multi',2,'Удалить',NULL,NULL,1510087640,1510087640),('cms/admin-cms-content-element__3/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194607,1504194607),('cms/admin-cms-content-element__3/inActivate-multi',2,'Деактивировать',NULL,NULL,1510087640,1510087640),('cms/admin-cms-content-element__3/index',2,'Список',NULL,NULL,1510087639,1510087639),('cms/admin-cms-content-element__3/rp',2,'Свойства',NULL,NULL,1510087640,1510087640),('cms/admin-cms-content-element__3/update',2,'Редактировать',NULL,NULL,1504194606,1504194606),('cms/admin-cms-content-element__3/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194607,1504194607),('cms/admin-cms-content-element/activate-multi',2,'Активировать',NULL,NULL,1504194332,1504194332),('cms/admin-cms-content-element/change-tree-multi',2,'Основной раздел',NULL,NULL,1504194332,1504194332),('cms/admin-cms-content-element/change-trees-multi',2,'Дополнительные разделы',NULL,NULL,1504194332,1504194332),('cms/admin-cms-content-element/create',2,'Добавить',NULL,NULL,1504194330,1504194330),('cms/admin-cms-content-element/delete',2,'Удалить',NULL,NULL,1504194331,1504194331),('cms/admin-cms-content-element/delete-multi',2,'Удалить',NULL,NULL,1504194332,1504194332),('cms/admin-cms-content-element/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194331,1504194331),('cms/admin-cms-content-element/inActivate-multi',2,'Деактивировать',NULL,NULL,1504194332,1504194332),('cms/admin-cms-content-element/index',2,'Список',NULL,NULL,1504194330,1504194330),('cms/admin-cms-content-element/rp',2,'Свойства',NULL,NULL,1504194333,1504194333),('cms/admin-cms-content-element/update',2,'Редактировать',NULL,NULL,1504194331,1504194331),('cms/admin-cms-content-element/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194331,1504194331),('cms/admin-cms-content-property',2,'Управление свойствами',NULL,NULL,1504194294,1504194294),('cms/admin-cms-content-property-enum',2,'Управление значениями свойств',NULL,NULL,1504194294,1504194294),('cms/admin-cms-content-property-enum/create',2,'Добавить',NULL,NULL,1504194354,1504194354),('cms/admin-cms-content-property-enum/delete',2,'Удалить',NULL,NULL,1504194354,1504194354),('cms/admin-cms-content-property-enum/delete-multi',2,'Удалить',NULL,NULL,1504194355,1504194355),('cms/admin-cms-content-property-enum/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194354,1504194354),('cms/admin-cms-content-property-enum/index',2,'Список',NULL,NULL,1504194353,1504194353),('cms/admin-cms-content-property-enum/update',2,'Редактировать',NULL,NULL,1504194354,1504194354),('cms/admin-cms-content-property-enum/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194354,1504194354),('cms/admin-cms-content-property/create',2,'Добавить',NULL,NULL,1504194352,1504194352),('cms/admin-cms-content-property/delete',2,'Удалить',NULL,NULL,1504194353,1504194353),('cms/admin-cms-content-property/delete-multi',2,'Удалить',NULL,NULL,1504194353,1504194353),('cms/admin-cms-content-property/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194353,1504194353),('cms/admin-cms-content-property/index',2,'Список',NULL,NULL,1504194352,1504194352),('cms/admin-cms-content-property/update',2,'Редактировать',NULL,NULL,1504194352,1504194352),('cms/admin-cms-content-property/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194352,1504194352),('cms/admin-cms-content-type',2,'Администрирование | Управление контентом',NULL,NULL,1439297538,1439297538),('cms/admin-cms-content-type/create',2,'Добавить',NULL,NULL,1504194350,1504194350),('cms/admin-cms-content-type/delete',2,'Удалить',NULL,NULL,1504194351,1504194351),('cms/admin-cms-content-type/delete-multi',2,'Удалить',NULL,NULL,1504194351,1504194351),('cms/admin-cms-content-type/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194351,1504194351),('cms/admin-cms-content-type/index',2,'Список',NULL,NULL,1504194350,1504194350),('cms/admin-cms-content-type/update',2,'Редактировать',NULL,NULL,1504194351,1504194351),('cms/admin-cms-content-type/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194351,1504194351),('cms/admin-cms-content/create',2,'Добавить',NULL,NULL,1504194355,1504194355),('cms/admin-cms-content/delete',2,'Удалить',NULL,NULL,1504194356,1504194356),('cms/admin-cms-content/delete-multi',2,'Удалить',NULL,NULL,1504194357,1504194357),('cms/admin-cms-content/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194356,1504194356),('cms/admin-cms-content/index',2,'Список',NULL,NULL,1504194355,1504194355),('cms/admin-cms-content/update',2,'Редактировать',NULL,NULL,1504194355,1504194355),('cms/admin-cms-content/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194356,1504194356),('cms/admin-cms-lang',2,'Администрирование | Управление языками',NULL,NULL,1439297538,1439297538),('cms/admin-cms-lang/activate-multi',2,'Активировать',NULL,NULL,1504194340,1504194340),('cms/admin-cms-lang/create',2,'Добавить',NULL,NULL,1504194336,1504194336),('cms/admin-cms-lang/delete',2,'Удалить',NULL,NULL,1504194337,1504194337),('cms/admin-cms-lang/delete-multi',2,'Удалить',NULL,NULL,1504194338,1504194338),('cms/admin-cms-lang/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194338,1504194338),('cms/admin-cms-lang/inActivate-multi',2,'Деактивировать',NULL,NULL,1504194341,1504194341),('cms/admin-cms-lang/index',2,'Список',NULL,NULL,1504194336,1504194336),('cms/admin-cms-lang/update',2,'Редактировать',NULL,NULL,1504194336,1504194336),('cms/admin-cms-lang/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194337,1504194337),('cms/admin-cms-site',2,'Администрирование | Управление сайтами',NULL,NULL,1439297538,1439297538),('cms/admin-cms-site/activate-multi',2,'Активировать',NULL,NULL,1504194335,1504194335),('cms/admin-cms-site/create',2,'Добавить',NULL,NULL,1504194333,1504194333),('cms/admin-cms-site/def-multi',2,'По умолчанию',NULL,NULL,1504194335,1504194335),('cms/admin-cms-site/delete',2,'Удалить',NULL,NULL,1504194334,1504194334),('cms/admin-cms-site/delete-multi',2,'Удалить',NULL,NULL,1504194335,1504194335),('cms/admin-cms-site/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194334,1504194334),('cms/admin-cms-site/inActivate-multi',2,'Деактивировать',NULL,NULL,1504194335,1504194335),('cms/admin-cms-site/index',2,'Список',NULL,NULL,1504194333,1504194333),('cms/admin-cms-site/update',2,'Редактировать',NULL,NULL,1504194333,1504194333),('cms/admin-cms-site/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194334,1504194334),('cms/admin-cms-tree-type',2,'Администрирование | Настройки разделов',NULL,NULL,1439297538,1439297538),('cms/admin-cms-tree-type-property',2,'Управление свойствами раздела',NULL,NULL,1504194293,1504194293),('cms/admin-cms-tree-type-property-enum',2,'Управление значениями свойств разделов',NULL,NULL,1504194294,1504194294),('cms/admin-cms-tree-type-property-enum/create',2,'Добавить',NULL,NULL,1504194343,1504194343),('cms/admin-cms-tree-type-property-enum/delete',2,'Удалить',NULL,NULL,1504194344,1504194344),('cms/admin-cms-tree-type-property-enum/delete-multi',2,'Удалить',NULL,NULL,1504194347,1504194347),('cms/admin-cms-tree-type-property-enum/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194344,1504194344),('cms/admin-cms-tree-type-property-enum/index',2,'Список',NULL,NULL,1504194343,1504194343),('cms/admin-cms-tree-type-property-enum/update',2,'Редактировать',NULL,NULL,1504194344,1504194344),('cms/admin-cms-tree-type-property-enum/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194344,1504194344),('cms/admin-cms-tree-type-property/create',2,'Добавить',NULL,NULL,1504194342,1504194342),('cms/admin-cms-tree-type-property/delete',2,'Удалить',NULL,NULL,1504194342,1504194342),('cms/admin-cms-tree-type-property/delete-multi',2,'Удалить',NULL,NULL,1504194343,1504194343),('cms/admin-cms-tree-type-property/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194343,1504194343),('cms/admin-cms-tree-type-property/index',2,'Список',NULL,NULL,1504194342,1504194342),('cms/admin-cms-tree-type-property/update',2,'Редактировать',NULL,NULL,1504194342,1504194342),('cms/admin-cms-tree-type-property/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194342,1504194342),('cms/admin-cms-tree-type/activate-multi',2,'Активировать',NULL,NULL,1504194350,1504194350),('cms/admin-cms-tree-type/create',2,'Добавить',NULL,NULL,1504194348,1504194348),('cms/admin-cms-tree-type/delete',2,'Удалить',NULL,NULL,1504194349,1504194349),('cms/admin-cms-tree-type/delete-multi',2,'Удалить',NULL,NULL,1504194350,1504194350),('cms/admin-cms-tree-type/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194349,1504194349),('cms/admin-cms-tree-type/inActivate-multi',2,'Деактивировать',NULL,NULL,1504194350,1504194350),('cms/admin-cms-tree-type/index',2,'Список',NULL,NULL,1504194348,1504194348),('cms/admin-cms-tree-type/update',2,'Редактировать',NULL,NULL,1504194348,1504194348),('cms/admin-cms-tree-type/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194349,1504194349),('cms/admin-cms-user-universal-property',2,'Администрирование | Управление свойствами пользователя',NULL,NULL,1445957836,1445957836),('cms/admin-cms-user-universal-property/create',2,'Добавить',NULL,NULL,1504194321,1504194321),('cms/admin-cms-user-universal-property/delete',2,'Удалить',NULL,NULL,1504194322,1504194322),('cms/admin-cms-user-universal-property/delete-multi',2,'Удалить',NULL,NULL,1504194322,1504194322),('cms/admin-cms-user-universal-property/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194322,1504194322),('cms/admin-cms-user-universal-property/index',2,'Список',NULL,NULL,1504194320,1504194320),('cms/admin-cms-user-universal-property/update',2,'Редактировать',NULL,NULL,1504194321,1504194321),('cms/admin-cms-user-universal-property/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194321,1504194321),('cms/admin-component-settings',2,'Администрирование | Управление настройками компонентов',NULL,NULL,1443695979,1443695979),('cms/admin-file-manager',2,'Администрирование | Файловый менеджер',NULL,NULL,1439297538,1439297538),('cms/admin-info',2,'Информация о системе',NULL,NULL,1504194294,1504194294),('cms/admin-info/index',2,'Общая информация',NULL,NULL,1504194358,1504194358),('cms/admin-marketplace',2,'Администрирование | Маркетплейс',NULL,NULL,1439297538,1439297538),('cms/admin-settings',2,'Администрирование | Управление настройками',NULL,NULL,1439297538,1439297538),('cms/admin-settings/index',2,'Настройки',NULL,NULL,1504194357,1504194357),('cms/admin-storage',2,'Администрирование | Управление серверами',NULL,NULL,1439297538,1439297538),('cms/admin-storage-files',2,'Администрирование | Управление файлами хранилища',NULL,NULL,1439297538,1439297538),('cms/admin-storage-files/create',2,'Добавить',NULL,NULL,1504194328,1504194328),('cms/admin-storage-files/delete',2,'Удалить',NULL,NULL,1504194328,1504194328),('cms/admin-storage-files/delete-multi',2,'Удалить',NULL,NULL,1504194329,1504194329),('cms/admin-storage-files/delete-tmp-dir',2,'Удалить временные файлы',NULL,NULL,1504194329,1504194329),('cms/admin-storage-files/delete-tmp-dir/own',2,'Удалить временные файлы (Только свои)','isAuthor',NULL,1504194329,1504194329),('cms/admin-storage-files/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194329,1504194329),('cms/admin-storage-files/download',2,'Скачать',NULL,NULL,1504194330,1504194330),('cms/admin-storage-files/download/own',2,'Скачать (Только свои)','isAuthor',NULL,1504194330,1504194330),('cms/admin-storage-files/index',2,'Список',NULL,NULL,1504194328,1504194328),('cms/admin-storage-files/update',2,'Редактировать',NULL,NULL,1504194328,1504194328),('cms/admin-storage-files/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194328,1504194328),('cms/admin-storage/index',2,'Управление серверами',NULL,NULL,1504194341,1504194341),('cms/admin-tree',2,'Администрирование | Дерево страниц',NULL,NULL,1439297538,1439297538),('cms/admin-tree-menu',2,'Администрирование | Управление позициями меню',NULL,NULL,1439297538,1439297538),('cms/admin-tree/delete',2,'Удалить',NULL,NULL,1504194327,1504194327),('cms/admin-tree/delete-multi',2,'Удалить',NULL,NULL,1504194327,1504194327),('cms/admin-tree/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194327,1504194327),('cms/admin-tree/index',2,'Разделы',NULL,NULL,1504194325,1504194325),('cms/admin-tree/list',2,'Список',NULL,NULL,1510087614,1510087614),('cms/admin-tree/update',2,'Редактировать',NULL,NULL,1504194325,1504194325),('cms/admin-tree/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194326,1504194326),('cms/admin-universal-component-settings',2,'Администрирование | Управление настройками компонента',NULL,NULL,1445806012,1445806012),('cms/admin-user',2,'Администрирование | Управление пользователями',NULL,NULL,1439297538,1439297538),('cms/admin-user-email',2,'Администрирование | Управление email адресами',NULL,NULL,1439297538,1439297538),('cms/admin-user-email/create',2,'Добавить',NULL,NULL,1504194323,1504194323),('cms/admin-user-email/delete',2,'Удалить',NULL,NULL,1504194324,1504194324),('cms/admin-user-email/delete-multi',2,'Удалить',NULL,NULL,1504194324,1504194324),('cms/admin-user-email/index',2,'Список',NULL,NULL,1504194323,1504194323),('cms/admin-user-email/update',2,'Редактировать',NULL,NULL,1504194323,1504194323),('cms/admin-user-phone',2,'Администрирование | Управление телефонами',NULL,NULL,1439297538,1439297538),('cms/admin-user-phone/create',2,'Добавить',NULL,NULL,1504194324,1504194324),('cms/admin-user-phone/delete',2,'Удалить',NULL,NULL,1504194325,1504194325),('cms/admin-user-phone/delete-multi',2,'Удалить',NULL,NULL,1504194325,1504194325),('cms/admin-user-phone/index',2,'Список',NULL,NULL,1504194324,1504194324),('cms/admin-user-phone/update',2,'Редактировать',NULL,NULL,1504194325,1504194325),('cms/admin-user/activate-multi',2,'Активировать',NULL,NULL,1504194320,1504194320),('cms/admin-user/create',2,'Добавить',NULL,NULL,1504194319,1504194319),('cms/admin-user/delete',2,'Удалить',NULL,NULL,1504194320,1504194320),('cms/admin-user/delete-multi',2,'Удалить',NULL,NULL,1504194320,1504194320),('cms/admin-user/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194320,1504194320),('cms/admin-user/inActivate-multi',2,'Деактивировать',NULL,NULL,1504194320,1504194320),('cms/admin-user/index',2,'Список',NULL,NULL,1504194319,1504194319),('cms/admin-user/update',2,'Редактировать',NULL,NULL,1504194319,1504194319),('cms/admin-user/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194319,1504194319),('cmsAgent/admin-cms-agent',2,'Администрирование | Агенты',NULL,NULL,1461231740,1461231740),('cmsAgent/admin-cms-agent/activate-multi',2,'Активировать',NULL,NULL,1504194301,1504194301),('cmsAgent/admin-cms-agent/create',2,'Добавить',NULL,NULL,1504194301,1504194301),('cmsAgent/admin-cms-agent/delete',2,'Удалить',NULL,NULL,1504194302,1504194302),('cmsAgent/admin-cms-agent/delete-multi',2,'Удалить',NULL,NULL,1504194300,1504194300),('cmsAgent/admin-cms-agent/inActivate-multi',2,'Деактивировать',NULL,NULL,1504194301,1504194301),('cmsAgent/admin-cms-agent/index',2,'Список',NULL,NULL,1504194300,1504194300),('cmsAgent/admin-cms-agent/update',2,'Редактировать',NULL,NULL,1504194302,1504194302),('cmsMarketplace/admin-composer-update',2,'Обновление платформы',NULL,NULL,1510952558,1510952558),('cmsMarketplace/admin-composer-update/update',2,'Обновление платформы',NULL,NULL,1510952569,1510952569),('cmsMarketplace/admin-marketplace',2,'Администрирование | Маркетплейс',NULL,NULL,1461231736,1461231736),('cmsMarketplace/admin-marketplace/catalog',2,'Каталог',NULL,NULL,1504194358,1504194358),('cmsMarketplace/admin-marketplace/index',2,'Установленные',NULL,NULL,1504194357,1504194357),('cmsMarketplace/admin-marketplace/install',2,'Установить/Удалить',NULL,NULL,1504194358,1504194358),('cmsMarketplace/admin-marketplace/update',2,'Обновление платформы',NULL,NULL,1504194358,1504194358),('cmsSearch/admin-search-phrase',2,'Список переходов',NULL,NULL,1504194295,1504194295),('cmsSearch/admin-search-phrase-group',2,'Список переходов',NULL,NULL,1504194295,1504194295),('cmsSearch/admin-search-phrase-group/index',2,'Список',NULL,NULL,1504194366,1504194366),('cmsSearch/admin-search-phrase/create',2,'Добавить',NULL,NULL,1504194365,1504194365),('cmsSearch/admin-search-phrase/delete',2,'Удалить',NULL,NULL,1504194365,1504194365),('cmsSearch/admin-search-phrase/delete-multi',2,'Удалить',NULL,NULL,1504194366,1504194366),('cmsSearch/admin-search-phrase/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194366,1504194366),('cmsSearch/admin-search-phrase/index',2,'Список',NULL,NULL,1504194364,1504194364),('cmsSearch/admin-search-phrase/update',2,'Редактировать',NULL,NULL,1504194365,1504194365),('cmsSearch/admin-search-phrase/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194365,1504194365),('dbDumper/admin-backup',2,'Администрирование | Бэкапы',NULL,NULL,1461231794,1461231794),('dbDumper/admin-settings',2,'Настройки',NULL,NULL,1504194294,1504194294),('dbDumper/admin-structure',2,'Администрирование | Структура базы данных',NULL,NULL,1461231792,1461231792),('editor',1,'Редактор (доступ а администрированию)',NULL,NULL,1439037301,1439037301),('form2/admin-form',2,'Администрирование | Управление формами',NULL,NULL,1439297538,1439297538),('form2/admin-form-property',2,'Администрирование | Управление свойствами',NULL,NULL,1445805998,1445805998),('form2/admin-form-send',2,'Администрирование | Сообщения с форм',NULL,NULL,1439297538,1439297538),('form2/admin-form-send/delete',2,'Удалить',NULL,NULL,1504194362,1504194362),('form2/admin-form-send/delete-multi',2,'Удалить',NULL,NULL,1504194363,1504194363),('form2/admin-form-send/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194362,1504194362),('form2/admin-form-send/index',2,'Список',NULL,NULL,1504194362,1504194362),('form2/admin-form-send/view',2,'Посмотреть',NULL,NULL,1504194363,1504194363),('form2/admin-form-send/view/own',2,'Посмотреть (Только свои)','isAuthor',NULL,1504194363,1504194363),('form2/admin-form/create',2,'Добавить',NULL,NULL,1504194359,1504194359),('form2/admin-form/delete',2,'Удалить',NULL,NULL,1504194360,1504194360),('form2/admin-form/delete-multi',2,'Удалить',NULL,NULL,1504194360,1504194360),('form2/admin-form/delete/own',2,'Удалить (Только свои)','isAuthor',NULL,1504194360,1504194360),('form2/admin-form/index',2,'Список',NULL,NULL,1504194359,1504194359),('form2/admin-form/update',2,'Редактировать',NULL,NULL,1504194359,1504194359),('form2/admin-form/update/own',2,'Редактировать (Только свои)','isAuthor',NULL,1504194359,1504194359),('form2/admin-form/view',2,'Результат',NULL,NULL,1504194361,1504194361),('form2/admin-form/view/own',2,'Результат (Только свои)','isAuthor',NULL,1504194361,1504194361),('guest',1,'Неавторизованный пользователь',NULL,NULL,1439037301,1439037301),('logDbTarget/admin-log-db-target',2,'Администрирование | Управление логами',NULL,NULL,1461231759,1461231759),('logDbTarget/admin-log-db-target/delete',2,'Удалить',NULL,NULL,1504194367,1504194367),('logDbTarget/admin-log-db-target/delete-multi',2,'Удалить',NULL,NULL,1504194367,1504194367),('logDbTarget/admin-log-db-target/index',2,'Список',NULL,NULL,1504194366,1504194366),('logDbTarget/admin-log-db-target/update',2,'Смотреть',NULL,NULL,1504194367,1504194367),('mailer/admin-test',2,'Администрирование | Тестирование отправки сообщений электронной почты с сайта',NULL,NULL,1461231752,1461231752),('manager',1,'Менеджер (доступ а администрированию)',NULL,NULL,1439037301,1439037301),('rbac/admin-permission',2,'Администрирование | Управление привилегиями',NULL,NULL,1464441890,1464441890),('rbac/admin-permission/create',2,'Create',NULL,NULL,1504194314,1504194314),('rbac/admin-permission/delete',2,'Удалить',NULL,NULL,1504194315,1504194315),('rbac/admin-permission/delete-multi',2,'Удалить',NULL,NULL,1504194314,1504194314),('rbac/admin-permission/index',2,'Список',NULL,NULL,1504194313,1504194313),('rbac/admin-permission/update',2,'Редактировать',NULL,NULL,1504194315,1504194315),('rbac/admin-permission/update-data',2,'Обновить привилегии',NULL,NULL,1504194315,1504194315),('rbac/admin-permission/view',2,'Смотреть',NULL,NULL,1504194315,1504194315),('rbac/admin-role',2,'Managing Roles',NULL,NULL,1504194293,1504194293),('rbac/admin-role/create',2,'Create',NULL,NULL,1504194318,1504194318),('rbac/admin-role/delete',2,'Удалить',NULL,NULL,1504194318,1504194318),('rbac/admin-role/delete-multi',2,'Удалить',NULL,NULL,1504194318,1504194318),('rbac/admin-role/index',2,'Список',NULL,NULL,1504194317,1504194317),('rbac/admin-role/update',2,'Update',NULL,NULL,1504194318,1504194318),('rbac/admin-role/view',2,'Смотреть',NULL,NULL,1504194319,1504194319),('reviews2.add.review',2,'Добавление отзывов',NULL,NULL,1441207879,1441207879),('reviews2/admin-message',2,'Администрирование | Управление отзывами',NULL,NULL,1439297538,1439297538),('root',1,'Суперпользователь',NULL,NULL,1439037301,1439037301),('sshConsole/admin-ssh',2,'Администрирование | Ssh console',NULL,NULL,1461231788,1461231788),('user',1,'Зарегистрированный пользователь',NULL,NULL,1439037301,1439037301);
CREATE TABLE "auth_item_child" (
    "parent" varchar(128) NOT NULL,
    "child" varchar(128) NOT NULL,
    PRIMARY KEY ("parent","child")
);

INSERT INTO "auth_item_child" VALUES ('root',''),('root','admin'),('root','admin/admin-permission'),('root','admin/admin-role'),('root','admin/checker'),('root','admin/clear'),('root','admin/db'),('root','admin/email'),('root','admin/gii'),('root','admin/index'),('root','admin/info'),('root','admin/ssh'),('root','approved'),('admin','cms.admin-access'),('editor','cms.admin-access'),('manager','cms.admin-access'),('root','cms.admin-access'),('cms.admin-access/update/own','cms.admin-access/update'),('root','cms.admin-access/update'),('root','cms.admin-access/update/own'),('root','cms.admin-dashboards-edit'),('admin','cms.controll-panel-access'),('editor','cms.controll-panel-access'),('manager','cms.controll-panel-access'),('root','cms.controll-panel-access'),('root','cms.edit-view-files'),('admin','cms.elfinder-additional-files'),('root','cms.elfinder-additional-files'),('admin','cms.elfinder-common-public-files'),('editor','cms.elfinder-common-public-files'),('manager','cms.elfinder-common-public-files'),('root','cms.elfinder-common-public-files'),('admin','cms.elfinder-user-files'),('editor','cms.elfinder-user-files'),('manager','cms.elfinder-user-files'),('root','cms.elfinder-user-files'),('admin','cms.model-create'),('editor','cms.model-create'),('manager','cms.model-create'),('root','cms.model-create'),('admin','cms.model-delete'),('cms.model-delete-own','cms.model-delete'),('manager','cms.model-delete'),('root','cms.model-delete'),('editor','cms.model-delete-own'),('root','cms.model-delete-own'),('admin','cms.model-update'),('cms.model-update-own','cms.model-update'),('manager','cms.model-update'),('root','cms.model-update'),('admin','cms.model-update-advanced'),('cms.model-update-advanced-own','cms.model-update-advanced'),('root','cms.model-update-advanced'),('root','cms.model-update-advanced-own'),('editor','cms.model-update-own'),('root','cms.model-update-own'),('root','cms.user-full-edit'),('root','cms/admin-clear'),('root','cms/admin-clear/index'),('root','cms/admin-cms-agent'),('root','cms/admin-cms-content'),('root','cms/admin-cms-content-element'),('root','cms/admin-cms-content-element__1'),('root','cms/admin-cms-content-element__1/activate-multi'),('root','cms/admin-cms-content-element__1/change-tree-multi'),('root','cms/admin-cms-content-element__1/change-trees-multi'),('root','cms/admin-cms-content-element__1/create'),('cms/admin-cms-content-element__1/delete/own','cms/admin-cms-content-element__1/delete'),('root','cms/admin-cms-content-element__1/delete'),('root','cms/admin-cms-content-element__1/delete-multi'),('root','cms/admin-cms-content-element__1/delete/own'),('root','cms/admin-cms-content-element__1/inActivate-multi'),('root','cms/admin-cms-content-element__1/index'),('root','cms/admin-cms-content-element__1/rp'),('cms/admin-cms-content-element__1/update/own','cms/admin-cms-content-element__1/update'),('root','cms/admin-cms-content-element__1/update'),('root','cms/admin-cms-content-element__1/update/own'),('root','cms/admin-cms-content-element__2'),('root','cms/admin-cms-content-element__2/activate-multi'),('root','cms/admin-cms-content-element__2/change-tree-multi'),('root','cms/admin-cms-content-element__2/change-trees-multi'),('root','cms/admin-cms-content-element__2/create'),('cms/admin-cms-content-element__2/delete/own','cms/admin-cms-content-element__2/delete'),('root','cms/admin-cms-content-element__2/delete'),('root','cms/admin-cms-content-element__2/delete-multi'),('root','cms/admin-cms-content-element__2/delete/own'),('root','cms/admin-cms-content-element__2/inActivate-multi'),('root','cms/admin-cms-content-element__2/index'),('root','cms/admin-cms-content-element__2/rp'),('cms/admin-cms-content-element__2/update/own','cms/admin-cms-content-element__2/update'),('root','cms/admin-cms-content-element__2/update'),('root','cms/admin-cms-content-element__2/update/own'),('root','cms/admin-cms-content-element__3'),('root','cms/admin-cms-content-element__3/activate-multi'),('root','cms/admin-cms-content-element__3/change-tree-multi'),('root','cms/admin-cms-content-element__3/change-trees-multi'),('root','cms/admin-cms-content-element__3/create'),('cms/admin-cms-content-element__3/delete/own','cms/admin-cms-content-element__3/delete'),('root','cms/admin-cms-content-element__3/delete'),('root','cms/admin-cms-content-element__3/delete-multi'),('root','cms/admin-cms-content-element__3/delete/own'),('root','cms/admin-cms-content-element__3/inActivate-multi'),('root','cms/admin-cms-content-element__3/index'),('root','cms/admin-cms-content-element__3/rp'),('cms/admin-cms-content-element__3/update/own','cms/admin-cms-content-element__3/update'),('root','cms/admin-cms-content-element__3/update'),('root','cms/admin-cms-content-element__3/update/own'),('root','cms/admin-cms-content-element/activate-multi'),('root','cms/admin-cms-content-element/change-tree-multi'),('root','cms/admin-cms-content-element/change-trees-multi'),('root','cms/admin-cms-content-element/create'),('cms/admin-cms-content-element/delete/own','cms/admin-cms-content-element/delete'),('root','cms/admin-cms-content-element/delete'),('root','cms/admin-cms-content-element/delete-multi'),('root','cms/admin-cms-content-element/delete/own'),('root','cms/admin-cms-content-element/inActivate-multi'),('root','cms/admin-cms-content-element/index'),('root','cms/admin-cms-content-element/rp'),('cms/admin-cms-content-element/update/own','cms/admin-cms-content-element/update'),('root','cms/admin-cms-content-element/update'),('root','cms/admin-cms-content-element/update/own'),('root','cms/admin-cms-content-property'),('root','cms/admin-cms-content-property-enum'),('root','cms/admin-cms-content-property-enum/create'),('cms/admin-cms-content-property-enum/delete/own','cms/admin-cms-content-property-enum/delete'),('root','cms/admin-cms-content-property-enum/delete'),('root','cms/admin-cms-content-property-enum/delete-multi'),('root','cms/admin-cms-content-property-enum/delete/own'),('root','cms/admin-cms-content-property-enum/index'),('cms/admin-cms-content-property-enum/update/own','cms/admin-cms-content-property-enum/update'),('root','cms/admin-cms-content-property-enum/update'),('root','cms/admin-cms-content-property-enum/update/own'),('root','cms/admin-cms-content-property/create'),('cms/admin-cms-content-property/delete/own','cms/admin-cms-content-property/delete'),('root','cms/admin-cms-content-property/delete'),('root','cms/admin-cms-content-property/delete-multi'),('root','cms/admin-cms-content-property/delete/own'),('root','cms/admin-cms-content-property/index'),('cms/admin-cms-content-property/update/own','cms/admin-cms-content-property/update'),('root','cms/admin-cms-content-property/update'),('root','cms/admin-cms-content-property/update/own'),('root','cms/admin-cms-content-type'),('root','cms/admin-cms-content-type/create'),('cms/admin-cms-content-type/delete/own','cms/admin-cms-content-type/delete'),('root','cms/admin-cms-content-type/delete'),('root','cms/admin-cms-content-type/delete-multi'),('root','cms/admin-cms-content-type/delete/own'),('root','cms/admin-cms-content-type/index'),('cms/admin-cms-content-type/update/own','cms/admin-cms-content-type/update'),('root','cms/admin-cms-content-type/update'),('root','cms/admin-cms-content-type/update/own'),('root','cms/admin-cms-content/create'),('cms/admin-cms-content/delete/own','cms/admin-cms-content/delete'),('root','cms/admin-cms-content/delete'),('root','cms/admin-cms-content/delete-multi'),('root','cms/admin-cms-content/delete/own'),('root','cms/admin-cms-content/index'),('cms/admin-cms-content/update/own','cms/admin-cms-content/update'),('root','cms/admin-cms-content/update'),('root','cms/admin-cms-content/update/own'),('root','cms/admin-cms-lang'),('root','cms/admin-cms-lang/activate-multi'),('root','cms/admin-cms-lang/create'),('cms/admin-cms-lang/delete/own','cms/admin-cms-lang/delete'),('root','cms/admin-cms-lang/delete'),('root','cms/admin-cms-lang/delete-multi'),('root','cms/admin-cms-lang/delete/own'),('root','cms/admin-cms-lang/inActivate-multi'),('root','cms/admin-cms-lang/index'),('cms/admin-cms-lang/update/own','cms/admin-cms-lang/update'),('root','cms/admin-cms-lang/update'),('root','cms/admin-cms-lang/update/own'),('root','cms/admin-cms-site'),('root','cms/admin-cms-site/activate-multi'),('root','cms/admin-cms-site/create'),('root','cms/admin-cms-site/def-multi'),('cms/admin-cms-site/delete/own','cms/admin-cms-site/delete'),('root','cms/admin-cms-site/delete'),('root','cms/admin-cms-site/delete-multi'),('root','cms/admin-cms-site/delete/own'),('root','cms/admin-cms-site/inActivate-multi'),('root','cms/admin-cms-site/index'),('cms/admin-cms-site/update/own','cms/admin-cms-site/update'),('root','cms/admin-cms-site/update'),('root','cms/admin-cms-site/update/own'),('root','cms/admin-cms-tree-type'),('root','cms/admin-cms-tree-type-property'),('root','cms/admin-cms-tree-type-property-enum'),('root','cms/admin-cms-tree-type-property-enum/create'),('cms/admin-cms-tree-type-property-enum/delete/own','cms/admin-cms-tree-type-property-enum/delete'),('root','cms/admin-cms-tree-type-property-enum/delete'),('root','cms/admin-cms-tree-type-property-enum/delete-multi'),('root','cms/admin-cms-tree-type-property-enum/delete/own'),('root','cms/admin-cms-tree-type-property-enum/index'),('cms/admin-cms-tree-type-property-enum/update/own','cms/admin-cms-tree-type-property-enum/update'),('root','cms/admin-cms-tree-type-property-enum/update'),('root','cms/admin-cms-tree-type-property-enum/update/own'),('root','cms/admin-cms-tree-type-property/create'),('cms/admin-cms-tree-type-property/delete/own','cms/admin-cms-tree-type-property/delete'),('root','cms/admin-cms-tree-type-property/delete'),('root','cms/admin-cms-tree-type-property/delete-multi'),('root','cms/admin-cms-tree-type-property/delete/own'),('root','cms/admin-cms-tree-type-property/index'),('cms/admin-cms-tree-type-property/update/own','cms/admin-cms-tree-type-property/update'),('root','cms/admin-cms-tree-type-property/update'),('root','cms/admin-cms-tree-type-property/update/own'),('root','cms/admin-cms-tree-type/activate-multi'),('root','cms/admin-cms-tree-type/create'),('cms/admin-cms-tree-type/delete/own','cms/admin-cms-tree-type/delete'),('root','cms/admin-cms-tree-type/delete'),('root','cms/admin-cms-tree-type/delete-multi'),('root','cms/admin-cms-tree-type/delete/own'),('root','cms/admin-cms-tree-type/inActivate-multi'),('root','cms/admin-cms-tree-type/index'),('cms/admin-cms-tree-type/update/own','cms/admin-cms-tree-type/update'),('root','cms/admin-cms-tree-type/update'),('root','cms/admin-cms-tree-type/update/own'),('root','cms/admin-cms-user-universal-property'),('root','cms/admin-cms-user-universal-property/create'),('cms/admin-cms-user-universal-property/delete/own','cms/admin-cms-user-universal-property/delete'),('root','cms/admin-cms-user-universal-property/delete'),('root','cms/admin-cms-user-universal-property/delete-multi'),('root','cms/admin-cms-user-universal-property/delete/own'),('root','cms/admin-cms-user-universal-property/index'),('cms/admin-cms-user-universal-property/update/own','cms/admin-cms-user-universal-property/update'),('root','cms/admin-cms-user-universal-property/update'),('root','cms/admin-cms-user-universal-property/update/own'),('root','cms/admin-component-settings'),('root','cms/admin-file-manager'),('root','cms/admin-info'),('root','cms/admin-info/index'),('root','cms/admin-marketplace'),('root','cms/admin-settings'),('root','cms/admin-settings/index'),('root','cms/admin-storage'),('root','cms/admin-storage-files'),('root','cms/admin-storage-files/create'),('cms/admin-storage-files/delete/own','cms/admin-storage-files/delete'),('root','cms/admin-storage-files/delete'),('root','cms/admin-storage-files/delete-multi'),('cms/admin-storage-files/delete-tmp-dir/own','cms/admin-storage-files/delete-tmp-dir'),('root','cms/admin-storage-files/delete-tmp-dir'),('root','cms/admin-storage-files/delete-tmp-dir/own'),('root','cms/admin-storage-files/delete/own'),('cms/admin-storage-files/download/own','cms/admin-storage-files/download'),('root','cms/admin-storage-files/download'),('root','cms/admin-storage-files/download/own'),('root','cms/admin-storage-files/index'),('cms/admin-storage-files/update/own','cms/admin-storage-files/update'),('root','cms/admin-storage-files/update'),('root','cms/admin-storage-files/update/own'),('root','cms/admin-storage/index'),('root','cms/admin-tree'),('root','cms/admin-tree-menu'),('cms/admin-tree/delete/own','cms/admin-tree/delete'),('root','cms/admin-tree/delete'),('root','cms/admin-tree/delete-multi'),('root','cms/admin-tree/delete/own'),('root','cms/admin-tree/index'),('root','cms/admin-tree/list'),('cms/admin-tree/update/own','cms/admin-tree/update'),('root','cms/admin-tree/update'),('root','cms/admin-tree/update/own'),('root','cms/admin-universal-component-settings'),('root','cms/admin-user'),('root','cms/admin-user-email'),('root','cms/admin-user-email/create'),('root','cms/admin-user-email/delete'),('root','cms/admin-user-email/delete-multi'),('root','cms/admin-user-email/index'),('root','cms/admin-user-email/update'),('root','cms/admin-user-phone'),('root','cms/admin-user-phone/create'),('root','cms/admin-user-phone/delete'),('root','cms/admin-user-phone/delete-multi'),('root','cms/admin-user-phone/index'),('root','cms/admin-user-phone/update'),('root','cms/admin-user/activate-multi'),('root','cms/admin-user/create'),('cms/admin-user/delete/own','cms/admin-user/delete'),('root','cms/admin-user/delete'),('root','cms/admin-user/delete-multi'),('root','cms/admin-user/delete/own'),('root','cms/admin-user/inActivate-multi'),('root','cms/admin-user/index'),('cms/admin-user/update/own','cms/admin-user/update'),('root','cms/admin-user/update'),('root','cms/admin-user/update/own'),('root','cmsAgent/admin-cms-agent'),('root','cmsAgent/admin-cms-agent/activate-multi'),('root','cmsAgent/admin-cms-agent/create'),('root','cmsAgent/admin-cms-agent/delete'),('root','cmsAgent/admin-cms-agent/delete-multi'),('root','cmsAgent/admin-cms-agent/inActivate-multi'),('root','cmsAgent/admin-cms-agent/index'),('root','cmsAgent/admin-cms-agent/update'),('root','cmsMarketplace/admin-composer-update'),('root','cmsMarketplace/admin-composer-update/update'),('root','cmsMarketplace/admin-marketplace'),('root','cmsMarketplace/admin-marketplace/catalog'),('root','cmsMarketplace/admin-marketplace/index'),('root','cmsMarketplace/admin-marketplace/install'),('root','cmsMarketplace/admin-marketplace/update'),('root','cmsSearch/admin-search-phrase'),('root','cmsSearch/admin-search-phrase-group'),('root','cmsSearch/admin-search-phrase-group/index'),('root','cmsSearch/admin-search-phrase/create'),('cmsSearch/admin-search-phrase/delete/own','cmsSearch/admin-search-phrase/delete'),('root','cmsSearch/admin-search-phrase/delete'),('root','cmsSearch/admin-search-phrase/delete-multi'),('root','cmsSearch/admin-search-phrase/delete/own'),('root','cmsSearch/admin-search-phrase/index'),('cmsSearch/admin-search-phrase/update/own','cmsSearch/admin-search-phrase/update'),('root','cmsSearch/admin-search-phrase/update'),('root','cmsSearch/admin-search-phrase/update/own'),('root','dbDumper/admin-backup'),('root','dbDumper/admin-settings'),('root','dbDumper/admin-structure'),('root','editor'),('root','form2/admin-form'),('root','form2/admin-form-property'),('root','form2/admin-form-send'),('form2/admin-form-send/delete/own','form2/admin-form-send/delete'),('root','form2/admin-form-send/delete'),('root','form2/admin-form-send/delete-multi'),('root','form2/admin-form-send/delete/own'),('root','form2/admin-form-send/index'),('form2/admin-form-send/view/own','form2/admin-form-send/view'),('root','form2/admin-form-send/view'),('root','form2/admin-form-send/view/own'),('root','form2/admin-form/create'),('form2/admin-form/delete/own','form2/admin-form/delete'),('root','form2/admin-form/delete'),('root','form2/admin-form/delete-multi'),('root','form2/admin-form/delete/own'),('root','form2/admin-form/index'),('form2/admin-form/update/own','form2/admin-form/update'),('root','form2/admin-form/update'),('root','form2/admin-form/update/own'),('form2/admin-form/view/own','form2/admin-form/view'),('root','form2/admin-form/view'),('root','form2/admin-form/view/own'),('root','guest'),('root','logDbTarget/admin-log-db-target'),('root','logDbTarget/admin-log-db-target/delete'),('root','logDbTarget/admin-log-db-target/delete-multi'),('root','logDbTarget/admin-log-db-target/index'),('root','logDbTarget/admin-log-db-target/update'),('root','mailer/admin-test'),('root','manager'),('root','rbac/admin-permission'),('root','rbac/admin-permission/create'),('root','rbac/admin-permission/delete'),('root','rbac/admin-permission/delete-multi'),('root','rbac/admin-permission/index'),('root','rbac/admin-permission/update'),('root','rbac/admin-permission/update-data'),('root','rbac/admin-permission/view'),('root','rbac/admin-role'),('root','rbac/admin-role/create'),('root','rbac/admin-role/delete'),('root','rbac/admin-role/delete-multi'),('root','rbac/admin-role/index'),('root','rbac/admin-role/update'),('root','rbac/admin-role/view'),('root','reviews2.add.review'),('root','reviews2/admin-message'),('root','sshConsole/admin-ssh'),('root','user');
CREATE TABLE "auth_rule" (
    "name" varchar(128) NOT NULL,
    "data" text ,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    PRIMARY KEY ("name")
);

INSERT INTO "auth_rule" VALUES ('isAuthor','O:26:"skeeks\cms\rbac\AuthorRule":3:{s:4:"name";s:8:"isAuthor";s:9:"createdAt";i:1439037301;s:9:"updatedAt";i:1439037301;}',1439037301,1439037301);
CREATE TABLE "cms_component_settings" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "component" varchar(510) DEFAULT NULL,
    "value" text ,
    "user_id" integer DEFAULT NULL,
    "namespace" varchar(100) DEFAULT NULL,
    "cms_site_id" integer DEFAULT NULL,
    PRIMARY KEY ("id")
);

INSERT INTO "cms_component_settings" VALUES (1,1,1,1439039222,1439039222,'skeeks\\cms\\cmsWidgets\\treeMenu\\TreeMenuCmsWidget','{\"treePid\":\"4\",\"active\":\"Y\",\"level\":\"\",\"label\":\"\",\"site_codes\":[],\"orderBy\":\"priority\",\"order\":\"3\",\"enabledCurrentSite\":\"Y\",\"enabledRunCache\":\"N\",\"runCacheDuration\":\"0\",\"activeQuery\":null,\"text\":\"\",\"viewFile\":\"@template/widgets/TreeMenuCmsWidget/sub-catalog\",\"defaultAttributes\":{\"treePid\":null,\"active\":\"Y\",\"level\":null,\"label\":null,\"site_codes\":[],\"orderBy\":\"priority\",\"order\":3,\"enabledCurrentSite\":\"Y\",\"enabledRunCache\":\"Y\",\"runCacheDuration\":0,\"activeQuery\":null,\"text\":\"\",\"viewFile\":\"default\",\"defaultAttributes\":[],\"namespace\":\"TreeMenuCmsWidget-sub-catalog-main\"},\"namespace\":\"TreeMenuCmsWidget-sub-catalog-main\"}',NULL,'TreeMenuCmsWidget-sub-catalog-main',NULL),(4,1,1,1443696591,1443697282,'skeeks\\cms\\cmsWidgets\\contentElements\\ContentElementsCmsWidget','{\"enabledPaging\":\"Y\",\"enabledPjaxPagination\":\"Y\",\"pageSize\":\"10\",\"pageSizeLimitMin\":\"1\",\"pageSizeLimitMax\":\"50\",\"pageParamName\":\"page\",\"orderBy\":\"published_at\",\"order\":\"3\",\"label\":\"\",\"enabledSearchParams\":\"N\",\"enabledCurrentTree\":\"N\",\"enabledCurrentTreeChild\":\"N\",\"enabledCurrentTreeChildAll\":\"N\",\"tree_ids\":\"\",\"limit\":\"0\",\"active\":\"Y\",\"createdBy\":[],\"content_ids\":[\"2\"],\"enabledActiveTime\":\"Y\",\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"dataProvider\":null,\"search\":null,\"viewFile\":\"@app/views/widgets/ContentElementsCmsWidget/slides\",\"defaultAttributes\":{\"enabledPaging\":\"Y\",\"enabledPjaxPagination\":\"Y\",\"pageSize\":10,\"pageSizeLimitMin\":1,\"pageSizeLimitMax\":50,\"pageParamName\":\"page\",\"orderBy\":\"published_at\",\"order\":3,\"label\":null,\"enabledSearchParams\":\"Y\",\"enabledCurrentTree\":\"Y\",\"enabledCurrentTreeChild\":\"Y\",\"enabledCurrentTreeChildAll\":\"Y\",\"tree_ids\":[],\"limit\":0,\"active\":\"\",\"createdBy\":[],\"content_ids\":[],\"enabledActiveTime\":\"Y\",\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"dataProvider\":null,\"search\":null,\"viewFile\":\"default\",\"defaultAttributes\":[],\"namespace\":\"ContentElementsCmsWidget-slides\"},\"namespace\":\"ContentElementsCmsWidget-slides\"}',NULL,'ContentElementsCmsWidget-slides',NULL),(5,1,1,1443698727,1455882445,'skeeks\\cms\\modules\\admin\\widgets\\gridView\\GridViewSettings','{\"enabledPjaxPagination\":\"Y\",\"pageSize\":\"10\",\"pageParamName\":\"page\",\"visibleColumns\":[\"5aa37184\",\"1cce4459\",\"6738afae\",\"e04112b1\",\"6f4cf3ff\",\"9d505500\",\"ab768f41\",\"c68056fd\",\"4369038e\",\"c79b45e9\",\"b77921d4\",\"7276e0e4\",\"4663e865\",\"2564e302\"],\"grid\":null,\"orderBy\":\"priority\",\"order\":\"4\",\"defaultAttributes\":{\"enabledPjaxPagination\":\"Y\",\"pageSize\":10,\"pageParamName\":\"page\",\"visibleColumns\":[],\"grid\":null,\"orderBy\":\"id\",\"order\":3,\"defaultAttributes\":[],\"namespace\":\"cms/admin-cms-content-element/index2\"},\"namespace\":\"cms/admin-cms-content-element/index2\"}',NULL,'cms/admin-cms-content-element/index2',NULL),(6,1,1,1443699012,1445808988,'skeeks\\cms\\cmsWidgets\\contentElements\\ContentElementsCmsWidget','{\"enabledPaging\":\"Y\",\"enabledPjaxPagination\":\"Y\",\"pageSize\":\"10\",\"pageSizeLimitMin\":\"1\",\"pageSizeLimitMax\":\"50\",\"pageParamName\":\"page\",\"orderBy\":\"published_at\",\"order\":\"3\",\"label\":\"Услуги\",\"enabledSearchParams\":\"Y\",\"enabledCurrentTree\":\"N\",\"enabledCurrentTreeChild\":\"Y\",\"enabledCurrentTreeChildAll\":\"Y\",\"tree_ids\":\"\",\"limit\":\"4\",\"active\":\"\",\"createdBy\":[],\"content_ids\":[\"3\"],\"enabledActiveTime\":\"Y\",\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"dataProvider\":null,\"search\":null,\"viewFile\":\"@template/widgets/ContentElementsCmsWidget/articles-footer\",\"defaultAttributes\":{\"enabledPaging\":\"Y\",\"enabledPjaxPagination\":\"Y\",\"pageSize\":10,\"pageSizeLimitMin\":1,\"pageSizeLimitMax\":50,\"pageParamName\":\"page\",\"orderBy\":\"published_at\",\"order\":3,\"label\":null,\"enabledSearchParams\":\"Y\",\"enabledCurrentTree\":\"Y\",\"enabledCurrentTreeChild\":\"Y\",\"enabledCurrentTreeChildAll\":\"Y\",\"tree_ids\":[],\"limit\":0,\"active\":\"\",\"createdBy\":[],\"content_ids\":[],\"enabledActiveTime\":\"Y\",\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"dataProvider\":null,\"search\":null,\"viewFile\":\"default\",\"defaultAttributes\":[],\"namespace\":\"ContentElementsCmsWidget-footer\"},\"namespace\":\"ContentElementsCmsWidget-footer\"}',NULL,'ContentElementsCmsWidget-footer',NULL),(7,1,1,1445809130,1445809161,'skeeks\\cms\\cmsWidgets\\contentElements\\ContentElementsCmsWidget','{\"enabledPaging\":\"N\",\"enabledPjaxPagination\":\"N\",\"pageSize\":\"10\",\"pageSizeLimitMin\":\"1\",\"pageSizeLimitMax\":\"50\",\"pageParamName\":\"page\",\"orderBy\":\"published_at\",\"order\":\"3\",\"label\":\"Услуги\",\"enabledSearchParams\":\"N\",\"enabledCurrentTree\":\"N\",\"enabledCurrentTreeChild\":\"N\",\"enabledCurrentTreeChildAll\":\"N\",\"tree_ids\":\"\",\"limit\":\"4\",\"active\":\"Y\",\"createdBy\":[],\"content_ids\":[\"3\"],\"enabledActiveTime\":\"Y\",\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"dataProvider\":null,\"search\":null,\"viewFile\":\"@app/views/widgets/ContentElementsCmsWidget/publications\",\"defaultAttributes\":{\"enabledPaging\":\"Y\",\"enabledPjaxPagination\":\"Y\",\"pageSize\":10,\"pageSizeLimitMin\":1,\"pageSizeLimitMax\":50,\"pageParamName\":\"page\",\"orderBy\":\"published_at\",\"order\":3,\"label\":null,\"enabledSearchParams\":\"Y\",\"enabledCurrentTree\":\"Y\",\"enabledCurrentTreeChild\":\"Y\",\"enabledCurrentTreeChildAll\":\"Y\",\"tree_ids\":[],\"limit\":0,\"active\":\"\",\"createdBy\":[],\"content_ids\":[],\"enabledActiveTime\":\"Y\",\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"dataProvider\":null,\"search\":null,\"viewFile\":\"default\",\"defaultAttributes\":[],\"namespace\":\"ContentElementsCmsWidget-home\"},\"namespace\":\"ContentElementsCmsWidget-home\"}',NULL,'ContentElementsCmsWidget-home',NULL),(10,1,1,1446145239,1455884222,'skeeks\\cms\\modules\\admin\\components\\settings\\AdminSettings','{\"languageCode\":\"ru\"}',1,NULL,NULL),(11,1,1,1446246127,1446246542,'skeeks\\cms\\components\\Cms','{\"adminEmail\":\"admin@skeeks.com\",\"notifyAdminEmailsHidden\":\"\",\"notifyAdminEmails\":\"\",\"appName\":\"SkeekS CMS\",\"noImageUrl\":\"http://vk.com/images/deactivated_100.gif\",\"userPropertyTypes\":[],\"registerRoles\":[\"user\"],\"languageCode\":\"ru\",\"passwordResetTokenExpire\":\"3600\",\"enabledHitAgents\":\"Y\",\"hitAgentsInterval\":\"60\",\"enabledHttpAuth\":\"N\",\"enabledHttpAuthAdmin\":\"N\",\"httpAuthLogin\":\"\",\"httpAuthPassword\":\"\",\"debugEnabled\":\"N\",\"debugAllowedIPs\":\"*\",\"giiEnabled\":\"N\",\"giiAllowedIPs\":\"*\",\"licenseKey\":\"\",\"templatesDefault\":{\"default\":{\"name\":\"Базовый шаблон (по умолчанию)\",\"pathMap\":{\"@app/views\":[\"@app/templates/default\"]}}},\"templates\":{\"default\":{\"name\":\"Базовый шаблон (по умолчанию)\",\"pathMap\":{\"@app/views\":[\"@app/templates/default\"]}}},\"template\":\"default\",\"emailTemplatesDefault\":{\"default\":{\"name\":\"Базовый шаблон (по умолчанию)\",\"pathMap\":{\"@app/mail\":[\"@app/mail\",\"@skeeks/cms/mail\"]}}},\"emailTemplates\":{\"default\":{\"name\":\"Базовый шаблон (по умолчанию)\",\"pathMap\":{\"@app/mail\":[\"@app/mail\",\"@skeeks/cms/mail\"]}}},\"emailTemplate\":\"default\",\"defaultAttributes\":{\"adminEmail\":\"admin@skeeks.com\",\"notifyAdminEmailsHidden\":\"\",\"notifyAdminEmails\":\"\",\"appName\":null,\"noImageUrl\":\"http://vk.com/images/deactivated_100.gif\",\"userPropertyTypes\":[],\"registerRoles\":[\"user\"],\"languageCode\":\"ru\",\"passwordResetTokenExpire\":3600,\"enabledHitAgents\":\"Y\",\"hitAgentsInterval\":60,\"enabledHttpAuth\":\"N\",\"enabledHttpAuthAdmin\":\"N\",\"httpAuthLogin\":\"\",\"httpAuthPassword\":\"\",\"debugEnabled\":\"N\",\"debugAllowedIPs\":\"*\",\"giiEnabled\":\"N\",\"giiAllowedIPs\":\"*\",\"licenseKey\":\"demo\",\"templatesDefault\":{\"default\":{\"name\":\"Базовый шаблон (по умолчанию)\",\"pathMap\":{\"@app/views\":[\"@app/templates/default\"]}}},\"templates\":[],\"template\":\"default\",\"emailTemplatesDefault\":{\"default\":{\"name\":\"Базовый шаблон (по умолчанию)\",\"pathMap\":{\"@app/mail\":[\"@app/mail\",\"@skeeks/cms/mail\"]}}},\"emailTemplates\":[],\"emailTemplate\":\"default\",\"defaultAttributes\":[],\"namespace\":null},\"namespace\":null}',NULL,NULL,NULL),(12,1,1,1446291154,1510344967,'skeeks\\cms\\components\\CmsToolbar','{\"allowedIPs\":[\"*\"],\"infoblocks\":[],\"editWidgets\":\"N\",\"editViewFiles\":\"N\",\"isOpen\":\"N\",\"enabled\":1,\"enableFancyboxWindow\":0,\"infoblockEditBorderColor\":\"red\",\"viewFiles\":[],\"inited\":false,\"editUrl\":\"\"}',1,NULL,NULL),(13,1,1,1455882391,1455882420,'skeeks\\cms\\cmsWidgets\\contentElements\\ContentElementsCmsWidget','{\"enabledPaging\":\"Y\",\"enabledPjaxPagination\":\"Y\",\"pageSize\":\"10\",\"pageSizeLimitMin\":\"1\",\"pageSizeLimitMax\":\"50\",\"pageParamName\":\"page\",\"orderBy\":\"priority\",\"order\":\"4\",\"label\":\"\",\"enabledSearchParams\":\"N\",\"enabledCurrentTree\":\"N\",\"enabledCurrentTreeChild\":\"N\",\"enabledCurrentTreeChildAll\":\"N\",\"tree_ids\":\"\",\"limit\":\"0\",\"active\":\"Y\",\"createdBy\":[],\"content_ids\":[\"2\"],\"enabledActiveTime\":\"Y\",\"enabledRunCache\":\"N\",\"runCacheDuration\":\"0\",\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"with\":[\"image\",\"cmsTree\"],\"dataProvider\":null,\"search\":null,\"viewFile\":\"@app/views/widgets/ContentElementsCmsWidget/slides\",\"defaultAttributes\":{\"enabledPaging\":\"Y\",\"enabledPjaxPagination\":\"Y\",\"pageSize\":10,\"pageSizeLimitMin\":1,\"pageSizeLimitMax\":50,\"pageParamName\":\"page\",\"orderBy\":\"published_at\",\"order\":3,\"label\":null,\"enabledSearchParams\":\"Y\",\"enabledCurrentTree\":\"Y\",\"enabledCurrentTreeChild\":\"Y\",\"enabledCurrentTreeChildAll\":\"Y\",\"tree_ids\":[],\"limit\":0,\"active\":\"\",\"createdBy\":[],\"content_ids\":[],\"enabledActiveTime\":\"Y\",\"enabledRunCache\":\"N\",\"runCacheDuration\":0,\"activeQueryCallback\":null,\"dataProviderCallback\":null,\"with\":[\"image\",\"cmsTree\"],\"dataProvider\":null,\"search\":null,\"viewFile\":\"default\",\"defaultAttributes\":[],\"namespace\":\"ContentElementsCmsWidget-home-slides\"},\"namespace\":\"ContentElementsCmsWidget-home-slides\"}',NULL,'ContentElementsCmsWidget-home-slides',NULL),(14,1,1,1510952550,1510952550,'skeeks\\cms\\toolbar\\CmsToolbar','{\"allowedIPs\":[\"*\"],\"infoblocks\":[],\"editWidgets\":\"N\",\"editViewFiles\":\"N\",\"isOpen\":\"Y\",\"enabled\":1,\"enableFancyboxWindow\":0,\"infoblockEditBorderColor\":\"red\",\"viewFiles\":[],\"inited\":false,\"editUrl\":\"\"}',1,NULL,NULL);
CREATE TABLE "cms_content" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "name" varchar(510) NOT NULL,
    "code" varchar(100) NOT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "priority" integer NOT NULL DEFAULT '500',
    "description" text ,
    "index_for_search" char(1) NOT NULL DEFAULT 'Y',
    "name_meny" varchar(200) DEFAULT NULL,
    "name_one" varchar(200) DEFAULT NULL,
    "tree_chooser" char(1) DEFAULT NULL,
    "list_mode" char(1) DEFAULT NULL,
    "content_type" varchar(64) NOT NULL,
    "default_tree_id" integer DEFAULT NULL,
    "is_allow_change_tree" varchar(2) NOT NULL DEFAULT 'Y',
    "root_tree_id" integer DEFAULT NULL,
    "viewFile" varchar(510) DEFAULT NULL,
    "meta_title_template" varchar(1000) DEFAULT NULL,
    "meta_description_template" text ,
    "meta_keywords_template" text ,
    "access_check_element" varchar(2) NOT NULL DEFAULT 'N',
    "parent_content_id" integer DEFAULT NULL,
    "visible" varchar(2) NOT NULL DEFAULT 'Y',
    "parent_content_on_delete" varchar(20) NOT NULL DEFAULT 'CASCADE',
    "parent_content_is_required" varchar(2) NOT NULL DEFAULT 'Y',
    PRIMARY KEY ("id"),
    UNIQUE ("code")
);

INSERT INTO "cms_content" VALUES (1,1,1,1443696561,1443696561,'Публикации','articles','Y',500,NULL,'','Элементы','Элемент',NULL,NULL,'publication',NULL,'Y',NULL,NULL,NULL,NULL,NULL,'N',NULL,'Y','CASCADE','Y'),(2,1,1,1443696578,1443696578,'Слайды','slide','Y',500,NULL,'','Элементы','Элемент',NULL,NULL,'other',NULL,'Y',NULL,NULL,NULL,NULL,NULL,'N',NULL,'Y','CASCADE','Y'),(3,1,1,1445806941,1445806941,'Услуги','services','Y',500,NULL,'','Элементы','Элемент',NULL,NULL,'clinic',6,'N',6,'default',NULL,NULL,NULL,'N',NULL,'Y','CASCADE','Y');
CREATE TABLE "cms_content_element" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "published_at" integer DEFAULT NULL,
    "published_to" integer DEFAULT NULL,
    "priority" integer NOT NULL DEFAULT '500',
    "active" char(1) NOT NULL DEFAULT 'Y',
    "name" varchar(510) NOT NULL,
    "image_id" integer DEFAULT NULL,
    "image_full_id" integer DEFAULT NULL,
    "code" varchar(510) DEFAULT NULL,
    "description_short" text ,
    "description_full" text ,
    "content_id" integer DEFAULT NULL,
    "tree_id" integer DEFAULT NULL,
    "show_counter" integer DEFAULT NULL,
    "show_counter_start" integer DEFAULT NULL,
    "meta_title" varchar(1000) NOT NULL,
    "meta_description" text ,
    "meta_keywords" text ,
    "description_short_type" varchar(20) NOT NULL DEFAULT 'text',
    "description_full_type" varchar(20) NOT NULL DEFAULT 'text',
    "parent_content_element_id" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("content_id","code"),
    UNIQUE ("tree_id","code")
);

INSERT INTO "cms_content_element" VALUES (1,1,1,1443697232,1455882577,1443697232,NULL,500,'Y','Слайд 1',7,NULL,'slayd-1','','',2,NULL,NULL,NULL,'','','','text','text',NULL),(2,1,1,1443697447,1455882577,1443697447,NULL,500,'Y','Слайд 2',6,NULL,'slayd-2','','',2,NULL,NULL,NULL,'','','','text','text',NULL),(3,1,1,1443698322,1455882578,1443698322,NULL,500,'Y','Слайд 3',8,NULL,'slayd-3','','',2,NULL,NULL,NULL,'','','','text','text',NULL),(4,1,1,1443698355,1455882578,1443698355,NULL,500,'Y','Слайд 4',9,NULL,'slayd-4','','',2,NULL,NULL,NULL,'','','','text','text',NULL),(5,1,1,1443698742,1455882578,1443698742,NULL,500,'Y','Слайд 5',10,NULL,'slayd-5','','',2,NULL,NULL,NULL,'','','','text','text',NULL),(6,1,1,1443699259,1443700220,1443699259,NULL,500,'Y','Дополнительная упаковка при доставке.',NULL,NULL,'dopolnitelnaya-upakovka-pri-dosta','<p><span style=\"line-height: 20.8px;\">С 1.08.2015 отгрузки через Деловые Линии, содержащие мотор-редукторы и комплектующие,</span><br style=\"line-height: 20.8px;\" />\r\n<span style=\"line-height: 20.8px;\">будут упаковываться в дополнительную деревянную обрешетку, оплата Получателем.</span><br style=\"line-height: 20.8px;\" />\r\n<span style=\"line-height: 20.8px;\">Чтобы отказаться от услуги, нужно заполнить Отказное письмо.</span></p>\r\n','<p>С 1.08.2015 отгрузки через Деловые Линии, содержащие мотор-редукторы и комплектующие,<br />\r\nбудут упаковываться в дополнительную деревянную обрешетку, оплата Получателем.<br />\r\nЧтобы отказаться от услуги, нужно заполнить Отказное письмо.</p>\r\n',1,NULL,NULL,NULL,'','','','editor','editor',NULL),(7,1,1,1443700182,1443700182,1443700182,NULL,500,'Y','Еще одна тестовая новость компании',NULL,NULL,'esche-odna-testovaya-novost-kompanii','<p><span style=\"line-height: 20.8px;\">С 1.08.2015 отгрузки через Деловые Линии, содержащие мотор-редукторы и комплектующие,</span><br style=\"line-height: 20.8px;\" />\r\n<span style=\"line-height: 20.8px;\">будут упаковываться в дополнительную деревянную обрешетку, оплата Получателем.</span><br style=\"line-height: 20.8px;\" />\r\n<span style=\"line-height: 20.8px;\">Чтобы отказаться от услуги, нужно заполнить Отказное письмо.</span></p>\r\n','<p><span style=\"line-height: 20.8px;\">С 1.08.2015 отгрузки через Деловые Линии, содержащие мотор-редукторы и комплектующие,</span><br style=\"line-height: 20.8px;\" />\r\n<span style=\"line-height: 20.8px;\">будут упаковываться в дополнительную деревянную обрешетку, оплата Получателем.</span><br style=\"line-height: 20.8px;\" />\r\n<span style=\"line-height: 20.8px;\">Чтобы отказаться от услуги, нужно заполнить Отказное письмо.</span></p>\r\n',1,NULL,NULL,NULL,'','','','editor','editor',NULL),(8,1,1,1445806961,1445807895,1445806961,NULL,500,'Y','Терапия',16,NULL,'terapiya','','<h2>Основными направлениями Терапевтической стоматологии в нашей клинике является:</h2>\r\n\r\n<ul>\r\n	<li>Лечение кариозных полостей</li>\r\n	<li>Лечение пульпита и переодантита</li>\r\n	<li>Лечение кисты</li>\r\n	<li>Удаление нерва</li>\r\n	<li>Пломбирование каналов</li>\r\n	<li>Гигиеническая чистка полости рта</li>\r\n</ul>\r\n\r\n<p>Поговорим о нескольких из них:</p>\r\n\r\n<h3>Методы и технологии применяемые нами:</h3>\r\n\r\n<ul>\r\n	<li>\r\n	<p>Наша клиника использует качественные пломбировочные материалы, биологически совместимые с тканями зуба, имеющие&nbsp;особо сильную прочность и имеющие&nbsp;международные сертификаты качества;</p>\r\n	</li>\r\n	<li>\r\n	<p>перед лечением мы проводим диагностику полости рта с использованием современных средств, позволяющих выявить кариес даже в труднодоступных местах;</p>\r\n	</li>\r\n	<li>\r\n	<p>мы используем &laquo;щадящие&raquo; технологи, позволяющие вылечить зуб с максимально возможным сохранением собственных тканей зуба.</p>\r\n	</li>\r\n	<li>\r\n	<p>наши врачи&nbsp;пломбируют зуб&nbsp;так,что следов лечения не заметно,выполняя&nbsp;художественное восстановление анатомической формы и цвета зуба.</p>\r\n	</li>\r\n</ul>\r\n\r\n<h2>Лечение кариозной полости:</h2>\r\n\r\n<p><iframe allowfullscreen=\"\" frameborder=\"0\" height=\"360\" src=\"//www.youtube.com/embed/OESvR1s-zMg?rel=0\" width=\"640\"></iframe></p>\r\n\r\n<h3>Пломбирование каналов зуба:</h3>\r\n\r\n<p>Способ&nbsp;пломбирования корневых каналов</p>\r\n\r\n<p>Метод &laquo;Латеральной конденсации гуттаперчи&raquo;: при пломбировании каналов этим способом, используют несколько гуттаперчевых штифтов различной конусности и определенной(заданной) длины. Гуттаперчевые штифты в сочетании со специальной пастой, вводят в, предварительно подготовленный канал, до апикального отверстия, далее вводят инструмент, напоминающий иглу, которым прижимают штифт к одной из стенок корневого канала. Инструмент вынимают, а в освободившееся место вводят следующий штифт, более меньшего диаметра и на меньшую глубину. Далее, инструментом прижимают к стенке этот и по очереди все последующие штифты. Таким образом, посредствам поочередного введения гуттаперчевых штифтов и конденсации их, достигается герметичное заполнение корневого канала.</p>\r\n\r\n<p><iframe allowfullscreen=\"\" frameborder=\"0\" height=\"360\" src=\"//www.youtube.com/embed/UxYNVkRjUHo?rel=0\" width=\"640\"></iframe></p>\r\n\r\n<h3>Гигиеническая чистка зубов:</h3>\r\n\r\n<p>Гигиеническая чистка зубов важна каждому человеку,вне зависимости реагируют зубы на раздражители или нет. Её главная задача удалить твердый зубной налет ( зубной камень ) и удаление мягкого зубного налета,и вот почему:</p>\r\n\r\n<p>Твердый зубной налет скапливается между зубом и десной, в итоге десна не плотно прилегает к зубу и создается карман,в который вместе с едой попадают бактерии. Десны начинают кровоточить,если долго не принемать действий, зубной камень будет скапливаться все глубже под десной что может привести к воспалению десен и расшатыванию зуба.</p>\r\n\r\n<p>Мягкий зубной налет опасен тем,что любой налет это бактерии и продукты их разложений, которые портят ткани зуба, именно из-за налета, образуется кариес.</p>\r\n\r\n<p>Гигиеническая чистка удаляет абсолютно всю негативную микрофлору полости рта,после удаления зубного камня, десенка &quot; вздыхает&quot; с облегчением, принимает здоровый цвет,&nbsp;перестает кровоточить и быстро востанавливается,начиная крепче держать Ваши зубки.</p>\r\n\r\n<p>&nbsp;</p>\r\n',3,6,NULL,NULL,'','','','text','editor',NULL),(9,1,1,1445807959,1445810203,1445807959,NULL,500,'Y','Протезирование зубов',17,NULL,'protezirovanie-zubov','','<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh4aflk\" id=\"ieh4aflk\">\r\n<p>Стоматология Адриа успешно занимается всеми видами протезирования:</p>\r\n\r\n<ul>\r\n	<li>\r\n	<p>Мостовидные протезы</p>\r\n	</li>\r\n	<li>\r\n	<p>Керамические коронки</p>\r\n	</li>\r\n	<li>\r\n	<p>Металлокерамические коронки</p>\r\n	</li>\r\n	<li>\r\n	<p>Коронки на основе диоксида циркония</p>\r\n	</li>\r\n	<li>\r\n	<p>Виниры</p>\r\n	</li>\r\n	<li>\r\n	<p>Съемные протезы</p>\r\n	</li>\r\n	<li>\r\n	<p>Культевые вкладки</p>\r\n	</li>\r\n	<li>\r\n	<p>Восстановление разрушенного зуба</p>\r\n	</li>\r\n	<li>\r\n	<p>Временные коронки</p>\r\n	</li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp; &nbsp;&nbsp;&nbsp;Поговорим о нескольких из них:</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h3>Мостовидные протезы,что это?</h3>\r\n\r\n<p>Мостовидные протезы &ndash; это универсальные конструкции, которые применяются, даже если в челюсти отсутствует несколько зубов. Показаниями для их использования&nbsp;считается необходимость восстановления 1-2, максимум 3 подряд отсутствующих зубов. В этом случае зубной мост будет надежно зафиксирован и выполнит свои функции в полном объеме.&nbsp;</p>\r\n\r\n<p>Мостовидный протез&nbsp;отличается высокой эстетичностью, прочностью, комфортом и продолжительным сроком службы. В зависимости от используемых материалов и типа конструкции, а также ухода за протезом, зубной мост в среднем прослужит не менее 10 лет.</p>\r\n</div>\r\n\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh62m2d\" id=\"ieh62m2d\">\r\n<p>Керамические коронки, металлокерамические коронки, коронки на основе диоксиа циркония.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Сегодня металлокерамические коронки являются наиболее удачным компромиссом между эстетикой, надежностью и стоимостью.</p>\r\n\r\n<p>Внутренняя часть металлокерамической коронки состоит из сплавов металла . В зависимости от использованных металлов и их сплавов различают металлокерамику на золоте, на кобальто-хромовых сплавах и тому подобное. Благодаря присутствию в сплаве золота удается достичь более естественного оттенка металлокерамических коронок, готовых к установке.&nbsp;Внутри коронки находится литой каркас.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Достоинства и недостатки металлокерамических коронок :</p>\r\n\r\n<ul>\r\n	<li>\r\n	<p>Функциональность. Вы не будете ощущать никакого различия при приеме пищи. Искусственные зубы пережевывают пищу идентично натуральным зубам.</p>\r\n	</li>\r\n	<li>\r\n	<p>Эстетичность. Хороший специалист изготовит металлокерамические коронки, которые на вид совершенно не будут отличаться от настоящих зубов.</p>\r\n	</li>\r\n	<li>\r\n	<p>Долговечность и прочность. Металлокерамика легко выдерживает серьезные нагрузки, поэтому нет никакого риска образования на ваших коронках растрескиваний, сколов или других повреждений. Срок службы металлокерамических коронок &ndash; более 15 лет при соблюдении элементарной гигиены полости рта.</p>\r\n	</li>\r\n	<li>\r\n	<p>Гигиеничность. В отличие от настоящих зубов металлокерамические коронки не подвержены воздействию микроорганизмов и бактерий. Это огромный плюс для пациентов, в жизни которых имеют место заболевания пародонта.</p>\r\n	</li>\r\n	<li>\r\n	<p>Биологическая совместимость. Качественные, правильно изготовленные и хорошо установленные металлокерамические коронки не вызывают в деснах никаких изменений.</p>\r\n	</li>\r\n	<li>\r\n	<p>Защита зуба под коронкой. Правильно изготовленная коронка прилегает к зубу настолько плотно, что идеально защищает его от воздействия агрессивной среды, присутствующей в ротовой полости человека, тем самым предотвращает его дальнейшее разрушение.</p>\r\n	</li>\r\n	<li>\r\n	<p>Стоимость металлокерамической коронки более доступна в сравнении, например, с неметаллическими протезами или&nbsp;имплантами.</p>\r\n	</li>\r\n</ul>\r\n</div>\r\n\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh703ce\" id=\"ieh703ce\">\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh703ce.0\" id=\"ieh703celine\">&nbsp;</div>\r\n</div>\r\n\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh71rpb\" id=\"ieh71rpb\">\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh71rpb.0\" id=\"ieh71rpbvideoFrame\"><iframe allowfullscreen=\"\" data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh71rpb.0.0\" frameborder=\"0\" height=\"100%\" src=\"http://www.youtube.com/embed/uFSb_9kBdW8?wmode=transparent&amp;autoplay=0&amp;theme=dark&amp;controls=1&amp;autohide=0&amp;loop=0&amp;showinfo=0&amp;rel=0&amp;playlist=false&amp;enablejsapi=0\" style=\"margin: 0px; padding: 0px; border-width: 0px; border-style: initial; outline: 0px; vertical-align: baseline; background: transparent;\" width=\"100%\"></iframe></div>\r\n</div>\r\n\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh780br\" id=\"ieh780br\">\r\n<p>Виниры:</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Виниры &ndash; это микропротезы,пластинки, которые позволяют восстановить цвет и форму одного или группы зубов.</p>\r\n\r\n<p>Винировые пластинки устанавливают на переднюю поверхность зуба. Их применяют для реставрации передних зубов, попадающих в линию улыбки.</p>\r\n\r\n<p>Зубы с винирами, ничем не отличаются от настоящих, так как при их изготовлении учитывается цвет зубов пациента.</p>\r\n\r\n<p>Винировые пластинки используются в тех случаях, когда восстановление и отбеливание зубов не дадут желаемого результата. Реставрация зубов винирами позволяет в кратчайшие сроки восстановить зубы, имеющие большие дефекты.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n</div>\r\n\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh7r3d7\" id=\"ieh7r3d7\">\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh7r3d7.0\" id=\"ieh7r3d7videoFrame\"><iframe allowfullscreen=\"\" data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$c1qbb.1.$ieh7r3d7.0.0\" frameborder=\"0\" height=\"100%\" src=\"http://www.youtube.com/embed/Q91izXON3Pc?wmode=transparent&amp;autoplay=0&amp;theme=dark&amp;controls=1&amp;autohide=0&amp;loop=0&amp;showinfo=0&amp;rel=0&amp;playlist=false&amp;enablejsapi=0\" style=\"margin: 0px; padding: 0px; border-width: 0px; border-style: initial; outline: 0px; vertical-align: baseline; background: transparent;\" width=\"100%\"></iframe></div>\r\n</div>\r\n',3,6,NULL,NULL,'','','','text','editor',NULL),(10,1,1,1445807986,1445810334,1445807986,NULL,500,'Y','Исправление прикуса (брекеты)',18,NULL,'ispravlenie-prikusa-breketyi','','<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$cg92.1.$ieh8p00n\" id=\"ieh8p00n\">\r\n<h3 style=\"text-align: center;\"><iframe allowfullscreen=\"\" frameborder=\"0\" height=\"360\" src=\"//www.youtube.com/embed/66iripWSEcM?rel=0\" width=\"640\"></iframe></h3>\r\n\r\n<h3>В каком возрасте возможно проведение ортодонтического лечения?</h3>\r\n\r\n<p>Вопреки распространенному мнению, исправить прикус и положение зубов можно в любом возрасте. При здоровой зубочелюстной системе возраст не является ограничивающим фактором, а в некоторых случаях ортодонтическая подготовка является обязательным этапом общего стоматологического лечения взрослого пациента. Длительность исправления прикуса зависит от скорости перестройки костной ткани, степени нарушения прикуса и в среднем оно занимает от одного года до полутора лет.</p>\r\n\r\n<p>В наше время брекеты наберают всю большую популярность, они компактно и аккуратно выглядят,являются символом ухода за своим здоровьем .</p>\r\n\r\n<p>Наш ортодонт пользуется самой современной брекет системой Damon, которая позволяет сократить срок пользования&nbsp;брекетами, делает их практически невидемыми при общении&nbsp;и удобными.</p>\r\n\r\n<p><strong>Показания к постановке&nbsp;брекетов:</strong></p>\r\n\r\n<p><strong>Функциональные.&nbsp;&nbsp;</strong></p>\r\n\r\n<ul>\r\n	<li>\r\n	<p>Человеческий организм - это единая система. Если в одном из отделов появился сбой, то он негативно влияет на весь организм. Вкраце - при патологическом&nbsp;прикусе, зубы смыкаются неправильно, и не обеспечивают должного&nbsp;размельчения и пережовывания пищи, в итоге страдает весь пищеварительный тракт</p>\r\n	</li>\r\n	<li>\r\n	<p>При патологическом прикусе, нагрузка на зубы распределяется неправильно, в итоге возникают клиновидные дифекты, уходит десна, за счет чего зубы становятся подвижными.</p>\r\n	</li>\r\n	<li>\r\n	<p>При патологическом расположении зубов, нет хорошего межзубного контакта, в итоге еда застревает на контактах и образуется кариес ( чаще наблюдается при скучености )</p>\r\n	</li>\r\n	<li>\r\n	<p>Дифекты речи</p>\r\n	</li>\r\n</ul>\r\n</div>\r\n\r\n<div data-reactid=\".0.$SITE_ROOT.$desktop_siteRoot.$PAGES_CONTAINER.1.1.$SITE_PAGES.$cg92.1.$ieha02or\" id=\"ieha02or\">\r\n<p><strong>Эстетические.</strong></p>\r\n\r\n<p>В наше время красивая улыбка играет далеко не последнюю роль. И если природа вас не наградила красивой улыбкой, то не огорчайтесь, все можно исправить, главное желание и терпение. Исправление положения зубов брекетами это самый гуманный метод придать вам Вашу натуральную и красивую улыбку.&nbsp;Ведь при постановки брекетов, не надо обтачивать абсолютно здоровые зубы,брекеты клеятся&nbsp;&nbsp;к эмали зуба на специальный состав, содержащий фтор. В результате эмаль не только герметично защищена от микроорганизмов&nbsp;, но и укрепляется под постоянным выделением фтора.</p>\r\n</div>\r\n',3,6,NULL,NULL,'','','','text','editor',NULL),(11,1,1,1445808011,1445810091,1445808011,NULL,500,'Y','Отбеливание зубов',19,NULL,'otbelivanie-zubov','','<p>Если Вас не устраивает свой цвет зубов, то вам может помоч профессиональное отбеливание,благодаря которому ваши зубы станут гораздо белее. Эффект от отбеливания держится в среднем до 6 лет ( в зависимости от образа жизни и гигиены), но вся прелесть в том, что после отбеливания, зубы уже никогда не вернуться к своему старому цвету, со временем ( спустя N лет...) зубки потемнеют, но будут гораздо светлее, чем были до отбеливания.</p>\r\n\r\n<p>Перед отбеливанием очень важно заранее провести гигиеническую чистку &ndash; освободить поверхность эмали от зубного камня, налета пищи и пленки бактерий. Данный этап необходим, чтобы отбеливание получилось равномерным и эффективным.</p>\r\n\r\n<p>В начале самой процедуры доктор с помощью тонких латексных пластин и специального состава изолирует от окружающих тканей зубы, подлежащие отбеливанию. Это убережет губы, язык и десны от попадания отбеливающего раствора. Далее на зубы наносится отбеливающий гель, который активируется лазерным лучом. Опытный специалист подбирает необходимую интенсивность и время воздействия лазера в каждом конкретном случае. Как только необходимый результат будет достигнут, лазер направляется на следующий зуб. Под действием лазерного луча активный кислород, содержащийся в геле, высвобождается, проникает в эмаль и нейтрализует темный пигмент. В конце процедуры на зубы наносят фтор-гель, укрепляющий осветленную эмаль. В целом лазерное отбеливание дает возможность осветлить зубы на 8-10 тонов. После отбеливания пациентам назначают &quot; белую диету&quot; на 2 нед, в ходе которой идет закрепление цвета.</p>\r\n\r\n<p><strong>Не вредно ли это ?</strong></p>\r\n\r\n<p>У многих людей существует такой стереотип, что отбеливание вредит здоровым зубам, истончает эмаль и так далее. Безусловно если не иметь базовых профильных знаний и самовольно пытаться отбелить зубы подручными средствами, то шансы не только на белоснежную улыбку,но и на здоровые зубы резко снижаются.&nbsp;</p>\r\n\r\n<p>Не секрет,что все отбеливающие системы содержат в своем составе перекись водорода.Разные системы используют разные концентрации данного ве-ва,но этот элемент присутствует везде без исключений.При нанесении отбеливающего геля на зубы,через дентинные канальца гель проникает внутрь зуба воздействуя на коллаген,который располагается на эмалево-дентинной границе. Зуб насыщен влагой(слюна),при попадании геля на зубы, перекись начинает вытеснять воду из зубов, эта реакция идет с выделением кислорода, за счет чего образуется пузырьки пены ,этих пузырьков очень много, они бьются друг об друга,лопаются и происходит вибрация,которая ощущает пульповая камера зуба,Именно из- за этой реакции ощущаются дискомфортные и болезненные ощущения.&nbsp;<br />\r\nЕщё какое-то время после отбеливания, дентинные канальца остаются открытыми и чувствительность на воздух и др. раздражители сохраняется, но спустя сутки, она полностью пропадает.<br />\r\nЗапомните ! &nbsp;отбеливающие системы воздействуют на коллаген, именно он отвечает за цвет ваших зубов, точно такой же коллаген есть и у глаз отвечающий за цвет глаз.</p>\r\n',3,6,NULL,NULL,'','','','text','editor',NULL);
CREATE TABLE "cms_content_element2cms_user" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "cms_user_id" integer NOT NULL,
    "cms_content_element_id" integer NOT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("cms_user_id","cms_content_element_id")
);

CREATE TABLE "cms_content_element_file" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "storage_file_id" integer NOT NULL,
    "content_element_id" integer NOT NULL,
    "priority" integer NOT NULL DEFAULT '100',
    PRIMARY KEY ("id"),
    UNIQUE ("storage_file_id","content_element_id")
);

CREATE TABLE "cms_content_element_image" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "storage_file_id" integer NOT NULL,
    "content_element_id" integer NOT NULL,
    "priority" integer NOT NULL DEFAULT '100',
    PRIMARY KEY ("id"),
    UNIQUE ("storage_file_id","content_element_id")
);

CREATE TABLE "cms_content_element_property" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "property_id" integer NOT NULL,
    "element_id" integer NOT NULL,
    "value" text NOT NULL,
    "value_enum" integer DEFAULT NULL,
    "value_num" decimal(18,4) DEFAULT NULL,
    "description" varchar(510) DEFAULT NULL,
    "value_bool" int4 DEFAULT NULL,
    "value_num2" decimal(18,4) DEFAULT NULL,
    "value_int2" integer DEFAULT NULL,
    "value_string" varchar(510) DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE "cms_content_element_tree" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "element_id" integer NOT NULL,
    "tree_id" integer NOT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("element_id","tree_id")
);

CREATE TABLE "cms_content_property" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "name" varchar(510) NOT NULL,
    "code" varchar(128) DEFAULT NULL,
    "content_id" integer DEFAULT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "priority" integer NOT NULL DEFAULT '500',
    "property_type" char(1) NOT NULL DEFAULT 'S',
    "multiple" char(1) NOT NULL DEFAULT 'N',
    "is_required" char(1) DEFAULT NULL,
    "component" varchar(510) DEFAULT NULL,
    "component_settings" text ,
    "hint" varchar(510) DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("code","content_id")
);

CREATE TABLE "cms_content_property2content" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "cms_content_property_id" integer NOT NULL,
    "cms_content_id" integer NOT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("cms_content_property_id","cms_content_id")
);

CREATE TABLE "cms_content_property2tree" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "cms_content_property_id" integer NOT NULL,
    "cms_tree_id" integer NOT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("cms_content_property_id","cms_tree_id")
);

CREATE TABLE "cms_content_property_enum" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "property_id" integer DEFAULT NULL,
    "value" varchar(510) NOT NULL,
    "def" char(1) NOT NULL DEFAULT 'N',
    "code" varchar(64) NOT NULL,
    "priority" integer NOT NULL DEFAULT '500',
    PRIMARY KEY ("id")
);

CREATE TABLE "cms_content_type" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "files" text ,
    "priority" integer NOT NULL DEFAULT '500',
    "name" varchar(510) NOT NULL,
    "code" varchar(64) NOT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("code")
);

INSERT INTO "cms_content_type" VALUES (1,1,1,1443696537,1443696537,NULL,500,'Публикации','publication'),(2,1,1,1443696547,1443696547,NULL,500,'Прочее','other'),(3,1,1,1445806903,1445806903,NULL,500,'Клиника','clinic');
CREATE TABLE "cms_dashboard" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "name" varchar(510) NOT NULL,
    "cms_user_id" integer DEFAULT NULL,
    "priority" integer NOT NULL DEFAULT '100',
    "columns" integer  NOT NULL DEFAULT '1',
    "columns_settings" text ,
    PRIMARY KEY ("id")
);

INSERT INTO "cms_dashboard" VALUES (1,1,1,1455882076,1455882267,'Стол 1 (общая информация)',NULL,100,2,NULL),(2,1,1,1455882157,1455882166,'Стол 2 (Контент)',NULL,100,3,NULL);
CREATE TABLE "cms_dashboard_widget" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "cms_dashboard_id" integer NOT NULL,
    "cms_dashboard_column" integer NOT NULL DEFAULT '1',
    "priority" integer NOT NULL DEFAULT '100',
    "component" varchar(510) NOT NULL,
    "component_settings" text ,
    PRIMARY KEY ("id")
);

INSERT INTO "cms_dashboard_widget" VALUES (1,1,1,1455882100,1455882319,1,1,100,'skeeks\\cms\\modules\\admin\\dashboards\\AboutCmsDashboard',''),(2,1,1,1455882123,1455882319,1,1,200,'skeeks\\cms\\modules\\admin\\dashboards\\CmsInformDashboard',''),(3,1,1,1455882162,1455882243,2,1,100,'skeeks\\cms\\modules\\admin\\dashboards\\ContentElementListDashboard','a:11:{s:4:\"name\";s:20:\"Публикации\";s:13:\"enabledPaging\";s:1:\"1\";s:8:\"pageSize\";s:2:\"10\";s:16:\"pageSizeLimitMin\";s:1:\"1\";s:16:\"pageSizeLimitMax\";s:2:\"50\";s:17:\"enabledActiveTime\";s:1:\"1\";s:11:\"content_ids\";a:1:{i:0;s:1:\"1\";}s:8:\"tree_ids\";s:0:\"\";s:5:\"limit\";s:1:\"0\";s:7:\"orderBy\";s:12:\"published_at\";s:5:\"order\";s:1:\"3\";}'),(4,1,1,1455882189,1455882243,2,2,100,'skeeks\\cms\\modules\\admin\\dashboards\\ContentElementListDashboard','a:11:{s:4:\"name\";s:12:\"Слайды\";s:13:\"enabledPaging\";s:1:\"1\";s:8:\"pageSize\";s:2:\"10\";s:16:\"pageSizeLimitMin\";s:1:\"1\";s:16:\"pageSizeLimitMax\";s:2:\"50\";s:17:\"enabledActiveTime\";s:1:\"1\";s:11:\"content_ids\";a:1:{i:0;s:1:\"2\";}s:8:\"tree_ids\";s:0:\"\";s:5:\"limit\";s:1:\"0\";s:7:\"orderBy\";s:8:\"priority\";s:5:\"order\";s:1:\"4\";}'),(5,1,1,1455882225,1455882243,2,3,100,'skeeks\\cms\\modules\\admin\\dashboards\\ContentElementListDashboard','a:11:{s:4:\"name\";s:12:\"Услуги\";s:13:\"enabledPaging\";s:1:\"1\";s:8:\"pageSize\";s:2:\"10\";s:16:\"pageSizeLimitMin\";s:1:\"1\";s:16:\"pageSizeLimitMax\";s:2:\"50\";s:17:\"enabledActiveTime\";s:1:\"1\";s:11:\"content_ids\";a:1:{i:0;s:1:\"3\";}s:8:\"tree_ids\";s:0:\"\";s:5:\"limit\";s:1:\"0\";s:7:\"orderBy\";s:12:\"published_at\";s:5:\"order\";s:1:\"3\";}'),(6,1,1,1455882252,1455882319,1,2,100,'skeeks\\cms\\modules\\admin\\dashboards\\DiscSpaceDashboard','');
CREATE TABLE "cms_event" (
    "id" integer NOT NULL,
    "event_name" varchar(510) NOT NULL,
    "name" varchar(200) DEFAULT NULL,
    "description" text ,
    "priority" integer NOT NULL DEFAULT '150',
    PRIMARY KEY ("id"),
    UNIQUE ("event_name")
);

CREATE TABLE "cms_lang" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "def" char(1) NOT NULL DEFAULT 'N',
    "priority" integer NOT NULL DEFAULT '500',
    "code" char(5) NOT NULL,
    "name" varchar(510) NOT NULL,
    "description" varchar(510) DEFAULT NULL,
    "image_id" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("code")
);

INSERT INTO "cms_lang" VALUES (1,1,1,1432126580,1432130752,'Y','Y',500,'ru','Русский','',NULL),(2,1,1,1432126667,1446071203,'Y','N',600,'en','Английский','',NULL);
CREATE TABLE "cms_site" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "def" char(1) NOT NULL DEFAULT 'N',
    "priority" integer NOT NULL DEFAULT '500',
    "code" char(15) NOT NULL,
    "name" varchar(510) NOT NULL,
    "server_name" varchar(510) DEFAULT NULL,
    "description" varchar(510) DEFAULT NULL,
    "image_id" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("code")
);

INSERT INTO "cms_site" VALUES (1,1,1,1432128290,1432130861,'Y','Y',500,'s1','Сайт 1','','',NULL);
CREATE TABLE "cms_site_domain" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "domain" varchar(510) NOT NULL,
    "cms_site_id" integer NOT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("domain")
);

CREATE TABLE "cms_storage_file" (
    "id" integer NOT NULL,
    "cluster_id" varchar(32) DEFAULT NULL,
    "cluster_file" varchar(510) DEFAULT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "size" bigint DEFAULT NULL,
    "mime_type" varchar(32) DEFAULT NULL,
    "extension" varchar(32) DEFAULT NULL,
    "original_name" varchar(510) DEFAULT NULL,
    "name_to_save" varchar(64) DEFAULT NULL,
    "name" varchar(510) DEFAULT NULL,
    "description_short" text ,
    "description_full" text ,
    "image_height" integer DEFAULT NULL,
    "image_width" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("cluster_id","cluster_file")
);

INSERT INTO "cms_storage_file" VALUES (6,'local','e7/3e/02/e73e0270e75c9f93738bbafefe182923.jpg',1,1,1443698527,1443698527,369564,'image/jpeg','jpg','%C2%A6%D0%AF%C2%A6-%C2%A6-%C2%A6%C2%AC%C2%A6%C2%ACT%D0%9C%C2%A6-%C2%A6-(4).jpg',NULL,'Слайд 2',NULL,NULL,500,1000),(7,'local','22/39/3f/22393fb271d072fd9e80629f56c457be.jpg',1,1,1443698567,1443698567,130519,'image/jpeg','jpg','kqfsv6ixxjjxz3dcs6ms.jpg',NULL,'Слайд 1',NULL,NULL,500,1000),(8,'local','c7/63/df/c763dfcc39d930e23502d088e518f099.jpg&w=1200&h=500&crop-to-fit=y',1,1,1443698642,1443698642,77241,'image/jpeg','jpg&w=1200&h=500','8664_rendering1-copy.jpg&w=1200&h=500&crop-to-fit=y',NULL,'Слайд 3',NULL,NULL,500,1200),(9,'local','6e/d4/c4/6ed4c434e7ec08d7a7489f5458f26b3d.png',1,1,1443698667,1443698667,618627,'image/png','png','444444.png',NULL,'Слайд 4',NULL,NULL,500,1200),(10,'local','a5/11/9e/a5119efa777546c9366e3affd8802571.png',1,1,1443698755,1443698756,477837,'image/png','png','1.png',NULL,'Слайд 5',NULL,NULL,500,1200),(16,'local','89/84/80/8984801767d98d26445c32e15725cb62.jpg',1,1,1445807895,1445807895,105935,'image/jpeg','jpg','image001.jpg',NULL,'Терапия',NULL,NULL,566,948),(17,'local','e3/b2/c7/e3b2c71dcdf5d325c0b73003380a808e.jpg',1,1,1445808804,1445808804,7517,'image/jpeg','jpg','big2owvasdc0ypez6x1fq7ng5ujl9kmht8rbi34.jpg',NULL,'Протезирование зубов',NULL,NULL,200,300),(18,'local','0c/2a/8c/0c2a8c95e88b17e27389d20b164025ba.jpg',1,1,1445808864,1445808864,47405,'image/jpeg','jpg','wsmile.jpg',NULL,'Исправление прикуса (брекеты)',NULL,NULL,335,534),(19,'local','26/a8/db/26a8dbb6439e79022c61e1a5fbcbec97.jpg',1,1,1445808934,1445808934,33713,'image/jpeg','jpg','32321_o420x258.jpg',NULL,'Отбеливание зубов',NULL,NULL,258,420);
CREATE TABLE "cms_tree" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "name" varchar(510) NOT NULL,
    "image_id" integer DEFAULT NULL,
    "image_full_id" integer DEFAULT NULL,
    "description_short" text ,
    "description_full" text ,
    "code" varchar(128) DEFAULT NULL,
    "pid" integer DEFAULT NULL,
    "pids" varchar(510) DEFAULT NULL,
    "level" integer DEFAULT '0',
    "dir" varchar(1000) DEFAULT NULL,
    "priority" integer NOT NULL DEFAULT '0',
    "published_at" integer DEFAULT NULL,
    "redirect" varchar(1000) DEFAULT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "meta_title" varchar(1000) DEFAULT NULL,
    "meta_description" text ,
    "meta_keywords" text ,
    "tree_type_id" integer DEFAULT NULL,
    "description_short_type" varchar(20) NOT NULL DEFAULT 'text',
    "description_full_type" varchar(20) NOT NULL DEFAULT 'text',
    "redirect_tree_id" integer DEFAULT NULL,
    "redirect_code" integer NOT NULL DEFAULT '301',
    "name_hidden" varchar(510) DEFAULT NULL,
    "view_file" varchar(256) DEFAULT NULL,
    "cms_site_id" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("pid","code")
);

INSERT INTO "cms_tree" VALUES (1,1,1,1416084922,1455882885,'Главная страница',NULL,NULL,'','<p>Текст редактируемый в подробном описании главного раздела сайта.</p>\r\n',NULL,NULL,'1',0,NULL,0,NULL,'','Y','Тестовый проект SkeekS CMS (Yii2)','','',1,'editor','editor',NULL,301,'',NULL,1),(4,1,1,1443695838,1445809375,'Контакты',NULL,NULL,'','','contacts',1,'1/4',1,'contacts',600,NULL,'','Y','','','',5,'text','text',NULL,301,NULL,NULL,1),(6,1,1,1445723753,1445809375,'Услуги',NULL,NULL,NULL,NULL,'uslugi',1,'1/6',1,'uslugi',200,NULL,NULL,'Y',NULL,NULL,NULL,2,'text','text',NULL,301,NULL,NULL,1),(7,1,1,1445723763,1445809375,'Цены',NULL,NULL,NULL,NULL,'tsenyi',1,'1/7',1,'tsenyi',300,NULL,NULL,'Y',NULL,NULL,NULL,2,'text','text',NULL,301,NULL,NULL,1),(8,1,1,1445723768,1445809375,'Специалисты',NULL,NULL,NULL,NULL,'spetsialistyi',1,'1/8',1,'spetsialistyi',400,NULL,NULL,'Y',NULL,NULL,NULL,2,'text','text',NULL,301,NULL,NULL,1),(9,1,1,1445723775,1445809375,'Акции',NULL,NULL,NULL,NULL,'aktsii',1,'1/9',1,'aktsii',500,NULL,NULL,'Y',NULL,NULL,NULL,2,'text','text',NULL,301,NULL,NULL,1),(18,1,1,1445809372,1455882645,'О сайте',NULL,NULL,'','<p>Это демо текст</p>\r\n\r\n<h2>Московская сертифицированная стоматологическая клиника. 1111</h2>\r\n\r\n<p>Клиника доктора Дмитрия Вадимовича Звонарева,одна из ведущих стоматологических клиник Зеленограда, специализируется на оказании комплексной помощи в области стоматологии - лечения зубов и дёсен, как то: терапевтической, хирургической, ортодонтической, ортопедической стоматологии и имплантологии. Клиника укомплектована всем необходимым для качественного лечения зубов: квалифицированным медицинским персоналом, современным&nbsp;оборудованием.</p>\r\n','about',1,'1/18',1,'about',100,NULL,'','Y','','','',2,'text','editor',NULL,301,'',NULL,1);
CREATE TABLE "cms_tree_file" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "storage_file_id" integer NOT NULL,
    "tree_id" integer NOT NULL,
    "priority" integer NOT NULL DEFAULT '100',
    PRIMARY KEY ("id"),
    UNIQUE ("storage_file_id","tree_id")
);

CREATE TABLE "cms_tree_image" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "storage_file_id" integer NOT NULL,
    "tree_id" integer NOT NULL,
    "priority" integer NOT NULL DEFAULT '100',
    PRIMARY KEY ("id"),
    UNIQUE ("storage_file_id","tree_id")
);

CREATE TABLE "cms_tree_property" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "property_id" integer NOT NULL,
    "element_id" integer NOT NULL,
    "value" text NOT NULL,
    "value_enum" integer DEFAULT NULL,
    "value_num" decimal(18,4) DEFAULT NULL,
    "description" varchar(510) DEFAULT NULL,
    "value_bool" int4 DEFAULT NULL,
    "value_num2" decimal(18,4) DEFAULT NULL,
    "value_int2" integer DEFAULT NULL,
    "value_string" varchar(510) DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE "cms_tree_type" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "name" varchar(510) NOT NULL,
    "code" varchar(100) NOT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "priority" integer NOT NULL DEFAULT '500',
    "description" text ,
    "index_for_search" char(1) NOT NULL DEFAULT 'Y',
    "name_meny" varchar(200) DEFAULT NULL,
    "name_one" varchar(200) DEFAULT NULL,
    "viewFile" varchar(510) DEFAULT NULL,
    "default_children_tree_type" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("code")
);

INSERT INTO "cms_tree_type" VALUES (1,1,1,1439037415,1445723743,'Главный раздел','home','Y',500,NULL,'','Разделы','Раздел','',2),(2,1,1,1439037424,1439037424,'Текстовый раздел','text','Y',500,NULL,'','Разделы','Раздел',NULL,NULL),(5,1,1,1443703382,1443703382,'Контакты','contacts','Y',500,NULL,'','Разделы','Раздел',NULL,NULL);
CREATE TABLE "cms_tree_type_property" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "name" varchar(510) NOT NULL,
    "code" varchar(128) DEFAULT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "priority" integer NOT NULL DEFAULT '500',
    "property_type" char(1) NOT NULL DEFAULT 'S',
    "multiple" char(1) NOT NULL DEFAULT 'N',
    "is_required" char(1) DEFAULT NULL,
    "component" varchar(510) DEFAULT NULL,
    "component_settings" text ,
    "hint" varchar(510) DEFAULT NULL,
    "tree_type_id" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("code","tree_type_id")
);

CREATE TABLE "cms_tree_type_property2type" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "cms_tree_type_property_id" integer NOT NULL,
    "cms_tree_type_id" integer NOT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("cms_tree_type_property_id","cms_tree_type_id")
);

CREATE TABLE "cms_tree_type_property_enum" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "property_id" integer DEFAULT NULL,
    "value" varchar(510) NOT NULL,
    "def" char(1) NOT NULL DEFAULT 'N',
    "code" varchar(64) NOT NULL,
    "priority" integer NOT NULL DEFAULT '500',
    PRIMARY KEY ("id")
);

CREATE TYPE cms_user_gender AS ENUM ('men','women');
CREATE TABLE "cms_user" (
    "id" integer NOT NULL,
    "username" varchar(510) NOT NULL,
    "auth_key" varchar(64) NOT NULL,
    "password_hash" varchar(510) NOT NULL,
    "password_reset_token" varchar(510) DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "_to_del_name" varchar(510) DEFAULT NULL,
    "image_id" integer DEFAULT NULL,
    "gender" cms_user_gender NOT NULL DEFAULT 'men',
    "active" char(1) NOT NULL DEFAULT 'Y',
    "updated_by" integer DEFAULT NULL,
    "created_by" integer DEFAULT NULL,
    "logged_at" integer DEFAULT NULL,
    "last_activity_at" integer DEFAULT NULL,
    "last_admin_activity_at" integer DEFAULT NULL,
    "email" varchar(510) DEFAULT NULL,
    "phone" varchar(128) DEFAULT NULL,
    "email_is_approved" integer  NOT NULL DEFAULT '0',
    "phone_is_approved" integer  NOT NULL DEFAULT '0',
    "first_name" varchar(510) DEFAULT NULL,
    "last_name" varchar(510) DEFAULT NULL,
    "patronymic" varchar(510) DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("username"),
    UNIQUE ("email"),
    UNIQUE ("phone")
);

INSERT INTO "cms_user" VALUES (1,'root','otv60YW-nV6-8GRI4La3vYNhu_-dmp_n','$2y$13$j4ZarxjwBV.eC9q7pbg41OWLuR0Nu2.LLfOzD0rkLqnwkgBvCTIvy','wn49wJLj9OMVjgj8bBzBjND4nFixyJgt_1413297645',NULL,1510989007,'Семенов Александр',NULL,'men','Y',1,NULL,1510327590,1510989007,1510989007,'semenov@skeeks.com',NULL,0,0,NULL,NULL,NULL);
CREATE TABLE "cms_user_authclient" (
    "id" integer NOT NULL,
    "user_id" integer NOT NULL,
    "provider" varchar(100) DEFAULT NULL,
    "provider_identifier" varchar(200) DEFAULT NULL,
    "provider_data" text ,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE "cms_user_email" (
    "id" integer NOT NULL,
    "user_id" integer DEFAULT NULL,
    "value" varchar(510) NOT NULL,
    "approved" char(1) NOT NULL DEFAULT 'N',
    "def" varchar(2) NOT NULL DEFAULT 'N',
    "approved_key" varchar(510) DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("value")
);

INSERT INTO "cms_user_email" VALUES (2,1,'semenov@skeeks.com','Y','Y',NULL,1455882778,1455882803);
CREATE TABLE "cms_user_phone" (
    "id" integer NOT NULL,
    "user_id" integer DEFAULT NULL,
    "value" varchar(510) NOT NULL,
    "approved" char(1) NOT NULL DEFAULT 'N',
    "def" varchar(2) NOT NULL DEFAULT 'N',
    "approved_key" varchar(510) DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("value")
);

CREATE TABLE "cms_user_property" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "property_id" integer NOT NULL,
    "element_id" integer NOT NULL,
    "value" varchar(510) NOT NULL,
    "value_enum" integer DEFAULT NULL,
    "value_num" decimal(18,4) DEFAULT NULL,
    "description" varchar(510) DEFAULT NULL,
    "value_bool" int4 DEFAULT NULL,
    "value_num2" decimal(18,4) DEFAULT NULL,
    "value_int2" integer DEFAULT NULL,
    "value_string" varchar(510) DEFAULT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE "cms_user_universal_property" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "name" varchar(510) NOT NULL,
    "code" varchar(128) DEFAULT NULL,
    "active" char(1) NOT NULL DEFAULT 'Y',
    "priority" integer NOT NULL DEFAULT '500',
    "property_type" char(1) NOT NULL DEFAULT 'S',
    "multiple" char(1) NOT NULL DEFAULT 'N',
    "is_required" char(1) DEFAULT NULL,
    "component" varchar(510) DEFAULT NULL,
    "component_settings" text ,
    "hint" varchar(510) DEFAULT NULL,
    PRIMARY KEY ("id"),
    UNIQUE ("code")
);

CREATE TABLE "cms_user_universal_property_enum" (
    "id" integer NOT NULL,
    "created_by" integer DEFAULT NULL,
    "updated_by" integer DEFAULT NULL,
    "created_at" integer DEFAULT NULL,
    "updated_at" integer DEFAULT NULL,
    "property_id" integer DEFAULT NULL,
    "value" varchar(510) NOT NULL,
    "def" char(1) NOT NULL DEFAULT 'N',
    "code" varchar(64) NOT NULL,
    "priority" integer NOT NULL DEFAULT '500',
    PRIMARY KEY ("id")
);


-- Post-data save --
COMMIT;
START TRANSACTION;

-- Typecasts --
ALTER TABLE "cms_content_element_property" ALTER COLUMN "value_bool" DROP DEFAULT, ALTER COLUMN "value_bool" TYPE boolean USING CAST("value_bool" as boolean);
ALTER TABLE "cms_tree_property" ALTER COLUMN "value_bool" DROP DEFAULT, ALTER COLUMN "value_bool" TYPE boolean USING CAST("value_bool" as boolean);
ALTER TABLE "cms_user_property" ALTER COLUMN "value_bool" DROP DEFAULT, ALTER COLUMN "value_bool" TYPE boolean USING CAST("value_bool" as boolean);

-- Foreign keys --
ALTER TABLE "auth_assignment" ADD CONSTRAINT "auth_assignment_ibfk_1" FOREIGN KEY ("item_name") REFERENCES "auth_item" ("name") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "auth_assignment" ("item_name");
ALTER TABLE "auth_assignment" ADD CONSTRAINT "auth_assignment_user_id" FOREIGN KEY ("user_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "auth_assignment" ("user_id");
ALTER TABLE "auth_item" ADD CONSTRAINT "auth_item_ibfk_1" FOREIGN KEY ("rule_name") REFERENCES "auth_rule" ("name") ON DELETE SET NULL ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "auth_item" ("rule_name");
ALTER TABLE "auth_item_child" ADD CONSTRAINT "auth_item_child_ibfk_1" FOREIGN KEY ("parent") REFERENCES "auth_item" ("name") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "auth_item_child" ("parent");
ALTER TABLE "auth_item_child" ADD CONSTRAINT "auth_item_child_ibfk_2" FOREIGN KEY ("child") REFERENCES "auth_item" ("name") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "auth_item_child" ("child");
ALTER TABLE "cms_component_settings" ADD CONSTRAINT "cms_component_settings_user_id" FOREIGN KEY ("user_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_component_settings" ("user_id");
ALTER TABLE "cms_component_settings" ADD CONSTRAINT "cms_component_settings__cms_site_id" FOREIGN KEY ("cms_site_id") REFERENCES "cms_site" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_component_settings" ("cms_site_id");
ALTER TABLE "cms_component_settings" ADD CONSTRAINT "cms_settings_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_component_settings" ("created_by");
ALTER TABLE "cms_component_settings" ADD CONSTRAINT "cms_settings_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_component_settings" ("updated_by");
ALTER TABLE "cms_content" ADD CONSTRAINT "cms_content_cms_content_type" FOREIGN KEY ("content_type") REFERENCES "cms_content_type" ("code") DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content" ("content_type");
ALTER TABLE "cms_content" ADD CONSTRAINT "cms_content_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content" ("created_by");
ALTER TABLE "cms_content" ADD CONSTRAINT "cms_content_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content" ("updated_by");
ALTER TABLE "cms_content" ADD CONSTRAINT "cms_content__cms_content" FOREIGN KEY ("parent_content_id") REFERENCES "cms_content" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content" ("parent_content_id");
ALTER TABLE "cms_content" ADD CONSTRAINT "cms_content__default_tree_id" FOREIGN KEY ("default_tree_id") REFERENCES "cms_tree" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content" ("default_tree_id");
ALTER TABLE "cms_content" ADD CONSTRAINT "cms_content__root_tree_id" FOREIGN KEY ("root_tree_id") REFERENCES "cms_tree" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content" ("root_tree_id");
ALTER TABLE "cms_content_element" ADD CONSTRAINT "cms_content_element_content_id" FOREIGN KEY ("content_id") REFERENCES "cms_content" ("id") DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element" ("content_id");
ALTER TABLE "cms_content_element" ADD CONSTRAINT "cms_content_element_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element" ("created_by");
ALTER TABLE "cms_content_element" ADD CONSTRAINT "cms_content_element_tree_id" FOREIGN KEY ("tree_id") REFERENCES "cms_tree" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element" ("tree_id");
ALTER TABLE "cms_content_element" ADD CONSTRAINT "cms_content_element_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element" ("updated_by");
ALTER TABLE "cms_content_element" ADD CONSTRAINT "cms_content_element__cms_content_element" FOREIGN KEY ("parent_content_element_id") REFERENCES "cms_content_element" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element" ("parent_content_element_id");
ALTER TABLE "cms_content_element" ADD CONSTRAINT "cms_content_element__image_full_id" FOREIGN KEY ("image_full_id") REFERENCES "cms_storage_file" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element" ("image_full_id");
ALTER TABLE "cms_content_element" ADD CONSTRAINT "cms_content_element__image_id" FOREIGN KEY ("image_id") REFERENCES "cms_storage_file" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element" ("image_id");
ALTER TABLE "cms_content_element2cms_user" ADD CONSTRAINT "cms_content_element2cms_user__cms_content_element_id" FOREIGN KEY ("cms_content_element_id") REFERENCES "cms_content_element" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element2cms_user" ("cms_content_element_id");
ALTER TABLE "cms_content_element2cms_user" ADD CONSTRAINT "cms_content_element2cms_user__cms_user_id" FOREIGN KEY ("cms_user_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element2cms_user" ("cms_user_id");
ALTER TABLE "cms_content_element2cms_user" ADD CONSTRAINT "cms_content_element2cms_user__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element2cms_user" ("created_by");
ALTER TABLE "cms_content_element2cms_user" ADD CONSTRAINT "cms_content_element2cms_user__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element2cms_user" ("updated_by");
ALTER TABLE "cms_content_element_file" ADD CONSTRAINT "cms_content_element_file_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_file" ("created_by");
ALTER TABLE "cms_content_element_file" ADD CONSTRAINT "cms_content_element_file_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_file" ("updated_by");
ALTER TABLE "cms_content_element_file" ADD CONSTRAINT "cms_content_element_file__content_element_id" FOREIGN KEY ("content_element_id") REFERENCES "cms_content_element" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_file" ("content_element_id");
ALTER TABLE "cms_content_element_file" ADD CONSTRAINT "cms_content_element_file__storage_file_id" FOREIGN KEY ("storage_file_id") REFERENCES "cms_storage_file" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_file" ("storage_file_id");
ALTER TABLE "cms_content_element_image" ADD CONSTRAINT "cms_content_element_image_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_image" ("created_by");
ALTER TABLE "cms_content_element_image" ADD CONSTRAINT "cms_content_element_image_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_image" ("updated_by");
ALTER TABLE "cms_content_element_image" ADD CONSTRAINT "cms_content_element_image__content_element_id" FOREIGN KEY ("content_element_id") REFERENCES "cms_content_element" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_image" ("content_element_id");
ALTER TABLE "cms_content_element_image" ADD CONSTRAINT "cms_content_element_image__storage_file_id" FOREIGN KEY ("storage_file_id") REFERENCES "cms_storage_file" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_image" ("storage_file_id");
ALTER TABLE "cms_content_element_property" ADD CONSTRAINT "cms_content_element_property_element_id" FOREIGN KEY ("element_id") REFERENCES "cms_content_element" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_property" ("element_id");
ALTER TABLE "cms_content_element_property" ADD CONSTRAINT "cms_content_element_property_property_id" FOREIGN KEY ("property_id") REFERENCES "cms_content_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_property" ("property_id");
ALTER TABLE "cms_content_element_property" ADD CONSTRAINT "cms_content_element_property__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_property" ("created_by");
ALTER TABLE "cms_content_element_property" ADD CONSTRAINT "cms_content_element_property__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_property" ("updated_by");
ALTER TABLE "cms_content_element_tree" ADD CONSTRAINT "cms_content_element_tree__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_tree" ("updated_by");
ALTER TABLE "cms_content_element_tree" ADD CONSTRAINT "cms_content_element_tree_element_id" FOREIGN KEY ("element_id") REFERENCES "cms_content_element" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_tree" ("element_id");
ALTER TABLE "cms_content_element_tree" ADD CONSTRAINT "cms_content_element_tree_tree_id" FOREIGN KEY ("tree_id") REFERENCES "cms_tree" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_tree" ("tree_id");
ALTER TABLE "cms_content_element_tree" ADD CONSTRAINT "cms_content_element_tree__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_element_tree" ("created_by");
ALTER TABLE "cms_content_property" ADD CONSTRAINT "cms_content_property__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property" ("updated_by");
ALTER TABLE "cms_content_property" ADD CONSTRAINT "cms_content_property_content_id" FOREIGN KEY ("content_id") REFERENCES "cms_content" ("id") DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property" ("content_id");
ALTER TABLE "cms_content_property" ADD CONSTRAINT "cms_content_property__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property" ("created_by");
ALTER TABLE "cms_content_property2content" ADD CONSTRAINT "cms_content_property2content__property_id" FOREIGN KEY ("cms_content_property_id") REFERENCES "cms_content_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2content" ("cms_content_property_id");
ALTER TABLE "cms_content_property2content" ADD CONSTRAINT "cms_content_property2content__content_id" FOREIGN KEY ("cms_content_id") REFERENCES "cms_content" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2content" ("cms_content_id");
ALTER TABLE "cms_content_property2content" ADD CONSTRAINT "cms_content_property2content__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2content" ("created_by");
ALTER TABLE "cms_content_property2content" ADD CONSTRAINT "cms_content_property2content__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2content" ("updated_by");
ALTER TABLE "cms_content_property2tree" ADD CONSTRAINT "cms_content_property2tree__property_id" FOREIGN KEY ("cms_content_property_id") REFERENCES "cms_content_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2tree" ("cms_content_property_id");
ALTER TABLE "cms_content_property2tree" ADD CONSTRAINT "cms_content_property2tree__cms_tree_id" FOREIGN KEY ("cms_tree_id") REFERENCES "cms_tree" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2tree" ("cms_tree_id");
ALTER TABLE "cms_content_property2tree" ADD CONSTRAINT "cms_content_property2tree__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2tree" ("created_by");
ALTER TABLE "cms_content_property2tree" ADD CONSTRAINT "cms_content_property2tree__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property2tree" ("updated_by");
ALTER TABLE "cms_content_property_enum" ADD CONSTRAINT "cms_content_property_enum__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property_enum" ("updated_by");
ALTER TABLE "cms_content_property_enum" ADD CONSTRAINT "cms_content_property_enum_property_id" FOREIGN KEY ("property_id") REFERENCES "cms_content_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property_enum" ("property_id");
ALTER TABLE "cms_content_property_enum" ADD CONSTRAINT "cms_content_property_enum__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_property_enum" ("created_by");
ALTER TABLE "cms_content_type" ADD CONSTRAINT "cms_content_type_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_type" ("created_by");
ALTER TABLE "cms_content_type" ADD CONSTRAINT "cms_content_type_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_content_type" ("updated_by");
ALTER TABLE "cms_dashboard" ADD CONSTRAINT "cms_dashboard_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_dashboard" ("created_by");
ALTER TABLE "cms_dashboard" ADD CONSTRAINT "cms_dashboard_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_dashboard" ("updated_by");
ALTER TABLE "cms_dashboard" ADD CONSTRAINT "cms_dashboard__cms_user_id" FOREIGN KEY ("cms_user_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_dashboard" ("cms_user_id");
ALTER TABLE "cms_dashboard_widget" ADD CONSTRAINT "cms_dashboard_widget_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_dashboard_widget" ("created_by");
ALTER TABLE "cms_dashboard_widget" ADD CONSTRAINT "cms_dashboard_widget_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_dashboard_widget" ("updated_by");
ALTER TABLE "cms_dashboard_widget" ADD CONSTRAINT "cms_dashboard_widget__cms_dashboard_id" FOREIGN KEY ("cms_dashboard_id") REFERENCES "cms_dashboard" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_dashboard_widget" ("cms_dashboard_id");
ALTER TABLE "cms_lang" ADD CONSTRAINT "cms_lang_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_lang" ("created_by");
ALTER TABLE "cms_lang" ADD CONSTRAINT "cms_lang_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_lang" ("updated_by");
ALTER TABLE "cms_lang" ADD CONSTRAINT "cms_lang__image_id" FOREIGN KEY ("image_id") REFERENCES "cms_storage_file" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_lang" ("image_id");
ALTER TABLE "cms_site" ADD CONSTRAINT "cms_site_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_site" ("created_by");
ALTER TABLE "cms_site" ADD CONSTRAINT "cms_site_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_site" ("updated_by");
ALTER TABLE "cms_site" ADD CONSTRAINT "cms_site__image_id" FOREIGN KEY ("image_id") REFERENCES "cms_storage_file" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_site" ("image_id");
ALTER TABLE "cms_site_domain" ADD CONSTRAINT "cms_site_domain__cms_site_id" FOREIGN KEY ("cms_site_id") REFERENCES "cms_site" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_site_domain" ("cms_site_id");
ALTER TABLE "cms_site_domain" ADD CONSTRAINT "cms_site_domain_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_site_domain" ("created_by");
ALTER TABLE "cms_site_domain" ADD CONSTRAINT "cms_site_domain_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_site_domain" ("updated_by");
ALTER TABLE "cms_storage_file" ADD CONSTRAINT "storage_file_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_storage_file" ("created_by");
ALTER TABLE "cms_storage_file" ADD CONSTRAINT "storage_file_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_storage_file" ("updated_by");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree_pid_cms_tree" FOREIGN KEY ("pid") REFERENCES "cms_tree" ("id") DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("pid");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree_tree_type_id" FOREIGN KEY ("tree_type_id") REFERENCES "cms_tree_type" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("tree_type_id");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree__cms_site_id" FOREIGN KEY ("cms_site_id") REFERENCES "cms_site" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("cms_site_id");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("created_by");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree__image_full_id" FOREIGN KEY ("image_full_id") REFERENCES "cms_storage_file" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("image_full_id");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree__image_id" FOREIGN KEY ("image_id") REFERENCES "cms_storage_file" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("image_id");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree__redirect_tree_id" FOREIGN KEY ("redirect_tree_id") REFERENCES "cms_tree" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("redirect_tree_id");
ALTER TABLE "cms_tree" ADD CONSTRAINT "cms_tree__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree" ("updated_by");
ALTER TABLE "cms_tree_file" ADD CONSTRAINT "cms_tree_file_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_file" ("created_by");
ALTER TABLE "cms_tree_file" ADD CONSTRAINT "cms_tree_file_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_file" ("updated_by");
ALTER TABLE "cms_tree_file" ADD CONSTRAINT "cms_tree_file__storage_file_id" FOREIGN KEY ("storage_file_id") REFERENCES "cms_storage_file" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_file" ("storage_file_id");
ALTER TABLE "cms_tree_file" ADD CONSTRAINT "cms_tree_file__tree_id" FOREIGN KEY ("tree_id") REFERENCES "cms_tree" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_file" ("tree_id");
ALTER TABLE "cms_tree_image" ADD CONSTRAINT "cms_tree_image_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_image" ("created_by");
ALTER TABLE "cms_tree_image" ADD CONSTRAINT "cms_tree_image_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_image" ("updated_by");
ALTER TABLE "cms_tree_image" ADD CONSTRAINT "cms_tree_image__storage_file_id" FOREIGN KEY ("storage_file_id") REFERENCES "cms_storage_file" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_image" ("storage_file_id");
ALTER TABLE "cms_tree_image" ADD CONSTRAINT "cms_tree_image__tree_id" FOREIGN KEY ("tree_id") REFERENCES "cms_tree" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_image" ("tree_id");
ALTER TABLE "cms_tree_property" ADD CONSTRAINT "cms_tree_property_element_id" FOREIGN KEY ("element_id") REFERENCES "cms_tree" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_property" ("element_id");
ALTER TABLE "cms_tree_property" ADD CONSTRAINT "cms_tree_property_property_id" FOREIGN KEY ("property_id") REFERENCES "cms_tree_type_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_property" ("property_id");
ALTER TABLE "cms_tree_property" ADD CONSTRAINT "cms_tree_property__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_property" ("created_by");
ALTER TABLE "cms_tree_property" ADD CONSTRAINT "cms_tree_property__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_property" ("updated_by");
ALTER TABLE "cms_tree_type" ADD CONSTRAINT "cms_tree_type_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type" ("created_by");
ALTER TABLE "cms_tree_type" ADD CONSTRAINT "cms_tree_type_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type" ("updated_by");
ALTER TABLE "cms_tree_type" ADD CONSTRAINT "cms_tree_type__default_children_tree_type" FOREIGN KEY ("default_children_tree_type") REFERENCES "cms_tree_type" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type" ("default_children_tree_type");
ALTER TABLE "cms_tree_type_property" ADD CONSTRAINT "cms_tree_type_property__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property" ("updated_by");
ALTER TABLE "cms_tree_type_property" ADD CONSTRAINT "cms_tree_type_property_tree_type_id" FOREIGN KEY ("tree_type_id") REFERENCES "cms_tree_type" ("id") DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property" ("tree_type_id");
ALTER TABLE "cms_tree_type_property" ADD CONSTRAINT "cms_tree_type_property__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property" ("created_by");
ALTER TABLE "cms_tree_type_property2type" ADD CONSTRAINT "cms_tree_type_property2type__property_id" FOREIGN KEY ("cms_tree_type_property_id") REFERENCES "cms_tree_type_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property2type" ("cms_tree_type_property_id");
ALTER TABLE "cms_tree_type_property2type" ADD CONSTRAINT "cms_tree_type_property2type__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property2type" ("created_by");
ALTER TABLE "cms_tree_type_property2type" ADD CONSTRAINT "cms_tree_type_property2type__type_id" FOREIGN KEY ("cms_tree_type_id") REFERENCES "cms_tree_type" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property2type" ("cms_tree_type_id");
ALTER TABLE "cms_tree_type_property2type" ADD CONSTRAINT "cms_tree_type_property2type__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property2type" ("updated_by");
ALTER TABLE "cms_tree_type_property_enum" ADD CONSTRAINT "cms_tree_type_property_enum__updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property_enum" ("updated_by");
ALTER TABLE "cms_tree_type_property_enum" ADD CONSTRAINT "cms_tree_type_property_enum_property_id" FOREIGN KEY ("property_id") REFERENCES "cms_tree_type_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property_enum" ("property_id");
ALTER TABLE "cms_tree_type_property_enum" ADD CONSTRAINT "cms_tree_type_property_enum__created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_tree_type_property_enum" ("created_by");
ALTER TABLE "cms_user" ADD CONSTRAINT "cms_user_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user" ("created_by");
ALTER TABLE "cms_user" ADD CONSTRAINT "cms_user_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user" ("updated_by");
ALTER TABLE "cms_user" ADD CONSTRAINT "cms_user__image_id" FOREIGN KEY ("image_id") REFERENCES "cms_storage_file" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user" ("image_id");
ALTER TABLE "cms_user_authclient" ADD CONSTRAINT "fk_user_id" FOREIGN KEY ("user_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_authclient" ("user_id");
ALTER TABLE "cms_user_email" ADD CONSTRAINT "cms_user_email_user_id" FOREIGN KEY ("user_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_email" ("user_id");
ALTER TABLE "cms_user_phone" ADD CONSTRAINT "cms_user_phone_user_id" FOREIGN KEY ("user_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_phone" ("user_id");
ALTER TABLE "cms_user_property" ADD CONSTRAINT "cms_user_property_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_property" ("created_by");
ALTER TABLE "cms_user_property" ADD CONSTRAINT "cms_user_property_element_id" FOREIGN KEY ("element_id") REFERENCES "cms_user" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_property" ("element_id");
ALTER TABLE "cms_user_property" ADD CONSTRAINT "cms_user_property_property_id" FOREIGN KEY ("property_id") REFERENCES "cms_user_universal_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_property" ("property_id");
ALTER TABLE "cms_user_property" ADD CONSTRAINT "cms_user_property_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_property" ("updated_by");
ALTER TABLE "cms_user_universal_property" ADD CONSTRAINT "cms_user_universal_property_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_universal_property" ("created_by");
ALTER TABLE "cms_user_universal_property" ADD CONSTRAINT "cms_user_universal_property_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_universal_property" ("updated_by");
ALTER TABLE "cms_user_universal_property_enum" ADD CONSTRAINT "cms_user_universal_property_enum_created_by" FOREIGN KEY ("created_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_universal_property_enum" ("created_by");
ALTER TABLE "cms_user_universal_property_enum" ADD CONSTRAINT "cms_user_universal_property_enum_property_id" FOREIGN KEY ("property_id") REFERENCES "cms_user_universal_property" ("id") ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_universal_property_enum" ("property_id");
ALTER TABLE "cms_user_universal_property_enum" ADD CONSTRAINT "cms_user_universal_property_enum_updated_by" FOREIGN KEY ("updated_by") REFERENCES "cms_user" ("id") ON DELETE SET NULL ON UPDATE SET NULL DEFERRABLE INITIALLY DEFERRED;
CREATE INDEX ON "cms_user_universal_property_enum" ("updated_by");

-- Sequences --
CREATE SEQUENCE cms_component_settings_id_seq;
SELECT setval('cms_component_settings_id_seq', max(id)) FROM cms_component_settings;
ALTER TABLE "cms_component_settings" ALTER COLUMN "id" SET DEFAULT nextval('cms_component_settings_id_seq');
CREATE SEQUENCE cms_content_id_seq;
SELECT setval('cms_content_id_seq', max(id)) FROM cms_content;
ALTER TABLE "cms_content" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_id_seq');
CREATE SEQUENCE cms_content_element_id_seq;
SELECT setval('cms_content_element_id_seq', max(id)) FROM cms_content_element;
ALTER TABLE "cms_content_element" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_element_id_seq');
CREATE SEQUENCE cms_content_element2cms_user_id_seq;
SELECT setval('cms_content_element2cms_user_id_seq', max(id)) FROM cms_content_element2cms_user;
ALTER TABLE "cms_content_element2cms_user" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_element2cms_user_id_seq');
CREATE SEQUENCE cms_content_element_file_id_seq;
SELECT setval('cms_content_element_file_id_seq', max(id)) FROM cms_content_element_file;
ALTER TABLE "cms_content_element_file" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_element_file_id_seq');
CREATE SEQUENCE cms_content_element_image_id_seq;
SELECT setval('cms_content_element_image_id_seq', max(id)) FROM cms_content_element_image;
ALTER TABLE "cms_content_element_image" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_element_image_id_seq');
CREATE SEQUENCE cms_content_element_property_id_seq;
SELECT setval('cms_content_element_property_id_seq', max(id)) FROM cms_content_element_property;
ALTER TABLE "cms_content_element_property" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_element_property_id_seq');
CREATE SEQUENCE cms_content_element_tree_id_seq;
SELECT setval('cms_content_element_tree_id_seq', max(id)) FROM cms_content_element_tree;
ALTER TABLE "cms_content_element_tree" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_element_tree_id_seq');
CREATE SEQUENCE cms_content_property_id_seq;
SELECT setval('cms_content_property_id_seq', max(id)) FROM cms_content_property;
ALTER TABLE "cms_content_property" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_property_id_seq');
CREATE SEQUENCE cms_content_property2content_id_seq;
SELECT setval('cms_content_property2content_id_seq', max(id)) FROM cms_content_property2content;
ALTER TABLE "cms_content_property2content" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_property2content_id_seq');
CREATE SEQUENCE cms_content_property2tree_id_seq;
SELECT setval('cms_content_property2tree_id_seq', max(id)) FROM cms_content_property2tree;
ALTER TABLE "cms_content_property2tree" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_property2tree_id_seq');
CREATE SEQUENCE cms_content_property_enum_id_seq;
SELECT setval('cms_content_property_enum_id_seq', max(id)) FROM cms_content_property_enum;
ALTER TABLE "cms_content_property_enum" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_property_enum_id_seq');
CREATE SEQUENCE cms_content_type_id_seq;
SELECT setval('cms_content_type_id_seq', max(id)) FROM cms_content_type;
ALTER TABLE "cms_content_type" ALTER COLUMN "id" SET DEFAULT nextval('cms_content_type_id_seq');
CREATE SEQUENCE cms_dashboard_id_seq;
SELECT setval('cms_dashboard_id_seq', max(id)) FROM cms_dashboard;
ALTER TABLE "cms_dashboard" ALTER COLUMN "id" SET DEFAULT nextval('cms_dashboard_id_seq');
CREATE SEQUENCE cms_dashboard_widget_id_seq;
SELECT setval('cms_dashboard_widget_id_seq', max(id)) FROM cms_dashboard_widget;
ALTER TABLE "cms_dashboard_widget" ALTER COLUMN "id" SET DEFAULT nextval('cms_dashboard_widget_id_seq');
CREATE SEQUENCE cms_event_id_seq;
SELECT setval('cms_event_id_seq', max(id)) FROM cms_event;
ALTER TABLE "cms_event" ALTER COLUMN "id" SET DEFAULT nextval('cms_event_id_seq');
CREATE SEQUENCE cms_lang_id_seq;
SELECT setval('cms_lang_id_seq', max(id)) FROM cms_lang;
ALTER TABLE "cms_lang" ALTER COLUMN "id" SET DEFAULT nextval('cms_lang_id_seq');
CREATE SEQUENCE cms_site_id_seq;
SELECT setval('cms_site_id_seq', max(id)) FROM cms_site;
ALTER TABLE "cms_site" ALTER COLUMN "id" SET DEFAULT nextval('cms_site_id_seq');
CREATE SEQUENCE cms_site_domain_id_seq;
SELECT setval('cms_site_domain_id_seq', max(id)) FROM cms_site_domain;
ALTER TABLE "cms_site_domain" ALTER COLUMN "id" SET DEFAULT nextval('cms_site_domain_id_seq');
CREATE SEQUENCE cms_storage_file_id_seq;
SELECT setval('cms_storage_file_id_seq', max(id)) FROM cms_storage_file;
ALTER TABLE "cms_storage_file" ALTER COLUMN "id" SET DEFAULT nextval('cms_storage_file_id_seq');
CREATE SEQUENCE cms_tree_id_seq;
SELECT setval('cms_tree_id_seq', max(id)) FROM cms_tree;
ALTER TABLE "cms_tree" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_id_seq');
CREATE SEQUENCE cms_tree_file_id_seq;
SELECT setval('cms_tree_file_id_seq', max(id)) FROM cms_tree_file;
ALTER TABLE "cms_tree_file" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_file_id_seq');
CREATE SEQUENCE cms_tree_image_id_seq;
SELECT setval('cms_tree_image_id_seq', max(id)) FROM cms_tree_image;
ALTER TABLE "cms_tree_image" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_image_id_seq');
CREATE SEQUENCE cms_tree_property_id_seq;
SELECT setval('cms_tree_property_id_seq', max(id)) FROM cms_tree_property;
ALTER TABLE "cms_tree_property" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_property_id_seq');
CREATE SEQUENCE cms_tree_type_id_seq;
SELECT setval('cms_tree_type_id_seq', max(id)) FROM cms_tree_type;
ALTER TABLE "cms_tree_type" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_type_id_seq');
CREATE SEQUENCE cms_tree_type_property_id_seq;
SELECT setval('cms_tree_type_property_id_seq', max(id)) FROM cms_tree_type_property;
ALTER TABLE "cms_tree_type_property" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_type_property_id_seq');
CREATE SEQUENCE cms_tree_type_property2type_id_seq;
SELECT setval('cms_tree_type_property2type_id_seq', max(id)) FROM cms_tree_type_property2type;
ALTER TABLE "cms_tree_type_property2type" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_type_property2type_id_seq');
CREATE SEQUENCE cms_tree_type_property_enum_id_seq;
SELECT setval('cms_tree_type_property_enum_id_seq', max(id)) FROM cms_tree_type_property_enum;
ALTER TABLE "cms_tree_type_property_enum" ALTER COLUMN "id" SET DEFAULT nextval('cms_tree_type_property_enum_id_seq');
CREATE SEQUENCE cms_user_id_seq;
SELECT setval('cms_user_id_seq', max(id)) FROM cms_user;
ALTER TABLE "cms_user" ALTER COLUMN "id" SET DEFAULT nextval('cms_user_id_seq');
CREATE SEQUENCE cms_user_authclient_id_seq;
SELECT setval('cms_user_authclient_id_seq', max(id)) FROM cms_user_authclient;
ALTER TABLE "cms_user_authclient" ALTER COLUMN "id" SET DEFAULT nextval('cms_user_authclient_id_seq');
CREATE SEQUENCE cms_user_email_id_seq;
SELECT setval('cms_user_email_id_seq', max(id)) FROM cms_user_email;
ALTER TABLE "cms_user_email" ALTER COLUMN "id" SET DEFAULT nextval('cms_user_email_id_seq');
CREATE SEQUENCE cms_user_phone_id_seq;
SELECT setval('cms_user_phone_id_seq', max(id)) FROM cms_user_phone;
ALTER TABLE "cms_user_phone" ALTER COLUMN "id" SET DEFAULT nextval('cms_user_phone_id_seq');
CREATE SEQUENCE cms_user_property_id_seq;
SELECT setval('cms_user_property_id_seq', max(id)) FROM cms_user_property;
ALTER TABLE "cms_user_property" ALTER COLUMN "id" SET DEFAULT nextval('cms_user_property_id_seq');
CREATE SEQUENCE cms_user_universal_property_id_seq;
SELECT setval('cms_user_universal_property_id_seq', max(id)) FROM cms_user_universal_property;
ALTER TABLE "cms_user_universal_property" ALTER COLUMN "id" SET DEFAULT nextval('cms_user_universal_property_id_seq');
CREATE SEQUENCE cms_user_universal_property_enum_id_seq;
SELECT setval('cms_user_universal_property_enum_id_seq', max(id)) FROM cms_user_universal_property_enum;
ALTER TABLE "cms_user_universal_property_enum" ALTER COLUMN "id" SET DEFAULT nextval('cms_user_universal_property_enum_id_seq');

-- Full Text keys --

COMMIT;
