<?php

declare(strict_types=1);

namespace core\web;

/**
 * Abstract class implementation of uploaded file config for ChunkUploader.
 * 
 * @see \core\web\ChunkUploader
 */
abstract class ChunkUploadedProviderAbstract
{
	/**
	 * Constructor.
	 * 
	 * @param ChunkUploader $ChunkUploader Chunk uploading handler.
	 */
	public function __construct(protected ChunkUploader $uploader)
	{
		$this->init();
	}

	/**
	 * Init function, to override if necessary
	 * 
	 * @return void
	 */
	protected function init(): void
	{
	}

	/**
	 * Get uploaded file callback data.
	 * 
	 * @return array Data to be json serialized.
	 */
	abstract public function toArray(): array;
}