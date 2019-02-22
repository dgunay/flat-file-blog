<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend\Post;
use BlogBackend\Exception\NotImplementedException;
use BlogBackend\Exception\JsonDecodeException;
use BlogBackend\Exception\FileNotFoundException;

/**
 * Retrieves all posts stored on the filesystem.
 */
class Archive
{
  /** @var string $file The file where the archive is stored */
  private $file;

  private $archive = false;

  public function __construct(string $file)
  {
    $this->file = $file;
    $this->loadArchive($this->file);
  }

  public function getFileName(): string
  {
    return $this->file;
  }

  /**
   * Loads metadata for all posts into a 1D array sorted by timestamp.
   *
   * @throws ArchiveJsonDecodeException if there is an error decoding the archive.
   * @return array
   */
  private function loadArchive(): void
  {
    $file_contents = @file_get_contents($this->file);
    if ($file_contents === false) {
      throw new FileNotFoundException("File {$this->file} not found.");
    }

    $archive = json_decode($file_contents, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new JsonDecodeException(json_last_error_msg());
    }

    $this->archive = $archive;
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
      throw new Exception('Failed to regex publish date from file ' . $path);
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

  /**
 * TODO: document and finish
 *
 * @return void
 */
  function generate_archive_by_folder()
  {
    throw new NotImplementedException();

    $archive = array();

    $years = glob($GLOBALS['blog_root'] . '/archive/*', GLOB_ONLYDIR);
    foreach ($years as $year) {
      $year = basename($year);
      $archive[$year] = array();

      $months = glob($GLOBALS['blog_root'] . '/archive/' . $year . '/*', GLOB_ONLYDIR);
      foreach ($months as $month) {
        $month = basename($month);
        $archive[$year][$month] = array();
        $days = glob(
          $GLOBALS['blog_root'] . '/archive/' . $year . '/' . $month . '/*',
          GLOB_ONLYDIR
        );

        foreach ($days as $day) {
          $day = basename($day);
          $archive[$year][$month][$day] = array();

          $posts = glob(
            $GLOBALS['blog_root'] . '/archive/' . $year . '/' . $month . '/' . $day . '/*'
          );
          foreach ($posts as $post) {
            $archive[$year][$month][$day][$post] = get_post_data($post);
          }
        }
      }
    }

    // sort years descending
    krsort($archive);

    // sort months
    foreach ($archive as $year => &$months) {
      uksort($months, function ($a, $b) {
        $month_a = date_parse($a)['month'];
        $month_b = date_parse($b)['month'];

        return $month_a - $month_b;
      });
    }

    file_put_contents(
      $GLOBALS['blog_root'] . '/archive.json',
      json_encode($archive)
    );
  }

  /**
 * Collects posts into a 1D sorted array by timestamp.
 *
 * @return void
 */
  function generate_archive_by_timestamp()
  {
    throw new NotImplementedException();

    $all_posts = glob($GLOBALS['blog_root'] . '/posts/*.md');

    $archive = array();
    foreach ($all_posts as $path_to_post) {
      $post_data = get_post_data($path_to_post);
      $archive[$post_data['last_modified']] = array_merge(
        array('path' => $path_to_post),
        $post_data
      );
    }

    krsort($archive, SORT_NUMERIC);

    // output the archive as .json
    file_put_contents(
      $GLOBALS['blog_root'] . '/timestamp_archive.json',
      json_encode($archive)
    );
  }

  /**
 * TODO: document
 *
 * @return void
 */
  function generate_archive_chronological()
  {
    throw new NotImplementedException();

    $archive = load_archive();

    $archive_in_order = array();
    foreach ($archive as $year) {
      krsort($year);
      foreach ($year as $month) {
        krsort($month);
        foreach ($month as $day) {
          foreach ($day as $path => $post) {
            $archive_in_order[$path] = $post;
          }
        }
      }
    }

    file_put_contents(
      $GLOBALS['blog_root'] . '/archive_chronological.json',
      json_encode($archive_in_order)
    );
  }

  function generate_archive(string $folder = null)
  {
    throw new NotImplementedException();

    $archive = array();
    $posts = glob($GLOBALS['blog_root'] . '/' . ($folder ?? 'archive') . '/*.md');

    foreach ($posts as $post) {
      preg_match('/^\d+/', basename($post), $match);
      if (isset($match[0])) {
        $publish_date = $match[0];
        $archive[$publish_date] = get_post_data($post);
      } else {
        throw new Exception('Failed to regex publish date from filename ' . $post);
      }
    }

    krsort($archive, SORT_NUMERIC);

    file_put_contents(
      $GLOBALS['blog_root'] . '/archive.json',
      json_encode($archive)
    );
  }

  function get_archive()
  {
    throw new NotImplementedException();
    return json_decode(
      $GLOBALS['blog_root'] . '/archive.json',
      true
    );
  }

  function update_archive()
  {
    throw new NotImplementedException();
    
    $archive = get_archive();

    $posts = glob($GLOBALS['blog_root'] . '/archive/*.md');
    foreach ($posts as $post) {
      // figure out if this post is in the archive, by filename
      $post_is_already_in_archive = false;
      foreach ($archive as $old_post) {
        if (basename($old_post['path']) == basename($post)) {
          $post_is_already_in_archive = true;
          break;
        }
      }

      // if it's new, make new data for it.
      if (!$post_is_already_in_archive) {
        $archive[filemtime($post)] = get_post_data($post);
      }
    }

    krsort($archive, SORT_NUMERIC);

    return $archive;
  }
}
