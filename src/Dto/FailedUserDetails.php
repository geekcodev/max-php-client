<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Подробная причина, по которой пользователя не удалось добавить в чат.
 * Официальная `schema.yaml` объявляет форму `{error_code, user_ids[]}`
 * (спека max-openapi — та же), в проде встречается прежняя `{user_id, error}`;
 * разбираются обе формы. Конструктор `(int $userId, string $error)` сохранён
 * для совместимости, новые поля идут после него.
 *
 * `$userId === 0` означает «поле не пришло»: значение `0` не встречается в
 * проде (идентификаторы начинаются с 1). Строгие nullable-поля — задача v2.0.0.
 */
readonly class FailedUserDetails
{
    /**
     * @param list<int>|null $userIds
     */
    public function __construct(
        public int $userId = 0,
        public string $error = '',
        public ?string $errorCode = null,
        public ?array $userIds = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: Json::int($data, 'user_id') ?? 0,
            error: Json::string($data, 'error') ?? '',
            errorCode: Json::string($data, 'error_code'),
            userIds: Json::arrayOfInts($data, 'user_ids'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId !== 0 ? $this->userId : null,
            'error' => $this->error !== '' ? $this->error : null,
            'error_code' => $this->errorCode,
            'user_ids' => $this->userIds,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
