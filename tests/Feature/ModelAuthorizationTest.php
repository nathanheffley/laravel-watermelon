<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Tests\models\TaskScoped;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ModelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2021-08-07 20:00:00');

        Config::set('watermelon.models', [
            'tasks' => TaskScoped::class,
        ]);
    }

    #[Test]
    public function it_can_apply_a_models_watermelon_scope_with_no_last_pulled_at_timestamp(): void
    {
        TaskScoped::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);

        TaskScoped::query()->create([
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
        ]);

        TaskScoped::query()->create([
            'watermelon_id' => 'thirdtaskid',
            'content' => 'Third Task',
            'is_completed' => false,
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
                            'id' => 'thirdtaskid',
                            'content' => 'Third Task',
                            'is_completed' => false,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    #[Test]
    public function it_can_apply_a_models_watermelon_scope_with_a_last_pulled_at_timestamp(): void
    {
        TaskScoped::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);

        TaskScoped::query()->create([
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
        ]);

        TaskScoped::query()->create([
            'watermelon_id' => 'thirdtaskid',
            'content' => 'Third Task',
            'is_completed' => false,
        ]);

        $lastPulledAt = now()->subMinutes(10)->timestamp;
        $response = $this->json('GET', '/sync?last_pulled_at='.$lastPulledAt);
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
                            'id' => 'thirdtaskid',
                            'content' => 'Third Task',
                            'is_completed' => false,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }

    #[Test]
    public function it_throws_an_exception_and_rolls_back_changes_when_trying_to_update_a_model_restricted_by_watermelon_scope(): void
    {
        TaskScoped::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);

        TaskScoped::query()->create([
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);

        $response = $this->json('POST', '/sync', [
            'tasks' => [
                'created' => [],
                'updated' => [
                    [
                        'id' => 'firsttaskid',
                        'content' => 'Updated First Task',
                        'is_completed' => true,
                    ],
                    [
                        'id' => 'secondtaskid',
                        'content' => 'Updated Second Task',
                        'is_completed' => false,
                    ],
                ],
                'deleted' => [],
            ],
        ]);
        $response->assertStatus(409);

        $this->assertDatabaseCount('tasks', 2);
        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);
        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);
    }

    #[Test]
    public function it_does_not_throw_an_error_but_does_not_delete_a_model_restricted_by_watermelon_scope(): void
    {
        TaskScoped::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
            'deleted_at' => null,
        ]);

        TaskScoped::query()->create([
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
            'deleted_at' => null,
        ]);

        $response = $this->json('POST', '/sync', [
            'tasks' => [
                'created' => [],
                'updated' => [],
                'deleted' => [
                    'firsttaskid',
                    'secondtaskid',
                ],
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseCount('tasks', 2);
        $this->assertSoftDeleted('tasks', [
            'watermelon_id' => 'firsttaskid',
        ]);
        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
            'deleted_at' => null,
        ]);
    }
}
