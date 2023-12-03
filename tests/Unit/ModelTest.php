<?php

namespace NathanHeffley\LaravelWatermelon\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use NathanHeffley\LaravelWatermelon\Traits\Watermelon;
use NathanHeffley\LaravelWatermelon\Tests\TestCase;

class ModelTest extends TestCase
{
    /** @test */
    public function it_returns_only_the_watermelon_id_by_default()
    {
        $model = new class extends Model {
            use Watermelon;

            protected $attributes = [
                'watermelon_id' => 'watermelonidvalue',
                'name' => 'Anonymous Class',
                'views' => 42,
                'published' => true,
            ];
        };

        $this->assertSame([
            'id' => 'watermelonidvalue'
        ], $model->toWatermelonArray());
    }

    /** @test */
    public function it_returns_all_watermelon_attributes_along_with_the_id()
    {
        $model = new class extends Model {
            use Watermelon;

            protected $attributes = [
                'watermelon_id' => 'watermelonidvalue',
                'name' => 'Anonymous Class',
                'views' => 42,
                'published' => true,
            ];

            protected array $watermelonAttributes = [
                'name',
                'published',
            ];
        };

        $this->assertSame([
            'id' => 'watermelonidvalue',
            'name' => 'Anonymous Class',
            'published' => true,
        ], $model->toWatermelonArray());
    }

    /** @test */
    public function it_returns_all_watermelon_attributes_along_with_the_id_pulled_from_customized_column_name()
    {
        Config::set('watermelon.identifier', 'custom_wm_id');

        $model = new class extends Model {
            use Watermelon;

            protected $attributes = [
                'custom_wm_id' => 'watermelonidvalue',
                'name' => 'Anonymous Class',
                'views' => 42,
                'published' => true,
            ];

            protected array $watermelonAttributes = [
                'name',
                'published',
            ];
        };

        $this->assertSame([
            'id' => 'watermelonidvalue',
            'name' => 'Anonymous Class',
            'published' => true,
        ], $model->toWatermelonArray());
    }
}
