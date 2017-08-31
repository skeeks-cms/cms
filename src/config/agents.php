<?php
return [
    'cms/cache/flush-all' =>
    [
        'description'       => 'Чистка кэша',
        'agent_interval'    => 3600*24,
    ],
    
    'ajaxfileupload/cleanup' =>
    [
        'description'       => 'Чистка временно загружаемых файлов',
        'agent_interval'    => 3600*24,
    ],
];