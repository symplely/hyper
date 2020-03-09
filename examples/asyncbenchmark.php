<?php
include 'vendor/autoload.php';

function taskConcurrentRequests()
{
    $runs = 379;
    $start = microtime(true);
    for ($i = 0; $i < $runs; ++$i) {
        $request[] = yield \request(\http_get('http://127.0.0.1:8081'));
    }
    $totalTime = microtime(true) - $start;
    echo "Runs: " . number_format($runs) . "\n";
    echo "Runs per second: " . floor($runs / $totalTime) . "\n";
    echo "Average time per run: " . number_format(($totalTime / $runs) * 1000, 4) . " ms\n";
    echo "Total time: " . number_format($totalTime, 4) . " s\n";

    print_r($request);

    $responses = yield \fetch_await($request, 2);
    print_r($responses);
}

\coroutine_run(\taskConcurrentRequests());
