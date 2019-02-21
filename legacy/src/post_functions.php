<?php
require_once(__DIR__ . '/../config/config.php');
// require_once(__DIR__ . '/generate_archive.php');
require_once($GLOBALS['parsedown_path']);

/**
 * 
 * Functions for retrieving blog posts in a variety of ways.
 * 
 * TODO: refactor into an object that can persist the archive
 * 
 * @author Devin Gunay <devingunay@gmail.com>
 */

function generate_link_to_post(array $post, string $text = null) {
	$link = '<a href="/blog/post.php?t=' . $post['publish_date'] . '">';
	if ($text === null) {
		$link .= $post['title'];
	}
	else {
		$link .= $text;
	}
	$link .= '</a>';

	return $link;
}


/**
 * Gets all posts modified between two Unix timestamps
 * 
 * @throws Exception If the bounds are unacceptable (i.e. lower bound > upper)
 * @param int $from_time Unix timestamp
 * @param int $to_time Unix timestamp
 * @return array
 */
function get_posts_by_range(int $from_time, int $to_time) {
	if ($from_time > $to_time) {
		throw new Exception('Bounds of get_posts_by_range() invalid.');
	}

	$archive = load_archive();
	
	$posts_in_range = array_filter(
		$archive, 
		function($val) use ($from_time, $to_time) {
			$post_time = filemtime($val);
			return $post_time >= $from_time && $post_time <= $to_time;
		}
	);

	return $posts_in_range;
}

/**
 * Returns posts that contain any of the tags in $tags.
 *
 * @throws Exception If $tags is empty.
 * @param array $tags Tags to search for.
 * @return array Posts with any matching tag, keyed by timestamp.
 */
function get_posts_by_tags(array $tags) {
	if (empty($tags)) {
		throw new Exception('Array of tags must not be empty.');
	}

	// prepend # to tags if not already present.
	array_walk($tags, function(&$val) {
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
 * @return array
 */
function get_post_data(string $path) {
	$fp_in = fopen($path, 'r');

	$tags_line = fgets($fp_in);
	// array_values() guarantees that the result is indexed, not associative.
	$tags = array_values(array_filter(explode(' ', $tags_line), function($val) {
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
	}
	else {
		throw new Exception('Failed to regex publish date from filename ' . $path);
	}

	return array(
		'path'					=> $path,
		'title'					=> $title,
		'tags'					=> $tags,
		'last_modified'	=> filemtime($path),
		'publish_date'	=> $publish_date,
	);
}

/**
 * Loads metadata for all posts into a 1D array sorted by timestamp.
 *
 * @throws Exception if there is an error decoding the archive.
 * @return array
 */
function load_archive() {
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
 * TODO: document
 */
function publish_post(string $path_to_post, int $time) {	
	$destination = $GLOBALS['blog_root'] . '/archive/' . $time . '_' . basename($path_to_post);
	return copy($path_to_post, $destination);
}

/**
 * TODO: document
 *
 * @param array $post
 * @return string
 */
function render_post(array $post) : string {
	$html = '<div class="blog-post">'
		. '<p class="text-muted">'
		. date("m/d/Y - g:i a", $post['publish_date'])
		. '</p>';

	// difference of greather than 1 min
	if (abs($post['last_modified'] - $post['publish_date']) > 59) {
		$html .= '<p class="text-muted">'
		. '<i>Edited: ' . date("m/d/Y - g:i a", $post['last_modified'])
		. '</i></p>';
	}

	// echo post tags with links to search
	$html .= "<p>";
	foreach ($post['tags'] as $tag) {
		$html .= '<a ' 
			. 'class="rounded text-white bg-secondary" '
			. 'href="/blog/search.php?tags[]=' . str_replace('#', '', $tag) . '" '
			. 'style="text-decoration:none;"'
			. '>'
			. $tag
			. '</a> ';
	}
	$html .= '</p>' . PHP_EOL;

	// parse post Markdown to HTML
	$pd = new Parsedown(); 
	$html .= $pd->text(file_get_contents($post['path']));

	// extra styling
	$html = preg_replace(
		'/<blockquote>/', 
		'<blockquote class="blockquote">', 
		$html
	);

	return $html . '</p></div>';
}

/**
 * Collects the paths to all .md files in ./posts into an associative array
 * with posts filed away by year and month. 
 * 
 * @author Devin Gunay <devingunay@gmail.com>
 */
function get_archive_by_year() {  
  $arhive = array();
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
    uksort($months, function($a, $b) {
      $month_a = date_parse($a)['month'];
      $month_b = date_parse($b)['month'];
  
      return $month_a - $month_b;
    });
  }

  return $archive;
}
