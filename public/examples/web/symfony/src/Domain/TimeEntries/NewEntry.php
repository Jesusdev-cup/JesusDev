<?php

namespace App\Domain\TimeEntries;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The DTO is the schema of the contract: #[MapRequestPayload] hydrates
 * and validates it — a violation returns 400 automatically.
 */
final class NewEntry
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $task,

        #[Assert\Positive]
        public readonly int $minutes,
    ) {}
}
