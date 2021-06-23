<?php

include_once WMUFS_PLUGIN_PATH . 'includes/class-wmufs-helper.php';

if (isset($_GET['max-size-updated'])) { ?>
    <div class="notice-success notice is-dismissible">
        <p><?php echo esc_html('Maximum Upload File Size Saved Changed!', 'wp-maximum-upload-file-size');?></p>
    </div>
<?php }

$max_size = get_option('max_file_size');
if ( ! $max_size ) {
    $max_size = 64 * 1024 * 1024;
}
$max_size = $max_size / 1024 / 1024;
$upload_sizes = array( 16, 32, 64, 128, 256, 512, 1024 );
$current_max_size = self::get_closest($max_size, $upload_sizes);

?>

<div class="wrap wmufs_mb_50">
    <h1><span class="dashicons dashicons-upload" style="font-size: inherit; line-height: unset;"></span><?php echo esc_html_e( 'Wp Maximum Upload File Size', 'wp-maximum-upload-file-size' ); ?></h1><br>
    <div class="wmufs_admin_deashboard">
        <!-- Row -->
        <div class="wmufs_row">

            <!-- Start Content Area -->
            <div class="wmufs_admin_left wmufs_card wmufs-col-8">
                <form method="post">
                   <?php settings_fields("header_section"); ?>
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row"><label for="upload_max_file_size_field">Choose Maximum Upload File Size</label></th>
                            <td>
                                <select id="upload_max_file_size_field" name="upload_max_file_size_field"> <?php
                                    foreach ( $upload_sizes as $size ) {
                                    echo '<option value="' . $size . '" ' . ($size == $current_max_size ? 'selected' : '') . '>' . ($size == 1024 ? '1GB' : $size . 'MB') . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <?php wp_nonce_field('upload_max_file_size_action', 'upload_max_file_size_nonce'); ?>
                    <?php submit_button(); ?>
                </form>

                <table class="wmufs-system-status">

                    <tr>
                        <th><?php esc_html_e('Title','wp-maximum-upload-file-size');?></th>
                        <th><?php esc_html_e('Status', 'wp-maximum-upload-file-size');?></th>
                        <th><?php esc_html_e('Message', 'wp-maximum-upload-file-size');?></th>
                    </tr>
                    <!-- PHP Version -->
                    <?php
                    foreach ( $system_status as $value ) { ?>
                    <tr>
                        <td><?php printf( '%s', esc_html( $value['title'] ) ); ?></td>

                        <td>
                            <?php if ( 1 == $value['status'] ) { ?>
                                <span class="dashicons dashicons-yes"></span>
                            <?php } else { ?>
                                <span class="dashicons dashicons-warning"></span>

                            <?php }; ?>
                        </td>
                        <td>
                            <?php if ( 1 == $value['status'] ) { ?>
                                <p class="wpifw_status_message">  <?php printf( '%s', esc_html( $value['version'] ) ); ?> <?php echo $value['success_message']; //phpcs:ignore ?></p>
                            <?php } else { ?>
                                <?php printf( '%s', esc_html( $value['version'] ) ); ?>
                                <p class="wpifw_status_message"><?php echo $value['error_message']; //phpcs:ignore ?></p>

                            <?php }; ?>

                        </td>
                    </tr>
                    <?php } ?>
                </table>


            </div>
            <!-- End Content Area -->

            <!-- Start Sidebar Area -->
            <div class="wmufs_admin_right_sidebar wmufs_card wmufs-col-4">
                <?php include_once WMUFS_PLUGIN_PATH . 'includes/class-wmufs-sidebar.php'; ?>
            </div>
            <!-- End Sidebar area-->

        </div> <!-- End Row--->
    </div>
</div> <!-- End Wrapper -->

