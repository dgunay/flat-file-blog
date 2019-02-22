<?php declare (strict_types = 1);

namespace BlogBackend\Test;

use BlogBackend\Archive;
use BlogBackend\Post;

use PHPUnit\Framework\TestCase;

final class ArchiveTest extends TestCase
{
	private $archive;

	public function setUp() : void {
    // Make a tempfile
    $filename = tempnam(sys_get_temp_dir(), 'blogbackendunittest_');
		file_put_contents($filename, json_encode($this->archiveFixture()));
    
		// Read from it
		$this->archive = new Archive($filename, sys_get_temp_dir());
  }
  
  public function tearDown() : void {
    unlink($this->archive->getFileName());
  }

	// private function getPostFixtures() : array {
	// 	return [
	// 		new Post(),
	// 		new Post(),
	// 		new Post(),
	// 	];
  // }
  
  private function archiveFixture() : array {
    return [
      "1523335881" => [
        // "path"  => "\/mnt\/c\/apache\/www\/html\/\/blog\/archive\/1523335881_smash_5_wishlist.md",
        "path"  => "/file/thing/wow.md",
        "title" => "Smash 5 Wishlist\n",
        "tags"  => [ "#smash", "#nintendo", "#switch", "#gaming" ],
        "last_modified" => 1523335881,
        "publish_date" => "1523335881"
      ],
      "1514618983" => [
        "path" => "/file/thing/wow2.md",
        "title" => "title\n",
        "tags" => [
          "#post"
        ],
        "last_modified" => 1514618983,
        "publish_date" => "1514618983"
      ],
      "1514618960" => [
        "path" => "/file/thing/wow3.md",
        "title" => "title\n",
        "tags" => [
          "#post"
        ],
        "last_modified" => 1514618960,
        "publish_date" => "1514618960"
      ]
    ];
  }

  public function testLoadArchive()
  {
    
  }

  public function testPostsByRange()
  {
    // should get wow and wow2
    $posts = $this->archive->postsByRange(1514618980, 1524618983);
    $this->assertEquals(array_slice($this->archiveFixture(), 0, 2), $posts);
  }

  public function testPostsByTags()
  {
    $this->markTestIncomplete('testPostsByTags not written yet.');
  }

  public function testPublishPost()
  {
    $this->markTestIncomplete('testPublishPost not written yet.');
  }

  public function testGetArchiveByYear()
  {
    $this->markTestIncomplete('testGetArchiveByYear not written yet.');
  }
}
