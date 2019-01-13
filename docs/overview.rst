========
Overview
========

Requirements
============

Software
~~~~~~~~
* apache (mod_rewrite) or php-fpm
* php >= 7.0
* DB mysql ~ 5.5

Php modules
~~~~~~~~~~~
* mbstring
* xml
* pdo
* pdo_mysql
* json
* phar
* simplexml
* timezonedb
* gd или imagik
* intl
* mcrypt
* fileinfo
* curl

Php settings
~~~~~~~~~~~~
* short_open_tag on

.. attention::

    Важно не забудьте проверить эту настройку!

Прочее
~~~~~~
* SSH доступ

.. _installation:


Installation
============

1. Installation composer
~~~~~~~~~~~~~~~~~~~~~~~~

The recommended way to install SkeekS CMS is with
`Composer <http://getcomposer.org>`_. Composer is a dependency management tool
for PHP that allows you to declare the dependencies your project needs and
installs them into your project.

If you do not have Composer, follow the instructions in the `Installing Yii <https://github.com/yiisoft/yii2/blob/master/docs/guide/start-installation.md#installing-via-composer>`_ section of the definitive guide to install it.

.. code-block:: bash

    # Install Composer
    curl -sS https://getcomposer.org/installer | COMPOSER_HOME=.composer php


.. note::

    Alternative commands, depending on the server configuration and your access rights:

.. code-block:: bash

    #composer if not installed globally, you can use this command
    COMPOSER_HOME=.composer php composer.phar
    # or use if composer installed globally
    composer

.. code-block:: bash

    php yii
    # or use (file yii must be executable)
    yii


2. Installation files
~~~~~~~~~~~~~~~~~~~~~

Establish **example.com** site in example.com folder

