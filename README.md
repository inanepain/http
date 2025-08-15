# ![icon](./icon.png) inanepain/http

Http client (psr-18), request and response object message interfaces
(psr-7).

# Install

composer

composer require inanepain/http

# Usage

    $client = new \Inane\Http\Client();
    $response = new \Inane\Http\Response();
    $response->setBody('{"title":"Example"}');
    $client->send($response);
