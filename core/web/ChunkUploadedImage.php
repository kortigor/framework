<?php

declare(strict_types=1);

namespace core\web;

/**
 * Uploaded image file config for ChunkUploader
 * @see \core\web\ChunkUploadedProviderAbstract
 */
class ChunkUploadedImage extends ChunkUploadedProviderAbstract
{
	/**
	 * @var array|bool Image info by getimagesize()
	 */
	private array|bool $imageInfo;

	/**
	 * @var int Image width in pixels
	 */
	private int $width;

	/**
	 * @var int Image height in pixels
	 */
	private int $height;

	/**
	 * {@inheritDoc} 
	 */
	protected function init(): void
	{
		$this->imageInfo = getimagesize($this->uploader->targetFile);
		$this->width = $this->imageInfo[0] ?? 0;
		$this->height = $this->imageInfo[1] ?? 0;
	}

	/**
	 * {@inheritDoc} 
	 */
	public function toArray(): array
	{
		return [
			'append' => true,
			'chunkIndex' => $this->uploader->chunkIndex,				// chunk index processed
			'initialPreview' => $this->uploader->targetUrl,				// thumbnail preview data (e.g. image)
			'initialPreviewConfig' => [
				[
					'type' => $this->uploader->type,					// check previewTypes (set it to 'other' if you want no content preview)
					'caption' => "{$this->width} x {$this->height} px",	// caption
					'key' => $this->uploader->fileId,					// keys for deleting/reorganizing preview
					'fileId' => $this->uploader->fileId,				// file identifier
					'size' => $this->uploader->fileSize,				// file size
					'zoomData' => $this->uploader->zoomUrl,				// separate larger zoom data
				],
			],
		];
	}
}