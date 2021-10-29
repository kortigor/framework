<?php

declare(strict_types=1);

namespace core\base;

use RuntimeException;
use core\orm\ActiveRecord;
use core\http\UploadedFile;
use core\exception\UploadException;
use core\exception\InvalidConfigException;
use core\helpers\FileHelper;
use core\helpers\ImageHelper;

/**
 * Class represents specific model functions to handle html forms.
 */
abstract class ModelForm extends Model
{
    /**
     * @var string Directory where uploaded file(s) are saved
     */
    protected string $_filesDir;
    /**
     * @var string Form's model attribute name which handle `\core\http\UploadedFile`
     */
    protected string $_fileAttribute;
    /**
     * @var string Form's input[type="file"] field name. If unset it means that same with `$fileAttribute`
     */
    protected string $_fileInputNameAttribute;
    /**
     * @var int Saved uploaded file(s) permissions, default 644.
     */
    protected int $_filePermission = 644;
    /**
     * @var int Uploaded file(s) maximum allowed size in bytes.
     * No liminations if unset.
     */
    protected int $_maxFileSize;
    /**
     * @var string Uploaded file(s) allowed extension(s), i.e. 'jpg,png'.
     * No liminations if unset.
     */
    protected string $_fileExtension;
    /**
     * @var string Uploaded file(s) allowed MIME type(s), i.e. 'image/jpeg,image/png'.
     * No liminations if unset.
     */
    protected string $_fileMimeType;
    /**
     * @var int Maximum allowed uploaded image width in pixels.
     * No liminations if unset. If set and image width exceeds,
     * the image will be automatically reduced with respect to width/height ratio.
     */
    protected int $_maxImageWidth;
    /**
     * @var int Maximum allowed uploaded image height in pixels.
     * No liminations if unset. If set and image height exceeds,
     * the image will be automatically reduced with respect to width/height ratio.
     */
    protected int $_maxImageHeight;
    /**
     * @var bool Check that file is required to be uploaded in form.
     */
    protected bool $_uploadRequired = false;
    /**
     * @var string Message if uploaded file size is invalid
     */
    protected string $_messageFileSizeInvalid = 'Размер не более ';
    /**
     * @var string Message if uploaded file extension is invalid
     */
    protected string $_messageFileExtensionInvalid = 'Допустимое расширение %s';
    /**
     * @var string Message if uploaded file MIME type is invalid
     */
    protected string $_messageFileMimeInvalid = 'Допустимый тип %s';
    /**
     * @var string Message if uploaded file MIME type is invalid
     */
    protected string $_messageRequiredInvalid = 'Необходимо загрузить файл';
    /**
     * @var UploadedFile[] PSR-7 array tree of UploadedFileInterface instances.
     * Empty if no uploaded files is present.
     */
    protected array $_uploadedFiles = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        try {
            $this->assertConfigValid();
        } catch (InvalidConfigException) {
            return;
        }

        if ($this->_uploadRequired) {
            $this->addRule(
                $this->_fileAttribute,
                ['required'],
                ['message' => $this->_messageRequiredInvalid]
            );
        }

        if (isset($this->_maxFileSize)) {
            $this->addRule(
                $this->_fileAttribute,
                ['uploadedSize' => $this->_maxFileSize],
                ['message' => $this->_messageFileSizeInvalid . f()->asShortSize($this->_maxFileSize)]
            );
        }

        if (isset($this->_fileExtension)) {
            $this->addRule(
                $this->_fileAttribute,
                ['uploadedExtension' => $this->_fileExtension],
                ['message' => $this->_messageFileExtensionInvalid]
            );
        }

