<?php

/**
 * Data provider for threads
 */
class MyDataProvider extends Threaded
{
    private $raw_links; // необработанные ссылки
    private $links; // обработанные ссылки
    //          структура
    // 'url' => $url
    // 'status' => http статус
    // 'depth' => глубина ссылки от начальной
    // 'parrent_url' => родительская ссылка
    // 'type' => тип ссылки

    private $unique_links; // массив уникальных ссылок

    private $max_depth; // глубина прохода по ссылкам
    private $root; // корневой адрес сайта
    private $transition; // стратегия отсеивания ссылок (INTERNAL / EXTERNAL / ALL)
    private $checkstatus; // проверка http статуса

    public $internal_counter;
    public $external_counter;

    private $threads_counter;

    public function __construct($depth, $root, $transition, $checkstatus)
    {
        $this->raw_links = [];
        $this->unique_links = [];
        $this->links = [];
        $this->max_depth = $depth;
        $this->root = $root;
        $this->transition = $transition;
        $this->checkstatus = $checkstatus;
        $this->internal_counter = $this->external_counter = 0;
        $this->threads_counter = 0;
    }

    public function getThreadsCounter()
    {
        return $this->threads_counter;
    }

    public function incThreadsCounter()
    {
        $this->threads_counter += 1;
    }

    public function decThreadsCounter()
    {
        $this->threads_counter = (int)$this->threads_counter - 1;
    }

    public function incInternalCounter()
    {
        $this->internal_counter++;
    }

    public function incExternalCounter()
    {
        $this->external_counter++;
    }

    public function getCheckstatus()
    {
        return $this->checkstatus;
    }

    public function getTransition()
    {
        return $this->transition;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getMaxDepth()
    {
        return $this->max_depth;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function getNextUrl()
    {
        echo 'Quantity links: ' . count($this->raw_links) . PHP_EOL;
        if (count($this->raw_links) > 0) {
            return (array)$this->raw_links->pop();
        } else return null;
    }

    public function putRawLink($arr)
    {
        $this->raw_links[] = $arr;
    }

    public function putLink($arr)
    {
        $this->links[] = $arr;
    }

    public function putUniqueLink($url)
    {
        $unique_links = (array)$this->unique_links;
        if (!isset($unique_links[$url])) {
            $this->unique_links[$url] = 1;
            return true;
        } else return false;
    }
}
