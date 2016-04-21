<?php
if (file_exists(TMP_CONFIG_FILE_EXTENSIONS))
{
    return (array) require(TMP_CONFIG_FILE_EXTENSIONS);
} else
{
    return (array) require(__DIR__ . '/config/main.php');
}
