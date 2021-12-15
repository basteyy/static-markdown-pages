<?php
declare(strict_types=1);

namespace basteyy\StaticMarkdownPages;

use basteyy\StaticMarkdownPages\Helper\SplitContent;
use DateTime;
use DirectoryIterator;
use Exception;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class Archive
{
    /** @var string Storage path where the files are stored */
    private string $storage_path;

    /** @var string Cache path */
    private string $cache_path;

    /** @var bool Use cache */
    private bool $cache;

    /** @var string Location of the cached index */
    private string $cache_index;

    /** @var array|mixed Cached Index-Array of the pages */
    private array $pages;

    /** @var array Cached Array of the loaded content from the markdown files */
    private array $pages_content;

    /** @var string The supported file extension of the markdown files (md) */
    private string $file_extension = 'md';

    /** @var int|float Lifetime for cached files */
    private int|float $cache_lifetime = 60 * 60 * 12; // 12 hours

    /** @var int|float Holds the current unixtimestamp for this request */
    private int|float $current_cache_lifetime;

    /** @var bool Indicator for regenerating the index file on deconstruct */
    private bool $indexPatched = false;

    public function __construct(string $storage_path, bool $cache = true, string $cache_path = null)
    {
        $this->storage_path = rtrim($storage_path, DIRECTORY_SEPARATOR);
        $this->cache = $cache;
        $this->cache_path = $cache_path ?? $this->storage_path . DIRECTORY_SEPARATOR . '.cache' . DIRECTORY_SEPARATOR;
        $this->cache_index = $this->cache_path . '.index.php';
        $this->current_cache_lifetime = time() - $this->cache_lifetime;

        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0777, true);
        }

        if ($this->cache && file_exists($this->cache_index)) {
            if (filemtime($this->cache_index) > $this->current_cache_lifetime) {
                $this->pages = include $this->cache_index;
            }
        }

    }

    /**
     * Get an array of all pages inside the storage
     * @return array
     */
    public function getFilesList(): array
    {
        if (!isset($this->pages)) {
            $this->generateIndex();
        }

        return $this->pages;
    }

    /**
     * Generate the index of all files
     */
    public function generateIndex(): void
    {
        /** @var SplFileInfo $file */
        foreach (new DirectoryIterator($this->storage_path) as $file) {
            if ($file->getExtension() === $this->file_extension) {
                $this->indexFile($file);
            }
        }

        if ($this->cache) {
            $this->generateIndexCache();
        }

    }

    /**
     * Add a file to the index
     * @param SplFileInfo $file
     * @throws Exception
     */
    private function indexFile(SplFileInfo $file): void
    {
        list($meta, $content) = SplitContent::get(file_get_contents($file->getRealPath()));
        $metadata = Yaml::parse($meta);

        $this->pages_content[$metadata['url']] = $content;

        $this->pages[$metadata['url']] = $metadata + [
                'filesize'    => $file->getSize(),
                'filepath'    => $file->getRealPath(),
                'last_update' => (new DateTime('@' . $file->getMTime()))->format('c')
            ];
    }

    /**
     * Write current context to the index cache
     */
    private function generateIndexCache(): void
    {
        file_put_contents(
            $this->cache_index,
            sprintf("<?php\ndeclare(strict_types=1);\n/* Cache created on %s */\n\nreturn %s;\n\n",
                (new DateTime())->format('c'),
                var_export($this->pages, true)
            )
        );
    }

    /**
     * Get a page by its url
     * @param string $url
     * @return Page
     * @throws Exceptions\IncompleteMetadataException
     */
    public function getByUrl(string $url): Page
    {
        if (!isset($this->pages)) {
            $this->generateIndex();
        }

        if (!isset($this->pages[$url])) {
            throw new Exception('Static Page not found.');
        }

        return $this->loadFile($this->pages[$url]);
    }

    /**
     * Load a file from filesystem or from cache (if activated)
     * @param array $page
     * @return Page
     * @throws Exceptions\IncompleteMetadataException
     */
    private function loadFile(array $page): Page
    {

        $page_cache_filename = $this->cache_path . '.' . hash('xxh3', $page['filepath']) . '.php';
        $live_raw_body = $this->pages_content[$page['url']] ?? false;
        $current_filesize = filesize($page['filepath']);

        if ($this->cache && file_exists($page_cache_filename) && filemtime($page_cache_filename) > $this->current_cache_lifetime && $page['filesize'] == $current_filesize) {
            $pageObject = unserialize(file_get_contents($page_cache_filename));
        } else {

            $pageObject = new Page($page, true);

            // Patch new filesize to index
            if ($page['filesize'] != $current_filesize) {
                $this->patchIndex($page['url'], ['filesize' => $current_filesize]);
            }

            // Write to cache
            if ($this->cache) {
                file_put_contents(
                    $page_cache_filename,
                    serialize($pageObject)
                );
            }
        }

        if ($live_raw_body) {
            $pageObject->setRawBody($live_raw_body);
        }

        return $pageObject;
    }

    /**
     * Patch an entry
     * @param string $page_url
     * @param array $data
     */
    private function patchIndex(string $page_url, array $data)
    {
        $this->indexPatched = true;

        foreach ($data as $field => $value) {
            $this->pages[$page_url][$field] = $value;
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->indexPatched) {
            $this->generateIndexCache();
        }
    }
}