<?php

$app->route('*', function () use ($app) {
    $request = $app->request();

    if ($request->url != '') {
        list($base, $query) = array_pad(explode('?', $request->url, 2), 2, null);

        if (substr($base, -1) == '/' && strlen($base) > 1) {
            $url = rtrim($base, '/');

            if ($query !== null) {
                $url .= '?' . $query;
            }

            $app->redirect($url, 301);
        }
    }

    return true;
});

// ==================================== Sections Routes ====================================
$app->route('GET /legal', function () use ($app) {
    $index = new App\Controllers\CategoryController($app);
    echo $index->indexAction('legal');
});

$app->route('GET /laboral', function () use ($app) {
    $index = new App\Controllers\CategoryController($app);
    echo $index->indexAction('laboral');
});

$app->route('GET /fiscal-contable', function () use ($app) {
    $index = new App\Controllers\CategoryController($app);
    echo $index->indexAction('fiscal-contable');
});

// ==================================== Content Routes ====================================
$app->route('GET /@category:[a-z0-9-]+/@slug:[a-z0-9-]+', function ($category, $slug) use ($app) {
    $show = new App\Controllers\PostController($app);
    echo $show->showAction($category, $slug);
});

// ==================================== Home Route ====================================
$app->route('GET /', function () use ($app) {
    $index = new App\Controllers\HomeController($app);
    echo $index->indexAction();
});
