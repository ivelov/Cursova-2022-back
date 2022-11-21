<?php

use Illuminate\Support\Facades\Route;

//UserController
Route::post('/login', 'UserController@login');
Route::post('/register', 'UserController@register');
Route::post('/logout', 'UserController@logout');

Route::middleware("auth:sanctum")->group( function () {
    Route::get('/isAuth', 'UserController@isAuth');
    Route::get('/get-perks', 'UserController@getPerks');
});

Route::get('/account', 'UserController@show');
Route::post('/account/save', 'UserController@update');

Route::get('/account/favorites', 'UserController@favoritesCount');

Route::get('/getChannelId', 'UserController@getUserId');


//ConferenceController
Route::post('/conferences/{page}', 'ConferenceController@index');
Route::get('/conferences/{page}', 'ConferenceController@index');

Route::post('/conferences/delete/{id}', 'ConferenceController@destroy');
Route::get('/conference/{id}', 'ConferenceController@show');
Route::post('/conference/{id}/save', 'ConferenceController@update');
Route::post('/conference/{id}/join', 'ConferenceController@joinAsListener');
Route::post('/conference/{id}/cancelJoin', 'ConferenceController@cancelJoin');
Route::post('/add', 'ConferenceController@create');

Route::post('/conference/{id}/getBusyTimes', 'ConferenceController@getBusyTimes');
Route::get('/conference/{id}/getCategory', 'ConferenceController@getCategory');

Route::post('/conferencesFind', 'ConferenceController@search');


//ReportController
Route::post('/reports/{page}', 'ReportController@index');
Route::get('/reports/{page}', 'ReportController@index');

Route::post('/reports/delete/{id}', 'ReportController@destroy');
Route::post('/addReport', 'ReportController@create');

Route::get('/report/{id}', 'ReportController@show');
Route::post('/report/{id}/save', 'ReportController@update');

Route::post('/report/{id}/favorite', 'ReportController@favorite');
Route::post('/report/{id}/unfavorite', 'ReportController@unfavorite');

Route::post('/reportsFind', 'ReportController@search');

Route::get('/presentation/{name}', 'ReportController@downloadPresentation');


//CommentController
Route::get('/report/{repId}/comments/{page}', 'CommentController@index');
Route::post('/report/{repId}/addComment', 'CommentController@create');
Route::post('/report/{repId}/updateComment', 'CommentController@update');


//CategoryController
Route::get('/categories', 'CategoryController@index');
Route::get('/categories/{parentId}', 'CategoryController@index');
Route::get('/category/{id}', 'CategoryController@show');
Route::post('/addCategory', 'CategoryController@create');
Route::post('/category/{id}/save', 'CategoryController@update');
Route::post('/category/{id}/destroy', 'CategoryController@destroy');

Route::get('/categoriesList', 'CategoryController@indexList');


//ExportController
Route::post('/export/conferences', 'ExportController@exportConferences');
Route::post('/export/conference/{id}/reports', 'ExportController@exportReports');
Route::post('/export/conference/{id}/listeners', 'ExportController@exportListeners');
Route::post('/export/report/{id}/comments', 'ExportController@exportComments');


//ZoomController
Route::get('/meetings/{page}', 'ZoomController@index');
