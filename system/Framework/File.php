<?php

/**
* @package      Upload
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;
class File {

    public $file_size = 2;

    public $token_length = 5;

    public $server_upload = '';

    public $extension_allowed = ['txt','png','jpe','jpeg','jpg','gif','bmp','ico','tiff','tif','svg','svgz','zip','rar','mp3','mov','pdf','doc'];

    public $mime_allowed = ['text/plain','image/png','image/jpeg','image/gif','image/bmp','image/svg+xml','application/zip','application/x-zip','application/x-zip-compressed','application/rar','application/x-rar','application/x-rar-compressed','application/octet-stream','audio/mpeg','video/quicktime','application/pdf', 'image/tiff'];
    
    public function __construct($registry) {
		$this->registry = $registry;
        $this->request = $this->registry->get('request');
	}

    public function upload($folder='') {

        $data = [];

        if(empty($folder)) { 
            $path = CONFIG_DIR_STORAGE . 'uploads/';
        }
        else {
            $path = CONFIG_DIR_STORAGE . 'uploads/' . $folder;
        }

        if (!is_dir($path)) {
            @mkdir($path, 0777);
        }

        if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {
            
            $filename = html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'); // Sanitize the filename

            // if ((mb_strlen($filename) < 3) || (mb_strlen($filename) > 128)) {
            //     $data['error'] = 'Filename must be between 3 and 128 characters!';
            // }

            if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $this->extension_allowed)) {
                $data['error'] = 'Invalid file type!';
            }

            if (!in_array($this->request->files['file']['type'], $this->mime_allowed)) {
                $data['error'] = 'Invalid file type!';
            }

            $content = file_get_contents($this->request->files['file']['tmp_name']); // Check to see if any PHP files are trying to be uploaded

            if (preg_match('/\<\?php/i', $content)) {
                $data['error'] = 'Invalid file type!';
            }

            if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
                $data['error'] = $this->codeToMessage($this->request->files['file']['error']); // Return any upload error
            }

            if (($this->request->files['file']['size'] > ($this->file_size * 1048576))){  
                $data['error'] = 'File too large.';
            }

        } 
        else {
            $data['error'] = 'File could not be uploaded!';
        }

        if (!$data) {
			$file = $this->token($this->token_length) . '-' . $filename;

            $this->uploadToServer($this->request->files['file']['tmp_name'], $path . $file);
            
            $data['success'] = 'Your file was successfully uploaded!';

            $data['server'] = $this->server_upload['url'];

            $data['extension'] = pathinfo($filename, PATHINFO_EXTENSION);

            // $data['file'] = $file;
            $data['filename'] = $file;
            $data['size'] = $this->request->files['file']['size'];
			
            $data['path'] = $path;
            $data['folder'] = $folder;
            $data['url'] = CONFIG_BASE_URL . 'storage/uploads/' . $folder . $file;
            
		}

        return $data;

    }

    private function uploadToServer($tmp, $file) {
        // add here the login to upload into specific server using ftp.
        move_uploaded_file($tmp, $file);
    }

    private function codeToMessage($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "Warning: The uploaded file exceeds the upload_max_filesize directive in php.ini.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "Warning: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "Warning: The uploaded file was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "Warning: No file was uploaded.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Warning: Missing a temporary folder.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Warning: Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "Warning: File upload stopped by extension.";
                break;
            default:
                $message = "Warning: Unknown upload error.";
                break;
        }
        return $message;
    }

    function token($length = 32) {
        $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($string) - 1;
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $string[mt_rand(0, $max)];
        }
        return $token;
    }

}