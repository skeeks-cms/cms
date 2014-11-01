Структура типового проекта
===================================

Структура папок
----------------
На базе одного проекта есть возможность запуска многих приложений, для этого необходимо соблюдать следующую структуру папок.

```
common              содержит общие файлы всех приложений
    config/              содержит общие конфигурационные файлы приложений
    mail/                содержит файлы представлений для электронной почты
    models/              содержит классы моделей, используемые во всех приложениях
console             консольное приложение, скрипты для крона и прочее
    config/              содержит конфигурационные файлы
    controllers/         содержит коносльные контроллеры (commands)
    migrations/          содержит миграции
    models/              contains console-specific model classes
    runtime/             contains files generated during runtime
frontend            приложение 1
    assets/              contains application assets such as JavaScript and CSS
    config/              contains frontend configurations
    controllers/         contains Web controller classes
    models/              contains frontend-specific model classes
    runtime/             contains files generated during runtime
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
    widgets/             contains frontend widgets
backend             приложение 2
    assets/              contains application assets such as JavaScript and CSS
    config/              contains backend configurations
    controllers/         contains Web controller classes
    models/              contains backend-specific model classes
    runtime/             contains files generated during runtime
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
vendor/                  contains dependent 3rd-party packages
environments/            contains environment-based overrides
tests                    contains various tests for the advanced application
    codeception/         contains tests developed with Codeception PHP Testing Framework
```


> [![skeeks!](https://gravatar.com/userimage/74431132/13d04d83218593564422770b616e5622.jpg)](http://www.skeeks.com)  
<i>Web development has never been so fun!</i>  
[www.skeeks.com](http://www.skeeks.com)
