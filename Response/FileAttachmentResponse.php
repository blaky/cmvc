<?php

namespace CacaoFw\Response;

/**
 * Response that streams a file back to the client
 *
 * @author Bence
 *
 */
class FileAttachmentResponse extends AbstractResponse {

    /**
     * Path of the file.
     *
     * @var file
     */
    private $file;

    /**
     * Name of the file that is added to the
     *
     * @var string
     */
    private $fileName;

    /**
     *
     * @var bool
     */
    private $isAttachment;

    /**
     *
     * @param string $file path to the file
     * @param string $fileName (optional) Name of the file that is added to the response headers. If not supplied, the file's actual name is used.
     * @param string $isAttachment (optional) Forces the file to be downloaded, if set to false, the behaviour depends on the browser.
     */
    public function __construct($file, $fileName = null, $isAttachment = true) {
        $this->file = $file;
        $this->fileName = is_null($fileName) ? basename($file) : $fileName;
        $this->isAttachment = $isAttachment;
        
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
        header('Content-Type: ' . mime_content_type($this->file));
        if ($this->isAttachment) {
            header('Content-Disposition: attachment; filename=' . $this->fileName);
        }
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