<?php

namespace Tests;

use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Never make real HTTP calls to the Expo push API during tests.
        $this->mock(PushNotificationService::class)->shouldIgnoreMissing();
    }
}
