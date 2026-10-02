<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // Counts are wired to real data as each module is built.
        return view('dashboard', [
            'stats' => ['employees' => 0, 'present' => 0, 'absent' => 0, 'leave' => 0],
            'compliance' => ['total' => 0, 'pending' => 0, 'week' => 0, 'overdue' => 0],
        ]);
    }
}
