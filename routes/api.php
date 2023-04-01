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
    //CMS Route
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('refresh', 'AuthController@refresh');
    
    Route::group(['middleware'=>'auth:api'],function($router){
        Route::get('user', 'AuthController@me');
        Route::post('logout', 'AuthController@logout');
        Route::get('/order', 'OrderController@index');
        Route::post('/order', 'OrderController@create');
    });

});
