<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JustificationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    // ----- Agent + Administrateur -----
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/scanner', [ScanController::class, 'index'])->name('scan');
    Route::post('/scanner', [ScanController::class, 'store'])->middleware('throttle:240,1')->name('scan.store');
    Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
    Route::get('/participants/{participant}', [ParticipantController::class, 'show'])->whereNumber('participant')->name('participants.show');
    Route::get('/presences', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/mot-de-passe', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/mot-de-passe', [PasswordController::class, 'update'])->name('password.update');

    // ----- Administrateur uniquement -----
    Route::middleware('can:admin')->group(function () {
        Route::get('/participants/nouveau', [ParticipantController::class, 'create'])->name('participants.create');
        Route::post('/participants', [ParticipantController::class, 'store'])->name('participants.store');
        Route::get('/participants/{participant}/modifier', [ParticipantController::class, 'edit'])->whereNumber('participant')->name('participants.edit');
        Route::put('/participants/{participant}', [ParticipantController::class, 'update'])->whereNumber('participant')->name('participants.update');
        Route::patch('/participants/{participant}/statut', [ParticipantController::class, 'toggle'])->whereNumber('participant')->name('participants.toggle');
        Route::delete('/participants/{participant}', [ParticipantController::class, 'destroy'])->whereNumber('participant')->name('participants.destroy');
        Route::post('/participants/{participant}/badge', [BadgeController::class, 'generate'])->whereNumber('participant')->name('participants.badge.generate');
        Route::get('/participants/{participant}/badge.pdf', [BadgeController::class, 'participantDownload'])->whereNumber('participant')->name('participants.badge.download');

        Route::get('/badges', [BadgeController::class, 'index'])->name('badges.index');
        Route::get('/badges/telecharger/zip', [BadgeController::class, 'zip'])->name('badges.zip');
        Route::get('/badges/telecharger/pdf', [BadgeController::class, 'pdfAll'])->name('badges.pdf');
        Route::get('/badges/imprimer', [BadgeController::class, 'printAll'])->name('badges.print');
        Route::get('/badges/{badge}', [BadgeController::class, 'show'])->whereNumber('badge')->name('badges.show');
        Route::get('/badges/{badge}/pdf', [BadgeController::class, 'download'])->whereNumber('badge')->name('badges.download');
        Route::get('/badges/{badge}/imprimer', [BadgeController::class, 'printOne'])->whereNumber('badge')->name('badges.print-one');
        Route::post('/badges/{badge}/remplacer', [BadgeController::class, 'replace'])->whereNumber('badge')->name('badges.replace');
        Route::patch('/badges/{badge}/statut', [BadgeController::class, 'toggle'])->whereNumber('badge')->name('badges.toggle');

        Route::get('/presences/export/{format}', [AttendanceController::class, 'export'])->whereIn('format', ['pdf', 'xlsx', 'csv'])->name('attendances.export');
        Route::get('/presences/justifier', [JustificationController::class, 'create'])->name('justifications.create');
        Route::post('/presences/justifier', [JustificationController::class, 'store'])->name('justifications.store');
        Route::get('/justifications/{justification}/document', [JustificationController::class, 'document'])->whereNumber('justification')->name('justifications.document');
        Route::delete('/justifications/{justification}', [JustificationController::class, 'destroy'])->whereNumber('justification')->name('justifications.destroy');
        Route::get('/rapports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/parametres', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/parametres', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
        Route::get('/utilisateurs/nouveau', [UserController::class, 'create'])->name('users.create');
        Route::post('/utilisateurs', [UserController::class, 'store'])->name('users.store');
        Route::get('/utilisateurs/{user}/modifier', [UserController::class, 'edit'])->whereNumber('user')->name('users.edit');
        Route::put('/utilisateurs/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::patch('/utilisateurs/{user}/statut', [UserController::class, 'toggle'])->whereNumber('user')->name('users.toggle');
        Route::delete('/utilisateurs/{user}', [UserController::class, 'destroy'])->whereNumber('user')->name('users.destroy');

        Route::get('/journal', [ActivityLogController::class, 'index'])->name('logs.index');
    });
});
