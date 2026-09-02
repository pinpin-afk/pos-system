<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Activity/Index', [
            'logs' => ActivityLog::query()
                ->with('user:id,name')
                ->latest()
                ->paginate(30),
        ]);
    }
}
