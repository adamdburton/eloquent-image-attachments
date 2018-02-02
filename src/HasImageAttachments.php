<?php

namespace AdamDBurton\EloquentImageAttachments;

use Illuminate\Database\Eloquent\Model;

trait HasImageAttachments
{
	public function getImageAttachments()
	{
		return $this->imageAttachments;
	}

	public static function bootHasImageAttachments()
	{
		static::retrieved(function(Model $model)
		{
			foreach($model->getImageAttachments() as $field)
			{
				$model->addCustomCast($field, Image::class);
			}
		});

		static::updating(function(Model $model)
		{
			// Save/remove attachments

			$originalModel = $model->getOriginal();
			$attachmentFields = isset($model->image_attachments) ? $model->image_attachments : [];

			foreach($attachmentFields as $field)
			{
				if($model->isDirty($field))
				{
					$value = null;

					if($model->getAttribute($field))
					{
						// Dirty and has a value, store it

						$value = ImageAttachmentService::saveImage($model->getAttribute($field));
					}

					if($originalModel[$field])
					{
						// Delete original images

						ImageAttachmentService::deleteImage($originalModel[$field]);
					}

					$model->setAttribute($field, $value);
				}
			}
		});

		static::deleted(function(Model $model)
		{
			$attachmentFields = isset($model->image_attachments) ? $model->image_attachments : [];

			if(count($attachmentFields) > 0)
			{
				foreach($attachmentFields as $value)
				{
					ImageAttachmentService::deleteImage($value);
				}
			}
		});
	}
}