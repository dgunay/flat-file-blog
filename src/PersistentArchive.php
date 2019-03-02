<?php declare (strict_types = 1);

namespace BlogBackend;

use BlogBackend \{Post, Archive
};

use BlogBackend\Exception \{
  JsonDecodeException,
  FileNotFoundException,
  ArchiveException
};

/**
 * Retrieves all posts stored on the filesystem, as well as serializes a JSON
 * archive of published posts for easier access.
 */
class PersistentArchive extends Archive
{
  /** @var string $file The file where the archive is stored flatly */
  private $flat_archive_file;

  /** @var string $file The file where the archive is stored by year/month/day */
  private $ymd_archive_file;

  // TODO: document once the API has settled
  public function __construct(
    string $published_folder,
    string $flat_archive_file,
    string $ymd_archive_file,
    array $post_files = null
  ) {
    parent::__construct($published_folder, $post_files);

    // See if the files exist
    if (!file_exists($flat_archive_file)) {
      throw new FileNotFoundException(
        "Folder {$flat_archive_file} for flat archive doesn't exist"
      );
    }

    if (!file_exists($flat_archive_file)) {
      throw new FileNotFoundException(
        "Folder {$flat_archive_file} for y/m/d doesn't exist"
      );
    }

    $this->flat_archive_file = $flat_archive_file;
    $this->ymd_archive_file  = $ymd_archive_file;
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
   * timestamp, via the archive file.
   * 
   * If the file doesn't exist, throws an exception.
   *
   * @throws JsonDecodeException if there is an error decoding the archive.
   * @throws \RuntimeException   if the archive file can't be loaded.
   * @return array
   */
  public function loadFlatArchiveFromFile(): array
  {
    $file_contents = $this->slurpFile($this->flat_archive_file);

    $archive = json_decode($file_contents, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new JsonDecodeException(json_last_error_msg());
    }

    foreach ($archive as $publish_time => $params) {
      $archive[$publish_time] = PostFactory::fromParams($params);
    }

    $this->flat_archive = $archive;
    return $archive;
  }

  private function slurpFile(string $filename): string
  {
    $file_contents = @file_get_contents($filename);
    if ($file_contents === false) {
      $err = error_get_last();
      $msg = $err['message'] ?? '';
      throw new \RuntimeException("Failed to load {$filename}: {$msg}");
    }

    return $file_contents;
  }

  public function loadYmdArchiveFromFile(): array
  {
    $file_contents = $this->slurpFile($this->ymd_archive_file);

    $archive = json_decode($file_contents, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new JsonDecodeException(json_last_error_msg());
    }

    // Map the posts into Post objects
    foreach ($archive as $year => $months) {
      foreach ($months as $month => $days) {
        foreach ($days as $day => $posts) {
          foreach ($posts as $index => $post_params) {
            $archive[$year][$month][$day][$index] = PostFactory::fromParams($post_params);
          }
        }
      }
    }

    $this->ymd_archive = $archive;
    return $archive;
  }

  /**
   * Creates a JSON file at $this->flat_archive_file by reading a series of post files and serializing them.
   *
   * @param string[] $post_files Array of files to override the usual published
   *                             folder.
   * @return void
   */
  public function generateFlatArchive(array $post_files = null): void
  {
    file_put_contents(
      $this->flat_archive_file,
      json_encode($this->loadFlatArchive($post_files))
    );
  }

  /**
   * Collects the paths to all published posts into an associative array with 
   * posts filed away by year and month and then serializes it to JSON.
   */
  function generateYmdArchive(array $post_files = null): void
  {
    // glob() doesn't work with vfsStream, so we need to inject the post files
    // for testing. Otherwise it will just look in the published folder.
    if ($post_files === null) {
      $post_files = glob($this->published_folder . '/*.md');
    } else {
      $post_files = $post_files;
    }

    $posts = array_map(function (string $filename): Post {
      return PostFactory::fromFilename($filename);
    }, $post_files);

    $archive_by_year = $this->constructYmdArchiveFromPosts($posts);
    file_put_contents(
      $this->ymd_archive_file,
      json_encode($archive_by_year)
    );
  }
}
