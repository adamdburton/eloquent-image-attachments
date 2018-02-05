<?php

namespace AdamDBurton\EloquentImageAttachments;

abstract class AttributeCast implements Castable
{
	protected $data;

	public function __construct($value = null)
	{
		if($value)
		{
			$this->fromValue($value);
		}
	}

	public function __toString()
	{
		return json_encode($this->saving());
	}

	public function restoring($data)
	{
		$this->data = $data;
	}
}