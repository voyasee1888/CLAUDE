<?php
/**
 * @var array|null $destination
 * @var string $error
 */
defined('ABSPATH') || exit;

$d = wp_parse_args($destination ?: [], [
    'id' => 0,
    'name' => '',
    'slug' => '',
    'country' => '',
    'country_code' => '',
    'region' => '',
    'lat' => '',
    'lng' => '',
    'content_taxonomy' => 'category',
    'content_term_slug' => '',
    'signature_line' => '',
    'did_you_know' => '',
    'cost_level' => 0,
    'safety_rating' => 0,
    'english_level' => '',
    'walkability' => '',
    'best_for' => '',
    'avg_days' => '',
    'hero_image_id' => '',
    'status' => 'active',
    'sort_order' => 0,
]);
$best_for_selected = '' !== $d['best_for'] ? explode(',', $d['best_for']) : [];
$best_for_options = ['history', 'beach', 'food', 'nature', 'nightlife', 'culture', 'adventure', 'shopping', 'architecture', 'romance', 'wildlife', 'hiking', 'diving', 'skiing', 'wellness', 'photography', 'art', 'music', 'family', 'spiritual'];
$hero_thumb = $d['hero_image_id'] ? wp_get_attachment_image_url((int) $d['hero_image_id'], 'thumbnail') : '';
?>
<div class="wrap v3da-admin-wrap">
    <h1><?php echo $d['id'] ? esc_html__('Edit Destination', 'voyasee-3d-atlas') : esc_html__('Add New Destination', 'voyasee-3d-atlas'); ?></h1>

    <?php if ($error): ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="v3da_save_destination">
        <input type="hidden" name="id" value="<?php echo esc_attr($d['id']); ?>">
        <?php wp_nonce_field('v3da_save_destination'); ?>

        <table class="form-table">
            <tr>
                <th><label for="v3da-name"><?php echo esc_html__('Destination name', 'voyasee-3d-atlas'); ?></label></th>
                <td><input id="v3da-name" name="name" type="text" class="regular-text" required value="<?php echo esc_attr($d['name']); ?>"></td>
            </tr>
            <tr>
                <th><label for="v3da-slug"><?php echo esc_html__('Slug', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-slug" name="slug" type="text" class="regular-text" value="<?php echo esc_attr($d['slug']); ?>" placeholder="<?php echo esc_attr__('auto-generated from name if left blank', 'voyasee-3d-atlas'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="v3da-country"><?php echo esc_html__('Country', 'voyasee-3d-atlas'); ?></label></th>
                <td><input id="v3da-country" name="country" type="text" class="regular-text" required value="<?php echo esc_attr($d['country']); ?>"></td>
            </tr>
            <tr>
                <th><label for="v3da-country-code"><?php echo esc_html__('Country code', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-country-code" name="country_code" type="text" maxlength="2" style="width:5em;text-transform:uppercase;" value="<?php echo esc_attr($d['country_code']); ?>">
                    <p class="description"><?php echo esc_html__('2-letter ISO 3166-1 code (e.g. JP, FR). Used to query Voyasee Country Intelligence and to pick the correct Booking.com market link.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-region"><?php echo esc_html__('Region', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-region" name="region" type="text" class="regular-text" value="<?php echo esc_attr($d['region']); ?>" placeholder="<?php echo esc_attr__('e.g. Europe, Southeast Asia', 'voyasee-3d-atlas'); ?>">
                    <p class="description"><?php echo esc_html__('Groups this destination in the visible A–Z destinations list.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-lat"><?php echo esc_html__('Latitude / Longitude', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-lat" name="lat" type="number" step="0.000001" min="-90" max="90" required value="<?php echo esc_attr($d['lat']); ?>" style="width:10em;">
                    <input id="v3da-lng" name="lng" type="number" step="0.000001" min="-180" max="180" required value="<?php echo esc_attr($d['lng']); ?>" style="width:10em;">
                </td>
            </tr>
            <tr>
                <th><label for="v3da-content-taxonomy"><?php echo esc_html__('Content taxonomy', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <select id="v3da-content-taxonomy" name="content_taxonomy">
                        <option value="category" <?php selected($d['content_taxonomy'], 'category'); ?>><?php echo esc_html__('Category', 'voyasee-3d-atlas'); ?></option>
                        <option value="post_tag" <?php selected($d['content_taxonomy'], 'post_tag'); ?>><?php echo esc_html__('Tag', 'voyasee-3d-atlas'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-content-term-slug"><?php echo esc_html__('Content term slug', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-content-term-slug" name="content_term_slug" type="text" class="regular-text" value="<?php echo esc_attr($d['content_term_slug']); ?>">
                    <p class="description"><?php echo esc_html__('The existing WordPress category or tag slug whose published posts should be linked to this destination.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-signature-line"><?php echo esc_html__('Signature line', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-signature-line" name="signature_line" type="text" class="large-text" maxlength="200" value="<?php echo esc_attr($d['signature_line']); ?>">
                    <p class="description"><?php echo esc_html__('Short editorial tagline shown in the sidebar (8-12 words).', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-did-you-know"><?php echo esc_html__('Did you know?', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <textarea id="v3da-did-you-know" name="did_you_know" class="large-text" rows="3" maxlength="600"><?php echo esc_textarea($d['did_you_know']); ?></textarea>
                    <p class="description"><?php echo esc_html__('One or two verified facts shown in the sidebar.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-cost-level"><?php echo esc_html__('Cost level', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <select id="v3da-cost-level" name="cost_level">
                        <option value="0" <?php selected((int) $d['cost_level'], 0); ?>><?php echo esc_html__('Not set', 'voyasee-3d-atlas'); ?></option>
                        <option value="1" <?php selected((int) $d['cost_level'], 1); ?>>$ &mdash; <?php echo esc_html__('Budget', 'voyasee-3d-atlas'); ?></option>
                        <option value="2" <?php selected((int) $d['cost_level'], 2); ?>>$$ &mdash; <?php echo esc_html__('Moderate', 'voyasee-3d-atlas'); ?></option>
                        <option value="3" <?php selected((int) $d['cost_level'], 3); ?>>$$$ &mdash; <?php echo esc_html__('Expensive', 'voyasee-3d-atlas'); ?></option>
                        <option value="4" <?php selected((int) $d['cost_level'], 4); ?>>$$$$ &mdash; <?php echo esc_html__('Luxury', 'voyasee-3d-atlas'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-safety-rating"><?php echo esc_html__('Safety rating', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <select id="v3da-safety-rating" name="safety_rating">
                        <option value="0" <?php selected((int) $d['safety_rating'], 0); ?>><?php echo esc_html__('Not set', 'voyasee-3d-atlas'); ?></option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo esc_attr($i); ?>" <?php selected((int) $d['safety_rating'], $i); ?>><?php echo esc_html($i); ?>/5</option>
                        <?php endfor; ?>
                    </select>
                    <p class="description"><?php echo esc_html__('1 = exercise extreme caution, 5 = very safe.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-english-level"><?php echo esc_html__('English level', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <select id="v3da-english-level" name="english_level">
                        <option value="" <?php selected($d['english_level'], ''); ?>><?php echo esc_html__('Not set', 'voyasee-3d-atlas'); ?></option>
                        <option value="low" <?php selected($d['english_level'], 'low'); ?>><?php echo esc_html__('Low', 'voyasee-3d-atlas'); ?></option>
                        <option value="medium" <?php selected($d['english_level'], 'medium'); ?>><?php echo esc_html__('Medium', 'voyasee-3d-atlas'); ?></option>
                        <option value="high" <?php selected($d['english_level'], 'high'); ?>><?php echo esc_html__('High', 'voyasee-3d-atlas'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-walkability"><?php echo esc_html__('Walkability', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <select id="v3da-walkability" name="walkability">
                        <option value="" <?php selected($d['walkability'], ''); ?>><?php echo esc_html__('Not set', 'voyasee-3d-atlas'); ?></option>
                        <option value="low" <?php selected($d['walkability'], 'low'); ?>><?php echo esc_html__('Low', 'voyasee-3d-atlas'); ?></option>
                        <option value="medium" <?php selected($d['walkability'], 'medium'); ?>><?php echo esc_html__('Medium', 'voyasee-3d-atlas'); ?></option>
                        <option value="high" <?php selected($d['walkability'], 'high'); ?>><?php echo esc_html__('High', 'voyasee-3d-atlas'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-avg-days"><?php echo esc_html__('Ideal stay', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-avg-days" name="avg_days" type="text" class="regular-text" placeholder="<?php echo esc_attr__('e.g. 3-5 days', 'voyasee-3d-atlas'); ?>" value="<?php echo esc_attr($d['avg_days']); ?>">
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__('Best for', 'voyasee-3d-atlas'); ?></th>
                <td>
                    <?php foreach ($best_for_options as $tag): ?>
                        <label style="display:inline-block;width:130px;margin-bottom:4px;">
                            <input type="checkbox" name="best_for[]" value="<?php echo esc_attr($tag); ?>" <?php checked(in_array($tag, $best_for_selected, true)); ?>>
                            <?php echo esc_html(ucfirst($tag)); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php echo esc_html__('Pick 2-3 tags shown in the sidebar\'s travel snapshot.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-hero-image"><?php echo esc_html__('Hero image', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <input id="v3da-hero-image-id" name="hero_image_id" type="hidden" value="<?php echo esc_attr($d['hero_image_id']); ?>">
                    <img id="v3da-hero-image-preview" src="<?php echo esc_url($hero_thumb); ?>" style="max-width:120px;display:<?php echo $hero_thumb ? 'block' : 'none'; ?>;margin-bottom:8px;">
                    <br>
                    <button type="button" class="button" id="v3da-hero-image-select"><?php echo esc_html__('Select Image', 'voyasee-3d-atlas'); ?></button>
                    <button type="button" class="button" id="v3da-hero-image-clear" style="display:<?php echo $hero_thumb ? 'inline-block' : 'none'; ?>;"><?php echo esc_html__('Remove', 'voyasee-3d-atlas'); ?></button>
                    <p class="description"><?php echo esc_html__('Optional.', 'voyasee-3d-atlas'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-status"><?php echo esc_html__('Status', 'voyasee-3d-atlas'); ?></label></th>
                <td>
                    <select id="v3da-status" name="status">
                        <option value="active" <?php selected($d['status'], 'active'); ?>><?php echo esc_html__('Active', 'voyasee-3d-atlas'); ?></option>
                        <option value="inactive" <?php selected($d['status'], 'inactive'); ?>><?php echo esc_html__('Inactive', 'voyasee-3d-atlas'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="v3da-sort-order"><?php echo esc_html__('Sort order', 'voyasee-3d-atlas'); ?></label></th>
                <td><input id="v3da-sort-order" name="sort_order" type="number" min="0" value="<?php echo esc_attr($d['sort_order']); ?>" style="width:6em;"></td>
            </tr>
        </table>

        <?php submit_button($d['id'] ? __('Update Destination', 'voyasee-3d-atlas') : __('Add Destination', 'voyasee-3d-atlas')); ?>
    </form>

    <p><a href="<?php echo esc_url(admin_url('admin.php?page=voyasee-3d-atlas')); ?>">&larr; <?php echo esc_html__('Back to Destinations', 'voyasee-3d-atlas'); ?></a></p>
</div>
