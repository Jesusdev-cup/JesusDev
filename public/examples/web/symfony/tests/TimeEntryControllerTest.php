<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TimeEntryControllerTest extends WebTestCase
{
    public function test_create_validates_the_payload(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/time-entries', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['task' => '', 'minutes' => -1]));

        // #[MapRequestPayload] violations → 422 with the problem shape.
        $this->assertResponseStatusCodeSame(422);
    }

    public function test_create_requires_permission(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/time-entries', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['task' => 'Deep work', 'minutes' => 90]));

        // #[IsGranted] without a token → 401/403 before the domain runs.
        $this->assertResponseStatusCodeSame(401);
    }
}
