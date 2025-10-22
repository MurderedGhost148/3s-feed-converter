<?php

namespace Receiving;

class Context {
    private string $house;
    private string $url;
    private bool $cleared;

    public function __construct(string $house, string $url)
    {
        $this->house = $house;
        $this->url = $url;
        $this->cleared = false;
    }

    public function getHouse() : string
    {
        return $this->house;
    }

    public function getUrl() : string
    {
        return $this->url;
    }

    public function isCleared() : bool
    {
        return $this->cleared;
    }

    public function setCleared(bool $cleared) : void
    {
        $this->cleared = $cleared;
    }
}