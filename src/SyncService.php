<?php

namespace NathanHeffley\LaravelWatermelon;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use NathanHeffley\LaravelWatermelon\Exceptions\ConflictException;

class SyncService
{
    protected array $models;

    public function __construct(array $models)
    {
        $this->models = $models;
    }

    public function pull(Request $request): JsonResponse
    {
        $lastPulledAt = $request->get('last_pulled_at');

        $timestamp = now()->timestamp;

        $changes = [];

        if ($lastPulledAt === 'null') {
            foreach ($this->models as $name => $class) {
                $changes[$name] = [
                    'created' => (new $class)::watermelon()
                        ->get()
                        ->map->toWatermelonArray(),
                    'updated' => [],
                    'deleted' => [],
                ];
            }
        } else {
            $lastPulledAt = Carbon::createFromTimestamp($lastPulledAt);

            foreach ($this->models as $name => $class) {
                $changes[$name] = [
                    'created' => (new $class)::withoutTrashed()
                        ->where('created_at', '>', $lastPulledAt)
                        ->watermelon()
                        ->get()
                        ->map->toWatermelonArray(),
                    'updated' => (new $class)::withoutTrashed()
                        ->where('created_at', '<=', $lastPulledAt)
                        ->where('updated_at', '>', $lastPulledAt)
                        ->watermelon()
                        ->get()
                        ->map->toWatermelonArray(),
                    'deleted' => (new $class)::onlyTrashed()
                        ->where('created_at', '<=', $lastPulledAt)
                        ->where('deleted_at', '>', $lastPulledAt)
                        ->watermelon()
                        ->get(config('watermelon.identifier'))
                        ->pluck(config('watermelon.identifier')),
                ];
            }
        }

        return response()->json([
            'changes' => $changes,
            'timestamp' => $timestamp,
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        DB::beginTransaction();

        foreach ($this->models as $name => $class) {
            if (!$request->input($name)) {
                continue;
            }

            collect($request->input("$name.created"))->each(function ($create) use ($class) {
                $create = collect((new $class)->toWatermelonArray())
                    ->keys()
                    ->map(function ($col) use ($create) {
                        return [$col, $create[$col]];
                    })->reduce(function ($assoc, $pair) {
                        list($key, $value) = $pair;
                        if ($key === 'id') {
                            $assoc[config('watermelon.identifier')] = $value;
                        } else {
                            $assoc[$key] = $value;
                        }
                        return $assoc;
                    }, collect());

                try {
                    $model = $class::query()->where(config('watermelon.identifier'), $create->get(config('watermelon.identifier')))->firstOrFail();
                    $model->update($create->toArray());
                } catch (ModelNotFoundException) {
                    $class::query()->create($create->toArray());
                }
            });
        }

        try {
            foreach ($this->models as $name => $class) {
                if (!$request->input($name)) {
                    continue;
                }

                collect($request->input("$name.updated"))->each(function ($update) use ($class) {
                    $update = collect((new $class)->toWatermelonArray())
                        ->keys()
                        ->map(function ($col) use ($update) {
                            return [$col, $update[$col]];
                        })->reduce(function ($assoc, $pair) {
                            list($key, $value) = $pair;
                            if ($key === 'id') {
                                $assoc[config('watermelon.identifier')] = $value;
                            } else {
                                $assoc[$key] = $value;
                            }
                            return $assoc;
                        }, collect());

                    if ($class::onlyTrashed()->where(config('watermelon.identifier'), $update->get(config('watermelon.identifier')))->count() > 0) {
                        throw new ConflictException;
                    }

                    try {
                        $task = $class::query()
                            ->where(config('watermelon.identifier'), $update->get(config('watermelon.identifier')))
                            ->watermelon()
                            ->firstOrFail();
                        $task->update($update->toArray());
                    } catch (ModelNotFoundException) {
                        try {
                            $class::query()->create($update->toArray());
                        } catch (QueryException) {
                            throw new ConflictException;
                        }
                    }
                });
            }
        } catch (ConflictException) {
            DB::rollBack();

            return response()->json('', 409);
        }

        foreach ($this->models as $name => $class) {
            if (!$request->input($name)) {
                continue;
            }

            collect($request->input("$name.deleted"))->each(function ($delete) use ($class) {
                $class::query()->where(config('watermelon.identifier'), $delete)->watermelon()->delete();
            });
        }

        DB::commit();

        return response()->json('', 204);
    }
}