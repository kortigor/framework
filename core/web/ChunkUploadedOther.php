<?php

declare(strict_types=1);

namespace core\web;

/**
 * Uploaded other files config for ChunkUploader
 * @see \core\web\ChunkUploadedProviderAbstract
 */
class ChunkUploadedOther extends ChunkUploadedProviderAbstract
{
	/**
	 * {@inheritDoc} 
	 */
	public function toArray(): array
	{
		return [
			'chunkIndex' => $this->uploader->chunkIndex,	// chunk index processed
			'key' => $this->uploader->fileId,       		// keys for deleting/reorganizing preview
			'fileId' => $this->uploader->fileId,    		// file identifier
			'append' => true
		];
	}
}