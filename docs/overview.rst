========
Overview
========

Requirements
============

Software
~~~~~~~~~~~
* apache
* mod_rewrite apache module
* php >= 5.5
* DB mysql ~ 5.5
* SSH access

Php modules
~~~~~~~~~~~~~
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
~~~~~~~~~~~~~~~~
* short_open_tag on

.. _installation:


Installation
============

1. Installation composer
~~~~~~~~~~~~~~~~~~~~~~~~~

The recommended way to install SkeekS CMS is with
`Composer <http://getcomposer.org>`_. Composer is a dependency management tool
for PHP that allows you to declare the dependencies your project needs and
installs them into your project.

.. code-block:: bash

    # Install Composer
    curl -sS https://getcomposer.org/installer | php


2. Installation files
~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: bash

    # Download latest version of composer
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '55d6ead61b29c7bdee5cccfb50076874187bd9f21f65d8991d46ec5cc90518f447387fb9f76ebae1fbbacf329e583e30') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    COMPOSER_HOME=.composer php composer-setup.php
    php -r "unlink('composer-setup.php');"

    # Installing the base project SkeekS CMS
    COMPOSER_HOME=.composer php composer.phar create-project --no-install --prefer-dist skeeks/app-basic example.com
    # Going into the project folder
    cd demo.ru
    # Download latest version of composer in project
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '55d6ead61b29c7bdee5cccfb50076874187bd9f21f65d8991d46ec5cc90518f447387fb9f76ebae1fbbacf329e583e30') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    COMPOSER_HOME=.composer php composer-setup.php
    php -r "unlink('composer-setup.php');"

    # Extra plug-ins
    COMPOSER_HOME=.composer php composer.phar global require fxp/composer-asset-plugin --no-plugins
    # Enter your github api key in composer.json
    # Download dependency
    COMPOSER_HOME=.composer php composer.phar install -o
    # Run the command to initialize the project, the installer executable file and the necessary rights to the directory
    php yii cms/init


3. Configuring the database
~~~~~~~~~~~~~~~~~~~~~~~~~~~~


Edit the file to access the database, it is located at **common/config/db.php**

4. Installation of migrations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: bash

    #Installation of ready-dump
    php yii dbDumper/mysql/restore


5. Configuring the server
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, your site opens at **//example.com/frontend/web/**

On hostings are configured by default under the usual sites.

But it can be reconfigured (and even necessary) in detail about this here: Server Configuration (web-server)


6. Authorization system
~~~~~~~~~~~~~~~~~~~~~~~~~~~~
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


Reporting a security vulnerability
==================================
Publicly disclosing a vulnerability can put the entire community at risk. If
you've discovered a security concern, please email us at
support@skeeks.com.

After a security vulnerability has been corrected, a security hotfix release will
be deployed as soon as possible.


Work with documents
==================================

Этот раздел тут временно

.. code-block:: bash

    make gettext
    make html
    sphinx-intl update -p _build/gettext -l ru
    make -e SPHINXOPTS="-D language='ru'" html