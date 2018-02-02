<?php

namespace AdamDBurton\EloquentImageAttachments;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class ImageAttachmentService
{
	private static function getConfig($path = null)
	{
		return Config::get('image-attachments' . ($path ? '.' . $path : ''));
	}

	private static function getExtension(Image $image)
	{
		$mime = $image->mime();

		if($mime == 'image/jpeg')
		{
			return '.jpg';
		}
		elseif($mime == 'image/png')
		{
			return '.png';
		}
		elseif($mime == 'image/gif')
		{
			return '.gif';
		}

		return '';
	}

	private static function generatePath(Image $image, $transformName)
	{
		return trim(self::getConfig('base_path'), '/') . '/' . Carbon::now()->format('Y/m/d') . '/' . md5($image->filename . time()) . '_' . $transformName . self::getExtension($image);
	}

	private static function storeImage(Image $image, $transformName, $quality)
	{
		$path = self::generatePath($image, $transformName);

		$disk = Storage::disk(self::getConfig('storage_driver'));
		$success = $disk->put($path, $image->stream(null, $quality));
		$url = $disk->url($path);

		if($success)
		{
			return [
				'path' => $path,
				'width' => $image->width(),
				'height' => $image->height(),
				'url' => $url,
			];
		}
	}

	private static function transformImage(Image $image, $width, $height, $crop, $scaleUp)
	{
		return $image->fit($width, $crop ? null : $height, function($constraint) use ($crop, $scaleUp)
		{
			if(!$crop)
			{
				$constraint->aspectRatio();
			}

			if(!$scaleUp)
			{
				$constraint->upsize();
			}
		});
	}

	public static function saveImage($originalImage)
	{
		$driver = self::getConfig('storage_driver');

		$data = [
			'driver' => $driver
		];

		$manager = new ImageManager;

		foreach(self::getConfig('transforms') as $transformName => $transformSettings)
		{
			$image = $manager->make($originalImage);

			$width = (int) isset($transformSettings['width']) ? $transformSettings['width'] : null;
			$height = (int) isset($transformSettings['height']) ? $transformSettings['height'] : null;
			$crop = (bool) isset($transformSettings['crop']) ? $transformSettings['crop'] : true;
			$scaleUp = (bool) isset($transformSettings['scaleUp']) ? $transformSettings['scaleUp'] : false;
			$quality = (int) isset($transformSettings['quality']) ? $transformSettings['quality'] : 85;
			$callback = isset($transformSettings['callback']) && is_callable($transformSettings['callback']) ? $transformSettings['callback'] : function(Image $image) {};

			$image = self::transformImage($image, $width, $height, $crop, $scaleUp);

			$callback($image);

			$data[$transformName] = self::storeImage($image, $transformName, $quality);
		}

		return array_filter($data);
	}

	public static function deleteImage($value)
	{dd($value);
		if(is_object($value) && $value->driver)
		{
			foreach($value as $transformName => $data)
			{
				if(is_object($data))
				{
					Storage::disk($value->driver)->delete($data->path);
				}
			}
		}
	}
}