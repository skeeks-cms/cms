<?php

$dealController = file_get_contents(dirname(__DIR__).'/src/controllers/AdminCmsDealController.php');
$billController = file_get_contents(dirname(__DIR__).'/src/controllers/AdminCmsBillController.php');
$documentController = file_get_contents(dirname(__DIR__).'/src/controllers/AdminCmsDocumentController.php');
$paymentController = file_get_contents(dirname(__DIR__, 2).'/cms-shop/src/controllers/AdminPaymentController.php');

function cmsFinanceListPolishExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

cmsFinanceListPolishExpect(
    strpos($dealController, "min-width: 200px; padding-left: 24px;") !== false,
    'The deal client column must retain visual separation from the amount.'
);
cmsFinanceListPolishExpect(
    strpos($paymentController, "sx-preview-card__related sx-collection-cell--inline") !== false
    && strpos($paymentController, "'style' => 'white-space: nowrap;'") !== false,
    'Payment client icons and labels must stay together on one line.'
);
cmsFinanceListPolishExpect(
    strpos($documentController, "'client',") !== false
    && strpos($documentController, "'label' => 'Клиент / Плательщик'") !== false,
    'Documents must expose one combined client and payer column.'
);
cmsFinanceListPolishExpect(
    strpos($billController, "'label'  => 'Клиент / Плательщик'") !== false,
    'Bills must capitalize the Payer label consistently.'
);

echo "CMS finance list polish contract: OK\n";
