<?php
return [

    'cms/db/refresh' =>
    [
        'description'       => 'Инвалидация кэша структуры таблиц',
        'agent_interval'    => 3600*3, //раз в три часа
        'next_exec_at'      => \Yii::$app->formatter->asTimestamp(time()) + 3600*3,
        'is_period'         => "N"
    ],

    'cms/cache/flush-runtimes' =>
    [
        'description'       => 'Чистка временных диррикторий',
        'agent_interval'    => 3600*24,
        'next_exec_at'      => \Yii::$app->formatter->asTimestamp(time()) + 3600*24,
        'is_period'         => "N"
    ],

    'cms/db/dump' =>
    [
        'description'       => 'Бэкап базы данных',
        'agent_interval'    => 3600*24, //раз в три часа
        'next_exec_at'      => \Yii::$app->formatter->asTimestamp(time()) + 3600*24,
        'is_period'         => "N"
    ]

];