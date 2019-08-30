<?php
/**
 * @see https://github.com/amphp/artax/blob/master/examples/6-parallel-requests.php
 */
include 'vendor/autoload.php';

use Async\Coroutine\Exceptions\PanicInterface;
use Async\Coroutine\Kernel;

function main() {
    $uris = [
        "https://google.com/",
        "https://github.com/",
        "https://stackoverflow.com/"
    ];

    try {
        $uriId = [];

        // Make an asynchronous HTTP request
        foreach ($uris as $uri) {
            $uriId[] = yield \request(\http_get($uri));
        }
        // `yield \request()` is asynchronous! It doesn't return a response.
        // Instead, it returns a `int` Http task id to resolve the response
        // at some point in the future when we've received the headers of the response.
        // Here we use yield which pauses the execution of the current coroutine task
        // until the http task id resolves. Hyper will automatically continue the
        // coroutine then.
        $bodies = yield \fetch($uriId);

        //yield Kernel::readWait($bodies[4]->getBody()->getResource());
            //$body = yield \response_body($bodies[4]);
           // print_r($body);
        foreach ($bodies as $id => $result) {
            $uri = \response_meta($result, 'uri');
            print "HTTP Task $id: ". $uri. \EOL; //. " - " . \strlen($body) . " bytes" . \EOL;
        }
    } catch (PanicInterface $error) {
        echo 'There was a problem: '.$error->getMessage();
    }
}

\coroutine_run(\main());
