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
            'id' => $this->watermelon_id,
        ], $this->only($this->watermelonAttributes ?? []));

        return $attributes;
    }
}
