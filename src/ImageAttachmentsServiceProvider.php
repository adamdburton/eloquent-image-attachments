<?php

namespace AdamDBurton\EloquentImageAttachments;

use Illuminate\Support\ServiceProvider;

class ImageAttachmentsServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/../config/image-attachments.php' => config_path('image-attachments.php'),
		]);

		$this->mergeConfigFrom(__DIR__.'/../config/image-attachments.php', 'image-attachments');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->handleConfig();
	}

	protected function handleConfig()
	{
		$packageConfig = __DIR__.'/../config/image-attachments.php';
		$destinationConfig = config_path('image-attachments.php');

		$this->publishes(array(
			$packageConfig => $destinationConfig,
		));
	}
}
