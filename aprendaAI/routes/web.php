<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AdaptiveLearningController;
use App\Http\Controllers\StudyPlanController;
use App\Http\Controllers\ExplanationController;
use App\Http\Controllers\LearningInsightsController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    
    // Visualização de conteúdo adaptativo
    Route::prefix('learn')->group(function () {
        Route::get('/', function () {
            return Inertia::render('learn/index');
        })->name('learn.index');
        
        Route::get('subjects', function () {
            return Inertia::render('learn/subjects');
        })->name('learn.subjects');
        
        Route::get('subject/{id}', function ($id) {
            return Inertia::render('learn/subject', ['id' => $id]);
        })->name('learn.subject');
        
        Route::get('topic/{id}', function ($id) {
            return Inertia::render('learn/topic', ['id' => $id]);
        })->name('learn.topic');
        
        Route::get('question/{id}', function ($id) {
            return Inertia::render('learn/question', ['id' => $id]);
        })->name('learn.question');
    });
    
    // Estudo adaptativo
    Route::prefix('study')->group(function () {
        Route::get('plans', function () {
            return Inertia::render('study/plans');
        })->name('study.plans');
        
        Route::get('plan/{id}', function ($id) {
            return Inertia::render('study/plan', ['id' => $id]);
        })->name('study.plan');
        
        Route::get('session/{id}', function ($id) {
            return Inertia::render('study/session', ['id' => $id]);
        })->name('study.session');
    });
    
    // Análise de desempenho
    Route::prefix('analytics')->group(function () {
        Route::get('/', function () {
            return Inertia::render('analytics/index');
        })->name('analytics.index');
        
        Route::get('progress', function () {
            return Inertia::render('analytics/progress');
        })->name('analytics.progress');
        
        Route::get('insights', function () {
            return Inertia::render('analytics/insights');
        })->name('analytics.insights');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
