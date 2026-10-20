<?php

$controller = file_get_contents(dirname(__DIR__).'/src/controllers/AdminCmsDocumentController.php');

function cmsDocumentRequisiteAddressExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

cmsDocumentRequisiteAddressExpect(
    strpos($controller, "'seller_contractor_address' => [") !== false,
    'The document form does not expose its seller address snapshot.'
);
cmsDocumentRequisiteAddressExpect(
    strpos($controller, "'buyer_contractor_address' => [") !== false,
    'The document form does not expose its buyer address snapshot.'
);
cmsDocumentRequisiteAddressExpect(
    substr_count($controller, 'Карточка контрагента и связанный счёт не изменятся.') === 2,
    'Document address fields must explain their snapshot-only scope.'
);

echo "CMS document requisite address fields contract: OK\n";
