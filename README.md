# ![icon](./icon.png) inanepain/http

Http client, request and response objects implementing psr-7 (message
interfaces).

# Install

composer

    composer require inanepain/http

# Usage

    $client = new \Inane\Http\Client();
    $response = new \Inane\Http\Response();
    $response->setBody('{"title":"Example"}');
    $client->send($response);
