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
    
    echo file_get_contents($filename);

		// Read from it
		$this->archive = new Archive($filename);
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
        "path" => "\/mnt\/c\/apache\/www\/html\/\/blog\/archive\/1514618983_test_post1.md",
        "title" => "title\n",
        "tags" => [
          "#post"
        ],
        "last_modified" => 1514618983,
        "publish_date" => "1514618983"
      ]
    ];
  }

  public function testLoadArchive()
  {
    
  }

  public function testPostsByRange()
  {
    $this->markTestIncomplete('testPostsByRange not written yet.');
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
