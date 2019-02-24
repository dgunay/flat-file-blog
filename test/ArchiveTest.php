<?php declare (strict_types = 1);

namespace BlogBackend\Test;

use BlogBackend\Archive;
use BlogBackend\PostFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class ArchiveTest extends TestCase
{
  /** @var Archive $archive */
  private $archive;

  /** @var org\bovigo\vfs\vfsStreamDirectory $root */
  private $root;

  public function setUp(): void
  {
    // set up the root of our virtual filesystem with two folders
    $this->root  = vfsStream::setUp('home');
    $published   = vfsStream::newDirectory('published')->at($this->root);

    // Copy our posts into them
    vfsStream::copyFromFileSystem(
      __DIR__ . '/Fixtures/virtual/published',
      $published
    );

    // Make a virtual 1D archive file and put our fixture in it as JSON
    vfsStream::newFile('flat_archive.json')->at($this->root);
    file_put_contents(
      vfsStream::url('home/flat_archive.json'),
      json_encode($this->flatArchiveFixture())
    );


    // Make a virtual YMD archive file and put our fixture in it as JSON
    vfsStream::newFile('ymd_archive.json')->at($this->root);
    file_put_contents(
      vfsStream::url('home/ymd_archive.json'),
      json_encode($this->ymdArchiveFixture())
    );

    // Give these virtual folders/files to the Archive
    $this->archive = new Archive(
      vfsStream::url('home/published'),
      vfsStream::url('home/flat_archive.json'),
      vfsStream::url('home/ymd_archive.json')
    );
  }

  private function flatArchiveFixture(): array
  {
    return [
      "1514618960" => [
        "fileName" => vfsStream::url("home/published/1514618960_wow3.md"),
        "title" => "title",
        "tags" => ["#post"],
        // "last_modified" => 1514618960,
        "publishTime" => 1514618960
      ],
      "1514618983" => [
        "fileName" => vfsStream::url("home/published/1514618983_wow2.md"),
        "title" => "title",
        "tags" => ["#post"],
        // "last_modified" => 1514618983,
        "publishTime" => 1514618983
      ],
      "1523335881" => [
        "fileName"  => vfsStream::url("home/published/1523335881_wow.md"),
        "title" => "Smash 5 Wishlist",
        "tags"  => ["#smash", "#nintendo", "#switch", "#gaming"],
        // "last_modified" => 1523335881,
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
              "tags"  => ["#smash", "#nintendo", "#switch", "#gaming"],
              // "last_modified" => 1523335881,
              "publishTime" => 1523335881
            ],
          ]
        ]
      ],
      '2017' => [
        '12' => [
          '30' => [
            [
              "fileName" => vfsStream::url("home/published/1514618983_wow2.md"),
              "title" => "title",
              "tags" => ["#post"],
              // "last_modified" => 1514618983,
              "publishTime" => 1514618983
            ],
            [
              "fileName" => vfsStream::url("home/published/1514618960_wow3.md"),
              "title" => "title",
              "tags" => ["#post"],
              // "last_modified" => 1514618960,
              "publishTime" => 1514618960
            ]
          ]
        ]
      ]
    ];
  }

  public function testPostsByRange()
  {
    // Load the archive in memory
    $this->archive->loadFlatArchive();

    $expected = array_map(function (array $params) {
      return PostFactory::fromParams($params);
    }, array_slice($this->flatArchiveFixture(), 0, 2, true));

    // should get wow and wow2
    $posts = $this->archive->postsByRange(1514618959, 1514618984);
    $this->assertEquals($expected, $posts);
  }

  /**
   * @dataProvider tagsProvider
   */
  public function testPostsByTags(array $tags, array $expected)
  {
    $this->archive->loadFlatArchive();

    // We have to map the array to Post here because dataProviders run before 
    // setUp().
    $expected = array_map(function (array $params) {
      return PostFactory::fromParams($params);
    }, $expected);

    $posts = $this->archive->postsByTags($tags);

    $this->assertEquals($expected, $posts);
  }

  public function tagsProvider()
  {
    $archive = $this->flatArchiveFixture();
    return [
      'Normal tags' => [
        ['#post'],
        [
          '1514618960' => $archive["1514618960"],
          '1514618983' => $archive["1514618983"],
        ],
      ],
      'missing # tags' => [
        ['smash'],
        [
          '1523335881' => $archive["1523335881"],
        ]
      ],
    ];
  }

  public function testPublishPost()
  {
    // Virtualize a new file.
    vfsStream::newFile('unpublished/my_new_post.md')->at($this->root)->setContent(
      file_get_contents(__DIR__ . '/Fixtures/my_new_post.md')
    );

    // Publish it
    $post = $this->archive->publish(
      vfsStream::url('home/unpublished/my_new_post.md'),
      100
    );

    // Did it get published?
    $this->assertTrue(file_exists(vfsStream::url('home/published/100_my_new_post.md')));

    // Are the post details correct?
    $expected = [
      "fileName" => vfsStream::url("home/published/100_my_new_post.md"),
      'title' => "My New Post",
      'tags' => ['#wow'],
      "publishTime" => 100,
    ];
    $actual = [
      "fileName" => $post->getFileName(),
      'title' => $post->getTitle(),
      'tags' => $post->getTags(),
      "publishTime" => $post->getPublishTime(),
    ];
    $this->assertEquals($expected, $actual);
  }

  public function testGetPostsFrom()
  {
    // Map expected to Posts
    $archive = $this->mapYmdArchiveFixtureToPosts();

    $this->archive->loadYmdArchive();

    // Get everything from 2017
    $expected = $archive['2017'];
    $this->assertEquals(
      $expected,
      $this->archive->getPostsFrom(2017),
      'Posts from 2017'
    );

    // Get posts from 4/10/2018
    $expected = $archive['2018']['4']['10'];
    $this->assertEquals(
      $expected,
      $this->archive->getPostsFrom(2018, 4, 10),
      'Posts on 10/4/2018'
    );

    // Cache miss
    $this->expectException(\OutOfBoundsException::class);
    $this->archive->getPostsFrom(2019);
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
