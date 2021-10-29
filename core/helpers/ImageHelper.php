<?php

declare(strict_types=1);

namespace core\helpers;

use InvalidArgumentException;
use GdImage;

/**
 * Require GD2 library
 */
class ImageHelper
{
	/**
	 * @var int Default JPEG quality.
	 */
	const DEFAULT_QUALITY = 90;

	/**
	 * @var array Patterns to check injection signatures.
	 */
	private const BAD_PATTERNS = [
		'#&(quot|lt|gt|nbsp|<?php);#i',
		'#&\#x([0-9a-f]+);#i',
		'#&\#([0-9]+);#i',
		'#([a-z]*)=([\`\'\"]*)script:#iU',
		'#([a-z]*)=([\`\'\"]*)javascript:#iU',
		'#([a-z]*)=([\'\"]*)vbscript:#iU',
		'#(<[^>]+)style=([\`\'\"]*).*expression\([^>]*>#iU',
		'#(<[^>]+)style=([\`\'\"]*).*behaviour\([^>]*>#iU',
		'#</*(applet|link|style|script|iframe|frame|frameset)[^>]*>#i',
	];

	/**
	 * Create image thumbnail with specified dimensions from original image,
	 * with save original width/height ratio
	 * 
	 * @param string $origFile original image file path
	 * @param string $dstFile destination image file path
	 * @param int $width destination image width
	 * @param int $height destination image height
	 * @param int $quality destination image JPEG quality
	 * 
	 * @return bool true on success, false on failure
	 * @throws InvalidArgumentException if image type unknown
	 */
	public static function createThumbnail(
		string $origFile,
		string $dstFile,
		int $width,
		int $height,
		int $quality = self::DEFAULT_QUALITY
	): bool {
		static::init();
		$type = static::getImageType($origFile);
		$origRes = static::getImageFromFile($origFile, $type);
		$origX = imagesx($origRes);
		$origY = imagesy($origRes);

		if ($origX > $width || $origY > $height) {
			if ($origX < $origY) {
				$dstW = round(($origX * $height) / $origY);
				$dstH = $height;
			} elseif ($origX > $origY) {
				$dstW = $width;
				$dstH = round(($origY * $width) / $origX);
			} else {
				$dstW = $width;
				$dstH = $height;
			}
		} else {
			$dstW = $origX;
			$dstH = $origY;
		}

		$dstRes = imagecreatetruecolor($dstW, $dstH);
		imagecopyresampled($dstRes, $origRes, 0, 0, 0, 0, $dstW, $dstH, $origX, $origY);
		return self::writeImageFile($dstRes, $dstFile, $type, $quality);
	}

	/**
	 * Create resized image with specified dimensions from original image
	 * 
	 * @param string $origFile original image file path
	 * @param string $dstFile destination image file path
	 * @param int $width destination image width
	 * @param int $height destination image height
	 * @param int $quality destination image JPEG quality
	 * 
	 * @return bool true on success, false on failure
	 * @throws InvalidArgumentException if image type unknown
	 */
	public static function createResized(
		string $origFile,
		string $dstFile,
		int $width,
		int $height,
		int $quality = self::DEFAULT_QUALITY
	): bool {
		static::init();
		$type = static::getImageType($origFile);
		$origRes = static::getImageFromFile($origFile, $type);
		$origX = imagesx($origRes);
		$origX = imagesy($origRes);

		$dstRes = imagecreatetruecolor($width, $height);
		imagecopyresampled($dstRes, $origRes, 0, 0, 0, 0, $width, $height, $origX, $origX);
		return static::writeImageFile($dstRes, $dstFile, $type, $quality);
	}

