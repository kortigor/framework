<?php

declare(strict_types=1);

namespace core\web;

use JsonSerializable;
use core\exception\InvalidConfigException;
use core\helpers\FileHelper;
use core\helpers\Url;
use core\http\UploadedFile;
use core\web\ServerRequest;
use core\base\Security;

/**
 * Upload handler for FileInput Resumable Uploads plugin
 * @see \kartik\file\Fileinput
 * 
 * @property-read string $targetDir Directory to place uploaded file.
 * @property-read string $targetFile Target file filesystem full path.
 * @property-read string $targetUrl Preview data (e.g. image).
 * @property-read string $type Plugin preview type.
 * @property-read string $chunkIndex Current chunk index.
 * @property-read string $zoomUrl Separate larger zoom url.
 * @property-read string $fileId File identifier. Used to identify file in sort/delete operations.
 * @property-read string $fileSize File size.
 */
class ChunkUploader implements JsonSerializable
{
	/**
	 * @var string Directory to place uploaded file.
	 */
	protected string $targetDir;
	/**
	 * @var string Base of preview Url
	 */
	protected string $baseUrl;
	/**
	 * @var bool Remove old temp files
	 */
	protected bool $cleanup = true;
	/**
	 * @var int Chunk temp files max age in seconds
	 */
	protected int $maxTempFileAge = 1 * 3600;
	/**
	 * @var string Post parameter name that stores the file blob
	 */
	protected string $fileBlob = 'fileBlob';
	/**
	 * @var string Check plugin previewTypes (set it to 'other' if no need content preview)
	 */
	protected string $type = 'image';
	/**
	 * @var string File identifier. Used to identify file in sort/delete operations.
	 * 
	 * Plugin service data.
	 */
	protected string $fileId;
	/**
	 * @var UploadedFile|null  Handler of uploaded file chunk.
	 */
	protected ?UploadedFile $file;
	/**
	 * @var string Uploaded file secure name
	 */
	protected string $targetFileName;
	/**
	 * @var string Target file filesystem full path
	 */
	protected string $targetFile;
	/**
	 * @var string Preview data (e.g. image)
	 */
	protected string $targetUrl;
	/**
	 * @var string Separate larger zoom url.
	 */
	protected string $zoomUrl;
	/**
	 * @var string File name.
	 * 
	 * Plugin service data
	 */
	protected string $fileName;
	/**
	 * @var string File size.
	 * 
	 * Plugin service data
	 */
	protected string $fileSize;
	/**
	 * @var int Current chunk index.
	 * 
	 * Plugin service data.
	 */
	protected int $chunkIndex;
	/**
	 * @var int Total number of chunks for this file.
	 * 
	 * Plugin service data.
	 */
	protected int $totalChunks;
	/**
	 * @var string Access token to verify.
	 */
	protected string $token;
	/**
	 * @var string
	 */
	protected string $error;
	/**
	 * @var bool Whether file upload complete (not one chunk)
	 */
	protected bool $isComplete = false;

	/**
	 * Constructor.
	 * 
	 * @param ServerRequest $request Server request to handle
	 * @param array $options Associative array of class attributes values.
	 */
	public function __construct(protected ServerRequest $request, array $options = [])
	{
		foreach ($options as $attribute => $value) {
			$this->$attribute = $value;
		}

		// Check target directory
		$this->assertWritable();

		// Get the upload token from request.
		$uploadToken = $this->request->post('uploadToken');

		// Validate token if exists
		if (isset($this->token) && !$this->validateToken($uploadToken)) {
			$this->error = 'Access not allowed'; // Access control error
		}

		$this->file = $this->request->files($this->fileBlob);
		$this->fileName = $this->request->post('fileName', '');
		$this->fileSize = $this->request->post('fileSize', '');
		$this->fileId = $this->request->post('fileId', '');
		$this->chunkIndex = (int) $this->request->post('chunkIndex', 0);
		$this->totalChunks = (int) $this->request->post('chunkCount', 1);

		// Create chunk files only if number of chunks greater than 1
		if ($this->totalChunks > 1) {
			// Generate chunk file name based on original file name
			$this->targetFileName = $this->fileName;
			$this->targetFile = FileHelper::getFilePath($this->targetDir, $this->fileName); // Target file path
			$this->targetFile .= '_chunk_' . str_pad(strval($this->chunkIndex), 4, '0', STR_PAD_LEFT);
		} else {
			// Generate secure file name. No need original file name
			$this->isComplete = true;
			$this->targetFileName = $this->getSecureName();
			$this->targetFile = FileHelper::getFilePath($this->targetDir, $this->targetFileName);
		}
	}

	/**
	 * Object properties magic getter.
	 * 
	 * @param string $name
	 * 
	 * @return mixed
	 */
	public function __get(string $name): mixed
	{
		if (property_exists($this, $name) && isset($this->$name)) {
			return $this->$name;
		}

		return null;
	}

