<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait ResolvesDate
{
    protected function resolveDate(Request $request, string $key = 'date'): Carbon
    {
        $request->validate([$key => ['nullable', 'date_format:Y-m-d']]);

        return $request->filled($key)
            ? Carbon::createFromFormat('Y-m-d', $request->input($key))->startOfDay()
            : today();
    }
}
