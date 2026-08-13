<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class BotCommandsResult
{
    /**
     * @param list<BotCommand>|null $commands
     */
    public function __construct(
        public ?array $commands = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            commands: Json::map($data, 'commands', static fn (mixed $item): BotCommand => BotCommand::fromArray((array) $item)),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'commands' => $this->commands === null
                ? null
                : array_map(static fn (BotCommand $command): array => $command->toArray(), $this->commands),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
