<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Factory\AppFactory;
use DI\Container;
use Valitron\Validator;
use Hexlet\Code\Connection;
use Hexlet\Code\GetHttpInfo;
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
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$customErrorHandler = function () use ($app) {
 // обработка несуществующей страницы
    $response = $app->getResponseFactory()->createResponse();
    return $this->get('renderer')->render($response, "error404.phtml");
};
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$router = $app->getRouteCollector()->getRouteParser();

try { // первичное подключение к бд
    $pdo = Connection::get()->connect();
    $parsedDatabaseSql = file_get_contents(__DIR__ . '/../database.sql');
    if ($parsedDatabaseSql === false) {
        throw new \Exception("Error reading database.sql");
    } else {
        $pdo->exec($parsedDatabaseSql);
    }
} catch (\Exception $e) {
    return $e->getMessage();
}

$app->get('/', function (Request $request, Response $response) {
    return $this->get('renderer')->render($response, "main.phtml");
})->setName('main');

$app->get('/urls', function ($request, $response) {
    $pdo = Connection::get()->connect();
    $queryUrl = 'SELECT id, name FROM urls ORDER BY created_at DESC';
    $stmt = $pdo->prepare($queryUrl);
    $stmt->execute();
    $selectedUrls = $stmt->fetchAll(\PDO::FETCH_UNIQUE);

    $queryChecks = 'SELECT 
    url_id, 
    created_at, 
    status_code, 
    h1, 
    title, 
    description 
    FROM url_checks';
    $stmt = $pdo->prepare($queryChecks);
    $stmt->execute();
    $selectedChecks = $stmt->fetchAll(\PDO::FETCH_UNIQUE);

    foreach ($selectedChecks as $key => $value) {
        if (array_key_exists($key, $selectedUrls)) {
            $selectedUrls[$key] = array_merge($selectedUrls[$key], $value);
        }
    }

    $params = [
        'data' => $selectedUrls
    ];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $messages = $this->get('flash')->getMessages();
    $alert = key($messages) === 'success' ? 'success' : 'warning';

    $id = $args['id'];

    $pdo = Connection::get()->connect();
    $query = 'SELECT * FROM urls WHERE id = ?';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $select = $stmt->fetch();

    if ($select) {
        $queryCheck = 'SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC';
        $stmt = $pdo->prepare($queryCheck);
        $stmt->execute([$id]);
        $selectedCheck = $stmt->fetchAll();

        $params = [
            'flash' => $messages,
            'data' => $select,
            'checkData' => $selectedCheck,
            'alert' => $alert
        ];
        return $this->get('renderer')->render($response, 'url.phtml', $params);
    }
})->setName('url');



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
                $selectId = (string) $stmt->fetchColumn();

                $this->get('flash')->addMessage('success', 'Страница уже существует');
                return $response->withRedirect($router->urlFor('url', ['id' => $selectId]));
            }

            $sql = "INSERT INTO urls (name, created_at) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$urlName, $createdAt]);
            $lastInsertId = (string) $pdo->lastInsertId();

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

        $queryUrl = 'SELECT name FROM urls WHERE id = ?';
        $stmt = $pdo->prepare($queryUrl);
        $stmt->execute([$id]);
        $selectedUrl = $stmt->fetch(\PDO::FETCH_COLUMN);

        $createdAt = Carbon::now();

        $client = new GetHttpInfo($selectedUrl);
        $statusCode = $client->getStatusCode();
        if ($statusCode === 'error') { // проверка исключений GuzzleHttp
            $errorMessage = 'Проверка была выполнена успешно, но сервер ответил c ошибкой';
            $this->get('flash')->addMessage('error', $errorMessage);
            $response = $response->withStatus(500);
            return $this->get('renderer')->render($response, 'error500x.phtml');
        }
        $htmlData = $client->getHtmlData();

        $sql = "INSERT INTO url_checks (
            url_id, 
            created_at, 
            status_code, 
            h1, 
            title, 
            description) 
            VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $createdAt, $statusCode, $htmlData['h1'], $htmlData['title'], $htmlData['description']]);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }

    return $response->withRedirect($router->urlFor('url', ['id' => $id]));
});

$app->run();
