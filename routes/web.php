<?php

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Route;
use League\Pipeline\PipelineBuilder;

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
//            function ($passable, $next) {
//                throw new Exception('Something went wrong');
//            },
        ])
        ->then(function ($passable) {
            return $passable;
        });

    dd($pipeline , $response);
});

// Testing Laravel Pipelines with league/pipeline
// https://github.com/thephpleague/pipeline
Route::get('/pipeline/2', function () {

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

    $pipeline =  (new League\Pipeline\Pipeline)
        ->pipe(function ($passable) {
            $functionStatus = 1; // pending

            if ($passable->status > $functionStatus) {
                return $passable;
            }

            $passable->status = $functionStatus;
            dump($passable->status);
            return $passable;
        })
        ->pipe(function ($passable) {
            $functionStatus = 2; // calculating

            if ($passable->status > $functionStatus) {
                return $passable;
            }

            $passable->status = $functionStatus;
            dump($passable->status);

            $passable->total = collect($passable->line_items)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });
            return $passable;
        })
        ->pipe(function ($passable) {
            $functionStatus = 3; // calculating

            if ($passable->status > $functionStatus) {
                return $passable;
            }

            $passable->status = $functionStatus;

            throw new Exception('Something went wrong');

            return $passable;
        })
        ->pipe(function ($passable) {
            $functionStatus = 4; // completed

            if ($passable->status > $functionStatus) {
                return $passable;
            }

            $passable->status = $functionStatus;
            dump($passable->status);
            return $passable;
        });

    try {
        $response = $pipeline->process($order);
    } catch(Exception $e) {
        $response = $e->getMessage();
    }

    dd($pipeline , $response, $order);
});

// Testing Laravel Pipelines with league/pipeline
// Testing with PipelineBuilder
Route::get('/pipeline/3', function () {
    // Prepare the builder
    $pipelineBuilder = (new PipelineBuilder)
        ->add(function ($passable) {
            return $passable . ' Hello';
        })
        ->add(function ($passable) {
            return $passable . ' World';
        });

    // Build the pipeline
    $pipeline = $pipelineBuilder->build();

    dd($pipelineBuilder, $pipeline, $pipeline->process(''));
});
