<?php

class Parser
{
    protected $debug;
    protected $curlTimeout;
    protected $connectionTimeout;
    protected $timeoutBetweenRequest;
    protected $curlParams;
    protected $successResponseCodes;
    protected $urls = [];
    protected $proxies = [];
    protected $successHandler = null;
    protected $failHandler = null;
    protected $exceptionHandler = null;

    public function __construct()
    {
        $this->debug = true;
        $this->curlTimeout = 10;
        $this->connectionTimeout = 5;
        $this->timeoutBetweenRequest = 1;
        $this->successResponseCodes = [
            200
        ];
        $this->curlParams = [
            \CURLOPT_COOKIESESSION => false,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_HEADER => true,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HTTPHEADER => [
                'cache-control: max-age=0',
                'user-agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 OPR/57.0.3098.116'
            ],
        ];
    }

    public function setDebug(bool $value)
    {
        $this->debug = $value;
    }

    public function setCurlTimeout(int $value)
    {
        $this->curlTimeout = $value;
    }

    public function setConnectionTimeout(int $value)
    {
        $this->connectionTimeout = $value;
    }

    public function setTimeoutBetweenRequest(int $value)
    {
        $this->timeoutBetweenRequest = $value;
    }

    public function setCurlParams(array $value)
    {
        $this->curlParams = $value;
    }

    public function appendCurlParams(array $value)
    {
        $this->curlParams = array_merge($this->curlParams, $value);
    }

    public function setSuccessResponseCodes(array $value)
    {
        $this->successResponseCodes = $value;
    }

    public function setUrls(array $value)
    {
        $this->urls = $value;
    }

    public function setProxies(array $value)
    {
        $this->proxies = $value;
    }

    public function setSuccessHandler(callable $value)
    {
        $this->successHandler = $value;
    }

    public function setFailHandler(callable $value)
    {
        $this->failHandler = $value;
    }

    public function setExceptionHandler(callable $value)
    {
        $this->exceptionHandler = $value;
    }

    public function run()
    {
        $proxyList = $this->proxies;
        $totalCount = count($this->urls);
        $activeProxy = array_shift($proxyList);
        foreach ($this->urls as $url) {
            if ($this->debug) {
                $totalCount--;
                echo "Left: {$totalCount}".PHP_EOL;
            }

            if ($this->proxies && !$activeProxy) {
                $proxyList = $this->proxies;
                $activeProxy = array_shift($proxyList);
            }

            $ch = $this->getCurlHandler($url, $activeProxy);
            try {
                $response = curl_exec($ch);
                $info = curl_getinfo($ch);
                $error = curl_error($ch);
            }
            catch (\Throwable $e) {
                if ($this->exceptionHandler) {
                    call_user_func($this->exceptionHandler, $e);
                }
                else {
                    throw $e;
                }
                if ($proxyList) {
                    $activeProxy = array_shift($proxyList);
                }
            }
            finally {
                curl_close($ch);
            }

            if ($error) {
                throw new \Exception($error);
            }

            $code = $info['http_code'] ?? null;
            if (in_array($code, $this->successResponseCodes)) {
                if ($this->successHandler) {
                    call_user_func($this->successHandler, $url, $response, $info);
                }
            }
            else if ($this->failHandler) {
                call_user_func($this->failHandler, $url, $response, $info);
            }

            if ($this->timeoutBetweenRequest) {
                sleep($this->timeoutBetweenRequest);
            }
        }
    }

    public function getCurlHandler(string $url, string $activeProxy = null)
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->curlParams);
        curl_setopt($ch, \CURLOPT_URL, $url);
        if ($activeProxy) {
            curl_setopt($ch, \CURLOPT_PROXY, $activeProxy);
        }
        if ($this->curlTimeout) {
            curl_setopt($ch, \CURLOPT_TIMEOUT, $this->curlTimeout);
        }
        if ($this->connectionTimeout) {
            curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        }
        return $ch;
    }
}
