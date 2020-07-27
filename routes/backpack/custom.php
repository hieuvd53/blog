<?php

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => [
        config('backpack.base.web_middleware', 'web'),
        config('backpack.base.middleware_key', 'admin'),
    ],
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes

    Route::crud('master_schools', 'SchoolCrudController');
    Route::group(['prefix' => 'master_schools/{school_id}'], function () {
        Route::crud('classes', 'SclassCrudController');
        Route::group(['prefix' => 'classes/{class_id}'], function () {
            Route::crud('students', 'StudentCrudController');
        });
    });

}); // this should be the absolute last line of this file