	/**
	 * Set file identifier.
	 * 
	 * @param string $id
	 * 
	 * @return self
	 * @see $fileId
	 */
	public function setFileId(string $id): self
	{
		$this->fileId = $id;
		return $this;
	}

	/**
	 * Get uploaded file secure name.
	 * 
	 * @return string
	 */
	public function getUploadedName(): string
	{
		return $this->targetFileName;
	}

	/**
	 * Whether file uploaded completely, not chunk.
	 * 
	 * @return bool
	 */
	public function isComplete(): bool
	{
		return $this->isComplete;
	}

	/**
	 * Upload file.
	 * 
	 * @return bool True if the file or chunk uploaded without errors.
	 */
	public function upload(): bool
	{
		if ($this->cleanup) {
			$this->cleanup();
		}

		if (isset($this->error)) {
			return false;
		}

		if (!$this->file) {
			$this->error = 'No file found';
			return false;
		}

		if (!$this->file->moveTo($this->targetFile)) {
			$this->error = 'Error uploading chunk ' . $this->chunkIndex;
			return false;
		}

		if ($this->totalChunks > 1) {
			// Get list of all chunks uploaded so far to server
			$chunks = glob("{$this->targetDir}/{$this->fileName}_chunk_*");
			// Check uploaded chunks so far (do not combine files if only one chunk received)
			$allChunksUploaded = count($chunks) === $this->totalChunks;
			// All chunks were uploaded
			if ($allChunksUploaded) {
				// Generate new file name
				$this->targetFileName = FileHelper::generateName() . '.' . $this->file->getExtension();
				// Ensure file name not exists in target directory
				$this->targetFileName = FileHelper::fileExists($this->targetDir, $this->targetFileName);
				// Change targetFile to secure name to combine chunks
				$this->targetFile = FileHelper::getFilePath($this->targetDir, $this->targetFileName);
				// Combines all file chunks to one file
				$this->combineChunks($chunks, $this->targetFile);
			}
		}

		// If you wish to generate a thumbnail image for the file
		$this->targetUrl = $this->baseUrl . $this->targetFileName;
		// Separate link for the full blown image file
		$this->zoomUrl = Url::getShemeHost(Url::$uri) . $this->baseUrl . $this->targetFileName;

		return true;
	}

	/**
	 * JsonSerializable implementation.
	 * 
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		if (isset($this->error)) {
			return ['error' => $this->error];
		}

		if (!$this->isComplete()) {
			return [
				'chunkIndex' => $this->chunkIndex,	// chunk index processed
				'append' => true
			];
		}

		return $this->getUploadedFileDataProvider()->toArray();
	}

	/**
	 * Generate file secure name.
	 * 
	 * @return string
	 */
	protected function getSecureName(): string
	{
		$outFile = FileHelper::generateName() . '.' . $this->file->getExtension();
		$outFile = FileHelper::fileExists($this->targetDir, $outFile);
		return $outFile;
	}

	/**
	 * Get chunk uploaded file data provider.
	 * 
	 * @return ChunkUploadedProviderAbstract
	 */
	protected function getUploadedFileDataProvider(): ChunkUploadedProviderAbstract
	{
		$class = __NAMESPACE__ . '\ChunkUploaded' . ucfirst($this->type);
		return new $class($this);
	}

	/**
	 * Combine all chunks no exception handling included here - you may wish to incorporate that
	 * 
	 * @param mixed $chunks
	 * @param mixed $targetFile
	 * 
	 * @return mixed
	 */
	protected function combineChunks(array $chunks, string $targetFile)
	{
		// Open target file handle
		$handle = fopen($targetFile, 'a+');
		foreach ($chunks as $file) {
			fwrite($handle, file_get_contents($file));
		}

		// You may need to do some checks to see if file 
		// is matching the original (e.g. by comparing file size)

		// After all are done delete the chunks
		foreach ($chunks as $file) {
			FileHelper::unlinkFile($file);
		}

		// Close the file handle
		fclose($handle);
		$this->isComplete = true;
	}

	/**
	 * Validate access token
	 * 
	 * @param string $token
	 * 
	 * @return bool
	 */
	protected function validateToken(string $token): bool
	{
		$unmasked = (new Security)->unmaskToken($token);
		return $this->token === $unmasked;
	}

	/**
	 * Remove old chunk temp files.
	 * They can stay in case of errors during uploads earlier.
	 *
	 * @return void
	 */
	private function cleanup()
	{
		$chunks = glob("{$this->targetDir}/*_chunk_*");

		foreach ($chunks as $file) {
			if (filemtime($file) < time() - $this->maxTempFileAge) {
				FileHelper::unlinkFile($file);
			}
		}
	}

	/**
	 * Check form directory to save is writable
	 * 
	 * @return void
	 * @throws InvalidConfigException if form directory to save not exists or not writable
	 * @see $targetDir
	 */
	private function assertWritable(): void
	{
		if (!is_writable($this->targetDir)) {
			throw new InvalidConfigException(
				'Target directory does not exists or not enough rights to write.'
			);
		}
	}
}