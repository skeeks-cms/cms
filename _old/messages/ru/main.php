<?php
return
[
    "Check availability file-storages" => "Проверка доступности файловых хранилищ",
    "The site has a file storage. It contains all downloaded files. It also consists of a storage cluster (separate servers for file storage). If the site is not connected to the servers, then when you add files to the sections, publications, etc. errors will occur." => "На сайте есть файловое хранилище.
    В него попадают все загруженные файлы.
    Так же это хранилище состоит из кластеров (отдельных серверов для хранения файлов).
    Если к сайту не подключены сервера, то при добавлении файлов, к разделам, публикациям и т.д. будет происходить с ошибками.",
    "No available servers"  => "Нет доступных серверов",
    "Connected servers"     => "Подключено серверов",
    "Server {server} {d} available space" => "Сверер {server} {d} доступно места",

    "Check availability {git}" => "Проверка наличия {git}",
    "To work correctly the update, requires a git client" => "Для корректной работы обновлений требуется наличие git клиента",
    "The git client is not installed at the server" => "На сервере не установлен git клиент",

    "Checking availability installation scripts" => "Проверка наличия установочных скриптов",
    "After installation it is recommended to remove the directory with installation script." => "После установки проекта рекоммендуется удалить диррикторию с установочными скриптами.",
    "You must remove the installation script" => "Необходимо удалить скрипт установщик",

    "Excess text into console" => "Лишний текст в консоль",

    "Check {php} and {notice} in the {console}" => "Проверка {php} и {notice} в {console}",
    "Checks console commands." => "Осуществляется проверка консольных команд.",

    "Sending e-mail messages larger than 64 KB (function {mail})" => "Отправка почтового сообщения больше 64Кб (функция {mail})",
    "Unable to retrieve the contents of the file" => "Не удалось получить содержимое файла",
    "Sent. Dispatch time: {s} sec." => "Отправлено. Время отправки: {s} сек.",
    "The letter has not been sent." => "Письмо не отправлено.",

    "Sending mail (function {mail})" => "Отправка почты (функция {mail})",
    "The system is transmitting a test letter to the postal address {email} through a standard php function {mail}." => "Осуществляется передача тестового письма на почтовый адрес {email} через стандартную php функцию {mail}.",
    "Created special mailbox, for maximality testing for real work." => "Чтобы максимально приблизить тест к реальной работе почты, заведен служебный ящик.",
    "As a test message text is transferred the source code of the script checking site." => "В качестве тестового текста письма передается исходный код скрипта проверки сайта.",
    "No user data is not transmitted!" => "Никакие пользовательские данные не передаются!",
    "Please note that the test does not check the delivery letter in the mailbox. Moreover, it is impossible to test the delivery of mail to other mail servers." => "Обратите внимание, что тест не проверяет доставку письма в почтовый ящик. Более того, нельзя протестировать доставку почты на другие почтовые сервера.",
    "If the time of sending the letter more than a second, it can significantly slow down the work site. Contact your hosting provider to set up a pending request to send mail (through the spooler), or turn on the transfer of mail (and the work of agents) through {cron}. To do this we must add the constant into {file}:" => "Если время отправки письма больше секунды, это может значительно затормозить работу сайта. Обратитесь к хостеру с просьбой настроить отложенную отправку почты (через спулер) или включите передачу почты (и работу агентов) через {cron}. Для этого в {file} надо добавить константу:",

    "Version MySQL server"  => "Версия MySQL сервера",
    "Known versions of MySQL with errors that prevent normal operation of the site:" => "Известны версии MySQL с ошибками, препятствующими нормальной работе сайта:",
    "incorrect method works {ex}, search does not work properly" => "некорректно работает метод {ex}, поиск работает неправильно",
    "Step auto_increment default is 2, requires 1" => "шаг auto_increment по умолчанию равен 2, требуется 1",
    "Update MySQL, if you have one of these versions." => "Обновите MySQL, если у вас установлена одна их этих версий.",
    "MySQL installed version {cur}, {req} is required" => "Установлена MySQL версии {cur}, требуется {req}",
    "Problem version of the database" => "Проблемная версия БД",
    "The current version of the database" => "Текущая версия БД",

    "Checking availability {mysqldump}" => "Проверка наличия {mysqldump}",
    "To work correctly the update, requires a {mysqldump}" => "Для корректной работы обновлений требуется наличие {mysqldump}",
    "The {obj} is not installed at the server" => "На сервере не установлен {obj}",

    "Time at database and web server" => "Время на БД и веб сервере",
    "Compares the system time database and web server. It may be of unsync when they are installed on different physical machines, but more often as a result of improper installation time zone." => "Сравнивается системное время базы данных и веб-сервера. Рассинхронизация может быть, когда они установлены на разные физические машины, но чаще всего в результате неправильной установки часового пояса.",
    "Time is different for {diff} seconds" => "Время отличается на {diff} секунд",

    "Availability of required modules {php}" => "Наличие необходимых модулей {php}",
    "To solve the problem, refer to the host, and for the local installation to independently install the required extension on the basis of documentation at website {site}" => "Для решения проблемы необходимо обратиться к хостеру, а для локальной установки самостоятельно установить требуемые расширения на основе документации на сайте {site}",
    "Not installed required extensions" => "Не установлены требуемые расширения",
    "Functions to work with sockets" => "Функции для работы с сокетами",
    "Support for regular expressions" => "Поддержка регулярных выражений",
    "GD Library"            => "Библиотека GD",
    "Jpeg support in GD"    => "Поддержка jpeg в GD",
    "The encryption function {mcrypt}" => "Функции шифрования {mcrypt}",
    "{p} support"           => "Поддержка {p}",
    "{ssl} support is not configured in {php}" => "Поддержка {ssl} не настроена в {php}",
    "Do not set extension {ext}. Will not work on the file download link (for those files which can not parse file extension in the url, for example {smpl}" => "Не установлено расширение {ext}. Не будет работать загрузка файлов по ссылке (для тех файлов где не удается спарсить расширение файла в url, например {smpl}",
    "Extension {ext} is installed" => "Расширение {ext} установлено",

    "Required parameters PHP" => "Обязательные параметры PHP",
    "Checks critical parameters defined in the configuration file php.ini. If an error occurs, shows a list of parameters that are not configured correctly. For details on each parameter can be found at php.net." => "Проверяются критические значения параметров, определяемых в файле настроек php.ini. В случае ошибки выводится список параметров, которые настроены неправильно. Подробную информацию по каждому параметру можно найти на сайте php.net.",
    "Incorrect settings"    => "Настройки неправильные",
    "Settings are correct"      => "Настройки правильные",
    "Installed version of PHP {cur}, {req] or higher is required" => "Установлена версия PHP {cur], требуется {req} и выше",
    "Parameter {p} = {v}, required {r}" => "Параметр {p} = {v}, требуется {r}",
    "{var} value should not be less than {max}. Current value" => "Значение {var} должно быть не ниже {max}. Текущее значение",
    "Current delimiter: {delim}, {delim2} is required" => "Текущий разделитель: {delim}, требуется {delim2}",
    "Parameter {p} has invalid value" => "Параметр {p} имеет неверное значение",
    "Loaded module {m}, there may be problems work in the administrative part ({s})" => "Загружен модуль {m}, возможны проблемы в работе административной части ({s})",
    "Not possible to change the value {v} through {f}" => "Нет возможности изменить значение {v} через {f}",

    "Sending mail (through the object {obj})" => "Отправка почты (через объект {obj})",
    "The system is transmitting a test letter to the postal address {email} through the library {obj}." => "Осуществляется передача тестового письма на почтовый адрес {email} через библиотеку {obj}.",

    "Web-server modules"    => "Модули веб-сервера",
    "Apache mod_security module like module php suhosin designed to protect the site from hackers, but in practice it often interferes with normal operation of the site. It is recommended to turn it off, instead, to use the module of proactive protection Skeeks CMS." => "Модуль Apache mod_security подобно модулю php suhosin призван защищать сайт от атак хакеров, но на практике он чаще препятствует нормальной работе сайта.
Рекомендуется его отключить, вместо него использовать модуль проактивной защиты Skeeks CMS.",
    "Identified conflicts"  => "Выявленные конфликты",
    "No conflicts found"    => "Конфликтов не выявлено",
    "Loaded module {m}, there may be problems in the work administrative part" => "Загружен модуль {m}, возможны проблемы в работе административной части",
    "Loaded module {m}, {m1} will not work" => "Загружен модуль {m}, {m1} не будет работать",

    "The values of server variables" => "Значения переменных сервера",
    "Check the values of variables defined by the web server." => "Проверяются значения переменных, определяемых веб сервером.",
    "value HTTP_HOST is taken based on the name of this virtual host (domain). Invalid domain leads to the fact that some browsers (ie, Internet Explorer 6) refuse to maintain his cookie, as a consequence - not stored authorization." => "Значение HTTP_HOST берется на основе имени текущего виртуального хоста (домена). Невалидный домен приводит к тому, что некоторые браузеры (например, Internet Explorer 6) отказываются сохранять для него cookie, как следствие - не сохраняется авторизация.",
    "Incorrect"               => "Не корректные",
    "Correct"                 => "Корректные",
    "The current domain is not valid ({val}). It may only contain numbers, letters and hyphens. It must contain the point." => "Текущий домен не валидный ({val}). Может содержать только цифры, латинские буквы и дефис. Должен содержать точку.",

    "Saved sessions"        => "Сохранение сессии",
    "Checking the ability to store data on the server using the session mechanism. This basic ability necessary to preserve authorization between hits." => "Проверяется возможность хранить данные на сервере используя механизм сессий. Эта базовая возможность необходима для сохранения авторизации между хитами.",
    "Sessions may not work if their support is not installed, in php.ini contains the incorrect folder to store the sessions or it is not available on the record." => "Сессии могут не работать, если их поддержка не установлена, в php.ini неправильно указана папка для хранения сессий или она не доступна на запись.",
    "Could not to keep the session" => "Не получилось сохранить сессию",

    "Checking kernel and libraries modification" => "Проверка модификации ядра и библиотек",
    "Checks, changes kernel {cms} and third-party libraries (Folder {folder}). Folder location and the name given by the global constant VENDOR_DIR. For the current project:" => "Осуществаляется проверка, изменения ядра {cms} и сторонних библиотек (Папка {folder}). Расположение папки и ее название задаются глобальной константой VENDOR_DIR. Для текущего проекта:",
    "We strongly not recommend to modify the core of the project, as it can bring to the update failed, or your modifications will be removed during the upgrade process. That in turn may result in errors of work the project." => "Мы настоятельно не рекоммендуем модифицировать ядро проекта, поскольку это может привезти к ошибкам обновления, или же ваши модификации будут удалены в процессе обновления. Что в свою очередь, может привести к ошибкам работы проекта.",
    "To solve the problem, you can run the command in the console" => "Для решения проблемы, можно запустить команду в консоле",
    "Found modified kernel" => "Найдены модификации ядра",
    "The kernel has not been modified" => "Ядро не модифицировалось",
    "Found an error in the process of console commands, check the kernel modification can not be started." => "Найдены ошибки в процессе работы консольных комманд, проверка модификации ядра не может быть запущена.",

];