<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Tests\models\CustomTask as Task;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;

class CustomWatermelonIDModelPullTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2021-08-07 20:00:00');

        Config::set('watermelon.identifier', 'custom_wm_id');

        Config::set('watermelon.models', [
            'tasks' => Task::class,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_no_data_and_no_last_pulled_at_timestamp(): void
    {
        $response = $this->json('GET', '/sync');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_no_data_and_a_null_last_pulled_at_timestamp(): void
    {
        $response = $this->json('GET', '/sync?last_pulled_at=null');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_no_data_and_a_zero_last_pulled_at_timestamp(): void
    {
        $response = $this->json('GET', '/sync?last_pulled_at=0');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_no_changes_and_a_last_pulled_at_timestamp(): void
    {
        $lastPulledAt = now()->subMinutes(10)->timestamp;
        $response = $this->json('GET', '/sync?last_pulled_at='.$lastPulledAt);
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_data_and_no_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'custom_wm_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'deletedtaskid',
            'content' => 'Deleted Task',
            'is_completed' => true,
            'deleted_at' => now(),
        ]);

        $response = $this->json('GET', '/sync');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [
                        [
                            'id' => 'firsttaskid',
                            'content' => 'First Task',
                            'is_completed' => false,
                        ],
                        [
                            'id' => 'secondtaskid',
                            'content' => 'Second Task',
                            'is_completed' => true,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_data_and_a_null_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'custom_wm_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'deletedtaskid',
            'content' => 'Deleted Task',
            'is_completed' => true,
            'deleted_at' => now(),
        ]);

        $response = $this->json('GET', '/sync?last_pulled_at=null');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [
                        [
                            'id' => 'firsttaskid',
                            'content' => 'First Task',
                            'is_completed' => false,
                        ],
                        [
                            'id' => 'secondtaskid',
                            'content' => 'Second Task',
                            'is_completed' => true,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_data_and_a_zero_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'custom_wm_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'deletedtaskid',
            'content' => 'Deleted Task',
            'is_completed' => true,
            'deleted_at' => now(),
        ]);

        $response = $this->json('GET', '/sync?last_pulled_at=0');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [
                        [
                            'id' => 'firsttaskid',
                            'content' => 'First Task',
                            'is_completed' => false,
                        ],
                        [
                            'id' => 'secondtaskid',
                            'content' => 'Second Task',
                            'is_completed' => true,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_data_and_a_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'custom_wm_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);

        Task::query()->create([
            'custom_wm_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(9),
            'updated_at' => now()->subMinutes(9),
        ]);

        Task::query()->create([
            'custom_wm_id' => 'updatedtaskid',
            'content' => 'Updated Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(9),
        ]);

        Task::query()->create([
            'custom_wm_id' => 'deletedtaskid',
            'content' => 'Deleted Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(9),
            'deleted_at' => now()->subMinutes(9),
        ]);

        $lastPulledAt = now()->subMinutes(10)->timestamp;
        $response = $this->json('GET', '/sync?last_pulled_at='.$lastPulledAt);
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [
                        [
                            'id' => 'secondtaskid',
                            'content' => 'Second Task',
                            'is_completed' => true,
                        ],
                    ],
                    'updated' => [
                        [
                            'id' => 'updatedtaskid',
                            'content' => 'Updated Task',
                            'is_completed' => false,
                        ],
                    ],
                    'deleted' => ['deletedtaskid'],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_with_lots_of_deleted_records_and_a_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'custom_wm_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
            'deleted_at' => now()->subMinutes(11),
        ]);

        Task::query()->create([
            'custom_wm_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(9),
            'deleted_at' => now()->subMinutes(9),
        ]);

        Task::query()->create([
            'custom_wm_id' => 'thirdtaskid',
            'content' => 'Third Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(9),
            'updated_at' => now()->subMinutes(9),
            'deleted_at' => now()->subMinutes(5),
        ]);

        $lastPulledAt = now()->subMinutes(10)->timestamp;
        $response = $this->json('GET', '/sync?last_pulled_at='.$lastPulledAt);
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [],
                    'updated' => [],
                    'deleted' => [
                        'secondtaskid',
                    ],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }
}
