<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * `hash` подписывает MAX только для контактов по кнопке `request_contact`;
 * в спеке поле nullable, поэтому отсутствие или `null` не ошибка разбора —
 * тогда `hash` равен пустой строке, а `ContactVerifier::verify()` с пустым
 * хэшем даёт `false` (fail-closed, подпись не проходит).
 */
readonly class ContactAttachmentPayload
{
    /**
     * @param array<mixed>|null $maxInfo
     */
    public function __construct(
        public string $hash,
        public ?string $vcfInfo = null,
        public ?string $vcfPhone = null,
        public ?array $maxInfo = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $maxInfo = $data['max_info'] ?? null;

        return new self(
            hash: Json::string($data, 'hash') ?? '',
            vcfInfo: Json::string($data, 'vcf_info'),
            vcfPhone: Json::string($data, 'vcf_phone'),
            maxInfo: \is_array($maxInfo) ? $maxInfo : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'hash' => $this->hash !== '' ? $this->hash : null,
            'vcf_info' => $this->vcfInfo,
            'vcf_phone' => $this->vcfPhone,
            'max_info' => $this->maxInfo,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
