<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Post;
use BlogBackend\Exception\NotImplementedException;
use BlogBackend\Exception\JsonDecodeException;
use BlogBackend\Exception\FileNotFoundException;
use BlogBackend\Exception\InvalidFileNameException;
use BlogBackend\Exception\ArchiveException;

/**
 * Retrieves all posts stored on the filesystem, as well as serializes a JSON
 * archive of published posts for easier access.
 */
class Archive
{
  /** @var string? $file The file where the archive is stored flatly */
  private $flat_archive_file;

  /** @var string? $file The file where the archive is stored by year/month/day */
  private $ymd_archive_file;

  /** @var string $posts_folder Where posts are stored before being published */
  private $posts_folder;

  /** @var string $published_folder Where published posts are stored */
  private $published_folder;

  /** @var array? $flat_archive Posts in a 1d array sorted by publish time */
  private $flat_archive = null;

  /** @var array? $ymd_archive Posts in a 3d array organized by year/month/day */
  private $ymd_archive = null;

  // TODO: document once the API has settled
  public function __construct(
    string $posts_folder,
    string $published_folder,
    string $flat_archive_file = null,
    string $ymd_archive_file  = null
  ) {
    // See if the folders exist
    if (!file_exists($posts_folder)) {
      throw new FileNotFoundException(
        "Folder {$posts_folder} for unpublished posts doesn't exist"
      );
    }

    if (!file_exists($published_folder)) {
      throw new FileNotFoundException(
        "Folder {$published_folder} for publishing posts doesn't exist"
      );
    }

    // These are nullable and will just cause exceptions later if the user 
    // hasn't set them
    $this->flat_archive_file = $flat_archive_file;
    $this->ymd_archive_file  = $ymd_archive_file;

    $this->posts_folder      = $posts_folder;
    $this->published_folder  = $published_folder;
  }

  /**
   * Gets the filename where the archive is or will be stored as a 1D JSON map. 
   *
   * @return string
   */
  public function getFlatArchiveFilename(): string
  {
    return $this->flat_archive_file;
  }

  /**
   * Gets the filename where the archive is or will be stored as a nested JSON 
   * map by year/month/day.
   *
   * @return string
   */
  public function getYmdArchiveFilename(): string
  {
    return $this->ymd_archive_file;
  }

  /**
   * Loads metadata for all currently published posts into a 1D array sorted by 
   * timestamp.
   * 
   * If the file doesn't exist, throws an exception.
   *
   * @throws ArchiveJsonDecodeException if there is an error decoding the archive.
   * @throws FileNotFound               if the archive file doesn't exist yet.
   * @return void
   */
  public function loadFlatArchive(): void
  {
    $file_contents = @file_get_contents($this->flat_archive_file);
    if ($file_contents === false) {
      throw new FileNotFoundException(
        "File {$this->flat_archive_file} not found. Make sure to generate it first."
      );
    }

    $archive = json_decode($file_contents, true);

    foreach ($archive as $publish_time => $params) {
      $archive[$publish_time] = PostFactory::fromParams($params);
    }

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new JsonDecodeException(json_last_error_msg());
    }

