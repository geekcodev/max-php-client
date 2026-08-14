<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class AddChatMembersResult
{
    /**
     * @param list<int>|null $failedUserIds
     * @param list<FailedUserDetails>|null $failedUserDetails
     */
    public function __construct(
        public ?bool $success = null,
        public ?array $failedUserIds = null,
        public ?array $failedUserDetails = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            success: Json::bool($data, 'success'),
            failedUserIds: Json::arrayOfInts($data, 'failed_user_ids'),
            failedUserDetails: Json::map($data, 'failed_user_details', static fn (mixed $item): FailedUserDetails => FailedUserDetails::fromArray((array) $item)),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'failed_user_ids' => $this->failedUserIds,
            'failed_user_details' => $this->failedUserDetails === null
                ? null
                : array_map(static fn (FailedUserDetails $details): array => $details->toArray(), $this->failedUserDetails),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
