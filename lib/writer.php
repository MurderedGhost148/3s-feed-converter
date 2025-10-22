<?php

class Writer {
    private string $filePath;

    /** @var resource|null */
    private $file = null;

    public function __construct(string $path, string $fileName)
    {
        if(!dir_exists($path)) {
            mkdir($path, 0777, true);
        }

        $this->filePath = $path . "/" . $fileName;
        register_shutdown_function([$this, 'close']);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function write(string $str) : void
    {
        fwrite($this->getFile(), $str);
    }

    public function close(): void
    {
        if (is_resource($this->file)) {
            fclose($this->file);
            $this->file = null;
        }
    }

    private function getFile()
    {
        if($this->file == null) {
            $this->file = fopen($this->filePath, 'w');
        }

        return $this->file;
    }
}