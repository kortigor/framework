<?php

declare(strict_types=1);

namespace utils\sitemap;

use DateTime;
use InvalidArgumentException;
use OutOfRangeException;
use RuntimeException;
use XMLWriter;

/**
 * Sitamap file(s) generator class.
 */
class Generator
{
    /**
     * Max size of a sitemap according to spec.
     * @see http://www.sitemaps.org/protocol.html
     */
    private const MAX_FILE_SIZE = 52428800;

    /**
     * Max number of urls per sitemap according to spec.
     * @see http://www.sitemaps.org/protocol.html
     */
    private const MAX_URLS_PER_SITEMAP = 50000;

    /**
     * Max number of sitemaps per index file according to spec.
     * @see http://www.sitemaps.org/protocol.html
     */
    private const MAX_SITEMAPS_PER_INDEX = 50000;

    /**
     * Total max number of URLs.
     */
    private const TOTAL_MAX_URLS = self::MAX_URLS_PER_SITEMAP * self::MAX_SITEMAPS_PER_INDEX;

    /**
     * Used xml namespaces.
     */
    private const XML_NS = [
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        'xmlns:xhtml' => 'http://www.w3.org/1999/xhtml',
        'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
    ];

    /**
     * @var string Name of sitemap file
     */
    private string $fileName = 'sitemap.xml';

    /**
     * @var string Name of sitemap index file
     */
    private string $indexFileName = 'sitemap-index.xml';

    /**
     * @var int Quantity of URLs per single sitemap file.
     * If Your links are very long, sitemap file can be bigger than 10MB, in this case use smaller value.
     */
    private int $maxUrlsPerSitemap = self::MAX_URLS_PER_SITEMAP;

    /**
     * @var bool If true, two sitemap files (.xml and .xml.gz) will be created and added to robots.txt.
     * If true, .gz file will be submitted to search engines.
     * If quantity of URLs will be bigger than 50.000, option will be ignored,
     * all sitemap files except sitemap index will be compressed.
     */
    private bool $isCompressionEnabled = false;

    /**
     * @var string Site url. Script will use it to send sitemaps to search engines.
     */
    private string $baseUrl;

    /**
     * @var string Base path. Relative to script location.
     * Use this if sitemap and robots files should be stored in other directory then script.
     */
    private string $basePath;

    /**
     * @var string Version of this class
     */
    private string $classVersion = '1.0.1';

    /**
     * @var XMLWriter Used for writing xml to files
     */
    private XMLWriter $xmlWriter;

    /**
     * @var string
     */
    private string $flushedFileNameFormat;

    /**
     * @var int
     */
    private int $flushedSize = 0;

    /**
     * @var int
     */
    private int $flushedCounter = 0;

    /**
     * @var array
     */
    private array $flushed = [];

    /**
     * @var bool
     */
    private bool $isStarted = false;

    /**
     * @var int
     */
    private int $totalUrlCount = 0;

    /**
     * @var int
     */
    private int $urlSetClosingTagLen = 10; // mb_strlen("</urlset>\n")

    /**
     * @var int
     */
    private int $urlCount = 0;

    /**
     * @var array
     */
    private array $generatedFiles = [];

    /**
     * Constructor.
     * 
     * @param string $baseUrl Site base url.
     * @param string $basePath Filesystem path where sitemap and robots should be stored.
     */
    public function __construct(string $baseUrl, string $basePath)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        if (!is_writable($basePath)) {
            throw new InvalidArgumentException("Provided basePath ({$basePath}) should be a writable directory.");
        }

        if (mb_strlen($basePath) > 0 && substr($basePath, -1) !== DIRECTORY_SEPARATOR) {
            $basePath = $basePath . DIRECTORY_SEPARATOR;
        }