    $this->flat_archive = $archive;
  }

  public function generateFlatArchive(): void
  {
    $post_files = glob($this->published_folder . '/*.md');

    $archive = [];
    foreach ($post_files as $post_file) {
      preg_match('/^\d+/', basename($post_file), $match);
      if (isset($match[0])) {
        $publish_date = $match[0];
        $archive[$publish_date] = new Post($post_file);
      } else {
        throw new InvalidFileNameException(
          "Failed to regex publish date from filename {$post_file}"
        );
      }
    }

    krsort($archive, SORT_NUMERIC);

    file_put_contents(
      $GLOBALS['blog_root'] . '/archive.json',
      json_encode($archive)
    );
  }

  /**
   * Gets all posts modified between two Unix timestamps
   * 
   * @throws \RangeException If the bounds are unacceptable (i.e. lower bound > upper)
   * @param int $from_time   Unix timestamp
   * @param int $to_time     Unix timestamp
   * @return array
   */
  public function postsByRange(int $from_time, int $to_time): array
  {
    if ($this->flat_archive === null) {
      throw new \RuntimeException(
        "Flat archive not loaded. Call loadFlatArchive() first."
      );
    }

    if ($from_time > $to_time) {
      throw new \RangeException('Bounds of get_posts_by_range() invalid.');
    }

    $posts_in_range = array_filter(
      $this->flat_archive,
      function (Post $post) use ($from_time, $to_time) {
        $post_time = $post->getPublishTime();
        return ($post_time >= $from_time && $post_time <= $to_time);
      }
    );

    return $posts_in_range;
  }

  public function postsByTags(array $tags)
  {
    if (empty($tags)) {
      throw new \InvalidArgumentException('Array of tags must not be empty.');
    }

    // prepend # to tags if not already present.
    array_walk($tags, function (string &$val) {
      if ($val[0] !== '#') {
        $val = '#' . $val;
      }
    });

    $posts_with_matching_tags = array();
    foreach ($this->flat_archive as $timestamp => $post) {
      if (!empty(array_intersect($tags, $post->getTags()))) {
        $posts_with_matching_tags[$timestamp] = $post;
      }
    }

    return $posts_with_matching_tags;
  }

  /**
   * Gets data for a single post.
   * 
   * Return form: array(
   * 	'title' 				=> string, 
   * 	'tags' 					=> string[], 
   * 	'last_modified' => int, 
   * 	...
   * );
   *
   * @param string $path
   * @return Post
   */
  function getPostData(string $path): Post
  {
    $fp_in = fopen($path, 'r');

    $tags_line = fgets($fp_in);
    // array_values() guarantees that the result is indexed, not associative.
    $tags = array_values(array_filter(explode(' ', $tags_line), function ($val) {
      return strpos($val, '#') !== false;
    }));

    $title = '';
    while (($line = fgets($fp_in)) !== false) {
      if (strpos($line, '#') === 0) {
        $title = ltrim($line, '# ');
        break;
      }
    }

    preg_match('/^\d+/', basename($path), $match);
    if (isset($match[0])) {
      $publish_date = $match[0];
    } else {
      throw new Exception('Failed to regex publish date from file ' . $path);
    }

    return array(
      'path'          => $path,
      'title'         => $title,
      'tags'          => $tags,
      'last_modified' => filemtime($path),
      'publish_date'  => $publish_date,
    );
  }

  public function publish(string $post_file, int $time = null): Post
  {
    $time = $time ?? time();
    $post_basename = basename($post_file);
    $destination = "{$this->published_folder}/{$time}_{$post_basename}";

    // Really shouldn't happen, but is technically possible.
    if (file_exists($destination)) {
      throw new ArchiveException("Published post already exists at $destination");
    }

    // Attempt the copy and throw an exception if it fails
    if (!@copy($post_file, $destination)) {
      throw new \RuntimeException(error_get_last());
    }

    return PostFactory::fromFilename($destination);
  }

  public function loadYmdArchive() : void {
    $file_contents = @file_get_contents($this->ymd_archive_file);
    if ($file_contents === false) {
      throw new FileNotFoundException(
        "File {$this->ymd_archive_file} not found. Make sure to generate it first."
      );
    }

    $archive = json_decode($file_contents, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new JsonDecodeException(json_last_error_msg());
    }

    foreach ($archive as $year => $months) {
      foreach ($months as $month => $days) {
        foreach ($days as $day => $posts) {
          foreach ($posts as $post_params) {
            $archive[$year][$month][$day] = PostFactory::fromParams($post_params);
          }
        }
      }
    }

    $this->ymd_archive = $archive;
  }

  /**
   * Collects the paths to all published posts into an associative array with 
   * posts filed away by year and month and then serializes it to JSON.
   */
  function generateYmdArchive(): void
  {
    throw new NotImplementedException("TODO: implement");

    if ($this->flat_archive === null) {
      throw new ArchiveException(
        "Call loadFlatArchive() before calling generateYmdArchive()."
      );
    }

    $archive_by_year = [];
    foreach ($this->flat_archive as $publish_time => $post) {
      // construct datetime from Unix timestamp
      $post_datetime = DateTime::createFromFormat(
        'U', // unix timestamp
        $publish_time,
        new DateTimeZone('America/Los_Angeles')
      );

      // use year and month to sort posts into data structure ($archive)
      $year  = $post_datetime->format('Y');
      $month = $post_datetime->format('m');

      $archive_by_year[$year][$month][] = $post;
    }

    // sort years descending
    arsort($archive_by_year);

    // sort months
    foreach ($archive_by_year as $year => &$months) {
      uksort($months, function ($a, $b) {
        $month_a = date_parse($a)['month'];
        $month_b = date_parse($b)['month'];

        return $month_a - $month_b;
      });
    }

    $this->ymd_archive = $archive_by_year;
  }
}
