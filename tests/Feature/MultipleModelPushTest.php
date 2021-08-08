<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Tests\models\Project;
use NathanHeffley\LaravelWatermelon\Tests\models\Task;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;

class MultipleModelPushTest extends TestCase
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
    public function it_persists_push_requests_for_multiple_models(): void
    {
        $firstTask = Task::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);
        $firstProject = Project::query()->create([
            'watermelon_id' => 'firstprojectid',
            'name' => 'First Project',
        ]);

        Task::query()->create([
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => false,
            'deleted_at' => null,
        ]);
        Project::query()->create([
            'watermelon_id' => 'secondprojectid',
            'name' => 'Second Project',
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
            'projects' => [
                'created' => [
                    [
                        'id' => 'newprojectid',
                        'name' => 'New Project',
                    ],
                ],
                'updated' => [
                    [
                        'id' => 'firstprojectid',
                        'name' => 'Updated Name',
                    ],

                ],
                'deleted' => ['secondprojectid'],
            ],
        ]);
        $response->assertNoContent();

        $firstTask->refresh();
        $this->assertEquals('Updated Content', $firstTask->content);
        $this->assertTrue($firstTask->is_completed);

        $firstProject->refresh();
        $this->assertEquals('Updated Name', $firstProject->name);

        $this->assertSoftDeleted('tasks', ['watermelon_id' => 'secondtaskid']);
        $this->assertSoftDeleted('projects', ['watermelon_id' => 'secondprojectid']);

        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'newtaskid',
            'content' => 'New Task',
            'is_completed' => false,
        ]);
        $this->assertDatabaseHas('projects', [
            'watermelon_id' => 'newprojectid',
            'name' => 'New Project',
        ]);
    }

    /** @test */
    public function it_rolls_back_push_request_changes_for_all_models_when_an_error_is_thrown_for_one_model(): void
    {
        Task::query()->create([
            'watermelon_id' => 'regulartaskid',
            'content' => 'Regular Task',
            'is_completed' => false,
        ]);

        Task::query()->create([
            'watermelon_id' => 'oldtaskid',
            'content' => 'Old Task',
            'is_completed' => false,
            'deleted_at' => null,
        ]);

        Project::query()->create([
            'watermelon_id' => 'deletedprojectid',
            'name' => 'Deleted Project',
            'deleted_at' => now()->subMinute(),
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
            'projects' => [
                'created' => [],
                'updated' => [
                    [
                        'id' => 'deletedprojectid',
                        'name' => 'New Name',
                    ],
                ],
                'deleted' => [],
            ],
        ]);
        $response->assertStatus(409);

        $this->assertDatabaseMissing('tasks', [
            'watermelon_id' => 'newtaskid',
            'content' => 'New Task',
            'is_completed' => false,
        ]);

        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'regulartaskid',
            'content' => 'Regular Task',
            'is_completed' => false,
        ]);

        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'oldtaskid',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('projects', [
            'watermelon_id' => 'deletedprojectid',
            'deleted_at' => now()->subMinute(),
        ]);
    }

    /** @test */
    public function it_persists_push_requests_even_when_one_model_of_many_is_missing(): void
    {
        $firstTask = Task::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);

        Task::query()->create([
            'watermelon_id' => 'secondtaskid',
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

        $this->assertSoftDeleted('tasks', ['watermelon_id' => 'secondtaskid']);

        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'newtaskid',
            'content' => 'New Task',
            'is_completed' => false,
        ]);
    }

    /** @test */
    public function it_persists_push_requests_even_when_an_unknown_model_is_encountered_among_many(): void
    {
        $firstTask = Task::query()->create([
            'watermelon_id' => 'firsttaskid',
            'content' => 'First Task',
            'is_completed' => false,
        ]);
        $firstProject = Project::query()->create([
            'watermelon_id' => 'firstprojectid',
            'name' => 'First Project',
        ]);

        Task::query()->create([
            'watermelon_id' => 'secondtaskid',
            'content' => 'Second Task',
            'is_completed' => false,
            'deleted_at' => null,
        ]);
        Project::query()->create([
            'watermelon_id' => 'secondprojectid',
            'name' => 'Second Project',
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
            'projects' => [
                'created' => [
                    [
                        'id' => 'newprojectid',
                        'name' => 'New Project',
                    ],
                ],
                'updated' => [
                    [
                        'id' => 'firstprojectid',
                        'name' => 'Updated Name',
                    ],

                ],
                'deleted' => ['secondprojectid'],
            ],
        ]);
        $response->assertNoContent();

        $firstTask->refresh();
        $this->assertEquals('Updated Content', $firstTask->content);
        $this->assertTrue($firstTask->is_completed);

        $firstProject->refresh();
        $this->assertEquals('Updated Name', $firstProject->name);

        $this->assertSoftDeleted('tasks', ['watermelon_id' => 'secondtaskid']);
        $this->assertSoftDeleted('projects', ['watermelon_id' => 'secondprojectid']);

        $this->assertDatabaseHas('tasks', [
            'watermelon_id' => 'newtaskid',
            'content' => 'New Task',
            'is_completed' => false,
        ]);
        $this->assertDatabaseHas('projects', [
            'watermelon_id' => 'newprojectid',
            'name' => 'New Project',
        ]);
    }
}
