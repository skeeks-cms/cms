<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\deal;

use skeeks\cms\models\CmsDeal;

/**
 * One kind of deal resources (a site, a VPS, an SSL certificate, a DNS zone)
 * owned by a package. The package keeps cms_deal_id in its own table; the core
 * never stores a copy of that link, it asks the provider.
 *
 * Registered in `dealAccess.resourceProviders` under a stable type code, which
 * is also stored in links to a resource (a task, a line of the deal
 * composition).
 */
interface DealResourceProviderInterface
{
    /**
     * Human name of the kind: «Сайт», «VPS», «DNS-зона».
     */
    public function label(): string;

    /**
     * @return DealResource[] resources whose own deal is this deal
     */
    public function findByDeal(CmsDeal $deal): array;

    public function findOne(int $id): ?DealResource;

    /**
     * Moves the resource into the deal. Called inside a transaction by
     * DealAccess::moveResource(), which writes the history.
     *
     * @throws \yii\base\UserException when the package refuses the move
     */
    public function move(int $id, CmsDeal $toDeal): void;
}
