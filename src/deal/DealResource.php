<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\deal;

use yii\base\BaseObject;

/**
 * A resource of a deal as answered by its provider.
 */
class DealResource extends BaseObject
{
    /** @var string provider type code (set by DealAccess) */
    public $type = '';

    /** @var int */
    public $id = 0;

    /** @var string «plitka-62.ru» */
    public $title = '';

    /** @var int|null the resource's own deal */
    public $dealId;

    /** @var array|null absolute admin route of the resource card, e.g. ['/hosting/admin-vps/update', 'pk' => 1] */
    public $url;

    /**
     * @var array|null absolute route of the same resource in the client cabinet (UPA),
     * e.g. ['/hosting/upa-sites/view', 'pk' => 1]; DealAccess::clientUrl() makes the link
     */
    public $clientUrl;

    /** @var string|null optional state for the card: «Работает», «Истекает 12.11» */
    public $status;
}
