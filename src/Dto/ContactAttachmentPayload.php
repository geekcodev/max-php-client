<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Контакт, присланный пользователем. `hash` подписывает `vcf_info`; отсутствие
 * подписи — `null`, проверка остаётся fail-closed.
 */
readonly class ContactAttachmentPayload
{
    /**
     * @param array<mixed>|null $maxInfo
     */
    public function __construct(
        public ?string $hash = null,
        public ?string $vcfInfo = null,
        public ?array $maxInfo = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $maxInfo = $data['max_info'] ?? null;

        return new self(
            hash: Json::string($data, 'hash'),
            vcfInfo: Json::string($data, 'vcf_info'),
            maxInfo: \is_array($maxInfo) ? $maxInfo : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'hash' => $this->hash,
            'vcf_info' => $this->vcfInfo,
            'max_info' => $this->maxInfo,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
