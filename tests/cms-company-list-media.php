<?php

$controller = file_get_contents(dirname(__DIR__).'/src/controllers/AdminCmsCompanyController.php');

function companyListMediaExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

companyListMediaExpect(
    strpos($controller, 'BackendEntityMedia::widget') !== false,
    'Company rows do not use canonical backend entity media.'
);
companyListMediaExpect(
    strpos($controller, "'icon'  => 'building'") !== false,
    'Company rows do not use the semantic building fallback.'
);
companyListMediaExpect(
    strpos($controller, 'Image::getCapSrc()') === false,
    'Company rows still render the legacy camera placeholder.'
);

echo "CMS company list media contract: OK\n";
