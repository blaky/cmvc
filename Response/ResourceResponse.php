<?php

namespace CacaoFw\Response;

class FileAttachmentResponse extends AbstractResponse {

    /**
     *
     * @var file
     */
    private $file;

    /**
     *
     * @var string
     */
    private $fileName;

    public function __construct($file, $fileName) {
        $this->file = $file;
        $this->fileName = $fileName;
        
        if (!file_exists($file)) {
            die("File Not Found");
        }
    }

    public function getFileName() {
        return $this->fileName;
    }

    public function getFile() {
        return $this->file;
    }

    public function build($requestParameters, $cfw) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $this->fileName);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($this->file));
        ob_clean();
        flush();
        readfile($this->file);
        exit();
    }
}