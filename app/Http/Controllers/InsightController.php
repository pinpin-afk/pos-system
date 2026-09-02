<?php

namespace App\Http\Controllers;

use App\Services\InsightService;
use Inertia\Inertia;
use Inertia\Response;

class InsightController extends Controller
{
    public function __invoke(InsightService $insights): Response
    {
        return Inertia::render('Insights/Index', [
            'suggestions' => $insights->suggestions(),
        ]);
    }
}
