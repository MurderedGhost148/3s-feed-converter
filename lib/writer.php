<?php

class Writer {
    private string $filePath;
    private string $started;

    private $file = null;

    public function __construct(string $path, string $fileName)
    {
        if(!dir_exists($path)) {
            mkdir($path, 0777, true);
        }

        $this->filePath = $path . "/" . $fileName;
        $this->started = false;
    }

    public function __destruct()
    {
        if($this->started && !empty($this->file)) {
            /** @noinspection PhpParamsInspection */
            fclose($this->file);
        }
    }

    public function write(string $str)
    {
        fwrite($this->getFile(), $str);
    }

    private function getFile()
    {
        if($this->file == null) {
            $this->file = fopen($this->filePath, 'w');
            $this->started = true;
        }

        return $this->file;
    }
}