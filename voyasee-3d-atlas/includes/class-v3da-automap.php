<?php
defined('ABSPATH') || exit;

/**
 * Automates what used to require manually editing each destination's
 * "content term slug" by hand: looks for a category/tag already named
 * after the destination, or failing that, for published posts clearly
 * titled about that destination that share a common category/tag -- and
 * suggests (or, in bulk mode, applies) that real term. A destination keeps
 * pointing at its own guessed slug (safe search-fallback) whenever nothing
 * meets that bar, rather than being guessed into an unrelated category.
 */
final class V3DA_AutoMap {
    /**
     * A destination only gets auto-mapped to a real term when there's a
     * strong, specific signal that the term is actually about that place --
     * not just "some published post happens to mention this word anywhere
     * in its body text." An earlier version used a plain 's' full-text
     * search and tallied every category/tag on every matching post, which
     * meant a passing mention of "Madrid" inside an unrelated "global street
     * food" roundup could out-vote real Madrid content and get the
     * destination mapped to a completely wrong category. Two much stricter
     * signals are used instead, in order:
     *   1. A category or tag literally named after the destination already
     *      exists -- the single most reliable signal available, since an
     *      admin (or this plugin) named it that on purpose.
     *   2. At least MIN_TITLE_MATCHES published posts have the destination
     *      name in their own title (not just body text), and share a
     *      common category/tag -- titles are a much stronger relevance
     *      signal than a body-text mention, and requiring more than one
     *      such post rules out a single coincidental match.
     * If neither holds, this returns null on purpose rather than guessing,
     * leaving the destination on its safe site-search fallback link.
     */
    private const MIN_TITLE_MATCHES = 2;

    /**
     * @return array{taxonomy:string,slug:string,name:string,matches:int}|null
     */
    public static function suggest_term(string $destination_name): ?array {
        foreach (['category', 'post_tag'] as $taxonomy) {
            $term = get_term_by('name', $destination_name, $taxonomy);
            if ($term && !is_wp_error($term)) {
                return [
                    'taxonomy' => $taxonomy,
                    'slug' => $term->slug,
                    'name' => $term->name,
                    'matches' => (int) $term->count,
                ];
            }
        }

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

        $title_matches = array_filter($query->posts, static function ($post_id) use ($destination_name) {
            return false !== mb_stripos((string) get_the_title($post_id), $destination_name);
        });
        if (count($title_matches) < self::MIN_TITLE_MATCHES) return null;

        $tally = [];
        foreach ($title_matches as $post_id) {
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
        if ($tally[$best_key] < self::MIN_TITLE_MATCHES) return null;

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
     * @param bool $force_recheck When true, also re-checks destinations that
     *   already resolve to a real term. This exists so a site that ran the
     *   old, looser matching logic (which could map a destination to an
     *   unrelated category on a weak text mention) can self-heal: a
     *   previously-mapped destination that no longer meets the stricter bar
     *   gets its mapping cleared back to the safe search-fallback link
     *   instead of silently keeping a wrong category.
     * @return array<int,array{name:string,status:string,detail:string}>
     */
    public static function run_bulk(bool $force_recheck = false): array {
        $report = [];
        foreach (V3DA_DB::get_all() as $destination) {
            $existing_link = V3DA_Content::term_link($destination['content_taxonomy'], $destination['content_term_slug']);
            if ($existing_link && !$force_recheck) {
                $report[] = ['name' => $destination['name'], 'status' => 'already-mapped', 'detail' => $destination['content_term_slug']];
                continue;
            }

            $suggestion = self::suggest_term($destination['name']);
            if (!$suggestion) {
                if ($existing_link) {
                    $cleared_slug = sanitize_title($destination['name']);
                    $result = V3DA_DB::update((int) $destination['id'], array_merge($destination, [
                        'content_taxonomy' => 'category',
                        'content_term_slug' => $cleared_slug,
                    ]));
                    $report[] = [
                        'name' => $destination['name'],
                        'status' => is_wp_error($result) ? 'error' : 'unmapped',
                        'detail' => is_wp_error($result)
                            ? $result->get_error_message()
                            : __('Previous mapping no longer meets the confidence bar and was cleared -- now using the safe search-fallback link.', 'voyasee-3d-atlas'),
                    ];
                } else {
                    $report[] = ['name' => $destination['name'], 'status' => 'no-match', 'detail' => __('No published posts are clearly about this destination yet.', 'voyasee-3d-atlas')];
                }
                continue;
            }

            if ($existing_link && $destination['content_taxonomy'] === $suggestion['taxonomy'] && $destination['content_term_slug'] === $suggestion['slug']) {
                $report[] = ['name' => $destination['name'], 'status' => 'already-mapped', 'detail' => $destination['content_term_slug']];
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
                'status' => $existing_link ? 'remapped' : 'mapped',
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
