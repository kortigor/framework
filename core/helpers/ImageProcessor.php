<?php

declare(strict_types=1);

namespace core\helpers;

use GdImage;

/**
 * Images processing class
 * 
 * - Supports jpg, png and gif
 * - Preserves png and gif transparency
 * - Rotate images
 * - Create image thumbnails on the fly
 * - Can be used with direct url imageprocessor.php?src=
 * - Can be used as a class in your own website/application code
 * - 6 types of image resizing (stretch, fit, crop, cropfitcenter)
 * - Watermark image with easy watermark positioning (pixels or top, bottom, center, left, right)
 * - Cache images for efficiency 
 * 
 * @author Unknown.
 * @author Kort <kort.igor@gmail.com> Added cropfitcenter & ratio resize modes, fixed apply transparency to resized gif images, php8 compatible.
 */
class ImageProcessor
{
	const RESIZE_STRETCH = 'stretch';
	const RESIZE_FIT = 'fit';
	const RESIZE_CROP = 'crop';
	const RESIZE_NOCUT_CENTER = 'nocut';
	const RESIZE_CROP_FIT_CENTER = 'cropfitcenter';
	const RESIZE_RATIO = 'ratio';

	const POSITION_TOP = 'top';
	const POSITION_CENTER = 'center';
	const POSITION_BOTTOM = 'bottom';
	const POSITION_LEFT = 'left';
	const POSITION_RIGHT = 'right';

	const EXT_JPG = 'jpg';
	const EXT_JPEG = 'jpeg';
	const EXT_PNG = 'png';
	const EXT_GIF = 'gif';

	private const MIMES = [
		self::EXT_JPG  => 'image/jpeg',
		self::EXT_JPEG => 'image/jpeg',
		self::EXT_PNG  => 'image/png',
		self::EXT_GIF  => 'image/gif',
	];

	/**
	 * Origninal image path
	 *
	 * @var string
	 */
	private string $_imagePath;

	/**
	 * Image name
	 * 
	 * @var string
	 */
	protected string $_imageName;

	/**
	 * Image type
	 * 
	 * @var int
	 * 
	 * @see getimagesize()
	 * @see imagetypes()
	 */
	private int $_imageType;

	/**
	 * Image mime type
	 *
	 * @var string
	 */
	protected string $_mime;

	/**
	 * Image file extension
	 *
	 * @var string
	 */
	protected string $_extension;

	/**
	 * Is it a direct url call?
	 * 
	 * @var bool
	 */
	private bool $_directCall = false;

	/**
	 * Old image height
	 * 
	 * @var int
	 */
	private int $_oldHeight;

	/**
	 * Old image width
	 * 
	 * @var int
	 */
	private int $_oldWidth;

	/**
	 * New image height
	 * 
	 * @var int
	 */
	private int $_newHeight;

	/**
	 * New image width
	 * 
	 * @var int
	 */
	private int $_newWidth;

	/**
	 * Resize mode
	 * 
	 * @var string
	 */
	private string $_resizeMode;

	/**
	 * Image resource
	 * 
	 * @var GdImage
	 */
	private $_resource;

	/**
	 * Cache folder
	 * 
	 * @var string
	 */
	private string $_cacheFolder;

	/**
	 * Cache time to live
	 * 
	 * @var int
	 */
	private int $_cacheTtl;

	/**
	 * Cache on
	 * 
	 * @var bool
	 */
	private bool $_cache = false;

	/**
	 * Cache skip
	 * 
	 * @var bool
	 */
	private bool $_cacheSkip = false;

	/**
	 * Constructor.
	 * 
	 * @param bool $directCall Use with direct url imageprocessor.php?src=
	 */
	public function __construct(bool $directCall = false)
	{
		// Check GD extension is loaded
		if (!extension_loaded('gd') && !extension_loaded('gd2')) {
			$this->showError('GD is not loaded');
		}

		$this->_directCall = $directCall;
	}