        $this->basePath = $basePath;
        $this->xmlWriter = $this->createXmlWriter();
        $this->flushedFileNameFormat = sprintf("sm-%%d-%d.xml", time());
    }

    /**
     * Create xml writer instance.
     * 
     * @return XMLWriter
     */
    private function createXmlWriter(): XMLWriter
    {
        $w = new XMLWriter;
        $w->openMemory();
        $w->setIndent(true);
        return $w;
    }

    /**
     * Set sitemap file name.
     * 
     * @param string $fileName
     * @return self
     */
    public function setFilename(string $fileName): self
    {
        if (!$fileName) {
            throw new InvalidArgumentException('Sitemap filename should not be empty');
        }

        if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'xml') {
            throw new InvalidArgumentException('Sitemap filename should have *.xml extension');
        }

        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Set sitemap index file name.
     * 
     * @param string $fileName
     * @return self
     */
    public function setIndexFilename(string $fileName): self
    {
        if (!$fileName) {
            throw new InvalidArgumentException('Filename should not be empty');
        }
        $this->indexFileName = $fileName;
        return $this;
    }

    /**
     * Set maximum allowed urls per sitemap.
     * 
     * Determine how many urls should be put into one file.
     * This feature is useful in case if site have too many urls and sitemap is out of allowed size (50Mb).
     * According to the standard protocol 50000 urls per sitemap is the maximum allowed value
     * 
     * @param int $value
     * @return self
     * 
     * @see http://www.sitemaps.org/protocol.html
     */
    public function setMaxUrlsPerSitemap(int $value): self
    {
        if ($value < 1 || self::MAX_URLS_PER_SITEMAP < $value) {
            throw new OutOfRangeException(
                sprintf('Value %d is out of range 1-%d', $value, self::MAX_URLS_PER_SITEMAP)
            );
        }
        $this->maxUrlsPerSitemap = $value;
        return $this;
    }

    /**
     * Enable sitemap compression
     * 
     * @return self
     */
    public function enableCompression(): self
    {
        $this->isCompressionEnabled = true;
        return $this;
    }

    /**
     * Disable sitemap compression
     * 
     * @return self
     */
    public function disableCompression(): self
    {
        $this->isCompressionEnabled = false;
        return $this;
    }

    /**
     * Whether sitemap compression enabled.
     * 
     * @return bool
     */
    public function isCompressionEnabled(): bool
    {
        return $this->isCompressionEnabled;
    }

    /**
     * Add url components.
     * 
     * Instead of storing all urls in the memory, the generator will flush sets of added urls
     * to the temporary files created on your disk.
     * The file format is 'sm-{index}-{timestamp}.xml'
     * 
     * @param Url $url Sitemap url object
     * @return self
     */
    public function addUrl(Url $url): self
    {
        if ($this->totalUrlCount >= self::TOTAL_MAX_URLS) {
            throw new OutOfRangeException(sprintf("Max url limit reached (%d)", self::TOTAL_MAX_URLS));
        }

        if (!$this->isStarted) {
            $this->writeStart();
        }

        $this->writeUrl($url);

        if ($this->totalUrlCount % 1000 === 0 || $this->urlCount >= $this->maxUrlsPerSitemap) {
            $this->flushWrite();
        }

        if ($this->urlCount === $this->maxUrlsPerSitemap) {
            $this->writeEnd();
        }

        return $this;
    }

    /**
     * Generate sitemap.
     * 
     * @return void
     */
    public function generate(): void
    {
        $this->flush();
        $this->finalize();
    }

    /**
     * Get information about generated filenames.
     * 
     * @return array Array of previously generated files
     */
    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    /**
     * Get filesystem base path.
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Flush all stored urls from memory to the disk and close all necessary tags.
     * 
     * @return void
     */
    private function flush(): void
    {
        $this->flushWrite();
        if ($this->isStarted) {
            $this->writeEnd();
        }
    }

    /**
     * Move flushed files to their final location. Compress if the option is enabled.
     * 
     * @return void
     * @throws RuntimeException Failed to finalize, please add urls and flush first
     */
    private function finalize(): void
    {
        $this->generatedFiles = [];

        // One sitemap
        if (count($this->flushed) === 1) {
            $this->finalizeSitemap();
            return;
        }

        // Several sitemaps with index
        if (count($this->flushed) > 1) {
            $this->finalizeIndex();
            return;
        }

        throw new RuntimeException('Failed to finalize, please add urls and flush first');
    }

    /**
     * Wrire url in sitemap
     * 
     * @param Url $url
     * 
     * @return void
     */
    private function writeUrl(Url $url): void
    {
        $this->xmlWriter->startElement('url');
        $this->xmlWriter->writeElement('loc', htmlspecialchars($this->baseUrl . $url->path, ENT_QUOTES));

        if ($url->lastModified !== null) {
            $this->xmlWriter->writeElement('lastmod', $url->lastModified->format(DateTime::ATOM));
        }

        if ($url->changeFrequency) {
            $this->xmlWriter->writeElement('changefreq', $url->changeFrequency);
        }

        if ($url->priority !== null) {
            $this->xmlWriter->writeElement('priority', number_format($url->priority, 1, '.', ''));
        }

        foreach ($url->alternates as $alternate) {
            $this->xmlWriter->startElement('xhtml:link');
            $this->xmlWriter->writeAttribute('rel', 'alternate');
            $this->xmlWriter->writeAttribute('hreflang', $alternate['hreflang']);
            $this->xmlWriter->writeAttribute('href', $alternate['href']);
            $this->xmlWriter->endElement();
        }

        $this->xmlWriter->endElement(); // url
        $this->urlCount++;
        $this->totalUrlCount++;
    }

    /**
     * Finalize single sitemap.
     * 
     * @return void
     */
    private function finalizeSitemap(): void
    {
        $targetFileName = $this->fileName;
        if ($this->isCompressionEnabled) {
            $targetFileName .= '.gz';
        }

        $targetFilePath = $this->basePath . $targetFileName;
        $this->moveFlushedFile($this->flushed[0], $targetFilePath);
        $this->generatedFiles['sitemaps_location'] = [$targetFilePath];
        $this->generatedFiles['sitemaps_index_url'] = $this->baseUrl . '/' . $targetFileName;
    }

    /**
     * Finalize index sitemap.
     * 
     * @return void
     */
    private function finalizeIndex(): void
    {
        $ext = '.' . pathinfo($this->fileName, PATHINFO_EXTENSION);
        $targetExt = $ext;
        if ($this->isCompressionEnabled) {
            $targetExt .= '.gz';
        }

        $sitemapUrls = [];
        $targetFilePaths = [];
        foreach ($this->flushed as $i => $flushedSitemap) {
            $targetFileName = str_replace($ext, ($i + 1) . $targetExt, $this->fileName);
            $targetFilePath = $this->basePath . $targetFileName;
            $this->moveFlushedFile($flushedSitemap, $targetFilePath);
            $sitemapUrls[] = htmlspecialchars($this->baseUrl . '/' . $targetFileName, ENT_QUOTES);
            $targetFilePaths[] = $targetFilePath;
        }

        $targetIndexFilePath = $this->basePath . $this->indexFileName;
        $this->createIndex($sitemapUrls, $targetIndexFilePath);
        $this->generatedFiles['sitemaps_location'] = $targetFilePaths;
        $this->generatedFiles['sitemaps_index_location'] = $targetIndexFilePath;
        $this->generatedFiles['sitemaps_index_url'] = $this->baseUrl . '/' . $this->indexFileName;
    }

    /**
     * Move flushed file to final destination or compress if option enabled.
     * 
     * @param string $file
     * @param string $target
     * 
     * @return void
     */
    private function moveFlushedFile(string $file, string $target): void
    {
        if ($this->isCompressionEnabled) {
            copy($file, 'compress.zlib://' . $target);
            unlink($file);
        } else {
            rename($file, $target);
        }
    }

    /**
     * Flush all stored urls from memory to the disk.
     * 
     * @return void
     */
    private function flushWrite(): void
    {
        $targetFilePath = $this->basePath . sprintf($this->flushedFileNameFormat, $this->flushedCounter);
        $flushedString = $this->xmlWriter->outputMemory(true);
        $flushedStringLen = mb_strlen($flushedString);

        if ($flushedStringLen === 0) {
            return;
        }

        $this->flushedSize += $flushedStringLen;

        if ($this->flushedSize > self::MAX_FILE_SIZE - $this->urlSetClosingTagLen) {
            $this->writeEnd();
            $this->writeStart();
        }

        file_put_contents($targetFilePath, $flushedString, FILE_APPEND);
    }

    /**
     * Start write sitemap
     * 
     * @return void
     */
    private function writeStart(): void
    {
        $this->writeDocumentHead();
        $this->xmlWriter->startElement('urlset');
        $this->writeNs(['xmlns', 'xmlns:xsi', 'xmlns:xhtml', 'xsi:schemaLocation']);
        $this->isStarted = true;
    }

    /**
     * End write sitemap
     * 
     * @return void
     */
    private function writeEnd(): void
    {
        $targetFilePath = $this->basePath . sprintf($this->flushedFileNameFormat, $this->flushedCounter);
        $this->xmlWriter->endElement(); // urlset
        $this->xmlWriter->endDocument();
        file_put_contents($targetFilePath, $this->xmlWriter->flush(true), FILE_APPEND);
        $this->isStarted = false;
        $this->flushed[] = $targetFilePath;
        $this->flushedCounter++;
        $this->urlCount = 0;
    }

    /**
     * Write xml document header and comments
     * 
     * @return void
     */
    private function writeDocumentHead(): void
    {
        $this->xmlWriter->startDocument('1.0', 'UTF-8');
        $this->xmlWriter->writeComment(sprintf('generator-class="%s"', get_class($this)));
        $this->xmlWriter->writeComment(sprintf('generator-version="%s"', $this->classVersion));
        $this->xmlWriter->writeComment(sprintf('generated-on="%s"', date('c')));
    }

    /**
     * Write xml elsment namespaces
     * 
     * @param array $namespaces Namespaces names as described in `self::XML_NS`
     * 
     * @return void
     * @see self::XML_NS
     */
    private function writeNs(array $namespaces): void
    {
        foreach ($namespaces as $ns) {
            $this->xmlWriter->writeAttribute($ns, self::XML_NS[$ns]);
        }
    }

    /**
     * Create sitemaps index file
     * 
     * @param array $sitemapUrls Sitemaps urls
     * @param string $indexFileName Index file name
     * 
     * @return void
     */
    private function createIndex(array $sitemapUrls, string $indexFileName): void
    {
        $this->xmlWriter->flush(true);
        $this->writeIndexStart();
        foreach ($sitemapUrls as $url) {
            $this->writeIndexUrl($url);
        }
        $this->writeIndexEnd();
        file_put_contents($indexFileName, $this->xmlWriter->flush(true));
    }

    /**
     * Start write sitemap index
     * 
     * @return void
     */
    private function writeIndexStart(): void
    {
        $this->writeDocumentHead();
        $this->xmlWriter->startElement('sitemapindex');
        $this->writeNs(['xmlns', 'xmlns:xsi', 'xsi:schemaLocation']);
    }

    /**
     * Write sitemap url into sitemaps index
     * 
     * @param string $url
     * 
     * @return void
     */
    private function writeIndexUrl(string $url): void
    {
        $this->xmlWriter->startElement('sitemap');
        $this->xmlWriter->writeElement('loc', htmlspecialchars($url, ENT_QUOTES));
        $this->xmlWriter->writeElement('lastmod', date('c'));
        $this->xmlWriter->endElement(); // sitemap
    }

    /**
     * End write sitemap index
     * 
     * @return void
     */
    private function writeIndexEnd(): void
    {
        $this->xmlWriter->endElement(); // sitemapindex
        $this->xmlWriter->endDocument();
    }
}