<?php

declare(strict_types=1);

namespace App\Domain\TimeEntries;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The DTO is the schema of the contract: #[MapRequestPayload] hydrates
 * and validates it — a violation returns 400 automatically.
 * Every field carries its full bounds; the contract is closed.
 */
final class NewEntry
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 1, max: 255)]
        public readonly string $task,

        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 1440)]
        public readonly int $minutes,
    ) {}
}
