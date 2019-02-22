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

  /** @var string $publish_date unix timestamp */
  protected $publish_date; 

  /** TODO: overload to be either an array or a filename constructor */
  // or use an interface?
  public function __construct(string $file_name)
  {
    // TODO: validation
    $this->path          = $file_name;
    $this->title         = $this->parseTitle();
    $this->tags          = $this->parseTags();
    $this->last_modified = filemtime($file_name);
    $this->publish_date  = parsePublishDate();
  }

  // parse title from first # 
  protected function parseTitle() : string {
    throw new NotImplementedException();
    return '';
  }
  // parse tags from first comment
  protected function parseTags() : array {
    throw new NotImplementedException();
    return [];
  }
  // parse from filename
  protected function parsePublishDate() : string {
    throw new NotImplementedException();
    return '';
  }
  
  public function getPath()        : string { return $this->path;          }
  public function getTitle()       : string { return $this->title;         }
  public function getTags()        : array  { return $this->tags;          }
  public function LastModified()   : string { return $this->last_modified; }
  public function getPublishDate() : string { return $this->publish_date;  }
}

