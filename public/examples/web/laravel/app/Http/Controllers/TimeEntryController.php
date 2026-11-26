<?php

namespace App\Http\Controllers;

use App\Domain\TimeEntries\CreateEntryRequest;
use App\Domain\TimeEntries\Entries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The controller is thin: request → domain → response.
 * Validation and auth happen before this code runs.
 */
class TimeEntryController extends Controller
{
    public function __construct(private readonly Entries $entries) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Domain\TimeEntries\TimeEntry::class);

        $day = $request->query('day', now()->toDateString());

        return response()->json($this->entries->listForDay($day));
    }

    public function store(CreateEntryRequest $request): JsonResponse
    {
        // Idempotency-Key header → domain dedup.
        $requestId = $request->header('Idempotency-Key', $request->header('X-Request-Id', str()->uuid()->toString()));

        $entry = $this->entries->create($request->validated(), $requestId);

        return response()->json($entry, 201);
    }
}
