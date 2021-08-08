<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Tests\models\Project;
use NathanHeffley\LaravelWatermelon\Tests\models\Task;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;

class MultipleModelPullTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2021-08-07 20:00:00');

        Config::set('watermelon.models', [
            'tasks' => Task::class,
            'projects' => Project::class,
        ]);
    }

    /** @test */
    public function it_can_respond_to_pull_requests_for_multiple_models_without_a_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'watermelon_id' => 'taskid',
            'content' => 'Task',
            'is_completed' => true,
        ]);
        Project::query()->create([
            'watermelon_id' => 'projectid',
            'name' => 'Project',
        ]);

        $response = $this->json('GET', '/sync');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [
                        [
                            'id' => 'taskid',
                            'content' => 'Task',
                            'is_completed' => true,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
                'projects' => [
                    'created' => [
                        [
                            'id' => 'projectid',
                            'name' => 'Project',
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
    public function it_can_respond_to_pull_requests_for_multiple_models_with_a_null_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'watermelon_id' => 'taskid',
            'content' => 'Task',
            'is_completed' => false,
        ]);
        Project::query()->create([
            'watermelon_id' => 'projectid',
            'name' => 'Project',
        ]);

        $response = $this->json('GET', '/sync?last_pulled_at=null');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [
                        [
                            'id' => 'taskid',
                            'content' => 'Task',
                            'is_completed' => false,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
                'projects' => [
                    'created' => [
                        [
                            'id' => 'projectid',
                            'name' => 'Project',
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
    public function it_can_respond_to_pull_requests_for_multiple_models_with_a_zero_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'watermelon_id' => 'taskid',
            'content' => 'Task',
            'is_completed' => true,
        ]);
        Project::query()->create([
            'watermelon_id' => 'projectid',
            'name' => 'Project',
        ]);

        $response = $this->json('GET', '/sync?last_pulled_at=0');
        $response->assertStatus(200);
        $response->assertExactJson([
            'changes' => [
                'tasks' => [
                    'created' => [
                        [
                            'id' => 'taskid',
                            'content' => 'Task',
                            'is_completed' => true,
                        ],
                    ],
                    'updated' => [],
                    'deleted' => [],
                ],
                'projects' => [
                    'created' => [
                        [
                            'id' => 'projectid',
                            'name' => 'Project',
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
    public function it_can_respond_to_pull_requests_for_multiple_models_with_a_last_pulled_at_timestamp(): void
    {
        Task::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);
        Project::query()->create([
            'watermelon_id' => 'firstprojectid',
            'name' => 'First Project',
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ]);

        Task::query()->create([
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(9),
            'updated_at' => now()->subMinutes(9),
        ]);
        Project::query()->create([
            'watermelon_id' => 'secondprojectid',
            'name' => 'Second Project',
            'created_at' => now()->subMinutes(9),
            'updated_at' => now()->subMinutes(9),
        ]);

        Task::query()->create([
            'watermelon_id' => 'updatedtaskid',
            'content' => 'Updated Task',
            'is_completed' => false,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(9),
        ]);
        Project::query()->create([
            'watermelon_id' => 'updatedprojectid',
            'name' => 'Updated Project',
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(9),
        ]);

        Task::query()->create([
            'watermelon_id' => 'deletedtaskid',
            'content' => 'Deleted Task',
            'is_completed' => true,
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(9),
            'deleted_at' => now()->subMinutes(9),
        ]);
        Project::query()->create([
            'watermelon_id' => 'deletedprojectid',
            'name' => 'Deleted Project',
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(9),
            'deleted_at' => now()->subMinutes(9),
        ]);

        // last_pulled_at = 10 minutes ago
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
                'projects' => [
                    'created' => [
                        [
                            'id' => 'secondprojectid',
                            'name' => 'Second Project',
                        ],
                    ],
                    'updated' => [
                        [
                            'id' => 'updatedprojectid',
                            'name' => 'Updated Project',
                        ],
                    ],
                    'deleted' => ['deletedprojectid'],
                ],
            ],
            'timestamp' => now()->timestamp,
        ]);
    }
}
