<?php

/**
* @package      Library Image
* @version      v1.0.1
* @author       YoYo
* @copyright    Copyright (c), script-php.ro
* @link         https://script-php.ro
*/

use System\Framework\Image;

class LibraryImage extends Library {

	public function resize(string $filename, int $width, int $height): string {
		if (!is_file($filename)) {
			return '';
		}

		$extension = pathinfo($filename, PATHINFO_EXTENSION);

		$image_old = $filename;

		$image_new = 'storage/cache/' . substr($filename, 0, strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;

		if (!is_file($image_new) || (filemtime($image_old) > filemtime($image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize($image_old);
				 
			if (!in_array($image_type, [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
				return $image_old;
			}

			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				if (!$path) {
					$path = $directory;
				} else {
					$path = $path . '/' . $directory;
				}
				if (!is_dir($path)) {
					@mkdir($path, 0777);
				}
			}

			if ($width_orig != $width || $height_orig != $height) {
				$image = new Image($image_old);
				$image->resize($width, $height);
				$image->save($image_new);
			} else {
				copy($image_old, $image_new);
			}
		}

		return $image_new;
	}
	
}