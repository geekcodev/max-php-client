<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

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
        $hash = Json::string($data, 'hash');
        if ($hash === null) {
            throw new InvalidResponseException('Field "hash" must be a string.');
        }

        $maxInfo = $data['max_info'] ?? null;

        return new self(
            hash: $hash,
            vcfInfo: Json::string($data, 'vcf_info'),
            vcfPhone: Json::string($data, 'vcf_phone'),
            maxInfo: \is_array($maxInfo) ? $maxInfo : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'hash' => $this->hash,
            'vcf_info' => $this->vcfInfo,
            'vcf_phone' => $this->vcfPhone,
            'max_info' => $this->maxInfo,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
