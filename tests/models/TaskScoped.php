<?php

namespace NathanHeffley\LaravelWatermelon\Tests\models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use NathanHeffley\LaravelWatermelon\Traits\Watermelon;

class TaskScoped extends Model {
    use SoftDeletes, Watermelon;

    protected $table = 'tasks';

    protected $guarded = [];

    protected $casts = [
        'is_completed' => 'bool',
    ];

    public function scopeWatermelon(Builder $query)
    {
        return $query->where('is_completed', false);
    }

    protected array $watermelonAttributes = [
        'content',
        'is_completed',
    ];
}
