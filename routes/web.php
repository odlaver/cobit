<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\DuelController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\QuestionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'dashboard');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/{user}', [ProfileController::class, 'show'])->name('profile.user');
    Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard');

    Route::middleware('role:mahasiswa')->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');

        Route::get('/challenges', [ChallengeController::class, 'index'])->name('challenges.index');
        Route::get('/challenges/lawan', [ChallengeController::class, 'opponents'])->name('challenges.opponents');
        Route::get('/challenges/status', [ChallengeController::class, 'status'])->name('challenges.status');
        Route::post('/challenges', [ChallengeController::class, 'store'])->name('challenges.store');
        Route::post('/challenges/{challenge}/accept', [ChallengeController::class, 'accept'])->name('challenges.accept');
        Route::post('/challenges/{challenge}/decline', [ChallengeController::class, 'decline'])->name('challenges.decline');
        Route::post('/challenges/{challenge}/cancel', [ChallengeController::class, 'cancel'])->name('challenges.cancel');

        Route::get('/duels/{duel}/draft', [DraftController::class, 'show'])->name('duels.draft');
        Route::post('/duels/{duel}/draft', [DraftController::class, 'store'])->name('duels.draft.store');
        Route::get('/duels/{duel}/state', [DraftController::class, 'state'])->name('duels.state');
        Route::get('/duels', [DuelController::class, 'index'])->name('duels.index');
        Route::get('/duels/{duel}', [DuelController::class, 'show'])->name('duels.show');
        Route::get('/duels/{duel}/result', [DuelController::class, 'result'])->name('duels.result');
        Route::get('/duels/{duel}/battle', [BattleController::class, 'show'])->name('duels.battle');
        Route::post('/duels/{duel}/battle', [BattleController::class, 'answer'])->name('duels.battle.answer');
        Route::get('/duels/{duel}/battle/state', [BattleController::class, 'state'])->name('duels.battle.state');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('courses', CourseController::class);
        Route::resource('materials', MaterialController::class)->except('show');
        Route::resource('questions', QuestionController::class)->except('show');
        Route::patch('questions/{question}/toggle-status', [QuestionController::class, 'toggleStatus'])->name('questions.toggle-status');
        Route::patch('questions/{question}/approve', [QuestionController::class, 'approve'])->name('questions.approve');
    });
});
