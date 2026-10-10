<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Lightweight "is the server (and its database) awake?" check.
 * Used by the sign-in screen and the public gateway page while a free-tier
 * host is waking up. It touches the database on purpose so a sleeping
 * database is woken too. It exposes no data.
 */
class WakeController extends Controller
{
    public function __invoke()
    {
        try {
            DB::select('select 1');
            $ok = true;
        } catch (Throwable) {
            $ok = false;
        }

        return response()
            ->json(['ok' => $ok], $ok ? 200 : 503)
            ->withHeaders([
                'Cache-Control' => 'no-store, max-age=0',
                'Access-Control-Allow-Origin' => '*',
            ]);
    }
}
