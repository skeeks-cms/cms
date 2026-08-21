<?php

$behavior = file_get_contents(dirname(__DIR__).'/src/behaviors/CmsLogBehavior.php');
$log = file_get_contents(dirname(__DIR__).'/src/models/CmsLog.php');

if (strpos($behavior, "'lock_version'") === false) {
    throw new RuntimeException('CmsLogBehavior must exclude optimistic-lock counters from new logs.');
}

if (strpos($log, "ArrayHelper::remove(\$dataValues, 'lock_version')") === false) {
    throw new RuntimeException('CmsLog must hide optimistic-lock counters stored in historical logs.');
}

if (substr_count($log, '$this->getRenderableDataValues()') !== 3) {
    throw new RuntimeException('Every structured CmsLog rendering branch must filter technical fields.');
}

echo "CMS log technical fields: ok\n";
