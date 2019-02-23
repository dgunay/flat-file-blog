<?php declare (strict_types = 1);

namespace BlogBackend\Test;

use PHPUnit\Framework\TestCase;
use BlogBackend\PostFactory;

final class PostFactoryTest extends TestCase
{
  /**
   * @dataProvider postTextProvider
   */
  public function testConstructPostFromFile(array $expected)
  {
    $post = PostFactory::fromFilename($expected['fileName']);
    $this->assertEquals($post->getPath(),         $expected['fileName']);
    $this->assertEquals($post->getTitle(),        $expected['title']);
    $this->assertEquals($post->getTags(),         $expected['tags']);
    $this->assertEquals($post->getLastModified(), $expected['last_modified']);
    $this->assertEquals($post->getPublishTime(),  $expected['publish_time']);
  }

  public function postTextProvider() {
    return [
      [
        [
          'fileName' => __DIR__ . '/Fixtures/1550908491_sample_post.md',
          'title' => 'Title of My Blog Post',
          'tags' => ['#BigChungus', '#memes'],
          'last_modified' => '1550908491',
          'publish_time' => '1550908491',
        ]
      ],
      [
        [
          'fileName' => __DIR__ . '/Fixtures/1550908492_sample_post_with_title.md',
          'title' => 'Title of My Blog Post',
          'tags' => ['#BigChungus', '#memes'],
          'last_modified' => '1550908492',
          'publish_time' => '1550908492',
        ]
      ],
    ];
  }

  public function testConstructPostFromParams()
  {
    $this->markTestIncomplete();
  }
}
