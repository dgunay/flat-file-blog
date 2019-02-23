<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Post;
use BlogBackend\Exception\NotImplementedException;

/**
 * Provides methods to handle constructing Post objects easily.
 */
class PostFactory
{
  /**
   * Parses the file for its attributes (like title, tags, etc) and then makes a
   * new Post from them.
   *
   * @param string $filename
   * @return Post
   */
  public static function fromFilename(string $filename): Post
  {
    // Parse out the attributes then send them to the constructor
    $params = PostFactory::parseHeader($filename);

    return new Post(
      $filename,
      $params['title'],
      $params['tags'],
      PostFactory::parsePublishTime($filename)
    );
  }

  /**
   * Constructs a post from an associative array of its params.
   *
   * @param array $params
   * @return Post
   */
  public static function fromParams(array $params): Post
  {
    return new Post(
      $params['fileName'],
      $params['title'],
      $params['tags'],
      $params['publishTime']
    );
  }

  /**
   * TODO: Undocumented function
   * 
   * @throws InvalidFileNameException if the filename doesn't start with a unix 
   *                                  timestamp
   * @param string $filename
   * @return int
   */
  protected static function parsePublishTime(string $filename): int
  {
    preg_match('/^\d+/', basename($filename), $match);
    if (isset($match[0])) {
      $publish_date = $match[0];
      return (int) $publish_date;
    } 

    throw new InvalidFileNameException(
      "Failed to regex publish date from filename {$filename}"
    );
  }

  protected static function parseHeader(string $filename) : array
  { 
    throw new NotImplementedException("TODO: implement parseHeader");
    return [];
  }
}
