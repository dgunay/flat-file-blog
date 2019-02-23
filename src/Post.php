<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Exception\NotImplementedException;

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
    int    $publishTime,
    string $author = null
  ) {
    // TODO: validation
    $this->fileName      = $fileName;
    $this->title         = $title;
    $this->tags          = $tags;
    $this->lastModified  = filemtime($fileName);
    $this->publishTime   = $publishTime;
    $this->author        = $author;
  }

  public function markdown(): string
  {
    throw new NotImplementedException(
      "TODO: chop off the header and return the markdown"
    );
    return '';
  }

  public function getFileName()     : string  { return $this->fileName;      }
  public function getTitle()        : string  { return $this->title;         }
  public function getTags()         : array   { return $this->tags;          }
  public function getLastModified() : int     { return $this->lastModified;  }
  public function getPublishTime()  : int     { return $this->publishTime;   }
  public function getAuthor()       : ?string { return $this->author;        }
}