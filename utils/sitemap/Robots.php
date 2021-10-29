<?php

declare(strict_types=1);

namespace utils\sitemap;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * Robots file updater for Sitamap
 */
class Robots
{
    /**
     * @var string Robots file name
     */
    private string $fileName = 'robots.txt';

    /**
     * @var array Lines for robots.txt file that are written if file does not exist
     */
    private array $emptyLines = [
        'User-agent: *',
        'Allow: /',
    ];

    /**
     * Constructor.
     * 
     * @param Generator $generator Sitemap generator instance.
     */
    public function __construct(private Generator $generator)
    {
    }

    /**
     * Set robots filename.
     * 
     * @param string $fileName
     * @return self
     * 
     * @throws InvalidArgumentException If filename is empty
     */
    public function setRobotsFileName(string $fileName): self
    {
        if (!$fileName) {
            throw new InvalidArgumentException('Filename should not be empty');
        }
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Adds sitemap url to robots.txt file located in generator's basePath.
     * If robots.txt file exists, method will append sitemap url to file.
     * If robots.txt does not exist, method will create new robots.txt file with sample content and sitemap url.
     * 
     * @throws BadMethodCallException
     */
    public function update()
    {
        if (!$this->generator->getGeneratedFiles()) {
            throw new BadMethodCallException('To update robots.txt, call Generator::generate() first.');
        }
        $filePath = $this->generator->getBasePath() . $this->fileName;
        $fileContent = $this->createContentFromFile($filePath);
        return file_put_contents($filePath, $fileContent);
    }

    /**
     * Create robots file content.
     * 
     * @param $filePath File full filesystem path to write.
     * @return string
     */
    private function createContentFromFile(string $filePath): string
    {
        if (file_exists($filePath)) {
            $fileContent = '';
            $robotsFile = explode(PHP_EOL, file_get_contents($filePath));
            foreach ($robotsFile as $key => $value) {
                if (substr($value, 0, 8) === 'Sitemap:') {
                    unset($robotsFile[$key]);
                } else {
                    $fileContent .= $value . PHP_EOL;
                }
            }
        } else {
            $fileContent = $this->getEmptyContent();
        }

        $generatedFiles = $this->generator->getGeneratedFiles();
        $fileContent .= "Sitemap: {$generatedFiles['sitemaps_index_url']}";

        return $fileContent;
    }

    /**
     * Generate empty robots file content
     * 
     * @return string
     */
    private function getEmptyContent(): string
    {
        return implode(PHP_EOL, $this->emptyLines) . PHP_EOL;
    }
}