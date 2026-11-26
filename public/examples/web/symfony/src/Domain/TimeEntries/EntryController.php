<?php

namespace App\Domain\TimeEntries;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Thin controller: the attributes declare the contract —
 * route + permission + validated payload. The body delegates to the domain.
 */
#[Route('/api/time-entries')]
final class EntryController extends AbstractController
{
    public function __construct(private readonly Entries $entries) {}

    #[Route(methods: ['GET'])]
    #[IsGranted('TIME_READ')]
    public function index(Request $request): JsonResponse
    {
        $day = $request->query->get('day', date('Y-m-d'));

        return $this->json($this->entries->listForDay($day));
    }

    #[Route(methods: ['POST'])]
    #[IsGranted('TIME_WRITE')]
    public function create(#[MapRequestPayload] NewEntry $dto, Request $request): JsonResponse
    {
        $requestId = $request->headers->get('X-Request-Id', bin2hex(random_bytes(16)));

        $entry = $this->entries->create($dto, $requestId);

        return $this->json($entry, 201);
    }
}
