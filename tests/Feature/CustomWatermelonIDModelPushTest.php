<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Tests\models\CustomTask as Task;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;

class CustomWatermelonIDModelPushTest extends TestCase
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
    public function it_ignores_changes_for_models_not_in_the_watermelon_models_config_array(): void
    {
        $response = $this->json('POST', '/sync', [
            'unknown' => [
                'created' => [
                    [
                        'id' => 'newunknown',
                        'value' => 'New Unknown',
                    ],
                ],
                'updated' => [
                    [
                        'id' => 'updatedunknown',
                        'value' => 'Updated Unknown',
                    ],
                ],
                'deleted' => ['deletedunknown'],
            ],
        ]);
        $response->assertNoContent();
    }

    /** @test */
    public function it_persists_push_request_changes(): void
    {
        $firstTask = Task::query()->create([
            'custom_wm_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => false,
            'deleted_at' => null,
        ]);

        $response = $this->json('POST', '/sync', [
            'tasks' => [
                'created' => [
                    [
                        'id' => 'newtaskid',
                        'content' => 'New Task',
                        'is_completed' => false,
                    ],
                ],
                'updated' => [
                    [
                        'id' => 'firsttaskid',
                        'content' => 'Updated Content',
                        'is_completed' => true,
                    ],
                ],
                'deleted' => ['secondtaskid'],
            ],
        ]);
        $response->assertNoContent();

        $firstTask->refresh();
        $this->assertEquals('Updated Content', $firstTask->content);
        $this->assertTrue($firstTask->is_completed);

        $this->assertSoftDeleted('custom_tasks', ['custom_wm_id' => 'secondtaskid']);

        $this->assertDatabaseHas('custom_tasks', [
            'custom_wm_id' => 'newtaskid',
            'content' => 'New Task',
            'is_completed' => false,
        ]);
    }

    /** @test */
    public function it_updates_the_record_if_there_is_an_attempt_to_create_an_existing_record(): void
    {
        Task::query()->create([
            'custom_wm_id' => 'taskid',
            'content' => 'Existing Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
            'deleted_at' => null,
        ]);

        $response = $this->json('POST', '/sync', [
            'tasks' => [
                'created' => [
                    [
                        'id' => 'taskid',
                        'content' => 'New Content',
                        'is_completed' => false,
                    ]
                ],
                'updated' => [],
                'deleted' => [],
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseCount('custom_tasks', 1);
        $this->assertDatabaseHas('custom_tasks', [
            'custom_wm_id' => 'taskid',
            'content' => 'New Content',
            'is_completed' => false,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_creates_a_record_if_there_is_an_attempt_to_update_a_non_existent_record(): void
    {
        $response = $this->json('POST', '/sync', [
            'tasks' => [
                'created' => [],
                'updated' => [
                    [
                        'id' => 'taskid',
                        'content' => 'New Content',
                        'is_completed' => true,
                    ]
                ],
                'deleted' => [],
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseHas('custom_tasks', [
            'custom_wm_id' => 'taskid',
            'content' => 'New Content',
            'is_completed' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_does_not_throw_an_error_if_there_is_an_attempt_to_delete_an_already_deleted_record(): void
    {
        $deletedAt = now()->subMinute();

        $task = Task::query()->create([
            'custom_wm_id' => 'taskid',
            'content' => 'Task',
            'is_completed' => false,
            'deleted_at' => $deletedAt,
        ]);

        $response = $this->json('POST', '/sync', [
            'tasks' => [
                'created' => [],
                'updated' => [],
                'deleted' => ['taskid'],
            ],
        ]);
        $response->assertNoContent();

        $this->assertEquals($deletedAt, $task->fresh()->deleted_at);
    }

    /** @test */
    public function it_rolls_back_push_request_changes_if_there_is_an_attempt_to_update_a_deleted_record(): void
    {
        Task::query()->create([
            'custom_wm_id' => 'regulartaskid',
            'content' => 'Regular Task',
            'is_completed' => false,
        ]);

        Task::query()->create([
            'custom_wm_id' => 'deletedtaskid',
            'content' => 'Deleted Task',
            'is_completed' => false,
            'deleted_at' => now()->subMinute(),
        ]);

        Task::query()->create([
            'custom_wm_id' => 'oldtaskid',
            'content' => 'Old Task',
            'is_completed' => false,
            'deleted_at' => null,
        ]);

        $response = $this->json('POST', '/sync', [
            'tasks' => [
                'created' => [
                    [
                        'id' => 'newtaskid',
                        'content' => 'New Task',
                        'is_completed' => false,
                    ],
                ],
                'updated' => [
                    [
                        'id' => 'regulartaskid',
                        'content' => 'Updated Regular Task',
                        'is_completed' => true,
                    ],
                    [
                        'id' => 'deletedtaskid',
                        'content' => 'New Content',
                        'is_completed' => true,
                    ],
                ],
                'deleted' => ['oldtaskid'],
            ],
        ]);
        $response->assertStatus(409);

        $this->assertDatabaseMissing('custom_tasks', [
            'custom_wm_id' => 'newtaskid',
            'content' => 'New Task',
            'is_completed' => false,
        ]);

        $this->assertDatabaseHas('custom_tasks', [
            'custom_wm_id' => 'regulartaskid',
            'content' => 'Regular Task',
            'is_completed' => false,
        ]);
        $this->assertDatabaseHas('custom_tasks', [
            'custom_wm_id' => 'deletedtaskid',
            'deleted_at' => now()->subMinute(),
        ]);

        $this->assertDatabaseHas('custom_tasks', [
            'custom_wm_id' => 'oldtaskid',
            'deleted_at' => null,
        ]);
    }
}
