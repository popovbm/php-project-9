<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Factory\AppFactory;
use DI\Container;
use Valitron\Validator;
use Hexlet\Code\Connection;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);


$app->get('/', function (Request $request, Response $response) {
    return $this->get('renderer')->render($response, "main.phtml");
})->setName('main');

$app->get('/urls', function ($request, $response) {
    $pdo = Connection::get()->connect();
    $query = 'SELECT * FROM urls ORDER BY id DESC';
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $select = $stmt->fetchAll();
    $params = [
        'data' => $select
    ];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $messages = $this->get('flash')->getMessages();
    $id = $args['id'];

    $pdo = Connection::get()->connect();
    $query = 'SELECT * FROM urls WHERE id = ?';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $select = $stmt->fetch();

    $params = [
        'flash' => $messages,
        'data' => $select
    ];
    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('url');

$router = $app->getRouteCollector()->getRouteParser();

$app->post('/urls', function ($request, $response) use ($router) {
    $formData = $request->getParsedBody()['url'];

    $validator = new Validator($formData);
    $validator->rule('required', 'name')->message('URL не должен быть пустым');
    $validator->rule('url', 'name')->message('Некорректный URL');
    $validator->rule('lengthMax', 'name', 255)->message('Некорректный URL');

    if ($validator->validate()) {
        try {
            $pdo = Connection::get()->connect();
            echo 'A connection to the PostgreSQL database sever has been established successfully.';

            $parsedDatabaseSql = file_get_contents(__DIR__ . '/../database.sql');
            $pdo->exec($parsedDatabaseSql);

            $url = strtolower($formData['name']);
            $parsedUrl = parse_url($url);
            $urlName = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
            $createdAt = Carbon::now();

            $queryUrl = 'SELECT name FROM urls WHERE name = ?';
            $stmt = $pdo->prepare($queryUrl);
            $stmt->execute([$urlName]);
            $selectedUrl = $stmt->fetchAll();

            if (count($selectedUrl) > 0) {
                $queryId = 'SELECT id FROM urls WHERE name = ?';
                $stmt = $pdo->prepare($queryId);
                $stmt->execute([$urlName]);
                $selectId = $stmt->fetchColumn();
                $this->get('flash')->addMessage('success', 'Страница уже существует');
                return $response->withRedirect($router->urlFor('url', ['id' => $selectId]));
            }

            $sql = "INSERT INTO urls (name, created_at) VALUES (?, ?);";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$urlName, $createdAt]);
            $lastInsertId = $pdo->lastInsertId();
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
            return $response->withRedirect($router->urlFor('url', ['id' => $lastInsertId]));
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    $errors = $validator->errors();
    $params = [
        'url' => $formData['name'],
        'errors' => $errors,
        'invalidForm' => 'is-invalid'
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'main.phtml', $params);
});

// $app->post('/urls/{id}/checks', function ($request, $response, $args) {
//     $id = $args['id'];
// });

$app->run();
