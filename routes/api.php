<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| We have four endpoints for this application - all of them use post
|
*/
Route::post('check-range', [ApiController::class, 'checkRange']);

Route::post('book', [ApiController::class, 'book']);

Route::post('amend-booking', [ApiController::class, 'amendBooking']);

Route::post('cancel-booking', [ApiController::class, 'cancelBooking']);
