<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\Duel;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'userCount' => User::count(),
            'duelCount' => class_exists(Duel::class) ? Duel::count() : 0,
            'questionCount' => Question::count(),
            'courses' => Course::withCount(['materials', 'questions'])
                ->orderBy('name')->get(),
        ]);
    }
}
