<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

$app = AppFactory::create();

session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
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
    $messages = $this->get('flash')->getMessages();
    $params = ['users' => $filteredUsers, 'flash' => $messages];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response, $args)  {

    return $this->get('renderer')->render($response, 'users/new.phtml');
})->setName('users_new');

$app->post('/users', function ($request, $response)  {
    $user = $request->getParsedBodyParam('user');
    $data = json_decode(file_get_contents('data/users.json'), true);

    $user = [
        "nickname" => $user['nickname'],
        "email" => $user['email'],
        "id" => uniqid()
    ];
    $data['users'][] = $user;
    file_put_contents('data/users.json', json_encode($data, JSON_PRETTY_PRINT));
    $this->get('flash')->addMessage('success', 'User was added successfully');
    return $response->withRedirect('/users');
})->setName('users_create');

$app->get('/users/{id}', function ($request, $response, $args) {
    $id = $args['id'];

    // Если файл не найден или не существует пользователь с заданным id, возвращаем 404 ошибку
    if (!file_exists('data/users.json')) {
        return $response->withStatus(404);
    } else {
        $data = file_get_contents('data/users.json');
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

$app->get('/foo', function ($req, $res) {
    // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
    // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
    // Например, можно ввести тип success и отражать его зеленым цветом (на Хекслете такого много)
    $this->get('flash')->addMessage('success', 'This is a message');

    return $res->withRedirect('/bar');
});

$app->get('/bar', function ($req, $res) {
    // Извлечение flash-сообщений, установленных на предыдущем запросе
    $messages = $this->get('flash')->getMessages();
    print_r($messages); // => ['success' => ['This is a message']]

    $params = ['flash' => $messages];
    return $this->get('renderer')->render($res, 'bar.phtml', $params);
});

$app->run();