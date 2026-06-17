<?php

namespace skeeks\cms\telephony;

use skeeks\cms\base\Component;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use skeeks\cms\traits\TConfigForm;

abstract class TelephonyHandler extends Component implements \skeeks\cms\IHasConfigForm, TelephonyHandlerInterface
{
    use HasComponentDescriptorTrait;
    use TConfigForm;


    /**
     * Собирает SIP-конфиг для softphone
     */
    abstract public function buildSipConfig(CmsTelephonyUser $telephonyUser): array;

    /**
     * Проверка webhook (опционально)
     */
    public function verifyWebhook(array $headers, array $payload): bool
    {
        return true;
    }

    /**
     * Нормализация webhook → единый формат
     */
    public function normalizeWebhook(array $payload): array
    {
        return $payload;
    }

    public function call(string $phone, CmsTelephonyUser $user): array
    {
        return [
            'success' => true,
            'provider_call_id' => $callbackId,
        ];
    }

    public function cancel(string $providerCallId): void
    {
    }

    public function saveRecord(CmsTelephonyCall $cmsTelephonyCall): void
    {

    }
}
