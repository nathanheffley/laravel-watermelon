<?php

namespace NathanHeffley\LaravelWatermelon\Traits;

trait Watermelon
{
    public function scopeWatermelon($query)
    {
        return $query;
    }

    public function toWatermelonArray(): array
    {
        $attributes = array_merge([
            'id' => $this[config('watermelon.identifier')],
        ], $this->only($this->watermelonAttributes ?? []));

        return $attributes;
    }
}
