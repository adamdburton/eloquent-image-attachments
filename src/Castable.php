<?php

namespace AdamDBurton\EloquentImageAttachments;

interface Castable
{
	public function creating($value);
	public function saving();
	public function restoring($data);
}