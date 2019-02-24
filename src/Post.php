<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Exception\FileNotFoundException;
use BlogBackend\Exception\InvalidFileNameException;

/**
 * Represents a post.
 */
class Post
{
  /** @var string $fileName Path to the file */
  protected $fileName;

  /** @var string $title */
  protected $title;

  /** @var array $tags */
  protected $tags;

  /** @var int $lastModified unix timestamp */
  protected $lastModified;

  /** @var int $publishTime unix timestamp */
  protected $publishTime;

  /** @var string|null $author */
  protected $author = null;

  public function __construct(
    string $fileName,
    string $title,
    array  $tags,
    string $author = null
  ) {
    if (!file_exists($fileName)) {
      throw new FileNotFoundException("{$fileName} does not exist.");
    }

    $this->fileName      = $fileName;
    $this->title         = $title;
    $this->tags          = $tags;
    $this->lastModified  = filemtime($fileName);
    $this->publishTime   = Post::parsePublishTime($fileName);
    $this->author        = $author;
  }

  /**
   * Parses publish time from the filename of a published post.
   * 
   * @throws InvalidFileNameException if the filename doesn't start with a unix 
   *                                  timestamp
   * @param string $filename
   * @return int
   */
  protected static function parsePublishTime(string $filename): int
  {
    preg_match('/^(\d+)_/', basename($filename), $match);
    if (isset($match[1])) {
      $publish_time = $match[1];
      return (int) $publish_time;
    } 

    throw new InvalidFileNameException(
      "Failed to regex publish date from filename {$filename}"
    );
  }

  public function text(): string
  {
    return file_get_contents($this->fileName);
  }

  public function array(): array 
  {
    return [
      'fileName'     => $this->fileName,
      'title'        => $this->title,
      'tags'         => $this->tags,
      'lastModified' => $this->lastModified,
      'publishTime'  => $this->publishTime,
      'author'       => $this->author,
    ];
  }

  public function getFileName()     : string  { return $this->fileName;     }
  public function getTitle()        : string  { return $this->title;        }
  public function getTags()         : array   { return $this->tags;         }
  public function getLastModified() : int     { return $this->lastModified; }
  public function getPublishTime()  : int     { return $this->publishTime;  }
  public function getAuthor()       : ?string { return $this->author;       }
}