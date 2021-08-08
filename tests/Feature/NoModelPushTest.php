<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Feature;

use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;

class NoModelPushTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('watermelon.models', []);
    }

    /** @test */
    public function it_can_respond_to_push_requests_with_no_models_and_no_data(): void
    {
        $response = $this->json('POST', '/sync');
        $response->assertNoContent();
    }

    /** @test */
    public function it_can_respond_to_push_requests_with_no_models_and_unknown_data(): void
    {
        $response = $this->json('POST', '/sync', [
            'unknown' => [
                'created' => [
                    [
                        'id' => 'newunknown',
                        'value' => 'New Value',
                    ],
                ],
                'updated' => [
                    [
                        'id' => 'updatedunknown',
                        'value' => 'Updated Value',
                    ],
                ],
                'deleted' => ['deletedunknown'],
            ],
        ]);
        $response->assertNoContent();
    }
}
