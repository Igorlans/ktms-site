<?php


namespace WPDM\admin\menus;


use WPDM\Package;

class Packages
{

    function __construct()
    {

        add_action('wp_ajax_wpdm_admin_upload_file', array($this, 'uploadFile'));
        add_action('save_post', array($this, 'savePackage'));

        add_action('manage_posts_columns', array($this, 'columnsTH'));
        add_action('manage_posts_custom_column', array($this, 'columnsTD'), 10, 2);

        add_filter( 'request', array($this, 'orderbyDownloads') );
        add_filter( 'manage_edit-wpdmpro_sortable_columns', array($this, 'sortableDownloads') );

        add_filter('post_row_actions', array($this, 'rowActions'), 10, 2);

        add_action( 'quick_edit_custom_box', array($this, 'quickEditForm'), 10, 2 );

        add_action('admin_footer', array($this, 'footerScripts'));

        add_action("admin_init", [$this, 'duplicate']);


    }

    function savePackage($post)
    {
        if(!current_user_can('edit_post', $post)) return;
        if (get_post_type() != 'wpdmpro' || !isset($_POST['file'])) return;

        // Deleted old zipped file
        $zipped = get_post_meta($post, "__wpdm_zipped_file", true);
        if($zipped!='' && file_exists($zipped)) { @unlink($zipped); }

        $cdata = get_post_custom($post);
        foreach ($cdata as $k => $v) {
            $tk = str_replace("__wpdm_", "", $k);
            if (!isset($_POST['file'][$tk]) && $tk !== $k && $tk != "masterkey") {
                delete_post_meta($post, $k);
            }

        }

        foreach ($_POST['file'] as $meta_key => $meta_value) {
            $key_name = "__wpdm_" . $meta_key;
            if($meta_key == 'package_dir' && $meta_value != '') { $meta_value = realpath($meta_value); }
            if($meta_key == 'package_size' && (double)$meta_value == 0) $meta_value = "";
            if($meta_key == 'files') $meta_value = array_unique($meta_value);
            if($meta_key == 'files'){
                foreach ($meta_value as &$value){
                    $value = wpdm_escs($value);
                }
            } else
                $meta_value = is_array($meta_value)?wpdm_sanitize_array($meta_value):wpdm_escs($meta_value);
            update_post_meta($post, $key_name, $meta_value);
        }

        if(get_post_meta($post, '__wpdm_masterkey', true) == '')
            update_post_meta($post, '__wpdm_masterkey', uniqid());

        if (isset($_POST['reset_key']) && $_POST['reset_key'] == 1)
            update_post_meta($post, '__wpdm_masterkey', uniqid());

        if(isset($_REQUEST['reset_udl'])) WPDM()->downloadHistory->resetUserDownloadCount($post, 'all');
        //do_action('after_update_package',$post, $_POST['file']);


    }


    function uploadFile(){
        check_ajax_referer(NONCE_KEY);
        if(!current_user_can('upload_files')) die('-2');

        $name = isset($_FILES['package_file']['name']) && !isset($_REQUEST["chunks"])?$_FILES['package_file']['name']:$_REQUEST['name'];
        $name = esc_attr($name);
        $ext = explode('.', $name);
        $ext = end($ext);
        $ext = strtolower($ext);
        if(!in_array($ext, wpdm_get_allowed_file_types())) die('-3');

        if(file_exists(UPLOAD_DIR.$name) && get_option('__wpdm_overwrrite_file',0) == 1){
            @unlink(UPLOAD_DIR.$name);
        }
        if(file_exists(UPLOAD_DIR.$name) && !isset($_REQUEST["chunks"]))
            $filename = time().'wpdm_'.$name;
        else
            $filename = $name;

        do_action("wpdm_before_upload_file", $_FILES['package_file']);

        if(get_option('__wpdm_sanitize_filename', 0) == 1)
            $filename = sanitize_file_name($filename);
        else {
            $filename = str_replace(["/", "\\"], "_", $filename);
        }

        if(isset($_REQUEST["chunks"])) $this->chunkUploadFile(UPLOAD_DIR.$filename);
        else {
            move_uploaded_file($_FILES['package_file']['tmp_name'], UPLOAD_DIR . $filename);
            do_action("wpdm_after_upload_file", UPLOAD_DIR . $filename);
        }
        //@unlink($status['file']);
        echo "|||".$filename."|||".wpdm_file_size(UPLOAD_DIR.$filename)."|||";
        exit;
    }

