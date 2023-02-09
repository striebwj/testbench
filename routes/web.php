<?php

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Testing Laravel Pipelines
// https://dev.to/abrardev99/pipeline-pattern-in-laravel-278p
Route::get('/pipeline', function () {

    $order = new stdClass();
    $order->status = 0;
    $order->line_items = [
        [
            'id' => 1,
            'name' => 'Item 1',
            'price' => 100,
            'quantity' => 2,
        ],
        [
            'id' => 2,
            'name' => 'Item 2',
            'price' => 200,
            'quantity' => 1,
        ],
    ];

    $pipeline = app(Pipeline::class);
    $response = $pipeline->send($order)
        ->through([
            function ($passable, $next) {
                $functionStatus = 1; // pending

                if ($passable->status > $functionStatus) {
                    return $next($passable);
                }

                $passable->status = $functionStatus;
                dump($passable->status);
                return $next($passable);
            },
            function ($passable, $next) {
                $functionStatus = 2; // calculating

                if ($passable->status > $functionStatus) {
                    return $next($passable);
                }

                $passable->status = $functionStatus;
                dump($passable->status);

                $passable->total = collect($passable->line_items)->sum(function ($item) {
                    return $item['price'] * $item['quantity'];
                });
                return $next($passable);
            },
        ])
        ->then(function ($passable) {
            return $passable;
        });

    dd($pipeline , $response);
});
