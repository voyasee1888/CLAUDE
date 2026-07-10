<?php
/**
 * JSON-LD structured data. On a per-country page it outputs country-specific
 * SoftwareApplication + Breadcrumb + FAQ + HowTo. On any normal page/post that
 * contains the [voyasee_tipping_calculator] shortcode it outputs the generic
 * tool schema. All content is derived from the plugin's own data.
 */

defined('ABSPATH') || exit;

final class VTC_Schema {

    public static function init(): void {
        add_action('wp_head', [self::class, 'output'], 20);
    }

    public static function output(): void {
        $t = VTC_Country_Pages::current();
        if ($t) {
            if ('service' === $t['type']) {
                self::service_schema($t['code'], $t['service']);
                return;
            }
            if ('country' === $t['type']) {
                self::country_schema($t['code']);
                return;
            }
            if ('region' === $t['type']) {
                self::place_breadcrumb(sprintf('Tipping in %s', $t['region']), home_url('/tipping-in-' . VTC_Data::region_slug($t['region']) . '/'));
                return;
            }
            if ('index' === $t['type']) {
                self::place_breadcrumb('Tipping Guides', home_url('/tipping-guides/'));
                return;
            }
        }
        if (is_singular()) {
            $post = get_post();
            if ($post && has_shortcode((string) $post->post_content, 'voyasee_tipping_calculator')) {
                self::tool_schema(get_permalink($post));
            }
        }
    }

