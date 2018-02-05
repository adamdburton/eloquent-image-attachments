<?php

// Make sure you run 'php artisan storage:link' if uploading to public storage paths
// To avoid storing absolute urls with the public (local) driver, remove `env('APP_URL') . ` from the config

/*
 * Transform properties:
 * width -> int (default: null)
 * height -> int (default: null)
 * crop -> bool (default: true) (height will be ignored and aspect ratio of width used instead, if set to true)
 * scaleUp -> bool (default: false) (allow scaling up larger than the original upload)
 * quality -> int(0-100) (default: 85) (jpg quality for output)
 * callback -> closure (default: function(Intervention\Image\Image $image) {}) (callback that is passed the image for any additional manipulation you might want to do (watermarks?))
 */

return [
	'image_driver' => 'gd', // gd or imagick
	'storage_driver' => 'public', // From filesystem.php
	'base_path' => 'images', // Leave blank for filesystem root
	'default_quality' => 90, // The default quality for saving/outputting jpg files
	'transforms' => [
		'large' => [ 'width' => 2000, 'crop' => false, 'quality' => 85 ],
		'thumbnail' => [ 'width' => 150, 'height' => 150, 'crop' => true, 'quality' => 85 ]
	]
];