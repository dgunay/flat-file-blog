<?php declare (strict_types = 1);

namespace BlogBackend\Test;

use PHPUnit\Framework\TestCase;
use BlogBackend\PostFactory;

final class PostFactoryTest extends TestCase
{
  /**
   * @dataProvider postProvider
   */
  public function testConstructPostFromFile(array $expected)
  {
    $post = PostFactory::fromFilename($expected['fileName']);
    $this->assertEquals($expected['fileName'],     $post->getFileName());
    $this->assertEquals($expected['title'],        $post->getTitle());
    $this->assertEquals($expected['tags'],         $post->getTags());
    $this->assertEquals($expected['publish_time'], $post->getPublishTime());
  }

  public function postProvider() {
    return [
      [
        [
          'fileName' => __DIR__ . '/Fixtures/1550908491_sample_post.md',
          'title' => 'Title of My Blog Post',
          'tags' => ['#BigChungus', '#memes'],
          'publish_time' => '1550908491',
        ]
      ],
      [
        [
          'fileName' => __DIR__ . '/Fixtures/1550908492_sample_post_with_title.md',
          'title' => 'Title of My Blog Post',
          'tags' => ['#BigChungus', '#memes'],
          'publish_time' => '1550908492',
        ]
      ],
    ];
  }

  /**
   * @dataProvider postProvider
   */
  public function testConstructPostFromParams(array $params)
  {
    $post = PostFactory::fromParams($params);
    $this->assertTrue(true);
  }
}
