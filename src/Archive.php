<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Post;
use BlogBackend\Exception\FileNotFoundException;
use BlogBackend\Exception\ArchiveException;
use BlogBackend\Exception\PostNotFoundException;

/**
 * Retrieves all posts stored on the filesystem and stores them in an in-memory
 * representation.
 */
class Archive
{
  /** @var string $published_folder Where published posts are stored */
  protected $published_folder;

  /** @var array<int, Post> $flat_archive Posts in a 1d array sorted by publish time */
  protected $flat_archive;

  // * @var array<int, array<int, array<int, array<int, Post>>>> $ymd_archive Posts in a 3d 
  /** 
   * @var array<int, array<int, array<int, array<int, Post>>>>
   * $ymd_archive Posts in a 3d array organized by year/month/day 
   * */
  protected $ymd_archive;

  // TODO: document once the API has settled
  public function __construct(string $published_folder, array $post_files = null)
  {
    // See if the folder exists
    if (!file_exists($published_folder)) {
      throw new FileNotFoundException(
        "Folder {$published_folder} for publishing posts doesn't exist"
      );
    }

    $this->published_folder  = $published_folder;
    $this->flat_archive = $this->loadFlatArchive($post_files);
    $this->ymd_archive  = $this->loadYmdArchive();
  }

  /**
   * Deserializes posts from the filesystem and into a flat archive. Will set
   * $this->flat_archive as a side effect.
   *
   * @param string[] $post_files Array of files to override $this->published_folder.
   * @return array<int, Post>
   */
  public function loadFlatArchive(array $post_files = null): array
  {
    // glob() doesn't work with vfsStream, so we need to inject the post files
    // for testing. Otherwise it will just look in the published folder.
    if ($post_files === null) {
      $post_files = glob($this->published_folder . '/*.md');
    } else {
      $post_files = $post_files;
    }

    $archive = [];
    foreach ($post_files as $post_file) {
      $post = PostFactory::fromFilename($post_file);
      $archive[$post->getPublishTime()] = $post;
    }
    
    // sort it by timestamp
    krsort($archive, SORT_NUMERIC);

    $this->flat_archive = $archive;
    return $archive;
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
    if ($from_time > $to_time) {
      throw new \RangeException('Bounds of get_posts_by_range() invalid.');
    }

    $posts_in_range = array_filter(
      $this->flat_archive,
      function (Post $post) use ($from_time, $to_time): bool {
        $post_time = $post->getPublishTime();
        return ($post_time >= $from_time && $post_time <= $to_time);
      }
    );

    return $posts_in_range;
  }

  /**
   * Returns posts with any overlap at all with the passed tags. Tags need not
   * have '#' symbol prepended.
   *
   * @param string[] $tags
   * @return array<int, Post>
   */
  public function postsByTags(array $tags): array
  {
    if (empty($tags)) {
      throw new \InvalidArgumentException('Array of tags must not be empty.');
    }

    // prepend # to tags if not already present.
    array_walk($tags, function (string &$val): void {
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
   * Takes an unpublished post from a given filename and publishes it into the
   * publishing folder.
   *
   * @throws ArchiveException if the post already exists or if it fails to copy 
   *                          the post to its publish location.
   * @param string $post_file Unpublished post markdown file.
   * @param int    $time      If set, sets the publish time to this unix 
   *                          timestamp. Otherwise it defaults to time().
   * @return Post
   */
  public function publish(string $post_file, int $time = null): Post
  {
    $time = $time ?? time();
    $post_basename = basename($post_file);
    $destination = "{$this->published_folder}/{$time}_{$post_basename}";

    // Really shouldn't happen, but is technically possible.
    if (file_exists($destination)) {
      throw new ArchiveException("Published post already exists at {$destination}");
    }

    // Attempt the copy and throw an exception if it fails
    if (!@copy($post_file, $destination)) {
      $err = error_get_last();
      throw new ArchiveException($err['msg'] ?? '');
    }

    try {
      return PostFactory::fromFilename($destination);
    } catch (\Exception $e) {
      // Clean up the copied file before rethrowing
      unlink($destination);
      throw $e;
    }
  }

  /**
   * Collects the paths to all published posts into an associative array with 
   * posts filed away by year and month and then serializes it to JSON.
   * 
   * @return array<int, array<int, array<int, array<int, Post>>>>
   */
  public function loadYmdArchive(): array
  {
    $archive_by_year = $this->constructYmdArchiveFromPosts($this->flat_archive);
    $this->ymd_archive = $archive_by_year;
    return $archive_by_year;
  }

  /**
   * Helper function for *YmdArchive() functions in this class and subclasses.
   * 
   * @param Post[] $posts
   * @return array
   */
  protected function constructYmdArchiveFromPosts(array $posts) : array {
    $archive_by_year = [];
    foreach ($posts as $post) {
      // construct datetime from Unix timestamp
      $post_datetime = \DateTime::createFromFormat(
        'U', // unix timestamp
        (string) $post->getPublishTime(),
        new \DateTimeZone('America/Los_Angeles')
      );

      if ($post_datetime === false) {
        throw new ArchiveException(
          "Failed to parse publish time '{$post->getPublishTime()}' for Post '{$post->getTitle()}'"
        );
      }

      // use year/month/day to sort posts into data structure ($archive)
      $year  = $post_datetime->format('Y');
      $month = $post_datetime->format('n'); // no leading zeroes.
      $day   = $post_datetime->format('j'); // no leading zeroes.

      // Just in case
      if (!$year || !$month || !$day) {
        throw new \LogicException("DateTime::format() failed for year, month, or day.");
      }

      $archive_by_year[(int) $year][(int) $month][(int) $day][] = $post;
    }

    foreach ($archive_by_year as $year => $months) {
      foreach ($months as $month => $days) {
        foreach ($days as $day => &$posts) {
          if (count($posts) > 1) {
            usort($posts, function(Post $a, Post $b) {
              return $a->getPublishTime() <=> $b->getPublishTime();
            });
            $archive_by_year[$year][$month][$day] = $posts;
          }
        }
      }
    }

    return $archive_by_year;
  }

  /**
   * Gets posts from a given year/month/day. Month and day are optional. The
   * resulting array is not flattened so you will have to do that yourself if
   * you are retrieving by year/month and not day.
   *
   * @throws PostNotFoundException if there are no posts in the year/month/day.
   * @param string|int $year
   * @param string|int $month
   * @param string|int $day
   * @return array
   */
  public function getPostsFrom($year, $month = null, $day = null): array
  {
    if (array_key_exists($year, $this->ymd_archive)) {
      $the_year = $this->ymd_archive[$year];

      if ($month !== null) {
        if (array_key_exists($month, $the_year)) {
          $the_month = $the_year[$month];

          if ($day !== null) {
            if (array_key_exists($day, $the_month)) {
              return $the_month[$day];
            } else {
              throw new PostNotFoundException(
                "No posts published on {$year}/{$month}/{$day}"
              );
            }
          }

          return $the_month;
        } else {
          throw new PostNotFoundException("No posts published in {$year}/{$month}");
        }
      }

      return $the_year;
    } else {
      throw new PostNotFoundException("No posts published in {$year}");
    }
  }

  /**
   * Gets the full underlying data structure of the flat Archive.
   *
   * @return array<int, Post>
   */
  public function getFlatArchive(): array
  {
    return $this->flat_archive;
  }

  /**
   * Gets the full underlying data structure of the y/m/d Archive.
   *
   * @return array<int, array<int, array<int, array<int, Post>>>>
   */
  public function getYmdArchive(): array
  {
    return $this->ymd_archive;
  }
}