    function chunkUploadFile($destFilePath){

        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

        $out = @fopen("{$destFilePath}.part", $chunk == 0 ? "wb" : "ab");
        if ($out) {
            // Read binary input stream and append it to temp file
            $in = @fopen($_FILES['package_file']['tmp_name'], "rb");

            if ($in) {
                while ($buff = fread($in, 4096))
                    fwrite($out, $buff);
            } else
                die('-3');

            @fclose($in);
            @fclose($out);

            @unlink($_FILES['package_file']['tmp_name']);
        } else
            die('-3');

        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$destFilePath}.part", $destFilePath);
            do_action("wpdm_after_upload_file", $destFilePath);
        }
    }


    function columnsTH($defaults) {
        if(get_post_type()!='wpdmpro') return $defaults;
        $img['image'] = "<span class='wpdm-th-icon ttip' style='font-size: 0.8em'><i  style='font-size: 80%' class='fa fa-image'></i></span>";
        wpdm_array_splice_assoc( $defaults, 1, 0, $img );
        $otf['download_count'] = "<span class='wpdm-th-icon ttip' style='font-size: 0.8em'><i  style='font-size: 80%' class='fa fa-download'></i></span>";
        $otf['wpdmembed'] = esc_attr__( 'Shortcode', 'download-manager' );
        wpdm_array_splice_assoc( $defaults, 3, 0, $otf );
        return $defaults;
    }


    function columnsTD($column_name, $post_ID) {
        if(get_post_type()!='wpdmpro') return;
        if ($column_name == 'download_count') {

            echo (int)get_post_meta($post_ID, '__wpdm_download_count', true);

        }
        if ($column_name == 'wpdmembed') {

            echo "<div class='w3eden'><div class='input-group short-code-wpdm'><input readonly=readonly class='form-control bg-white' onclick='this.select();' value=\"[wpdm_package id='$post_ID']\" id='sci{$post_ID}' /><div class='input-group-btn'><button type='button' onclick=\"WPDM.copy('sci{$post_ID}')\" class='btn btn-secondary'><i class='fa fa-copy'></i></button></div></div></div>";
            //echo "<div class='w3eden'><button type='button' href='#' data-toggle='modal' data-target='#embModal' data-pid='{$post_ID}' class='btn btn-secondary btn-embed'><i class='fa fa-bars'></i></button></div>";

        }
        if ($column_name == 'image') {
            if(has_post_thumbnail($post_ID))
                echo get_the_post_thumbnail( $post_ID, 'thumbnail', array('class'=>'img60px') );
            else {
                $icon = get_post_meta($post_ID,'__wpdm_icon', true);
                if($icon!=''){
                    $icon = $icon;
                    echo "<img src='$icon' class='img60px' alt='Icon' />";
                }
            }
        }
    }


    function orderbyDownloads( $vars ) {

        if ( isset( $vars['orderby'] ) && 'download_count' == $vars['orderby'] ) {
            $vars = array_merge( $vars, array(
                'meta_key' => '__wpdm_download_count',
                'orderby' => 'meta_value_num'
            ) );
        }

        return $vars;
    }

    function sortableDownloads( $columns ) {

        if(get_post_type()!='wpdmpro') return $columns;

        $columns['download_count'] = 'download_count';

        return $columns;
    }


    function rowActions($actions, $post)
    {
        if($post->post_type == 'wpdmpro') {
            $actions['duplicate'] = '<a title="' . __( "Duplicate" , "download-manager" ) . '" href="' . admin_url("/?wpdm_duplicate={$post->ID}&__copynonce=".wp_create_nonce(NONCE_KEY)) . '" class="wpdm_duplicate w3eden">'.esc_attr__( 'Duplicate', 'download-manager' ).'</a>';
            $actions['download_link'] = '<a title="' . __('Direct Download', 'download-manager') . '" href="' . \WPDM\Package::getMasterDownloadURL($post->ID) . '" class="view_stats"><i class="fa fa-download text-success"></i></a>';
        }

        return $actions;
    }

    function quickEditForm($column_name, $post_type){


    }


    function footerScripts(){
        global $pagenow;
        ?>
        <div class="w3eden">
                <div class="modal fade" tabindex="-1" role="dialog" id="embModal" style="display: none">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h4 class="modal-title"><i class="fa fa-paste color-green"></i> <?php _e("Embed Package", "download-manager"); ?></h4>
                            </div>
                            <div class="modal-body">

                                <div class="input-group input-group-lg">
                                    <input type="text" value="[wpdm_package id='{{ID}}']" id="cpsc" readonly="readonly" class="form-control bg-white" style="font-family: monospace;font-weight: bold;text-align: center">
                                    <div class="input-group-btn">
                                        <button style="padding-left: 30px;padding-right: 30px" onclick="WPDM.copy('cpsc');" type="button" class="btn btn-secondary"><i class="fa fa-copy"></i> <?=esc_attr__( 'Copy', 'download-manager' );?></button>
                                    </div>
                                </div>
                                <div class="alert alert-info" style="margin-top: 20px">
                                    <?=esc_attr__( 'If you are on Gutenberg Editor or elementor, you may use gutenberg block or elementor add-on for wpdm to embed wpdm packages and categories or generate another available layouts', 'download-manager' ); ?>
                                </div>

                                <div class="panel panel-default card-plain">
                                    <div class="panel-heading">
                                        <?=esc_attr__( 'Go To Page', 'download-manager' );?>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-9"><?php wp_dropdown_pages(['class' => 'form-control wpdm-custom-select', 'id' => 'gotopg']); ?></div>
                                            <div class="col-md-3"><button onclick="location.href='post.php?action=edit&post='+jQuery('#gotopg').val()" type="button" class="btn btn-secondary btn-block"><?=esc_attr__( 'Go', 'download-manager' ); ?></button></div>
                                        </div>

                                    </div>
                                    <div class="panel-footer bg-white">
                                        <a href="post-new.php?post_type=page"><?=esc_attr__( 'Create new page', 'download-manager' );?></a>
                                    </div>
                                </div>


                                    <?php if(!defined('__WPDM_GB__')) { ?>
                                        <a class="btn btn-block btn-secondary thickbox open-plugin-details-modal" href="<?=admin_url('/plugin-install.php?tab=plugin-information&plugin=wpdm-gutenberg-blocks&TB_iframe=true&width=600&height=550')?>"><?=esc_attr__( 'Install Gutenberg Blocks by WordPress Download Manager', 'download-manager' );?></a>
                                    <?php } ?>
                                    <?php if(!defined('__WPDM_ELEMENTOR__')) { ?>
                                        <a class="btn btn-block btn-secondary thickbox open-plugin-details-modal" style="margin-top: 10px" href="<?=admin_url('/plugin-install.php?tab=plugin-information&plugin=wpdm-elementor&TB_iframe=true&width=600&height=550')?>"><?=esc_attr__( 'Install Download Manager Addons for Elementor', 'download-manager' );?></a>
                                    <?php } ?>
                                <?php if(!function_exists('LiveForms')) { ?>
                                    <a class="btn btn-block btn-info thickbox open-plugin-details-modal" style="margin-top: 10px" href="<?=admin_url('/plugin-install.php?tab=plugin-information&plugin=liveforms&TB_iframe=true&width=600&height=550')?>"><?=esc_attr__( 'Install The Best WordPress Contact Form Builder', 'download-manager' );?></a>
                                <?php } ?>




                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php _e("Close", "download-manager"); ?></button>
                            </div>
                        </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                </div><!-- /.modal -->
        </div>
        <script>
            jQuery(function ($){
                $('body').on('click', '.btn-embed', function (){
                    var sc = "[wpdm_package id='{{ID}}']";
                    sc = sc.replace("{{ID}}", $(this).data('pid'));
                    console.log(sc);
                    $('#cpsc').val(sc);
                });
            });
        </script>
        <?php
        if($pagenow === 'themes.php' || $pagenow === 'theme-install.php'){
            if(!file_exists(ABSPATH.'/wp-content/themes/attire/')) {
                ?>
                <script>
                    jQuery(function ($) {
                        $('.page-title-action').after('<a href="<?php echo admin_url('/theme-install.php?search=attire'); ?>" class="hide-if-no-js page-title-action" style="border: 1px solid #0f9cdd;background: #13aef6;color: #ffffff;">Suggested Theme</a>');
                    });
                </script>
                <?php
            }
        }

    }


    function duplicate()
    {
        if(wpdm_query_var('wpdm_duplicate', 'int') > 0 && get_post_type(wpdm_query_var('wpdm_duplicate')) === 'wpdmpro') {
            if(!current_user_can('edit_posts') || !wp_verify_nonce(wpdm_query_var('__copynonce'), NONCE_KEY)) wp_die(esc_attr__( 'You are not authorized!', 'download-manager' ));
            Package::copy(wpdm_query_var('wpdm_duplicate', 'int'));
            wp_redirect("edit.php?post_type=wpdmpro");
            die();
        }
    }


}


