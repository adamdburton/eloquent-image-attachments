<?php

namespace AdamDBurton\EloquentImageAttachments;

use AdamDBurton\EloquentCustomCasts\CustomCast;

class Image extends CustomCast
{
	private $image;

	public function __get($name)
	{
		return $this->data->transforms->$name ?? null;
	}

	public function transform($args)
	{
		$width = $args['width'] ?? null;
		$height = $args['height'] ?? null;
		$crop = $args['crop'] ?? null;
		$scaleUp = $args['scaleUp'] ?? null;
		$quality = $args['quality'] ?? null;
		$save = $args['save'] ?? null;

		$image = ImageAttachmentService::makeImage($this->image);
		$transformedImage = ImageAttachmentService::transformImage($image, $width, $height, $crop, $scaleUp);

		if($save)
		{
			$transformName = ($width ?: 'auto') . '_' . ($height ?: 'auto') . '_' . ($crop ? 'cropped' : 'uncropped') . '_' . ($scaleUp ? 'scaled' : 'unscaled');

			$data = ImageAttachmentService::storeImage($transformedImage, $transformName, $quality);

			$this->additionalTransforms[$transformName] = $data;
		}

		return $transformedImage->response(null, $quality);
	}

	public function restoring($data)
	{
		$this->data = $data;
		$this->image = ImageAttachmentService::getImageFromStorage($this->data->driver, $this->data->original->path);
	}

	public function creating($image)
	{
		// Called when the value is new

		$this->image = $image;
	}

	public function saving()
	{
		$data = ImageAttachmentService::saveImage($this->image);
		$data['transforms'] = array_merge($data['transforms'], $this->additionalTransforms); // Merge in any manually created transforms

		return $data;
	}

	public function delete()
	{

	}
}