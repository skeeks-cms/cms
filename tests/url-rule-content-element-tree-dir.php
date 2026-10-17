<?php

$source = file_get_contents(dirname(__DIR__).'/src/components/urlRules/UrlRuleContentElement.php');

function urlRuleTreeDirExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$parse = substr($source, strpos($source, 'function parseRequest'));
$parse = substr($parse, 0, strpos($parse, 'function isTreeDir'));

urlRuleTreeDirExpect(
    substr_count($parse, 'if ($this->isTreeDir($pathInfo)) {') === 2,
    'Both element URL patterns must yield to an existing section path.'
);
urlRuleTreeDirExpect(
    strpos($parse, 'isTreeDir') > strpos($parse, "'/\\/(?<code>\\S+)\\-(?<id>\\d+)\$/i'"),
    'The section check must run only after the element pattern matched.'
);
urlRuleTreeDirExpect(
    strpos($source, '"cms_site_id" => $siteId') !== false && strpos($source, 'getTableCacheTagCmsSite()') !== false,
    'The section lookup must be limited to the current site and cached by the tree tag.'
);

echo "CMS element URL rule yields to section paths: OK\n";
