<?php
require_once "simplehtmldom/simple_html_dom.php";
require_once 'MyWorker.php';
require_once 'MyWork.php';
require_once 'MyDataProvider.php';

set_time_limit(0);
define("INTERNAL", 1); // только внутренние
define("EXTERNAL", 2); // только внешние
define("ALL", 3); // внутренние и внешние (переход на внешние не делается)

class Urlparser
{
    private $max_threads;
    private $save_to_db;
    private $starturl;
    public $provider;

    public function __construct($url, $max_threads, $depth = 0, $transition = INTERNAL, $checkstatus = false, $save_to_db = false)
    {
        $this->starturl = $url;
        $parts = parse_url($url);
        $root = $parts['scheme'] . '://' . $parts['host'];
        $this->provider = new MyDataProvider($depth, $root, $transition, $checkstatus);
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
        //$pool = new Pool($this->max_threads, 'MyWorker', [$this->provider]);
        $i = 1; // номер потока
        do {
            $next_url = null;
            $this->provider->synchronized(function ($provider) use (&$next_url) {
                $next_url = $provider->getNextUrl();
            }, $this->provider);
            if ($next_url === null) {
                echo 'next = null' . PHP_EOL;
                usleep(500000); //ждать полсекунды
            } else {
                //$pool->submit(new MyWork($i, $next_url));
                new MyWork($i, $next_url);
                echo 'Start #' . $i . PHP_EOL;
                $i++;
            }
            $threads_counter = 0;
            $this->provider->synchronized(function ($provider) use (&$threads_counter) {
                $threads_counter = $provider->getThreadsCounter();
            }, $this->provider);
            echo 'pool: ' . $threads_counter . PHP_EOL;
        } while ($threads_counter > 0 || $next_url !== null);
        echo 'Start threads stopped!' . PHP_EOL;
        //$pool->shutdown();

        /*if ($save_to_db) {
            $this->saveToDB(true);
        }*/
    }
}