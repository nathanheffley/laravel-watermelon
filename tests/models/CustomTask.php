<?php

namespace NathanHeffley\LaravelWatermelon\Tests\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use NathanHeffley\LaravelWatermelon\Traits\Watermelon;

class CustomTask extends Model {
    use SoftDeletes, Watermelon;

    protected $guarded = [];

    protected $casts = [
        'is_completed' => 'bool',
    ];

    protected array $watermelonAttributes = [
        'content',
        'is_completed',
    ];
}
