<?php
defined('ABSPATH') || exit;

/**
 * Automates what used to require manually editing each destination's
 * "content term slug" by hand: searches existing published posts for
 * mentions of the destination name, tallies which categories/tags those
 * matching posts actually belong to, and suggests (or, in bulk mode,
 * applies) the best-scoring real term -- so a destination only keeps
 * pointing at its own guessed slug when nothing better can be found.
 */
final class V3DA_AutoMap {
    /**
     * @return array{taxonomy:string,slug:string,name:string,matches:int}|null
     */
    public static function suggest_term(string $destination_name): ?array {
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $destination_name,
            'posts_per_page' => 30,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'fields' => 'ids',
        ]);
        if (empty($query->posts)) return null;

        $tally = [];
        foreach ($query->posts as $post_id) {
            foreach (['category', 'post_tag'] as $taxonomy) {
                $terms = get_the_terms($post_id, $taxonomy);
                if (!$terms || is_wp_error($terms)) continue;
                foreach ($terms as $term) {
                    $key = $taxonomy . '|' . $term->term_id;
                    $tally[$key] = ($tally[$key] ?? 0) + 1;
                }
            }
        }
        if (empty($tally)) return null;

        arsort($tally);
        $best_key = (string) array_key_first($tally);
        [$taxonomy, $term_id] = explode('|', $best_key);
        $term = get_term((int) $term_id, $taxonomy);
        if (!$term || is_wp_error($term)) return null;

        return [
            'taxonomy' => $taxonomy,
            'slug' => $term->slug,
            'name' => $term->name,
            'matches' => $tally[$best_key],
        ];
    }

    /**
     * Runs suggest_term() for every destination that doesn't already
     * resolve to a real term, and applies the best match directly. Returns
     * a per-destination report for display, not a boolean -- an admin
     * should be able to see exactly what changed.
     *
     * @return array<int,array{name:string,status:string,detail:string}>
     */
    public static function run_bulk(): array {
        $report = [];
        foreach (V3DA_DB::get_all() as $destination) {
            $existing_link = V3DA_Content::term_link($destination['content_taxonomy'], $destination['content_term_slug']);
            if ($existing_link) {
                $report[] = ['name' => $destination['name'], 'status' => 'already-mapped', 'detail' => $destination['content_term_slug']];
                continue;
            }

            $suggestion = self::suggest_term($destination['name']);
            if (!$suggestion) {
                $report[] = ['name' => $destination['name'], 'status' => 'no-match', 'detail' => __('No published posts mention this destination yet.', 'voyasee-3d-atlas')];
                continue;
            }

            $result = V3DA_DB::update((int) $destination['id'], array_merge($destination, [
                'content_taxonomy' => $suggestion['taxonomy'],
                'content_term_slug' => $suggestion['slug'],
            ]));

            if (is_wp_error($result)) {
                $report[] = ['name' => $destination['name'], 'status' => 'error', 'detail' => $result->get_error_message()];
                continue;
            }

            $report[] = [
                'name' => $destination['name'],
                'status' => 'mapped',
                'detail' => sprintf(
                    /* translators: 1: taxonomy term name, 2: number of matching posts */
                    __('Mapped to "%1$s" (%2$d matching post(s)).', 'voyasee-3d-atlas'),
                    $suggestion['name'],
                    $suggestion['matches']
                ),
            ];
        }
        return $report;
    }

    /**
     * Creates a real WordPress category named after the destination (if
     * one doesn't already exist) and points the destination at it -- for
     * the case where suggest_term() found nothing because no post has
     * been written about that place yet.
     *
     * @return true|WP_Error
     */
    public static function create_category_for(int $destination_id) {
        $destination = V3DA_DB::get($destination_id);
        if (!$destination) {
            return new WP_Error('v3da_not_found', __('Destination not found.', 'voyasee-3d-atlas'));
        }

        $existing = get_term_by('name', $destination['name'], 'category');
        if ($existing && !is_wp_error($existing)) {
            $slug = $existing->slug;
        } else {
            $created = wp_insert_term($destination['name'], 'category');
            if (is_wp_error($created)) return $created;
            $term = get_term((int) $created['term_id'], 'category');
            if (!$term || is_wp_error($term)) {
                return new WP_Error('v3da_term_lookup_failed', __('Category was created but could not be read back.', 'voyasee-3d-atlas'));
            }
            $slug = $term->slug;
        }

        return V3DA_DB::update($destination_id, array_merge($destination, [
            'content_taxonomy' => 'category',
            'content_term_slug' => $slug,
        ]));
    }
}
