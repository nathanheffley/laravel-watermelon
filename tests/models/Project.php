<?php

namespace NathanHeffley\LaravelWatermelon\Tests\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use NathanHeffley\LaravelWatermelon\Traits\Watermelon;

class Project extends Model {
    use SoftDeletes, Watermelon;

    protected $guarded = [];

    protected array $watermelonAttributes = [
        'name',
    ];
}
