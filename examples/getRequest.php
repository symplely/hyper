<?php

include 'vendor/autoload.php';

use Async\Coroutine\Exceptions\Panicking;

function main() {
    try {
        // Make an asynchronous HTTP request
        $response = yield \http_get($argv[1] ?? 'https://httpbin.org/user-agent');

        // Output the results
        \printf(
            "HTTP/%s %d %s\n\n",
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        $body = yield \response_body();
        print $body . "\n";
    } catch (Panicking $error) {
        // If something goes wrong Amp will throw the exception where the promise was yielded.
        // The Client::request() method itself will never throw directly, but returns a promise.
        echo $error;
    }
}

\coroutine_run(\main());