Navigate to the folder where are your projects (such as **/var/www/sites/**).

.. code-block:: bash

    # Download latest version of composer
    curl -sS https://getcomposer.org/installer | COMPOSER_HOME=.composer php

    # Installing the base project SkeekS CMS
    COMPOSER_HOME=.composer php composer.phar create-project --prefer-dist --stability=dev skeeks/app-basic demo.ru
    # Going into the project folder
    cd demo.ru


3. Configuring the database
~~~~~~~~~~~~~~~~~~~~~~~~~~~


Edit the file to access the database, it is located at **common/config/db.php**

Update configs:

.. code-block:: bash

    COMPOSER_HOME=.composer php composer.phar self-update && COMPOSER_HOME=.composer php composer.phar du


4. Installation of migrations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: bash

    #Installation of ready-dump
    php yii migrate -t=migration_install -p=backup/migrations


5. Configuring the server
~~~~~~~~~~~~~~~~~~~~~~~~~

By default, your site opens at **//example.com/frontend/web/**

On hostings are configured by default under the usual sites.

But it can be reconfigured (and even necessary) in detail about this here: Server Configuration (web-server)


6. Authorization system
~~~~~~~~~~~~~~~~~~~~~~~
Default management system is available at the following address (if desired, it can be reconfigured)

**//example.com/~sx/admin/auth/**

**root** (login)

**skeeks** (password)

7. Check the working environment
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If the installation process has been completed, but there are still not clear to you the error, it is likely that something is wrong is configured on the server.
To do so, download to /frontend/web/ and run the file to test https://github.com/skeeks-cms/cms/blob/master/requirements.php environment.
//example.com/frontend/web/requirements.php or //example.com/requirements.php (depends on item 4).

.. attention::

    It is important to remember to check the setting of php: **short_open_tag on**


Update
============

Standart update
~~~~~~~~~~~~~~~

.. code-block:: bash

    # Composer update to the latest stable version
    COMPOSER_HOME=.composer php composer.phar self-update && COMPOSER_HOME=.composer php composer.phar update -o

Configuring Web Servers
=======================
.. note::
    Info: You may skip this subsection for now if you are just test driving Yii with no intention of deploying it to a production server.

The application installed according to the above instructions should work out of box with either an Apache HTTP server or an Nginx HTTP server, on Windows, Mac OS X, or Linux running PHP 5.5 or higher. Yii 2.0 is also compatible with facebook's HHVM. However, there are some edge cases where HHVM behaves different than native PHP, so you have to take some extra care when using HHVM.

On a production server, you may want to configure your Web server so that the application can be accessed via the URL **//www.example.com/index.php** instead of **//www.example.com/frontend/web/index.php**. Such configuration requires pointing the document root of your Web server to the basic/web folder. You may also want to hide index.php from the URL, as described in the Routing and URL Creation section. In this subsection, you'll learn how to configure your Apache or Nginx server to achieve these goals.

Recommended Apache Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use the following configuration in Apache's httpd.conf file or within a virtual host configuration. Note that you should replace path/to/basic/web with the actual path for basic/web.


.. code-block:: bash

    # Set document root to be "frontend/web"
    DocumentRoot "path/to/frontend/web"

    <Directory "path/to/frontend/web">
        # use mod_rewrite for pretty URL support
        RewriteEngine on
        # If a directory or a file exists, use the request directly
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        # Otherwise forward the request to index.php
        RewriteRule . index.php

        # ...other settings...
    </Directory>

Recommended Nginx Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To use Nginx, you should install PHP as an FPM SAPI. You may use the following Nginx configuration, replacing path/to/frontend/web with the actual path for frontend/web and mysite.local with the actual hostname to serve.

.. code-block:: bash

    server {
        charset utf-8;
        client_max_body_size 128M;

        listen 80; ## listen for ipv4
        #listen [::]:80 default_server ipv6only=on; ## listen for ipv6

        server_name mysite.local;
        root        /path/to/frontend/web;
        index       index.php;

        access_log  /path/to/frontend/log/access.log;
        error_log   /path/to/frontend/log/error.log;

        location / {
            # Redirect everything that isn't a real file to index.php
            try_files $uri $uri/ /index.php$is_args$args;
        }

        # uncomment to avoid processing of calls to non-existing static files by Yii
        #location ~ \.(js|css|png|jpg|gif|swf|ico|pdf|mov|fla|zip|rar)$ {
        #    try_files $uri =404;
        #}
        #error_page 404 /404.html;

        # deny accessing php files for the /assets directory
        location ~ ^/assets/.*\.php$ {
            deny all;
        }

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_pass 127.0.0.1:9000;
            #fastcgi_pass unix:/var/run/php5-fpm.sock;
            try_files $uri =404;
        }

        location ~* /\. {
            deny all;
        }
    }

When using this configuration, you should also set cgi.fix_pathinfo=0 in the php.ini file in order to avoid many unnecessary system stat() calls.

Also note that when running an HTTPS server, you need to add fastcgi_param HTTPS on; so that Yii can properly detect if a connection is secure.


Reporting a security vulnerability
==================================
Publicly disclosing a vulnerability can put the entire community at risk. If
you've discovered a security concern, please email us at
support@skeeks.com.

After a security vulnerability has been corrected, a security hotfix release will
be deployed as soon as possible.


Work with documents
===================

Для написания документации используется генератор документации `Sphinx <http://sphinx-ru.readthedocs.io/ru/latest/>`_.
Стандартный синтаксис разметки `reStructuredText <http://sphinx-ru.readthedocs.io/ru/latest/rst-markup.html>`_

Полезные команды для генерации:

.. code-block:: bash

    apt-get install python-pip
    pip install Sphinx
    pip install sphinx-intl
    pip install sphinx_rtd_theme

    make gettext
    make html
    sphinx-intl update -p _build/gettext -l ru
    #make -e SPHINXOPTS="-D language='ru'" html

    sphinx-build -D language='ru' ./ build/ru
    sphinx-build ./ build/en