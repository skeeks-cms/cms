<?php
if (file_exists(TMP_CONSOLE_CONFIG_FILE_EXTENSIONS))
{
    return (array) require(TMP_CONSOLE_CONFIG_FILE_EXTENSIONS);
} else
{
    return (array) require(__DIR__ . '/config/main-console.php');
}
