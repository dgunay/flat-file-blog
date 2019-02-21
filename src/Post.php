<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Exception\NotImplementedException;


/**
 * Represents a post.
 */
class Post
{
  public function __construct(string $file_name)
  {
    throw new NotImplementedException(
      'TODO: implement constructor for Post'
    );
  }
}

