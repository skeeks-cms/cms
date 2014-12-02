Apache + Nginx рекоммендации
============================

Подробное конфигурирование пока не даем. Но важно понимать, что схема должны быть следующей

- for application 1 `/var/www/sites/test.ru/frontend/web/` and using the URL `http://test.ru/`
- for application 2 `/var/www/sites/test.ru/backend/web/` and using the URL `http://application2.test.ru/`

Пока оставлю это здесь.

```
# #########################################################
# Nginx config
# @date 13.09.14
# @copyright skeeks.com
# @author Semenov Alexander <semenov@skeeks.com>
# #########################################################

server {
    set $serverName "main.blank.cms.skeeks.com";
    server_name main.blank.cms.skeeks.com *.main.blank.cms.skeeks.com
    ;

        set $root "/var/www/sites/$serverName/frontend/web";
        root $root;
        index index.html index.htm index.php;

        include /var/www/libs/_skeeks/additional/nginx/conf/vz/backends/apache2/php55.conf;
        include /var/www/libs/_skeeks/additional/nginx/conf/vz/contrib/protected.conf;
        include /var/www/libs/_skeeks/additional/nginx/conf/vz/contrib/static-files.conf;
        include /var/www/libs/_skeeks/additional/nginx/conf/vz/contrib/error-404.conf;


        access_log off;
        error_log /var/log/nginx/errors-main.blank.cms.skeeks.com.log;
}

```