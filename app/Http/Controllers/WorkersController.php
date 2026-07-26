<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Inertia\Inertia;
use Inertia\Response;

class WorkersController extends Controller
{
    public function index(): Response
    {
        $workers = Worker::orderBy('created_at', 'desc')->get();

        return Inertia::render('workers/index', [
            'workers' => $workers,
        ]);
    }

    public function show(Worker $worker): Response
    {
        return Inertia::render('workers/[id]/index', [
            'worker' => $worker,
        ]);
    }
}
