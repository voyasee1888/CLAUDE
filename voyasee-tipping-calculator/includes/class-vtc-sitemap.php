<?php
/**
 * Registers all programmatic tipping pages with WordPress core sitemaps so
 * search engines can discover the country, region, service and index guides.
 */

defined('ABSPATH') || exit;

if (!class_exists('WP_Sitemaps_Provider')) {
    return;
}

final class VTC_Sitemap extends WP_Sitemaps_Provider {

    public function __construct() {
        $this->name = 'tipping';
        $this->object_type = 'tipping';
    }

    private function all_urls(): array {
        $urls = [['loc' => home_url('/tipping-guides/')]];
        foreach (VTC_Data::regions() as $region) {
            $urls[] = ['loc' => home_url('/tipping-in-' . VTC_Data::region_slug($region) . '/')];
        }
        foreach (VTC_Data::country_list() as $c) {
            $cslug = VTC_Data::country_slug($c['name']);
            $urls[] = ['loc' => home_url('/tipping-in-' . $cslug . '/')];
            $country = VTC_Data::resolve_country($c['code']);
            if (!empty($country['flags']['not_customary']) || !empty($country['flags']['tipping_offensive'])) {
                continue; // no service pages where nothing is tipped
            }
            foreach (VTC_Data::services() as $skey => $meta) {
                $band = $country['services'][$skey];
                if (($band[1] ?? 0) <= 0 && ($band[2] ?? 0) <= 0) {
                    continue; // skip services with no meaningful tip
                }
                $urls[] = ['loc' => home_url('/tipping-in-' . $cslug . '/' . VTC_Data::service_slug($skey) . '/')];
            }
        }
        return $urls;
    }

    public function get_url_list($page_num, $object_subtype = '') {
        $all = $this->all_urls();
        $max = wp_sitemaps_get_max_urls($this->object_type);
        return array_slice($all, ((int) $page_num - 1) * $max, $max);
    }

    public function get_max_num_pages($object_subtype = '') {
        $count = count($this->all_urls());
        $max = wp_sitemaps_get_max_urls($this->object_type);
        return (int) ceil($count / max(1, $max));
    }
}