	/**
	 * Create image with specified dimensions from originals's specified rectangle area (crop area)
	 * 
	 * @param string $origFile origFile image file path
	 * @param string $dstFile destination image file path
	 * @param int $width destination image width
	 * @param int $height destination image height
	 * @param int $x1 original's rectangle x1 coordinate (rectangle's left bottom point)
	 * @param int $y1 original's rectangle y1 coordinate (rectangle's left bottom point)
	 * @param int $x2 original's rectangle x2 coordinate (rectangle's right top point)
	 * @param int $y2 original's rectangle y2 coordinate (rectangle's right top point)
	 * @param int $quality destination image JPEG quality
	 * 
	 * @return bool true on success, false on failure
	 * @throws InvalidArgumentException if image type unknown
	 */
	public static function createCrop(
		string $origFile,
		string $dstFile,
		int $width,
		int $height,
		int $x1,
		int $y1,
		int $x2,
		int $y2,
		int $quality = self::DEFAULT_QUALITY
	): bool {
		static::init();
		$type = static::getImageType($origFile);
		$origRes = static::getImageFromFile($origFile, $type);
		$srcWidth = $x2 - $x1;
		$srcHeight = $y2 - $y1;

		$dstRes = imagecreatetruecolor($width, $height);
		imagecopyresampled($dstRes, $origRes, 0, 0, $x1, $y1, $width, $height, $srcWidth, $srcHeight);
		return static::writeImageFile($dstRes, $dstFile, $type, $quality);
	}

	/**
	 * Check that image have no injections
	 * 
	 * @param string $file image file path to check
	 * 
	 * @return bool
	 */
	public static function verifyImage(string $file): bool
	{
		$content = file_get_contents($file);
		foreach (self::BAD_PATTERNS as $pattern) {
			if (preg_match($pattern, $content)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Set PHP limits to handle image operation
	 * and check GD library availability
	 * 
	 * @return void
	 */
	private static function init(): void
	{
		if (!extension_loaded('gd')) {
			trigger_error('GD extension not loaded', E_USER_ERROR);
		}
		set_time_limit(0);
		ini_set('memory_limit', '300M');
	}

	/**
	 * Get image resource from file
	 * 
	 * @param string $file image file path to read from
	 * @param int $type IMAGETYPE_XXX constant https://www.php.net/manual/en/image.constants.php
	 * 
	 * @return GdImage|false image resource identifier on success, false on errors
	 * @throws InvalidArgumentException if file type is unknown
	 */
	private static function getImageFromFile(string $file, int $type): GdImage|false
	{
		return match ($type) {
			IMAGETYPE_JPEG => imagecreatefromjpeg($file), // create a new image from jpeg file 
			IMAGETYPE_PNG => imagecreatefrompng($file), // create a new image from png file 
			IMAGETYPE_GIF => imagecreatefromgif($file), // create a new image from gif file 
			default => throw new InvalidArgumentException('Unknown image type to read')
		};
	}

	/**
	 * Write image to file
	 * 
	 * @param GdImage $res image resource to write from
	 * @param string $file image file path to write
	 * @param int $type IMAGETYPE_XXX constant https://www.php.net/manual/en/image.constants.php
	 * @param int $quality jpeg quality
	 * 
	 * @return bool true on success, false on failure
	 * @throws InvalidArgumentException if file type is unknown
	 */
	private static function writeImageFile(GdImage $res, string $file, int $type, int $quality): bool
	{
		touch($file);
		return match ($type) {
			IMAGETYPE_JPEG => imagejpeg($res, $file, $quality),
			IMAGETYPE_PNG => imagepng($res, $file),
			IMAGETYPE_GIF => imagegif($res, $file),
			default => throw new InvalidArgumentException('Unknown image type to write')
		};
	}

	/**
	 * Get image type
	 * 
	 * @param string $file image file path to get type
	 * 
	 * @return int IMAGETYPE_XXX constant
	 * @see https://www.php.net/manual/en/image.constants.php
	 * @throws InvalidArgumentException if image type unknown
	 */
	private static function getImageType(string $file): int
	{
		$info = @getimagesize($file);
		$type = $info[2] ?? null;

		if ($type === null) {
			throw new InvalidArgumentException('Unknown image type');
		}

		return $type;
	}

	private function __construct()
	{
		// Can not instantiate
	}
}