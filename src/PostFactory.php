<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Post;
use BlogBackend\Exception\InvalidFileNameException;

use Symfony\Component\Yaml\Yaml;

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
    if ( ($fp_in = fopen($filename, 'r')) === false) {
      throw new \RuntimeException("Failed to open {$filename} for reading.");
    }

    // First line is a comment <!--
    while ($line = fgets($fp_in)) {
      if (preg_match('/<!--/', $line)) {
        break;
      }
    }

    // Now read the YAML
    $yaml = '';
    while ($line = fgets($fp_in)) {
      // end as soon as -->
      if (preg_match('/-->/', $line)) {
        break;
      }
      else {
        $yaml .= $line;
      }
    }
    
    $header = Yaml::parse($yaml);

    // Get the title from the first H1 (#) if it's not already in the header.
    if (!array_key_exists('title', $header)) {
      while ($line = fgets($fp_in)) {
        if (preg_match('/#(.+)/', $line, $match)) {
          $header['title'] = trim($match[1]);
          fclose($fp_in);
          return $header;
        }
      }

      throw new \RuntimeException(
        "Unable to parse title: reached end of file {$filename}"
      );
    }

    return $header;
  }
}
