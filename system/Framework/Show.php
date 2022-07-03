<?php

/**
* @package      Show
* @version      1.0.0
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

namespace System\Framework;

class Show {

    public function html(string $filename, array $data = [], bool $code = false) {
		if(file_exists($filename)) {
			if($code) {
				ob_start();
				extract($data);
				include $filename;
				$content = ob_get_contents();
				ob_end_clean();
			}
			else {
				$content = file_get_contents($filename);
				if($data != NULL) {
					foreach($data as $key => $value) {
						$content = str_replace('{'.strtoupper($key).'}', $value, $content); 
					}
				}
			}
			$content = str_replace("\t", "", $content);
			if(preg_match('/(\s){2,}/s', $content) === 1) {
				$content = preg_replace('/(\s){2,}/s', '', $content);
			}
			$content = preg_replace("/[\n\r]/","",$content);
			return $content;
		}
		else {
			exit('File "'.$filename.'" does not exist.');
		}
	}

	public function json($Response, $header=TRUE) {
		$json = json_encode($Response);
		if($header) {
			header('Content-type: text/json;charset=UTF-8');
			echo $json;
		}
		else {
			return $json;
		}
	}

	public static function text(string $text,array $params=NULL) {
		if($params != NULL) {
			foreach($params as $param => $value) {
				$text = str_replace('{'.strtoupper($param).'}',$value,$text);
			}
		}
		return $text;
	}

	public static function file($path) {
		$patch = NULL;
		if(file_exists($path)) {
			ob_start();
			include $path;
			$path = ob_get_contents();
			ob_end_clean();
			return $path;
		}
		else { exit('File "'.$path.'" does not exist.'); }
	}
    
}