<?php
/**
 * @var array $destinations
 * @var string $notice
 */
defined('ABSPATH') || exit;
?>
<div class="wrap v3da-admin-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Voyasee 3D Atlas — Destinations', 'voyasee-3d-atlas'); ?></h1>
    <a href="<?php echo esc_url(add_query_arg(['page' => 'voyasee-3d-atlas', 'action' => 'new'], admin_url('admin.php'))); ?>" class="page-title-action">
        <?php echo esc_html__('Add New Destination', 'voyasee-3d-atlas'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ('saved' === $notice): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Destination saved.', 'voyasee-3d-atlas'); ?></p></div>
    <?php elseif ('deleted' === $notice): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Destination deleted.', 'voyasee-3d-atlas'); ?></p></div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Name', 'voyasee-3d-atlas'); ?></th>
                <th><?php echo esc_html__('Country', 'voyasee-3d-atlas'); ?></th>
                <th><?php echo esc_html__('Region', 'voyasee-3d-atlas'); ?></th>
                <th><?php echo esc_html__('Lat / Lng', 'voyasee-3d-atlas'); ?></th>
                <th><?php echo esc_html__('Content term', 'voyasee-3d-atlas'); ?></th>
                <th><?php echo esc_html__('Status', 'voyasee-3d-atlas'); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($destinations)): ?>
                <tr><td colspan="7"><?php echo esc_html__('No destinations yet. Click "Add New Destination" to create the first one.', 'voyasee-3d-atlas'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($destinations as $d): ?>
                <?php
                $edit_url = add_query_arg(['page' => 'voyasee-3d-atlas', 'action' => 'edit', 'id' => $d['id']], admin_url('admin.php'));
                $delete_url = wp_nonce_url(
                    add_query_arg(['action' => 'v3da_delete_destination', 'id' => $d['id']], admin_url('admin-post.php')),
                    'v3da_delete_destination_' . $d['id']
                );
                ?>
                <tr>
                    <td><strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($d['name']); ?></a></strong><br><code><?php echo esc_html($d['slug']); ?></code></td>
                    <td><?php echo esc_html($d['country']); ?> <?php echo $d['country_code'] ? '(' . esc_html($d['country_code']) . ')' : ''; ?></td>
                    <td><?php echo esc_html($d['region']); ?></td>
                    <td><?php echo esc_html($d['lat']); ?>, <?php echo esc_html($d['lng']); ?></td>
                    <td><?php echo esc_html($d['content_taxonomy']); ?>: <?php echo esc_html($d['content_term_slug']); ?></td>
                    <td><?php echo esc_html($d['status']); ?></td>
                    <td>
                        <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html__('Edit', 'voyasee-3d-atlas'); ?></a>
                        |
                        <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this destination?', 'voyasee-3d-atlas')); ?>');" style="color:#b32d2e;"><?php echo esc_html__('Delete', 'voyasee-3d-atlas'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
