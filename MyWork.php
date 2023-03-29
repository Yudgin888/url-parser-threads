<?php


class MyWork extends Threaded
{
    private $number; // номер потока
    private $counter; // количество обработанных url

    private $provider;
    private $root;
    private $transition;
    private $checkstatus;
    private $next_url;

    public function __construct($number, $next_url)
    {
        $this->counter = 0;
        $this->number = $number;
        $this->next_url = $next_url;
    }

    public function run()
    {
        $this->provider = $this->worker->getProvider();

        $this->root = $this->provider->getRoot();
        $this->transition = $this->provider->getTransition();
        $this->checkstatus = $this->provider->getCheckstatus();
        $data = $this->next_url;

        $url = $data['url'];
        $depth = $data['depth'];
        $parrent_url = $data['parrent_url'];

        $typeUrl = $this->checkTypeUrl($url);
        if ($typeUrl === $this->transition || $this->transition === ALL) {
            $arr = [
                'url' => $url,
                'status' => $this->checkstatus ? $this->getStatus($url) : null,
                'depth' => $depth,
                'parrent_url' => $parrent_url,
                'type' => $typeUrl,
            ];
            $this->provider->synchronized(function ($provider) use ($arr, $typeUrl) {
                $provider->putLink($arr, $typeUrl);
            }, $this->provider);
            $this->counter++;

            echo 'Th: ' . $this->number . ' (' . $this->counter . ') url: ' . $arr['url'] . ', status: ' . $arr['status'] .
                ', depth: ' . $arr['depth'] . PHP_EOL;
        }
        if ($typeUrl === INTERNAL) {
            $this->pageParser($url, $depth + 1);
        }

        $this->provider->synchronized(function ($provider) {
            $provider->decThreadsCounter();
        }, $this->provider);
        //echo "Thread {$this->number} stopped" . PHP_EOL;
    }

    private function pageParser($url, $depth)
    {
        $html = new simple_html_dom();
        try {
            $html->load_file($url);
            if ($html !== null && is_object($html) && isset($html->nodes) && count($html->nodes) > 0) {
                $alllinks = $html->find('a[href]');
                foreach ($alllinks as $link) {
                    $href = $link->attr['href'];
                    //echo 'Parse url:' . $href . PHP_EOL;
                    if ($href != null) {
                        if (preg_match('/\.(png|jpeg|gif|jpg|js|css|xml|pdf)/', $href)) {
                            continue;
                        }
                        $href = $this->prepareUrl($href);
                        $rawLink = [
                            'url' => $href,
                            'depth' => $depth,
                            'parrent_url' => $url,
                        ];
                        $this->provider->synchronized(function ($provider) use ($href, $rawLink) {
                            if ($provider->putUniqueLink($href)) {
                                $provider->putRawLink($rawLink);
                            }
                        }, $this->provider);
                    }
                }
            }
        } catch (Exception $ex) {
            echo 'Error: ' . $url . PHP_EOL;
        }
        $html->clear();
    }

    private function checkTypeUrl($url)
    {
        $parts = parse_url($url);
        if (!isset($parts['scheme']) && !isset($parts['host'])) {
            return INTERNAL;
        } else {
            if (strcmp($this->root, $parts['scheme'] . '://' . $parts['host']) === 0) {
                return INTERNAL;
            } else return EXTERNAL;
        }
    }

    private function getStatus($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_exec($handle);
        return curl_getinfo($handle, CURLINFO_HTTP_CODE);
    }

    private function prepareUrl($url)
    {
        if ($url === '/') {
            $url = $this->root;
        }
        $parts = parse_url($url);
        if (!isset($parts['scheme']) && !isset($parts['host'])) {
            $url = $this->root . $url;
        } elseif (!isset($parts['scheme'])) {
            $url = 'http:' . $url;
        }
        if (isset($parts['fragment'])) {
            $url = substr($url, 0, strpos($url, '#'));
        }
        return $url;
    }
}
