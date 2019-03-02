<?php declare (strict_types = 1);

namespace BlogBackend\Test;

use BlogBackend\PersistentArchive;
use BlogBackend\PostFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStreamContent;

final class PersistentArchiveTest extends TestCase
{
  /** @var PersistentArchive $archive */
  private $archive;

  /** @var org\bovigo\vfs\vfsStreamDirectory $root */
  private $root;

  /** @var string[] $post_files Used to get around limitations of vfs. */
  private $post_files = [];

  public function setUp(): void
  {
    // set up the root of our virtual filesystem with two folders
    $this->root  = vfsStream::setUp('home');
    $published   = vfsStream::newDirectory('published')->at($this->root);

    // Make a virtual 1D archive file and put our fixture in it as JSON
    vfsStream::newFile('flat_archive.json')->at($this->root);

    // Make a virtual YMD archive file and put our fixture in it as JSON
    vfsStream::newFile('ymd_archive.json')->at($this->root);

    // Virtualize our published posts
    vfsStream::copyFromFileSystem(
      __DIR__ . '/Fixtures/virtual/published',
      $published
    );

    // Get the post URIs
    $published_post_uris = array_map(function (vfsStreamContent $child) {
      return $child->url();
    }, $published->getChildren());

    $this->post_files = $published_post_uris;

    // Give these virtual folders/files to the Archive
    $this->archive = new PersistentArchive(
      vfsStream::url('home/published'),
      vfsStream::url('home/flat_archive.json'),
      vfsStream::url('home/ymd_archive.json'),
      $published_post_uris
    );
  }

  private function flatArchiveFixture(): array
  {
    return [
      '1514618960' => [
        "fileName" => vfsStream::url("home/published/1514618960_wow3.md"),
        "title" => "title",
        "tags" => ["#post"],
        "publishTime" => 1514618960
      ],
      '1514618983' => [
        "fileName" => vfsStream::url("home/published/1514618983_wow2.md"),
        "title" => "title",
        "tags" => ["#post"],
        "publishTime" => 1514618983
      ],
      '1523335881' => [
        "fileName"  => vfsStream::url("home/published/1523335881_wow.md"),
        "title" => "Smash 5 Wishlist",
        "tags"  => ["#BigChungus", "#memes"],
        "publishTime" => 1523335881
      ],
    ];
  }

  // Same as the flat one, but arranged by year/month/day
  private function ymdArchiveFixture(): array
  {
    return [
      '2018' => [
        '4' => [
          '10' => [
            [
              "fileName"  => vfsStream::url("home/published/1523335881_wow.md"),
              "title" => "Smash 5 Wishlist",
              "tags"  => ["#BigChungus", "#memes"],
              "publishTime" => 1523335881
            ],
          ]
        ]
      ],
      '2017' => [
        '12' => [
          '30' => [
            [
              "fileName" => vfsStream::url("home/published/1514618960_wow3.md"),
              "title" => "title",
              "tags" => ["#post"],
              "publishTime" => 1514618960
            ],
            [
              "fileName" => vfsStream::url("home/published/1514618983_wow2.md"),
              "title" => "title",
              "tags" => ["#post"],
              "publishTime" => 1514618983
            ],
          ]
        ]
      ]
    ];
  }

  public function testGetFlatArchiveFromFile()
  {
    $this->archive->generateFlatArchive($this->post_files);

    $expected = array_map(function (array $params) {
      return PostFactory::fromParams($params);
    }, $this->flatArchiveFixture());

    $actual = $this->archive->loadFlatArchiveFromFile();
    $this->assertEquals($expected, $actual);
  }

  public function testGetYmdArchiveFromFile()
  {
    $this->archive->generateYmdArchive($this->post_files);
    $expected = $this->mapYmdArchiveFixtureToPosts();

    $actual = $this->archive->loadYmdArchiveFromFile();
    $this->assertEquals($expected, $actual);
  }

  private function mapYmdArchiveFixtureToPosts(): array
  {
    $archive = $this->ymdArchiveFixture();
    foreach ($archive as $year => $months) {
      foreach ($months as $month => $days) {
        foreach ($days as $day => $posts) {
          foreach ($posts as $index => $post_params) {
            $archive[$year][$month][$day][$index] = PostFactory::fromParams($post_params);
          }
        }
      }
    }

    return $archive;
  }
}
