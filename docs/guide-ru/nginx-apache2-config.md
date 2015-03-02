Apache + Nginx рекоммендации
============================

Подробное конфигурирование пока не даем. Но важно понимать, что схема должны быть следующей

- `/var/www/sites/test.ru/frontend/web/` and using the URL `http://test.ru/`

Пока оставлю это здесь (рабочие примеры).

Nginx config
------------

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

        include backends/apache2/php55.conf;
        include contrib/protected.conf;
        include contrib/static-files.conf;
        include /contrib/error-404.conf;
}

```

Apache config
------------

```

# #########################################################
# @date 13.09.14
# @copyright skeeks.com
# @author Semenov Alexander <semenov@skeeks.com>
# #########################################################

<VirtualHost *:8080>
    ServerAdmin admin@skeeks.com


    ServerName main.blank.cms.skeeks.com
    ServerAlias *.main.blank.cms.skeeks.com

    DocumentRoot /var/www/sites/main.blank.cms.skeeks.com/frontend/web

    <Directory /var/www/sites/main.blank.cms.skeeks.com/frontend/web>
        Options Indexes FollowSymLinks
        AllowOverride All
        Order allow,deny
        allow from all
    </Directory>

    Header set X-Apache2-RT %D

    ErrorLog ${APACHE_LOG_DIR}/main.blank.cms.skeeks.com-error.log
    LogLevel warn
    CustomLog /dev/null combined

</VirtualHost>


```