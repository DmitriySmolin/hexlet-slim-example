<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$app = AppFactory::create();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
})->setName('index');

//$app->get('/users', function ($request, $response) {
//    return $response->write('GET /users');
//});

//$app->post('/users', function ($request, $response) {
//    return $response->write('POST /users');
//});

//$app->post('/users', function ($request, $response) {
//    return $response->withStatus(302);
//});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('courses');

//$app->get('/users/{id}', function ($request, $response, $args) {
//    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
//    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
//    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
//    // $this в Slim это контейнер зависимостей
//    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
//});

$app->get('/users', function ($request, $response) use ($users) {

    $term = $request->getQueryParam('term');
    $filteredUsers = array_filter($users, fn($user) => str_contains($user, $term));
    $params = ['users' => $filteredUsers];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response, $args)  {

    return $this->get('renderer')->render($response, 'users/new.phtml');
})->setName('users_new');

$app->post('/users', function ($request, $response)  {
    $user = $request->getParsedBodyParam('user');
    $user['id'] = uniqid();
    $jsonData = json_encode($user);
    file_put_contents('data/user.json', $jsonData);

    return $response->withRedirect('/users');
})->setName('users_create');

$app->get('/users/{id}', function ($request, $response, $args) {
    $id = $args['id'];

    // Если файл не найден или не существует пользователь с заданным id, возвращаем 404 ошибку
    if (!file_exists('data/user.json')) {
        return $response->withStatus(404);
    } else {
        $data = file_get_contents('data/user.json');
        $users = json_decode($data, true);

        foreach ($users['users'] as $user) {
            if ($user['id'] === $id) {
                $params = ['user' => $user];
                return $this->get('renderer')->render($response, 'users/show.phtml', $params);
            }
        }
    }
    return $response->withStatus(404);
})->setName('users_show');

$app->run();