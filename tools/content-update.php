<?php
/**
 * Two Stars content updater. Applies the site content snapshot (pages, tours, FAQs, SEO, image links)
 * to the WordPress site it runs in. Matches everything by slug/title/filename, never by database ID,
 * so it works on staging and production. Safe to run more than once.
 *
 * It does NOT touch leads, CRM tables, visitors, chat history, bookings, users or plugin settings.
 *
 * Usage (from the WordPress root, over SSH):
 *   php wp-content/tools/content-update.php --dry-run     # show what would change
 *   php wp-content/tools/content-update.php               # apply
 *
 * Before running: take a full backup (UpdraftPlus), and copy wp-content/uploads/2026/10/ to the server.
 */

if (PHP_SAPI !== 'cli') {
	exit("Run this from the command line.\n");
}

$dry = in_array('--dry-run', $argv, true);

if (! defined('ABSPATH')) {
	$root = dirname(__DIR__, 2);

	if (! is_file($root . '/wp-load.php')) {
		exit("wp-load.php not found at {$root}. Run from the WordPress root.\n");
	}

	$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? (getenv('TS_HOST') ?: 'localhost');
	define('WP_USE_THEMES', false);
	require $root . '/wp-load.php';
}

// The snapshot is our own trusted content. Without a logged-in admin, WordPress would HTML-encode
// characters such as & in titles ("&amp;"), so switch the content filters off for this run.
if (function_exists('kses_remove_filters')) {
	kses_remove_filters();
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

$snapshot_file = __DIR__ . '/content-snapshot.json';
$snap          = json_decode((string) file_get_contents($snapshot_file), true);

if (! is_array($snap)) {
	exit("Snapshot missing or invalid: {$snapshot_file}\n");
}

$report = ['updated' => 0, 'created' => 0, 'skipped' => 0, 'warnings' => []];
$say    = static function (string $m): void { echo $m . "\n"; };
$home   = untrailingslashit(home_url());
$say(($dry ? '[DRY RUN] ' : '') . 'Target: ' . $home);

/* ---------- attachments ---------- */

$uploads = wp_get_upload_dir();
$att_map = []; // basename => id

$load_att_map = static function () use (&$att_map): void {
	global $wpdb;
	$rows = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'", ARRAY_A);

	foreach ($rows as $row) {
		$att_map[strtolower(basename((string) $row['meta_value']))] = (int) $row['post_id'];
	}
};
$load_att_map();

foreach ((array) ($snap['attachments'] ?? []) as $a) {
	$base = strtolower((string) $a['file']);
	$id   = $att_map[$base] ?? 0;

	if ($id === 0) {
		$path = $uploads['basedir'] . '/' . ltrim((string) $a['path'], '/');

		if (! is_file($path)) {
			$report['warnings'][] = "Missing file for new attachment: {$a['path']} (copy uploads/2026/10 first)";
			continue;
		}

		if ($dry) {
			$say("  + would register media: {$a['file']}");
			$report['created']++;
			continue;
		}

		$type = wp_check_filetype($path);
		$id   = wp_insert_attachment(
			[
				'post_mime_type' => $type['type'] ?: 'image/webp',
				'post_title'     => pathinfo($a['file'], PATHINFO_FILENAME),
				'post_status'    => 'inherit',
				'guid'           => $uploads['baseurl'] . '/' . ltrim((string) $a['path'], '/'),
			],
			$path
		);

		if (is_wp_error($id) || ! $id) {
			$report['warnings'][] = "Could not register {$a['file']}";
			continue;
		}

		wp_update_attachment_metadata((int) $id, wp_generate_attachment_metadata((int) $id, $path));
		$att_map[$base] = (int) $id;
		$report['created']++;
		$say("  + registered media: {$a['file']} (#{$id})");
	}

	if ($id > 0 && ! empty($a['alt']) && ! $dry) {
		update_post_meta($id, '_wp_attachment_image_alt', (string) $a['alt']);
	}
}

foreach ((array) ($snap['alts'] ?? []) as $file => $alt) {
	$id = $att_map[strtolower((string) $file)] ?? 0;

	if ($id > 0 && ! $dry) {
		update_post_meta($id, '_wp_attachment_image_alt', (string) $alt);
	}
}

/* ---------- token resolution ---------- */

$resolve_att = static function ($value) use (&$att_map, &$report) {
	if (is_string($value) && str_starts_with($value, '@att:')) {
		$id = $att_map[strtolower(substr($value, 5))] ?? 0;

		if ($id === 0) {
			$report['warnings'][] = 'No media found for ' . substr($value, 5);
		}

		return $id;
	}

	return $value;
};

$resolve = static function ($data) use (&$resolve, $resolve_att, $home) {
	if (is_array($data)) {
		foreach ($data as $k => $v) {
			$data[$k] = $resolve($v);
		}

		return $data;
	}

	if (is_string($data)) {
		if (str_starts_with($data, '@att:')) {
			return $resolve_att($data);
		}

		return str_replace('{{home}}', $home, $data);
	}

	return $data;
};

/* ---------- posts ---------- */

$find = static function (string $type, array $slugs): ?WP_Post {
	foreach ($slugs as $slug) {
		$post = get_page_by_path($slug, OBJECT, $type);

		if ($post instanceof WP_Post) {
			return $post;
		}
	}

	return null;
};

$apply_meta = static function (int $id, array $meta) use ($resolve, $resolve_att, $dry): void {
	foreach ($meta as $key => $value) {
		if ($key === '_thumbnail_id') {
			$thumb = $resolve_att($value);
			if ($thumb) { $dry || set_post_thumbnail($id, (int) $thumb); }
			continue;
		}

		if (is_array($value) && ($value['_t'] ?? '') === 'att_list') {
			$ids = array_values(array_filter(array_map(static fn($f) => $resolve_att('@att:' . $f), (array) $value['files'])));
			$dry || update_post_meta($id, $key, $ids);
			continue;
		}

		if ($value === null || $value === '') {
			$dry || delete_post_meta($id, $key);
			continue;
		}

		$dry || update_post_meta($id, $key, $resolve($value));
	}
};

foreach ((array) ($snap['posts'] ?? []) as $item) {
	$type  = (string) $item['type'];
	$post  = $find($type, (array) $item['slugs']);
	$label = $type . ':' . $item['slugs'][0];

	if (! $post && ! empty($item['create'])) {
		if ($dry) {
			$say("  + would create {$label}");
			$report['created']++;
			continue;
		}

		$new = wp_insert_post(
			wp_slash(
				[
					'post_type'    => $type,
					'post_status'  => (string) ($item['status'] ?? 'publish'),
					'post_title'   => (string) $item['title'],
					'post_name'    => (string) $item['slugs'][0],
					'post_content' => (string) ($item['content'] ?? ''),
					'menu_order'   => (int) ($item['menu_order'] ?? 0),
				]
			),
			true
		);

		if (is_wp_error($new)) {
			$report['warnings'][] = "Could not create {$label}";
			continue;
		}

		$post = get_post((int) $new);
		$report['created']++;
		$say("  + created {$label}");
	}

	if (! $post) {
		$report['warnings'][] = "Not found, skipped: {$label}";
		$report['skipped']++;
		continue;
	}

	$id = (int) $post->ID;

	if (! empty($item['fields']) && ! $dry) {
		$fields = ['ID' => $id];

		foreach (['title' => 'post_title', 'slug' => 'post_name', 'content' => 'post_content', 'excerpt' => 'post_excerpt'] as $k => $col) {
			if (array_key_exists($k, (array) $item['fields'])) {
				$fields[$col] = (string) $item['fields'][$k];
			}
		}

		// Keep the old URL working as a redirect when a slug changes.
		if (isset($fields['post_name']) && $fields['post_name'] !== $post->post_name) {
			add_post_meta($id, '_wp_old_slug', $post->post_name);
		}

		wp_update_post(wp_slash($fields));
	}

	if (! empty($item['elementor'])) {
		$data = $resolve($item['elementor']);

		if (! $dry) {
			update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
			delete_post_meta($id, '_elementor_element_cache');
			delete_post_meta($id, '_elementor_css');
		}
	}

	$apply_meta($id, (array) ($item['meta'] ?? []));
	$report['updated']++;
	$say("  ~ updated {$label} (#{$id})");
}

/* ---------- options ---------- */

foreach ((array) ($snap['options'] ?? []) as $name => $value) {
	if (! $dry) {
		update_option((string) $name, $value);
	}

	$say("  = option {$name} = " . (is_scalar($value) ? $value : wp_json_encode($value)));
}

foreach ((array) ($snap['option_att_lists'] ?? []) as $name => $files) {
	$ids = array_values(array_filter(array_map(static fn($f) => $resolve_att('@att:' . $f), (array) $files)));

	if (! $dry && $ids !== []) {
		update_option((string) $name, $ids);
	}

	$say("  = option {$name} = " . count($ids) . ' images');
}

if (! $dry) {
	flush_rewrite_rules();

	if (class_exists('\\Elementor\\Plugin')) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}

$say("\nDone. updated={$report['updated']} created={$report['created']} skipped={$report['skipped']}");

foreach ($report['warnings'] as $w) {
	$say('  WARNING: ' . $w);
}

if (! $dry) {
	$say("Next: Elementor > Tools > Regenerate CSS, clear any cache plugin, check the pages.");
}
