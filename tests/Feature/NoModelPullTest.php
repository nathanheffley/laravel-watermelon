<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;

class NoModelPullTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2021-08-07 20:00:00');

        Config::set('watermelon.models', []);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_without_models_and_no_last_pulled_at(): void
    {
        $response = $this->json('GET', '/sync');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_without_models_and_null_last_pulled_at(): void
    {
        $response = $this->json('GET', '/sync?last_pulled_at=null');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_without_models_and_zero_last_pulled_at(): void
    {
        $response = $this->json('GET', '/sync?last_pulled_at=0');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_without_models_and_last_pulled_at(): void
    {
        $lastPulledAt = now()->subMinutes(10)->timestamp;
        $response = $this->json('GET', '/sync?last_pulled_at='.$lastPulledAt);
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [],
            'timestamp' => now()->timestamp,
        ]);
    }
}