	/**
	 * Resize.
	 *
	 * @param int $width Resized image width. Set or leave as 0 to resize based on specified height.
	 * @param int $height Resized image height. Set or leave as 0 to resize based on specified width.
	 * @param string $mode Image resize mode
	 * 
	 * @return void
	 * 
	 * @see RESIZE_* constants
	 */
	public function resize(int $width = 0, int $height = 0, string $mode = self::RESIZE_STRETCH): void
	{
		if ($this->isModeValid($mode)) {
			$this->_resizeMode = $mode;
		} else {
			$this->showError("The resize mode '{$mode}' does not exists.");
		}

		// Aspect ratio resize based on width
		if ($width > 0 && $height === 0) {
			$ratio = $this->_oldWidth / $width;
			$height = ceil($this->_oldHeight / $ratio);
		}

		// Aspect ratio resize based on height
		if ($height > 0 && $width === 0) {
			$ratio = $this->_oldHeight / $height;
			$width = ceil($this->_oldWidth / $ratio);
		}

		// Mode calculations
		switch ($mode) {
			case self::RESIZE_NOCUT_CENTER:
				$dstX = 0;
				$dstY = 0;
				$srcX = 0;
				$srcY = 0;
				$dstW = $width;
				$dstH = $height;
				$srcW = $this->_oldWidth;
				$srcH = $this->_oldHeight;
				if ($this->_oldWidth / $height > $this->_oldHeight / $height) {
					$k = $width / $this->_oldWidth;
					$dstH = round($this->_oldHeight * $k);
					$dstY = round(($height - $dstH) / 2);
				} else {
					$k = $height / $this->_oldHeight;
					$dstW = round($this->_oldWidth * $k);
					$dstX = round(($width - $dstW) / 2);
				}
				break;

			case self::RESIZE_RATIO:
				$dstX = 0;
				$dstY = 0;
				$srcX = 0;
				$srcY = 0;
				$srcW = $this->_oldWidth;
				$srcH = $this->_oldHeight;

				if ($srcW > $width || $srcH > $height) {
					if ($srcW < $srcH) {
						$dstW = round(($srcW * $height) / $srcH);
						$dstH = $height;
					} elseif ($srcW > $srcH) {
						$dstW = $width;
						$dstH = round(($srcH * $width) / $srcW);
					} else {
						$dstW = $width;
						$dstH = $height;
					}
				} else {
					$dstW = $srcW;
					$dstH = $srcH;
				}

				$width = $dstW;
				$height = $dstH;
				break;

			case self::RESIZE_STRETCH:
				$dstX = 0;
				$dstY = 0;
				$srcX = 0;
				$srcY = 0;
				$dstW = $width;
				$dstH = $height;
				$srcW = $this->_oldWidth;
				$srcH = $this->_oldHeight;
				break;

			case self::RESIZE_FIT:
				$dstX = 0;
				$dstY = 0;
				$srcX = 0;
				$srcY = 0;
				$dstW = ($this->_oldWidth > $this->_oldHeight) ? $this->_oldWidth : $width;
				$dstH = ($this->_oldHeight > $this->_oldWidth) ? $this->_oldHeight : $height;
				$srcW = $this->_oldWidth;
				$srcH = $this->_oldHeight;
				if ($dstW == $this->_oldWidth) {
					$ratio = $dstH / $this->_oldHeight;
					$dstW = floor($dstW * $ratio);
				}
				if ($dstH == $this->_oldHeight) {
					$ratio = $dstW / $this->_oldWidth;
					$dstH = floor($dstH * $ratio);
				}

				$width = $width > $dstW ? $dstW : $width;
				$height = $height > $dstH ? $dstH : $height;
				break;

			case self::RESIZE_CROP_FIT_CENTER:
				$dstX = 0;
				$dstY = 0;
				$dstW = $width;
				$dstH = $height;

				// Horizontal picture
				if ($this->_oldWidth >= $this->_oldHeight) {
					$ratio = $width / $height;
					$srcRatio = $this->_oldWidth / $this->_oldHeight;

					// Crop area ratio wider than image ratio
					if ($ratio > $srcRatio) {
						$srcW = $this->_oldWidth;
						$srcH = round($srcW / $ratio);
						$srcX = 0;
						$srcY = round(($this->_oldHeight - $srcH) / 2);
					} else {
						$srcW = round($ratio * $this->_oldHeight);
						$srcH = $this->_oldHeight;
						$srcX = round(($this->_oldWidth - $srcW) / 2);
						$srcY = 0;
					}
				}
				// Vertical picture
				elseif ($this->_oldWidth < $this->_oldHeight) {
					$ratio = $height / $width;
					$srcRatio = $this->_oldHeight / $this->_oldWidth;
					if ($ratio > $srcRatio) {
						$srcH = $this->_oldHeight;
						$srcW = round($srcH / $ratio);
						$srcY = 0;
						$srcX = round(($this->_oldWidth - $srcW) / 2);
					} else {
						$srcH = round($ratio * $this->_oldWidth);
						$srcW = $this->_oldWidth;
						$srcY = round(($this->_oldHeight - $srcH) / 2);
						$srcX = 0;
					}
				}
				break;

			case self::RESIZE_CROP:
				$width = $width > $this->_oldWidth ? $this->_oldWidth : $width;
				$height = $height > $this->_oldHeight ? $this->_oldHeight : $height;
				$dstX = 0;
				$dstY = 0;
				$calc_x = ceil($this->_oldWidth / 2) - floor($width / 2);
				$srcX = $calc_x > 0 ? $calc_x : 0;
				$calc_y = ceil($this->_oldHeight / 2) - floor($height / 2);
				$srcY = $calc_y > 0 ? $calc_y : 0;
				$dstW = $this->_oldWidth;
				$dstH = $this->_oldHeight;
				$srcW = $this->_oldWidth;
				$srcH = $this->_oldHeight;
				break;
		}

		// Set news size vars because these are used
		// for the cache name generation
		$this->_newWidth = $width;
		$this->_newHeight = $height;

		$this->_oldWidth = $width;
		$this->_oldHeight = $height;

		// Lazy load for the directurl cache to work
		$this->lazyLoad();
		if ($this->_cacheSkip) {
			return;
		}

		// Create canvas for the new image
		$newImage = imagecreatetruecolor($width, $height);
		imagefill($newImage, 0, 0, imagecolorallocatealpha($newImage, 255, 255, 255, 127));

		// Check if this image is PNG or GIF to preserve its transparency
		if (($this->_imageType === IMG_PNG) || ($this->_imageType === IMG_GIF)) {
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
			$transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
			imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
		}

		imagecopyresampled(
			$newImage,
			$this->_resource,
			(int) $dstX,
			(int) $dstY,
			(int) $srcX,
			(int) $srcY,
			(int) $dstW,
			(int) $dstH,
			(int) $srcW,
			(int) $srcH
		);

		// Apply transparency to resized gif images
		if ($this->_extension === self::EXT_GIF) {
			$trnprt_indx = imagecolortransparent($this->_resource);
			$palletsize = imagecolorstotal($this->_resource);
			if ($trnprt_indx >= 0 && $trnprt_indx < $palletsize) {
				$trnprt_color = imagecolorsforindex($this->_resource, $trnprt_indx);
				$trnprt_indx = imagecolorallocate($newImage, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
				imagefill($newImage, 0, 0, $trnprt_indx);
				imagecolortransparent($newImage, $trnprt_indx);
			}
		}

		$this->_resource = $newImage;
	}

	/**
	 * Rotate an image
	 *
	 * @param int $degrees
	 * @return void
	 */
	public function rotate(int $degrees): void
	{
		$this->lazyLoad();
		if ($this->_cacheSkip) {
			return;
		}
		$this->_resource = imagerotate($this->_resource, $degrees, 0);
	}

	/**
	 * Add watermark
	 * 
	 * @param string $image
	 * @param string $horizontal
	 * @param string $vertical
	 * @return void
	 */
	public function watermark(string $image, string $horizontal = self::POSITION_RIGHT, string $vertical = self::POSITION_BOTTOM): void
	{
		// Lazy load
		$this->lazyLoad();
		if ($this->_cacheSkip) {
			return;
		}

		// Get extension
		$extension = $this->getExtension($image);

		// Image info
		list($width, $height, $type) = getimagesize($image);

		// Get image resource
		$watermark = $this->getImageResource($image, $extension);

		// Resource width and height
		$imageWidth = imagesx($this->_resource);
		$imageHeight = imagesy($this->_resource);

		// Calculate positions
		$positionX = $horizontal;
		$positionY = $vertical;
		switch ($positionX) {
			case self::POSITION_LEFT:
				$positionX = 0;
				break;
			case self::POSITION_CENTER:
				$positionX = ceil($imageWidth / 2) - floor($width / 2);
				break;
			case self::POSITION_RIGHT:
				$positionX = $imageWidth - $width;
				break;
		}
		switch ($positionY) {
			case self::POSITION_TOP:
				$positionY = 0;
				break;
			case self::POSITION_CENTER:
				$positionY = ceil($imageHeight / 2) - floor($height / 2);
				break;
			case self::POSITION_BOTTOM:
				$positionY = $imageHeight - $height;
				break;
		}

		$extension = $this->getExtension($image);
		if ($extension === self::EXT_PNG) {
			$this->imageCopyMergeAlpha($this->_resource, $watermark, $positionX, $positionY, 0, 0, $width, $height, 100);
		} else {
			imagecopymerge($this->_resource, $watermark, $positionX, $positionY, 0, 0, $width, $height, 100);
		}

		// Destroy watermark
		imagedestroy($watermark);
	}

	/**
	 * Create image resource from path or url
	 * 
	 * @param string $location
	 * @param bool $lazy
	 * 
	 * @return bool True if image loaded successfully.
	 */
	public function load(string $image, bool $lazy = false): bool
	{
		$res = false;

		// Cleanup image url
		$image = $this->cleanUrl($image);

		// Get mime type of the image
		$extension = $this->getExtension($image);
		$mime = self::MIMES[$extension] ?? null;

		// Check if it is a valid image
		if ($mime && (file_exists($image) || $this->isExternal($image))) {
			$res = true;

			// Urlencode if http
			if ($this->isExternal($image)) {
				$image = str_replace(['http%3A%2F%2F', 'https%3A%2F%2F', '%2F'], ['http://', 'https://', '/'], urlencode($image));
			}

			$this->_extension = $extension;
			$this->_mime = $mime;
			$this->_imagePath = $image;
			$parts = explode('/', $image);
			$this->_imageName = str_replace('.' . $this->_extension, '', end($parts));

			// Get image size
			list($width, $height, $type) = getimagesize($image);
			$this->_oldWidth = $width;
			$this->_oldHeight = $height;
			$this->_imageType = $type;
		}

		if (!$lazy) {
			$resource = $this->getImageResource($image, $extension);
			$this->_resource = $resource;
		}

		return $res;
	}

	/**
	 * Save image to computer
	 *
	 * @param string $destination
	 * @param int $quality Jpeg quality
	 * @return void
	 */
	public function save(string $destination, int $quality = 80): void
	{
		if ($this->_extension === self::EXT_PNG || $this->_extension === self::EXT_GIF) {
			imagesavealpha($this->_resource, true);
		}

		switch ($this->_extension) {
			case self::EXT_JPG:
				imagejpeg($this->_resource, $destination, $quality);
				break;
			case self::EXT_JPEG:
				imagejpeg($this->_resource, $destination, $quality);
				break;
			case self::EXT_GIF:
				imagegif($this->_resource, $destination);
				break;
			case self::EXT_PNG:
				imagepng($this->_resource, $destination);
				break;
			default:
				$this->showError('Failed to save image!');
		}
	}

	/**
	 * Print image to screen
	 * 
	 * @param int $quality Jpeg quality
	 * 
	 * @return void
	 */
	public function parse(int $quality = 80): void
	{
		$name = $this->generateCacheName();
		$content = '';
		if (!$this->_cache || ($this->_cache && $this->isCacheExpired())) {
			ob_start();
			header('Content-type: ' . $this->_mime);

			if ($this->_extension === self::EXT_PNG || $this->_extension === self::EXT_GIF) {
				imagesavealpha($this->_resource, true);
			}

			switch ($this->_extension) {
				case self::EXT_JPG:
					imagejpeg($this->_resource, null, $quality);
					break;
				case self::EXT_JPEG:
					imagejpeg($this->_resource, null, $quality);
					break;
				case self::EXT_GIF:
					imagegif($this->_resource);
					break;
				case self::EXT_PNG:
					imagepng($this->_resource);
					break;
				default:
					$this->showError('Failed to save image!');
			}

			$content = ob_get_contents();
			ob_end_clean();
		} else {
			header('Content-type: ' . $this->_mime);
			echo $this->cachedImage($name);
			exit();
		}

		// Save image content
		if (!empty($content) && $this->_cache) {
			$this->cacheImage($name, $content);
		}

		// Destroy image
		$this->destroy();

		echo $content;
		exit();
	}

	/**
	 * Get cached image path
	 * 
	 * @param int $quality Jpeg quality
	 * 
	 * @return string
	 */
	public function getCacheImagePath(int $quality = 80): string
	{
		$name = $this->generateCacheName();
		$path = '';
		$content = '';
		if (!$this->_cache || ($this->_cache && $this->isCacheExpired())) {
			ob_start();
			//header ('Content-type: ' . $this->_mime);

			if ($this->_extension === self::EXT_PNG || $this->_extension === self::EXT_GIF) {
				imagesavealpha($this->_resource, true);
			}

			switch ($this->_extension) {
				case self::EXT_JPG:
					imagejpeg($this->_resource, null, $quality);
					break;
				case self::EXT_JPEG:
					imagejpeg($this->_resource, null, $quality);
					break;
				case self::EXT_GIF:
					imagegif($this->_resource);
					break;
				case self::EXT_PNG:
					imagepng($this->_resource);
					break;
				default:
					$this->showError('Failed to save image!');
			}

			$content = ob_get_contents();
			ob_end_clean();
		}

		if (!empty($content) && $this->_cache) {
			$this->cacheImage($name, $content);
		}

		$path = $this->_cacheFolder . $name;
		return $path;
	}

	/**
	 * Destroy resources
	 * 
	 * @return void
	 */
	public function destroy(): void
	{
		imagedestroy($this->_resource);
	}

	/**
	 * Filter: Negative effect
	 * 
	 * @return void
	 */
	public function filterNegative(): void
	{
		if (isset($this->_resource)) {
			imagefilter($this->_resource, IMG_FILTER_NEGATE);
		} else {
			$this->showError('Load an image first');
		}
	}

	/**
	 * Filter: Grayscale effect
	 * 
	 * @return void
	 */
	public function filterGray(): void
	{
		$this->lazyLoad();
		if ($this->_cacheSkip) {
			return;
		}

		if (isset($this->_resource)) {
			imagefilter($this->_resource, IMG_FILTER_GRAYSCALE);
		} else {
			$this->showError('Load an image first');
		}
	}

	/**
	 * Get image resources
	 * 
	 * @return GdImage
	 */
	public function getResource(): GdImage
	{
		return $this->_resource;
	}

	/**
	 * Set image resources
	 * 
	 * @param GdImage $image
	 * 
	 * @return void
	 */
	public function setResource(GdImage $image): void
	{
		$this->_resource = $image;
	}

	/**
	 * Enable caching
	 * 
	 * @param string $folder
	 * @param int $ttl
	 * 
	 * @return bool
	 */
	public function enableCache(string $folder = 'cache/', int $ttl = 60): bool
	{
		if (!is_dir($folder) || !is_writable($folder)) {
			$this->showError("Directory '{$folder}' does not exist or not writable");
		} else {
			$this->_cache = true;
			$this->_cacheFolder = $folder;
			$this->_cacheTtl = $ttl;
			return true;
		}

		return false;
	}

	/**
	 * Whether image is external (loaded from http(s) resource)
	 * 
	 * @param string $image
	 * 
	 * @return bool
	 */
	private function isExternal(string $image): bool
	{
		return strstr($image, 'http://') || strstr($image, 'https://');
	}

	/**
	 * Validate resize mode;
	 * 
	 * @param mixed $mode
	 * 
	 * @return bool
	 */
	private function isModeValid($mode): bool
	{
		return in_array($mode, [
			self::RESIZE_STRETCH,
			self::RESIZE_FIT,
			self::RESIZE_NOCUT_CENTER,
			self::RESIZE_RATIO,
			self::RESIZE_CROP,
			self::RESIZE_CROP_FIT_CENTER,
		]);
	}

	/**
	 * Cleanup url
	 * 
	 * @param string $image
	 * @return string
	 */
	private function cleanUrl($image): string
	{
		$cimage = str_replace("\\", "/", $image);
		return $cimage;
	}

	/**
	 * Show error
	 * 
	 * @param string $message
	 * @return void
	 */
	private function showError($message = ''): void
	{
		if ($this->_directCall) {
			header('HTTP/1.1 400 Bad Request');
			die($message);
		} else {
			trigger_error($message, E_USER_WARNING);
		}
	}

	/**
	 * Get image resource
	 * 
	 * @param string $image
	 * @param string $extension
	 * @return GdImage
	 */
	private function getImageResource(string $image, string $extension): GdImage
	{
		switch ($extension) {
			case self::EXT_JPEG:
				@ini_set('gd.jpeg_ignore_warning', '1');
				$resource = imagecreatefromjpeg($image);
				break;
			case self::EXT_JPG:
				@ini_set('gd.jpeg_ignore_warning', '1');
				$resource = imagecreatefromjpeg($image);
				break;
			case self::EXT_GIF:
				$resource = imagecreatefromgif($image);
				break;
			case self::EXT_PNG:
				$resource = imagecreatefrompng($image);
				break;
		}
		return $resource;
	}

	/**
	 * Save image to cache folder
	 * 
	 * @param mixed $name
	 * @param mixed $content
	 * 
	 * @return void
	 */
	private function cacheImage(string $name, string $content): void
	{
		// Write content file
		$path = $this->_cacheFolder . $name;
		$fh = fopen($path, 'w') or die("can't open file");
		fwrite($fh, $content);
		fclose($fh);
	}

	/**
	 * Get an image from cache
	 * 
	 * @param string $name
	 * 
	 * @return string|false
	 */
	private function cachedImage(string $name): string|false
	{
		$file = $this->_cacheFolder . $name;
		$fh = fopen($file, 'r');
		$content = fread($fh, filesize($file));
		fclose($fh);
		return $content;
	}

	/**
	 * Get name of the cache file
	 * 
	 * @return string
	 */
	private function generateCacheName(): string
	{
		$get = implode('-', $_GET);
		$name = $this->_resizeMode . $this->_imagePath . $this->_oldWidth . $this->_oldHeight . $this->_newWidth . $this->_newHeight . $get;
		return md5($name) . '.' . $this->_extension;
	}

	/**
	 * Check if a cache file is expired
	 * 
	 * @return bool
	 */
	private function isCacheExpired(): bool
	{
		$path = $this->_cacheFolder . $this->generateCacheName();
		if (file_exists($path)) {
			$filetime = filemtime($path);
			return $filetime < (time() - $this->_cacheTtl);
		} else {
			return true;
		}
	}

	/**
	 * Merges to layers for watermark
	 * but keeps a clean images when using 24bit png
	 *
	 * @return void
	 */
	private function imageCopyMergeAlpha(
		GdImage $dstIm,
		GdImage $srcIm,
		int $dstX,
		int $dstY,
		int $srcX,
		int $srcY,
		int $srcW,
		int $srcH,
		float $pct
	): void {
		$pct /= 100;
		// Get image width and height 
		$w = imagesx($srcIm);
		$h = imagesy($srcIm);
		// Turn alpha blending off 
		imagealphablending($srcIm, false);
		// Find the most opaque pixel in the image (the one with the smallest alpha value) 
		$minAlpha = 127;
		for ($x = 0; $x < $w; $x++)
			for ($y = 0; $y < $h; $y++) {
				$alpha = (imagecolorat($srcIm, $x, $y) >> 24) & 0xFF;
				if ($alpha < $minAlpha) {
					$minAlpha = $alpha;
				}
			}
		// Loop through image pixels and modify alpha for each 
		for ($x = 0; $x < $w; $x++) {
			for ($y = 0; $y < $h; $y++) {
				// Get current alpha value (represents the TANSPARENCY!) 
				$colorXY = imagecolorat($srcIm, $x, $y);
				$alpha = ($colorXY >> 24) & 0xFF;
				// Calculate new alpha 
				if ($minAlpha !== 127) {
					$alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minAlpha);
				} else {
					$alpha += 127 * $pct;
				}
				// Get the color index with new alpha 
				$alphaColorXY = imagecolorallocatealpha($srcIm, ($colorXY >> 16) & 0xFF, ($colorXY >> 8) & 0xFF, $colorXY & 0xFF, $alpha);
				// Set pixel with the new color + opacity 
				if (!imagesetpixel($srcIm, $x, $y, $alphaColorXY)) {
					return;
				}
			}
		}
		// The image copy 
		imagecopy($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH);
	}

	/**
	 * Get the extension name of a file
	 *
	 * @param string $file
	 * 
	 * @return string
	 */
	private function getExtension(string $file): string
	{
		return strtolower(pathinfo($file, PATHINFO_EXTENSION));
	}

	/**
	 * Lazy load the image resource needed for the caching to work
	 *
	 * @return void
	 */
	private function lazyLoad(): void
	{
		if (empty($this->_resource)) {
			if ($this->_cache && !$this->isCacheExpired()) {
				$this->_cacheSkip = true;
				return;
			}
			$resource = $this->getImageResource($this->_imagePath, $this->_extension);
			$this->_resource = $resource;
		}
	}
}