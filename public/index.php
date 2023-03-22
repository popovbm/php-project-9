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
    $pdo = Connection::get()->connect();

    $parsedDatabaseSql = file_get_contents(__DIR__ . '/../database.sql');
    $pdo->exec($parsedDatabaseSql);
    return $this->get('renderer')->render($response, "main.phtml");
})->setName('main');

$app->get('/urls', function ($request, $response) {
    $pdo = Connection::get()->connect();
    $queryUrl = 'SELECT id, name FROM urls ORDER BY created_at DESC';
    $stmt = $pdo->prepare($queryUrl);
    $stmt->execute();
    $selectUrl = $stmt->fetchAll(\PDO::FETCH_UNIQUE);
    //dump($selectUrl);

    $queryChecks = 'SELECT url_id, created_at FROM url_checks';
    $stmt = $pdo->prepare($queryChecks);
    $stmt->execute();
    $selectedChecks = $stmt->fetchAll(\PDO::FETCH_UNIQUE);
    //dump($selectedChecks);

    foreach ($selectedChecks as $key => $value) {
        if (array_key_exists($key, $selectUrl)) {
            $selectUrl[$key] = array_merge($selectUrl[$key], $value);
        }
    }
    //dump($selectUrl);

    $params = [
        'data' => $selectUrl
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

    $queryCheck = 'SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC';
    $stmt = $pdo->prepare($queryCheck);
    $stmt->execute([$id]);
    $selectedCheck = $stmt->fetchAll();

    $params = [
        'flash' => $messages,
        'data' => $select,
        'checkData' => $selectedCheck
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

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router) {
    $id = $args['url_id'];

    try {
        $pdo = Connection::get()->connect();
        $createdAt = Carbon::now();
        $sql = "INSERT INTO url_checks (url_id, created_at) VALUES (?, ?);";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $createdAt]);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }

    return $response->withRedirect($router->urlFor('url', ['id' => $id]));
});

$app->run();
