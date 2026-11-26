<?php

declare(strict_types=1);

namespace App\Domain\TimeEntries;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * The voter IS the single permission matrix — #[IsGranted('TIME_WRITE')]
 * on the controller reads it. Same function as arc_can(), other syntax.
 */
final class TimeEntryVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['TIME_READ', 'TIME_WRITE'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($user === null) {
            return false;
        }

        return match ($attribute) {
            'TIME_READ' => in_array('time.read', $user->getPermissions(), true),
            'TIME_WRITE' => in_array('time.write', $user->getPermissions(), true),
            default => false,
        };
    }
}
