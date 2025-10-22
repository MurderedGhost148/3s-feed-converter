<?php

namespace Processing;

require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/writer.php";

class Context {
    private string $house;
    private \Writer $writer;

    public function __construct(string $house, \Writer $writer)
    {
        $this->house = $house;
        $this->writer = $writer;
    }

    public function getHouse() : string
    {
        return $this->house;
    }

    public function getWriter() : \Writer
    {
        return $this->writer;
    }
}