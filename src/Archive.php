<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Post;
use BlogBackend\Exception\NotImplementedException;

/**
 * Retrieves all posts stored on the filesystem.
 */
class Archive
{
  public function __construct(string $file_name)
  {
    throw new NotImplementedException(
      'TODO: implement constructor for Archive'
    );

    // TODO: load the archive here
    $this->loadArchive();
  }

  /**
   * Loads metadata for all posts into a 1D array sorted by timestamp.
   *
   * @throws Exception if there is an error decoding the archive.
   * @return array
   */
  private function loadArchive(): array
  {
    throw new NotImplementedException("TODO: Config this out");

    $archive = json_decode(
      file_get_contents($GLOBALS['blog_root'] . '/archive.json'),
      true
    );

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception(json_last_error_msg());
    }

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

    $archive = load_archive();

    $posts_in_range = array_filter(
      $archive,
      function ($val) use ($from_time, $to_time) {
        $post_time = filemtime($val);
        return $post_time >= $from_time && $post_time <= $to_time;
      }
    );

    return $posts_in_range;
  }

  public function postsByTags(array $tags)
  {
    if (empty($tags)) {
      throw new Exception('Array of tags must not be empty.');
    }

    // prepend # to tags if not already present.
    array_walk($tags, function (string &$val) {
      if ($val[0] !== '#') {
        $val = '#' . $val;
      }
    });

    $archive = load_archive();

    $posts_with_matching_tags = array();
    foreach ($archive as $timestamp => $post_data) {
      if (!empty(array_intersect($tags, $post_data['tags']))) {
        $posts_with_matching_tags[$timestamp] = $post_data;
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
      throw new Exception('Failed to regex publish date from filename ' . $path);
    }

    return array(
      'path'          => $path,
      'title'          => $title,
      'tags'          => $tags,
      'last_modified'  => filemtime($path),
      'publish_date'  => $publish_date,
    );
  }

  public function publishPost(Post $post, int $time = null): void
  {
    throw new NotImplementedException("TODO: implement publishPost");
    $destination = $GLOBALS['blog_root'] . '/archive/' . $time . '_' . basename($path_to_post);
    copy($path_to_post, $destination);
  }

  /**
   * Collects the paths to all .md files in ./posts into an associative array
   * with posts filed away by year and month. 
   */
  function get_archive_by_year()
  {
    throw new NotImplementedException("TODO: implement");
    $archive = array();
    $timestamp_archive = load_archive();
    foreach ($timestamp_archive as $publish_time => $post) {
      // construct datetime from Unix timestamp
      $post_datetime = DateTime::createFromFormat(
        'U', // unix timestamp
        $publish_time,
        new DateTimeZone('America/Los_Angeles')
      );

      // use year and month to sort posts into data structure ($archive)
      $year = $post_datetime->format('Y');
      $month = $post_datetime->format('m');

      $archive[$year][$month][] = $post;
    }

    // sort years descending
    arsort($archive);

    // sort months
    foreach ($archive as $year => &$months) {
      uksort($months, function ($a, $b) {
        $month_a = date_parse($a)['month'];
        $month_b = date_parse($b)['month'];

        return $month_a - $month_b;
      });
    }

    return $archive;
  }
}
