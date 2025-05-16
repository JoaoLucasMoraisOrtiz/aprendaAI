<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdaptiveLearningController;
use App\Http\Controllers\ExplanationController;
use App\Http\Controllers\LearningInsightsController;
use App\Http\Controllers\StudyPlanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Rotas de Aprendizado Adaptativo
    Route::prefix('adaptive-learning')->group(function () {
        Route::get('topics', [AdaptiveLearningController::class, 'index']);
        Route::get('questions/{topicId}', [AdaptiveLearningController::class, 'getQuestions']);
        Route::post('submit-answer', [AdaptiveLearningController::class, 'submitAnswer']);
        Route::get('explanation/{questionId}', [AdaptiveLearningController::class, 'getExplanation']);
        Route::get('performance-analysis', [AdaptiveLearningController::class, 'getPerformanceAnalysis']);
        Route::get('recommendations', [AdaptiveLearningController::class, 'recommendNextSteps']);
    });

    // Rotas de Explicações
    Route::prefix('explanations')->group(function () {
        Route::get('/', [ExplanationController::class, 'index']);
        Route::post('generate', [ExplanationController::class, 'generate']);
        Route::get('{id}', [ExplanationController::class, 'show']);
        Route::post('{id}/rate', [ExplanationController::class, 'rate']);
        Route::delete('{id}', [ExplanationController::class, 'destroy']);
    });

    // Rotas de Insights de Aprendizado
    Route::prefix('insights')->group(function () {
        Route::get('/', [LearningInsightsController::class, 'index']);
        Route::post('generate', [LearningInsightsController::class, 'generateInsights']);
        Route::get('{id}', [LearningInsightsController::class, 'show']);
        Route::get('topic/{topicId}', [LearningInsightsController::class, 'topicPerformance']);
    });

    // Rotas de Planos de Estudo
    Route::prefix('study-plans')->group(function () {
        Route::get('/', [StudyPlanController::class, 'index']);
        Route::get('create', [StudyPlanController::class, 'create']);
        Route::post('/', [StudyPlanController::class, 'store']);
        Route::post('adaptive', [StudyPlanController::class, 'generateAdaptive']);
        Route::get('{id}', [StudyPlanController::class, 'show']);
        Route::get('{id}/edit', [StudyPlanController::class, 'edit']);
        Route::put('{id}', [StudyPlanController::class, 'update']);
        Route::delete('{id}', [StudyPlanController::class, 'destroy']);
        Route::post('{planId}/sessions/{sessionId}/complete', [StudyPlanController::class, 'completeSession']);
        Route::post('{planId}/sessions/{sessionId}/reschedule', [StudyPlanController::class, 'rescheduleSession']);
    });
});