        if (isset($this->_fileMimeType)) {
            $this->addRule(
                $this->_fileAttribute,
                ['uploadedType' => $this->_fileMimeType],
                ['message' => $this->_messageFileMimeInvalid]
            );
        }
    }

    /**
     * Get uploaded file object
     * 
     * @return UploadedFile|null uploaded file object or null if no uploaded files
     * @throws InvalidConfigException if model can't handle file operations
     */
    public function getUploadedFile(): ?UploadedFile
    {
        $this->assertConfigValid();
        $attribute = $this->getFileAttribute();
        return $this->$attribute instanceof UploadedFile ? $this->$attribute : null;
    }

    /**
     * Update previously uploaded form file.
     * 
     * Note: Use carefully, only if you sure about the successful of future saving data into DB!!!
     * 
     * @param ActiveRecord $record database record to be updated
     * @param string $attribute ActiveRecord's attribute with old file name
     * 
     * @return string uploaded new file name, or old file name (if no uploaded file)
     * @throws InvalidConfigException if model can't handle file operations
     * @throws UploadException if file uploaded with error
     */
    public function updateFileFromRecord(ActiveRecord $record, string $attribute): string
    {
        $oldFileName = $record->$attribute;
        $newFileName = $this->saveFile();
        if (!$newFileName) {
            return $oldFileName;
        }

        if ($oldFileName) {
            FileHelper::unlinkFile($this->filePath($oldFileName));
        }

        return $newFileName;
    }

    /**
     * Indicate the presence of the uploaded file
     * 
     * @return bool
     * @throws InvalidConfigException if model can't handle file operations
     */
    public function hasUploadedFile(): bool
    {
        $uploaded = $this->getUploadedFile();
        return $uploaded !== null && $uploaded->getError() === UPLOAD_ERR_OK;
    }

    /**
     * Save uploaded form file on server to directory specified in `$_filesDir` attribute
     * 
     * @return string uploaded file name
     * @throws InvalidConfigException if model can't handle file operations
     * @throws UploadException if file uploaded with error
     * @throws RuntimeException if uploaded file is image and:
     *  - unsafe (contains injections)
     *  - failed to resize if oversized
     */
    public function saveFile(): string
    {
        $this->assertConfigValid();
        if (!$this->hasUploadedFile()) {
            return '';
        }

        $this->assertWritable();
        $uplFile = FileHelper::generateName() . '.' . $this->getUploadedFile()->getExtension();
        $uplFile = FileHelper::fileExists($this->_filesDir, $uplFile);
        $uplPath = $this->filePath($uplFile);

        if (!$this->getUploadedFile()->moveTo($uplPath)) {
            throw new UploadException($this->getUploadedFile()->getError());
        }

        $this->setPermission($uplPath);

        // uploaded file is image
        if (strstr($this->getUploadedFile()->getClientMediaType(), 'image')) {
            $this->assertImageSafe($uplFile);
            // there is size limitations for uploaded images
            if (isset($this->_maxImageWidth) || isset($this->_maxImageHeight)) {
                return $this->processImageReduction($uplFile);
            }
            return $uplFile;
        }
        // uploaded file is no image
        else {
            return $uplFile;
        }
    }

    /**
     * Set file attribute from PSR-7 array contains tree of UploadedFileInterface instances.
     * 
     * @param array $data PSR-7 uploaded metadata.
     * @param string|null $formName the form name to use to load the data into the model.
     * If not set, `formName()` is used.
     * 
     * @return bool Just for similar behavior as `fill()`.
     * Always return `true`, because it doesn't matter if there are uploaded files or not.
     * If you need to be sure about uploaded files use appropriate validation.
     * 
     * @see ServerRequestInterface::getUploadedFiles();
     * @see Model::fill()
     */
    public function fillFiles(array $data, string $formName = null): bool
    {
        $scope = $formName ?? $this->formName();
        if ($scope === '' && !empty($data)) {
            $this->setFileAttribute($data);
        } elseif (isset($data[$scope])) {
            $this->setFileAttribute($data[$scope]);
        }

        return true;
    }

    /**
     * Get full form file path
     * 
     * @param string $file file name
     * @param bool $strict if true check that file path exists 
     * 
     * @return string|null
     */
    private function filePath(string $file, bool $strict = false): ?string
    {
        $path = FileHelper::getFilePath($this->_filesDir, $file);
        if ($strict) {
            return is_file($path) ? $path : null;
        }
        return $path;
    }

    /**
     * Get attribute name which handle uploaded file.
     * 
     * @return string
     */
    private function getFileAttribute(): string
    {
        return $this->_fileInputNameAttribute ?? $this->_fileAttribute;
    }

    /**
     * Attach `UploadedFile` instance from server uploaded metadata
     * to model's attribute according `$_fileAttribute` value.
     * 
     * @return void
     */
    private function setFileAttribute(array $data): void
    {
        $this->assertConfigValid();
        $attribute = $this->getFileAttribute();
        if (!isset($data[$attribute])) {
            $this->$attribute = null;
            return;
        }

        $this->$attribute = $data[$attribute] instanceof UploadedFile ? $data[$attribute] : null;
    }

    /**
     * Set file mode (permissions)
     * 
     * @param string $filePath file path to set permissions
     * 
     * @return void
     * @throws RuntimeException if permission set failed
     */
    private function setPermission(string $filePath): void
    {
        if (!chmod($filePath, $this->_filePermission)) {
            throw new RuntimeException(
                sprintf('Unable to set mode "%s" to file "%s"', $this->_filePermission, $filePath)
            );
        }
    }

    /**
     * Reduce image size if oversized
     * 
     * @param string $file image file name to process
     * 
     * @return string processed image file name
     * @throws RuntimeException if uploaded image failed to resize
     */
    private function processImageReduction(string $fileName): string
    {
        $maxWidth = $this->_maxImageWidth ?? $this->_maxImageHeight;
        $maxHeight = $this->_maxImageHeight ?? $this->_maxImageWidth;
        $filePath = $this->filePath($fileName);
        list($width, $height) = getimagesize($filePath);
        if ($maxWidth >= $width && $maxHeight >= $height) {
            return $fileName;
        }

        $prefix = 'rsz_';
        $rszFile = $prefix . FileHelper::generateName() . '.' . $this->getUploadedFile()->getExtension();
        $rszFile = FileHelper::fileExists($this->_filesDir, $rszFile, $prefix);
        $rszPath = $this->filePath($rszFile);
        if (!ImageHelper::createThumbnail($filePath, $rszPath, $maxWidth, $maxHeight)) {
            throw new RuntimeException("Resize uploaded image to file '{$rszPath}' is failed");
        }
        $this->setPermission($rszPath);
        FileHelper::unlinkFile($filePath);

        return $rszFile;
    }

    /**
     * Assert image is safe
     * 
     * @param string $fileName image file name to verify
     * 
     * @return void
     * @throws RuntimeException if uploaded image unsafe (contains injections)
     */
    private function assertImageSafe(string $fileName): void
    {
        $filePath = $this->filePath($fileName);
        if (!ImageHelper::verifyImage($filePath)) {
            FileHelper::unlinkFile($filePath);
            throw new RuntimeException('Uploaded image file is unsafe');
        }
    }

    /**
     * Check that form correctly configured to handle file operations
     * 
     * @return void
     * @throws InvalidConfigException if model can't handle file operations
     * @see $_fileAttribute
     * @see $_filesDir
     */
    private function assertConfigValid(): void
    {
        if (!isset($this->_fileAttribute) || !isset($this->_filesDir)) {
            throw new InvalidConfigException(
                'Invalid form configuration. Check "$fileAttribute" and "$_filesDir" form properties'
            );
        }
    }

    /**
     * Assert form directory to save is writable
     * 
     * @return void
     * @throws InvalidConfigException if form directory to save not exists or not writable
     * @see $_filesDir
     */
    private function assertWritable(): void
    {
        if (!is_writable($this->_filesDir)) {
            throw new InvalidConfigException(
                'Form destination directory does not exists or not enough rights to write.'
            );
        }
    }
}