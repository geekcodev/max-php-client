<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ContactAttachmentRequestPayload
{
    public function __construct(
        public ?string $name = null,
        public ?int $contactId = null,
        public ?string $vcfInfo = null,
        public ?string $vcfPhone = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: Json::string($data, 'name'),
            contactId: Json::int($data, 'contact_id'),
            vcfInfo: Json::string($data, 'vcf_info'),
            vcfPhone: Json::string($data, 'vcf_phone'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'contact_id' => $this->contactId,
            'vcf_info' => $this->vcfInfo,
            'vcf_phone' => $this->vcfPhone,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
