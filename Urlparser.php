<?php
require_once "simplehtmldom/simple_html_dom.php";
require_once 'MyWorker.php';
require_once 'MyWork.php';
require_once 'MyDataProvider.php';
require_once "mysql_wrapper.php";

set_time_limit(0);
define("INTERNAL", 1); // только внутренние
define("EXTERNAL", 2); // только внешние
define("ALL", 3); // внутренние и внешние (переход на внешние не делается)

class Urlparser
{
    private $max_threads;
    private $save_to_db;
    private $starturl;
    private $max_depth;
    public $provider;

    public function __construct($url, $max_threads, $depth = 0, $transition = INTERNAL, $checkstatus = false, $save_to_db = false)
    {
        $this->starturl = $url;
        $this->max_depth = $depth;
        $parts = parse_url($url);
        $root = $parts['scheme'] . '://' . $parts['host'];
        $this->provider = new MyDataProvider($root, $transition, $checkstatus);
        $this->provider->putUniqueLink($url);
        $rawLink = [
            'url' => $url,
            'depth' => 0,
            'parrent_url' => null,
        ];
        $this->provider->putRawLink($rawLink);
        $this->max_threads = $max_threads;
        $this->save_to_db = $save_to_db;
    }

    public function parse()
    {
        echo 'Start parse!' . PHP_EOL;
        $pool = new Pool($this->max_threads, 'MyWorker', [$this->provider]);
        $i = 1; // номер потока
        while ($this->checkStatus()) {
            $next_url = null;
            $this->provider->synchronized(function ($provider) use (&$next_url) {
                $next_url = $provider->getNextUrl();
            }, $this->provider);
            if ($next_url !== null && ($this->max_depth === 0 || $next_url['depth'] <= $this->max_depth)) {
                $this->provider->synchronized(function ($provider) {
                    $provider->incThreadsCounter();
                }, $this->provider);
                $pool->submit(new MyWork($i, $next_url));
                //echo 'Start thread #' . $i . PHP_EOL;
                $i++;
            }
        }
        echo 'Stop parse!' . PHP_EOL;
        if ($this->save_to_db) {
            $this->saveToDB(true);
        }
        $pool->shutdown();
    }

    public function checkStatus()
    {
        $status = false;
        $this->provider->synchronized(function ($provider) use (&$status) {
            $status = $provider->checkStatus();
        }, $this->provider);
        return $status;
    }

    public function saveToDB($overwrite)
    {
        saveAll((array)$this->provider->getLinks(), $overwrite);
    }
}