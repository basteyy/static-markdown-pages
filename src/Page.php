<?php
declare(strict_types=1);

namespace basteyy\StaticMarkdownPage;

use basteyy\StaticMarkdownPage\Exceptions\IncompleteMetadataException;
use basteyy\StaticMarkdownPage\Helper\SplitContent;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use function basteyy\StaticMarkdownPage\Helper\toSnakeCase;

class Page
{

    /** @var string|mixed Filepath of the current page */
    private string $filepath;

    /** @var string|mixed Title of the current page */
    private string $title;

    /** @var string|mixed Url of the current page */
    private string $url;

    /** @var string|mixed|null Author of the current page */
    private string|null $author;

    /** @var string Storage for the current raw body */
    private string $raw_body;

    /** @var string Storage for the compiled body */
    private string $compiled_body;

    public function __construct(array $file, bool $load_content = false)
    {
        if (!isset($file['title'])) {
            throw new IncompleteMetadataException(sprintf('The Metadata of %s is incomplete: title is missing.', $file['path']));
        }

        if (!isset($file['url'])) {
            throw new IncompleteMetadataException(sprintf('The Metadata of %s is incomplete: url is missing.', $file['path']));
        }

        foreach($file as $meta => $value) {
            $this->{$meta} = $value;
        }

        if ($load_content) {
            if (!isset($this->compiled_body)) {
                $this->compileContent();
            }
        }
    }

    /**
     * Compile content
     */
    private function compileContent(): void
    {
        $this->compiled_body = (new GithubFlavoredMarkdownConverter())->convertToHtml($this->getRawBody())->getContent();
    }

    /**
     * Return the raw content
     * @return string
     */
    public function getRawBody(): string
    {
        if (!isset($this->raw_body)) {
            $this->loadContent();
        }

        return $this->raw_body;
    }

    /**
     * External way to write the body of the page in case, its somewhere else already loaded
     * @param string $content
     * @param bool $recompile
     */
    public function setRawBody(string $content, bool $recompile = true): void
    {
        $this->raw_body = $content;

        if ($recompile) {
            $this->compileContent();
        }
    }

    /**
     * Load the content from filesystem
     */
    private function loadContent(): void
    {
        $this->raw_body = SplitContent::getBody(file_get_contents($this->getFilepath()));
    }

    /**
     * Return the filepath
     * @return string
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * Return the title of the document
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Return the url of the document
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Return the author (if set) of the document
     * @return string|null
     */
    public function getAuthor(): string|null
    {
        return $this->author;
    }

    /**
     * Return the compiled html
     * @return string
     */
    public function getHtml(): string
    {
        if (!isset($this->compiled_body)) {
            $this->compileContent();
        }

        return $this->compiled_body;
    }

    /**
     * The "magical" call
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws IncompleteMetadataException
     */
    public function __call(string $name, array $arguments)
    {
        if(str_starts_with($name, 'get')) {
            $name = toSnakeCase(substr($name, 3));

            if(isset($this->{$name})) {
                return $this->{$name};
            }
        }

        throw new IncompleteMetadataException(sprintf('Static File %s dosnt contain meta %s', $this->getFilepath(), $name));
    }
}