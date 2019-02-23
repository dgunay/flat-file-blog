<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Exception\NotImplementedException;

/**
 * Represents a post.
 */
class Post
{
  /** @var string $path File path */
  protected $path;

  /** @var string $title */
  protected $title;

  /** @var array $tags */
  protected $tags;

  /** @var string $last_modified unix timestamp */
  protected $last_modified;

  /** @var string $publish_time unix timestamp */
  protected $publish_time;

  /** TODO: overload to be either an array or a filename constructor */
  // or use an interface?
  public function __construct(
    string $path,
    string $title,
    array  $tags,
    string $publish_time
  ) {
    // TODO: validation
    $this->path          = $path;
    $this->title         = $title;
    $this->tags          = $tags;
    $this->last_modified = filemtime($path);
    $this->publish_time  = $publish_time;
  }

  public function markdown(): string
  {
    throw new NotImplementedException(
      "TODO: chop off the header and return the markdown"
    );
    return '';
  }

  public function getPath()         : string { return $this->path;          }
  public function getTitle()        : string { return $this->title;         }
  public function getTags()         : array  { return $this->tags;          }
  public function getLastModified() : string { return $this->last_modified; }
  public function getPublishTime()  : string { return $this->publish_time;  }

