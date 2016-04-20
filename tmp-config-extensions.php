<?php
$tmpConfig = [];
if (file_exists(TMP_CONFIG_FILE_EXTENSIONS))
{
    $tmpConfig = unserialize(file_get_contents(TMP_CONFIG_FILE_EXTENSIONS));
} else
{
    return (array) require(__DIR__ . '/config/main.php');
}
return $tmpConfig;