    private static function service_schema(string $code, string $service): void {
        $c = VTC_Data::resolve_country($code);
        $slug = VTC_Data::country_slug($c['name']);
        $sslug = VTC_Data::service_slug($service);
        $phrase = VTC_Data::service_phrase($service);
        $url = home_url('/tipping-in-' . $slug . '/' . $sslug . '/');
        $overview = VTC_Calculator::country_overview($code);
        $display = '';
        foreach ($overview['rows'] as $row) {
            if ($row['key'] === $service) $display = $row['display'];
        }
        $name = sprintf('How Much to Tip %s in %s', $phrase, $c['name']);
        $desc = ('Not expected' === $display)
            ? sprintf('Tipping %s is not generally expected in %s. %s', $phrase, $c['name'], self::strip($c['note']))
            : sprintf('For %s in %s, the usual guidance is %s. Use the free Voyasee calculator for the exact amount.', $phrase, $c['name'], $display);

        self::emit(self::software($url, $name, $desc));
        self::emit([
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tipping Calculator', 'item' => home_url('/tipping-calculator/')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'Tipping in ' . $c['name'], 'item' => home_url('/tipping-in-' . $slug . '/')],
                ['@type' => 'ListItem', 'position' => 4, 'name' => ucfirst($phrase), 'item' => $url],
            ],
        ]);
        $answer = ('Not expected' === $display)
            ? sprintf('Tipping %s is not generally expected in %s.', $phrase, $c['name'])
            : sprintf('For %s in %s, the usual guidance is %s. Enter your exact amount in the calculator for the precise tip.', $phrase, $c['name'], $display);
        self::emit(self::faq([
            [sprintf('How much should I tip %s in %s?', $phrase, $c['name']), $answer],
            [sprintf('Is the %s tipping guide free?', $c['name']), 'Yes — the calculator and this guide are completely free, with no account required.'],
        ]));
    }

    private static function place_breadcrumb(string $name, string $url): void {
        self::emit([
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tipping Calculator', 'item' => home_url('/tipping-calculator/')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $url],
            ],
        ]);
    }

    private static function emit(array $data): void {
        echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    private static function software(string $url, string $name, string $desc): array {
        return [
            '@context'            => 'https://schema.org',
            '@type'               => 'SoftwareApplication',
            'name'                => $name,
            'applicationCategory' => 'TravelApplication',
            'applicationSubCategory' => 'Tipping Calculator',
            'operatingSystem'     => 'Web',
            'url'                 => $url,
            'description'         => $desc,
            'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock'],
            'publisher'           => ['@type' => 'Organization', 'name' => 'Voyasee', 'url' => 'https://voyasee.com/'],
            'featureList'         => [
                'Culture-aware tipping advice for 200+ countries and territories',
                'Tells you when a tip is not expected, or when a service charge is already included',
                'Restaurant, cafe, bar, taxi, delivery, tour guide, spa, hotel housekeeping and porter',
                'Adjusts for service quality and splits the tip between any number of people',
                'Infographic breakdown of bill, tip and total, with a local tipping-culture gauge',
                'Optional approximate conversion into your home currency',
                'Shareable result links and one-tap print / PDF',
            ],
        ];
    }

    private static function tool_schema(string $url): void {
        self::emit(self::software($url, 'Voyasee Tipping Calculator', 'A free, worldwide tipping calculator covering 200+ countries. See how much to tip for restaurants, taxis, hotels, tours and more — with culture-aware advice, an infographic breakdown, bill splitting, and print or share options. No account needed.'));
        self::emit(self::howto($url));
        self::emit(self::faq_generic());
    }

    private static function country_schema(string $code): void {
        $c = VTC_Data::resolve_country($code);
        $slug = VTC_Data::country_slug($c['name']);
        $url = home_url('/tipping-in-' . $slug . '/');
        $name = sprintf('How Much to Tip in %s — Voyasee Tipping Calculator', $c['name']);
        $desc = sprintf('How much to tip in %s: %s Use the free Voyasee calculator for an exact tip on any bill.', $c['name'], self::strip($c['note']));

        self::emit(self::software($url, $name, $desc));
        self::emit([
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tipping Calculator', 'item' => home_url('/tipping-calculator/')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'Tipping in ' . $c['name'], 'item' => $url],
            ],
        ]);
        self::emit(self::howto($url));
        self::emit(self::faq_country($c));
    }

    private static function howto(string $url): array {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'HowTo',
            'name'        => 'How to work out the right tip anywhere',
            'description' => 'Get a culturally-correct tip for any country in three steps.',
            'totalTime'   => 'PT30S',
            'step'        => [
                ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Pick the country', 'text' => 'Choose the country you are in from 200+ options.', 'url' => $url . '#step-1'],
                ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Choose the service and enter the bill', 'text' => 'Select restaurant, taxi, hotel or another service and type the bill amount.', 'url' => $url . '#step-2'],
                ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Read your tip and total', 'text' => 'See the suggested tip, the total, the per-person split and a note on the local tipping culture.', 'url' => $url . '#step-3'],
            ],
        ];
    }

    private static function faq_generic(): array {
        return self::faq([
            ['Is the Tipping Calculator free to use?', 'Yes. There is no account, no login and no paywall. Choosing a country and calculating a tip is completely free.'],
            ['How many countries does it cover?', 'It covers more than 200 countries and territories, each with its own tipping culture, currency and per-service guidance.'],
            ['Does it know when I should not tip?', 'Yes. In places where tipping is not customary or can even cause offence, the calculator says so clearly instead of pushing a number, and it warns you when a service charge is usually already included.'],
            ['Where does the tipping data come from?', 'It is Voyasee\'s own curated tipping intelligence, compiled from widely-documented tipping customs and normalised into one consistent model. No third-party API is used.'],
            ['Can I split the tip between friends?', 'Yes. Enter how many people are sharing the bill and the calculator shows the tip and total per person.'],
            ['Can I save or share the result?', 'Yes. Every result has a shareable link that reopens the same calculation, plus a one-tap print or save-to-PDF option.'],
        ]);
    }

    private static function faq_country(array $c): array {
        $name = $c['name'];
        $verdict = self::strip($c['note']);
        $rest = VTC_Calculator::country_overview($c['code']);
        $restaurant_display = '';
        foreach ($rest['rows'] as $row) {
            if ('restaurant' === $row['key']) {
                $restaurant_display = $row['display'];
            }
        }
        $q = [];
        $q[] = ['How much should I tip in ' . $name . '?', $verdict];
        if ($restaurant_display && 'Not expected' !== $restaurant_display) {
            $q[] = ['How much do you tip at a restaurant in ' . $name . '?', sprintf('At a sit-down restaurant in %s the usual guidance is %s. Enter your exact bill in the calculator for the precise amount.', $name, $restaurant_display)];
        }
        $q[] = ['Do you tip taxi drivers in ' . $name . '?', self::taxi_line($rest, $name)];
        $q[] = ['Is tipping expected in ' . $name . '?', $c['verdict'] . '. ' . $verdict];
        $q[] = ['Is the Voyasee tipping guide for ' . $name . ' free?', 'Yes — the calculator and this guide are completely free, with no account required.'];
        return self::faq($q);
    }

    private static function taxi_line(array $overview, string $name): string {
        foreach ($overview['rows'] as $row) {
            if ('taxi' === $row['key']) {
                if ('Not expected' === $row['display']) {
                    return sprintf('Tipping taxi drivers is not generally expected in %s — rounding up the fare is a friendly gesture but not required.', $name);
                }
                return sprintf('For taxis in %s the usual guidance is %s; rounding up the fare is common.', $name, $row['display']);
            }
        }
        return sprintf('Rounding up the fare is a common, friendly gesture in %s.', $name);
    }

    private static function faq(array $pairs): array {
        $entities = [];
        foreach ($pairs as [$q, $a]) {
            $entities[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]];
        }
        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities];
    }

    private static function strip(string $s): string {
        return trim(wp_strip_all_tags($s));
    }
}
