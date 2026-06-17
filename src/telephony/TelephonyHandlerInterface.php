<?php

namespace skeeks\cms\telephony;

use skeeks\cms\models\CmsTelephonyUser;

interface TelephonyHandlerInterface
{
    public function call(string $phone, CmsTelephonyUser $user): array;

    public function cancel(string $providerCallId): void;

    public function normalizeWebhook(array $payload): array;

}