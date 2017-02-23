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
~~~~~~~~~~~~~~~
* short_open_tag on


.. note::

    Не забудте

.. _installation:


Installation
============

The recommended way to install Guzzle is with
`Composer <http://getcomposer.org>`_. Composer is a dependency management tool
for PHP that allows you to declare the dependencies your project needs and
installs them into your project.

.. code-block:: bash

    # Install Composer
    curl -sS https://getcomposer.org/installer | php

You can add Guzzle as a dependency using the composer.phar CLI:

.. code-block:: bash

    php composer.phar require guzzlehttp/guzzle:~6.0

Alternatively, you can specify Guzzle as a dependency in your project's
existing composer.json file:

.. code-block:: js

    {
      "require": {
         "guzzlehttp/guzzle": "~6.0"
      }
   }

After installing, you need to require Composer's autoloader:

.. code-block:: php

    require 'vendor/autoload.php';

You can find out more on how to install Composer, configure autoloading, and
other best-practices for defining dependencies at `getcomposer.org <http://getcomposer.org>`_.


.. code-block:: js

   {
      "require": {
         "guzzlehttp/guzzle": "~6.0@dev"
      }
   }

Update
============


Reporting a security vulnerability
==================================
Publicly disclosing a vulnerability can put the entire community at risk. If
you've discovered a security concern, please email us at
support@skeeks.com.

After a security vulnerability has been corrected, a security hotfix release will
be deployed as soon as possible.
