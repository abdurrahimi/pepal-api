<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

$router->get('/', function () use ($router) {
    echo "<center>Copyright &copy <a href='//instagram.com/puresomniac'>Abdur Rahimi</a> <script>document.write(new Date().getFullYear())</script></center>";
});


Route::group([

    'prefix' => 'v1'

], function ($router) {

    //Front Routes
    Route::get('/blog/post', 'Front\BlogController@index');
    Route::post('/blog/detail', 'Front\BlogController@detail');

    //CMS Route
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('register', 'AuthController@register')->name('register');
    Route::post('refresh', 'AuthController@refresh');
    Route::get('/get-rate', 'RateController@index');
    
    
    Route::group(['middleware'=>'auth:api'],function($router){
        Route::get('user', 'AuthController@me');
        Route::post('logout', 'AuthController@logout');
        Route::get('/order', 'OrderController@index');
        Route::get('/order/{id}', 'OrderController@show');
        Route::post('/order', 'OrderController@create');
        Route::post('/catatan-order/{id}', 'OrderController@storeCatatan');
        Route::post('/history-order/{id}', 'OrderController@storeHistory');

        Route::resource('category', 'CategoryController')->except(['show','create','edit']);
        Route::get('/category/all','CategoryController@all');
        Route::resource('post', 'PostController')->except(['create']);
    });

});
