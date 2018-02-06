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

	private static function getDriver()
	{
		return self::getConfig('storage_driver');
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

	public static function storeImage(Image $image, $transformName, $quality = null)
	{
		if($quality === null)
		{
			$quality = self::getConfig('default_quality');
		}

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

	public static function transformImage(Image $image, $width = null, $height = null, $crop = true, $scaleUp = false)
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

	public static function makeImage($image)
	{
		$manager = new ImageManager;

		return $manager->make($image);
	}

	public static function performTransform(Image $image, $transformName, $transformSettings)
	{
		$width = (int) isset($transformSettings['width']) ? $transformSettings['width'] : null;
		$height = (int) isset($transformSettings['height']) ? $transformSettings['height'] : null;
		$crop = (bool) isset($transformSettings['crop']) ? $transformSettings['crop'] : null;
		$scaleUp = (bool) isset($transformSettings['scaleUp']) ? $transformSettings['scaleUp'] : null;
		$quality = (int) isset($transformSettings['quality']) ? $transformSettings['quality'] : null;
		$callback = isset($transformSettings['callback']) && is_callable($transformSettings['callback']) ? $transformSettings['callback'] : function(Image $image) {};

		$image = self::transformImage($image, $width, $height, $crop, $scaleUp);

		$callback($image);

		return self::storeImage($image, $transformName, $quality);
	}

	public static function saveImage($originalImage)
	{
		$driver = self::getConfig('storage_driver');

		$data = [
			'driver' => $driver,
			'transforms' => []
		];

		$image = self::makeImage($originalImage);

		$data['original'] = self::storeImage($image, 'original', 100);

		foreach(self::getConfig('transforms') as $transformName => $transformSettings)
		{
			$image = self::makeImage($originalImage);
			$image = self::performTransform($image, $transformName, $transformSettings);

			$data['transforms'][$transformName] = $image;
		}

		return array_filter($data);
	}

	public static function getImageFromStorage($driver, $path)
	{
		return Storage::disk($driver)->path($path);
	}

	public static function deleteImage($value)
	{dd($value);
		if(is_object($value) && $value->driver)
		{
			foreach($value->transforms as $transformName => $data)
			{
				if(is_object($data))
				{
					Storage::disk($value->driver)->delete($data->path);
				}
			}
		}
	}
}