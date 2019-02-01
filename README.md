# php-http-parser

Парсер сайтов.

## Пример использования

```php
$parser = new Parser();
$parser->setConnectionTimeout(3);
$parser->setTimeoutBetweenRequest(1);

// $parser->setCurlParams([
//     \CURLOPT_HEADER => false,
// ]);
$parser->appendCurlParams([
    \CURLOPT_HEADER => false,
]);
$parser->setSuccessResponseCodes([
    '200',
]);
$parser->setUrls([
    'https://google.com',
    'https://ya.ru',
    'https://google.com',
]);
// $parser->setProxies([
//     '118.173.232.75:59724',
//     '118.172.201.55:44232',
//     '95.111.124.24:60327',
// ]);

// save pages
$savePagesDir = __DIR__.'/pages';
$parser->setSuccessHandler(function($url, $responseContent, $curlInfo) use($savePagesDir) {
    $pageFile = md5($url).'.html';
    file_put_contents("{$savePagesDir}/{$pageFile}", $responseContent);
});
$parser->setFailHandler(function($url, $responseContent, $curlInfo) {
    echo '<pre>';
    var_dump([
        "fail",
        $curlInfo['http_code'],
        $responseContent
    ]);
    echo '</pre>';
    die();
});
$parser->setExceptionHandler(function(\Throwable $e) {
    echo '<pre>';
    var_dump([
        "exception",
        $e->getError(),
    ]);
    echo '</pre>';
    die();
});
$parser->run();
```
