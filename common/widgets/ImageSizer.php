<?php

declare(strict_types=1);

namespace common\widgets;

use core\helpers\ImageProcessor;
use core\helpers\Html;

class ImageSizer extends \core\widgets\Widget
{
	/**
	 * @var string Image to resize filesystem path.
	 */
	public string $filePath;

	/**
	 * @var string Base url to image (without filename).
	 */
	public string $webBaseUrl;

	/**
	 * @var string Picture url to show if $filePath does not exists
	 */
	public string $noImageWebPath;

	/**
	 * @var int Thumbnails TTL in seconds.
	 */
	public int $thumbsTtl = 60 * 60 * 24 * 365;

	/**
	 * @var string
	 */
	public string $thumbsSubDir = 'thumbs/';

	/**
	 * @var int Thumbnails jpeg quality.
	 */
	public int $quality = 80;

	/**
	 * @var int
	 */
	public int $width = 0;

	/**
	 * @var int
	 */
	public int $height = 0;

	/**
	 * @var array img tag options
	 */
	public array $options = [];

	/**
	 * @var bool Return only resized file path
	 */
	public bool $asPath = false;

	/**
	 * @var string Thumbnails resize method
	 * @see ImageProcessor::RESIZE_* constants
	 */
	public string $method = ImageProcessor::RESIZE_STRETCH;

	/**
	 * {@inheritdoc}
	 */
	public function run()
	{
		if (!is_file($this->filePath) && isset($this->noImageWebPath)) {
			return Html::img($this->noImageWebPath, $this->options);
		}

		if ($this->width === 0 && $this->height === 0) {
			$fileName = $this->getFilename($this->filePath);
		} else {
			$thumb = $this->getRezisedPath();
			$fileName = $this->getFilename($thumb);
			$fileName = $this->thumbsSubDir . $fileName;
		}

		return $this->asPath ? $this->getPath($fileName) : $this->getTag($fileName);
	}

	/**
	 * Get img tag
	 * 
	 * @param string $fileName
	 * 
	 * @return string
	 */
	protected function getTag(string $fileName): string
	{
		$src = $this->getPath($fileName);
		return Html::img($src, $this->options);
	}

	/**
	 * Get img path
	 * 
	 * @param string $fileName
	 * 
	 * @return string
	 */
	protected function getPath(string $fileName): string
	{
		return $this->webBaseUrl . $fileName;
	}

	/**
	 * Get file directory
	 * 
	 * @return string File directory
	 */
	protected function getDirectory(): string
	{
		return pathinfo($this->filePath, PATHINFO_DIRNAME);
	}

	/**
	 * Get file name
	 * 
	 * @return string File name
	 */
	protected function getFilename($filePath): string
	{
		return pathinfo($filePath, PATHINFO_BASENAME);
	}

	/**
	 * Get resized thumbnail filesystem path
	 * 
	 * @return string
	 */
	protected function getRezisedPath(): string
	{
		$thumbsDir = normalizePath($this->getDirectory() . DS . $this->thumbsSubDir);
		$processor = new ImageProcessor(true);
		$res = $processor->load($this->filePath, true);
		$path = '';

		if ($res) {
			$processor->enableCache($thumbsDir . DS, $this->thumbsTtl);
			$processor->resize($this->width, $this->height, $this->method);
			$path = $processor->getCacheImagePath($this->quality);
		}

		return $path;
	}
}
