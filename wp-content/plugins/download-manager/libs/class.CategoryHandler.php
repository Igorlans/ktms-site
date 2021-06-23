<?php

namespace WPDM\libs;

use WPDM\Package;
use WPDM\Query;
use WPDM\Template;

class CategoryHandler
{

    private $cbTreeInit = 0;

    function __construct()
    {

    }

    function hArray($skip_filter = 1, $parent = 0)
    {
        $terms = get_terms(array('taxonomy' => 'wpdmcategory', 'parent' => $parent, 'hide_empty' => false));
        $allterms = _get_term_hierarchy('wpdmcategory');
        $allcats = array();
        foreach ($terms as $term) {
            $allcats[$term->term_id] = array('category' => $term, 'access' => maybe_unserialize(get_term_meta($term->term_id, '__wpdm_access')), 'childs' => array());
            $this->exploreChilds($term, $allcats[$term->term_id]['childs']);
        }

        if ($skip_filter === 0)
            $allcats = apply_filters("WPDM_libs_CategoryHandler_hArray", $allcats);

        return $allcats;
    }

    /**
     * Generates WordPress Download Manager category selector wit checkbox and ul/li
     * @param $name
     * @param array $selected
     * @param array $extras
     */
    function checkboxTree($name, $selected = array(), $extras = array())
    {
        echo "<ul class='ptypes m-0 p-0' id='wpdmcat-tree'>";
        $parent = wpdm_valueof($extras, 'parent', ['validate' => 'int']);
        $allcats = WPDM()->categories->hArray(0, $parent);

        /*$cparent = is_array($extras) && isset($extras['base_category']) ? $extras['base_category'] : 0;
        if ($cparent !== 0) {
            $cparent = get_term($cparent);
            $cparent = $cparent->term_id;
            echo "<input type='hidden' value='{$cparent}' name='cats[]' />";
        }*/
        if($parent > 0 && wpdm_valueof($extras, 'hide_parent', ['validate' => 'int', 'default' => 0]) == 0) {
            $term = get_term($parent);
            $value = wpdm_valueof($extras, 'value') === 'slug' ? $term->slug : $term->term_id;
            ?>
            <ul>
            <li class="<?php echo wpdm_valueof($extras, 'liclass'); ?>">
                <label><input type="checkbox" <?php checked(1, in_array($value, $selected)); ?>
                              name="<?php echo $name; ?>[]"
                              class="<?php echo wpdm_valueof($extras, 'cbclass'); ?>"
                              value="<?php echo $value; ?>"> <?php echo $term->name; ?> </label>
                <ul>
            <?php
        }
        $this->checkboxList($name, $allcats, $selected, $extras);
        if($parent > 0 && wpdm_valueof($extras, 'hide_parent', ['validate' => 'int', 'default' => 0]) == 0) echo "</li></ul>";
        echo "</ul>";
    }

    private function checkboxList($name, $allcats, $selected = array(), $extras = array())
    {
        foreach ($allcats as $cat_id => $cat) {

            $value = wpdm_valueof($extras, 'value') === 'slug' ? $cat['category']->slug : $cat_id;
            $category = $cat['category'];
            ?>
            <li class="<?php echo wpdm_valueof($extras, 'liclass'); ?>">
                <label><input type="checkbox" <?php checked(1, in_array($value, $selected)); ?>
                              name="<?php echo $name; ?>[]"
                              class="<?php echo wpdm_valueof($extras, 'cbclass'); ?>"
                              value="<?php echo $value; ?>"> <?php echo $category->name; ?> </label>
                <?php
                if (count($cat['childs']) > 0) {
                    echo "<ul id='wpdmcats-childof-{$cat_id}'>";
                    $this->checkboxList($name, $cat['childs'], $selected, $extras);
                    echo "</ul>";
                }
                ?>
            </li>
            <?php
        }
    }

    function exploreChilds($term, &$allcats)
    {
        $child_ids = get_term_children($term->term_id, 'wpdmcategory');
        if (count($child_ids) > 0) {
            foreach ($child_ids as $child_id) {
                $term = get_term($child_id);
                $allcats[$child_id] = array('category' => $term, 'access' => maybe_unserialize(get_term_meta($child_id, '__wpdm_access')), 'childs' => array());
                $this->exploreChilds($term, $allcats[$child_id]['childs']);
            }
        }
    }

    public static function getAllowedRoles($term_id)
    {
        $roles = maybe_unserialize(get_term_meta($term_id, '__wpdm_access', true));
        if (!is_array($roles)) {
            $MetaData = get_option("__wpdmcategory");
            $MetaData = maybe_unserialize($MetaData);

            $roles = maybe_unserialize(get_term_meta($term_id, '__wpdm_access', true));

            if (!is_array($roles))
                $roles = isset($MetaData[$term_id], $MetaData[$term_id]['access']) && is_array($MetaData[$term_id]['access']) ? $MetaData[$term_id]['access'] : array();

            $roles = apply_filters("wpdm_categoryhandler_getallowedroles", $roles, $term_id);
        }
        foreach ($roles as $index => $role) {
            if (!is_string($roles[$index])) unset($roles[$index]);
        }
        return $roles;
    }

    function parentRoles($cid)
    {
        if (!$cid) return array();
        $roles = array();
        $parents = \WPDM\libs\CategoryHandler::categoryParents($cid, 0);
        $MetaData = get_option("__wpdmcategory");
        $MetaData = maybe_unserialize($MetaData);
        foreach ($parents as $catid) {
            $croles = maybe_unserialize(get_term_meta($catid, '__wpdm_access', true));
            if (!is_array($roles))
                $croles = isset($MetaData[$catid], $MetaData[$catid]['access']) && is_array($MetaData[$catid]['access']) ? $MetaData[$catid]['access'] : array();
            $roles += $croles;
        }
        return array_unique($roles);
    }


    public static function icon($term_id)
    {
        $icon = get_term_meta($term_id, '__wpdm_icon', true);
        if ($icon == '') {
            $MetaData = get_option("__wpdmcategory");
            $MetaData = maybe_unserialize($MetaData);
            $icon = get_term_meta($term_id, '__wpdm_icon', true);
            if ($icon == '')
                $icon = isset($MetaData[$term_id]['icon']) ? $MetaData[$term_id]['icon'] : '';
        }
        return $icon;
    }

    public static function categoryParents($cid, $offset = 1)
    {
        $CategoryBreadcrumb = array();
        if ($cid > 0) {
            $cat = get_term($cid, 'wpdmcategory');
            $parent = $cat->parent;
            $CategoryParents[] = $cat->term_id;
            while ($parent > 0) {
                $cat = get_term($parent, 'wpdmcategory');
                $CategoryParents[] = $cat->term_id;
                $parent = $cat->parent;
            }
            if ($offset)
                array_pop($CategoryBreadcrumb);
            $CategoryParents = array_reverse($CategoryParents);
        }

        return $CategoryParents;

    }

    /**
     * Check if current user has access to the given term
     * @param $term_id
     * @return bool
     */
    public static function userHasAccess($term_id)
    {
        global $current_user;
        $roles = maybe_unserialize(get_term_meta($term_id, '__wpdm_access', true));
        $roles = is_array($roles) ? $roles : array();
        if(in_array('guest', $roles)) return true;
        $user_roles = is_array($current_user->roles) ? $current_user->roles : array();
        $has_role = array_intersect($roles, $user_roles);
        $users = maybe_unserialize(get_term_meta($term_id, '__wpdm_user_access', true));
        $users = is_array($users) ? $users : array();
        if (count($has_role) > 0 || in_array($current_user->user_login, $users)) return true;
        if(count($roles) === 0 && count($users) === 0) return true;
        return false;
    }

    public static function categoryBreadcrumb($cid, $offset = 1)
    {
        $CategoryBreadcrumb = array();
        if ($cid > 0) {
            $cat = get_term($cid, 'wpdmcategory');
            $parent = $cat->parent;
            $CategoryBreadcrumb[] = "<a href='#' class='folder' data-cat='{$cat->term_id}'>{$cat->name}</a>";
            while ($parent > 0) {
                $cat = get_term($parent, 'wpdmcategory');
                $CategoryBreadcrumb[] = "<a href='#' class='folder' data-cat='{$cat->term_id}'>{$cat->name}</a>";
                $parent = $cat->parent;
            }
            if ($offset)
                array_pop($CategoryBreadcrumb);
            $CategoryBreadcrumb = array_reverse($CategoryBreadcrumb);
        }
        echo "<a href='#' class='folder' data-cat='0'>Home</a>&nbsp; <i class='fa fa-angle-right'></i> &nbsp;" . implode("&nbsp; <i class='fa fa-angle-right'></i> &nbsp;", $CategoryBreadcrumb);

    }

    function embed($params = array('id' => '', 'operator' => 'IN', 'items_per_page' => 10, 'title' => false, 'desc' => false, 'orderby' => 'create_date', 'order' => 'desc', 'paging' => false, 'toolbar' => 1, 'template' => '', 'cols' => 3, 'colspad' => 2, 'colsphone' => 1, 'morelink' => 1))
    {
        extract($params);
        $fnparams = $params;
        if (!isset($id)) return '';
        if (!isset($items_per_page)) $items_per_page = 10;
        if (!isset($template)) $template = 'link-template-calltoaction3.php';
        if (!isset($cols)) $cols = 3;
        if (!isset($colspad)) $colspad = 2;
        if (!isset($colsphone)) $colsphone = 1;
        $toolbar = isset($toolbar) ? $toolbar : 0;
        $scid = isset($scid) ? $scid : md5($id);
        $taxo = 'wpdmcategory';
        if (isset($tag) && $tag == 1) $taxo = 'wpdmtag';
        $css_class = isset($css_class) ? $css_class : '';
        $cwd_class = "col-lg-" . (int)(12 / $cols);
        $cwdsm_class = "col-md-" . (int)(12 / $colspad);
        $cwdxs_class = "col-" . (int)(12 / $colsphone);

        $id = trim($id, ", ");
        $cids = explode(",", $id);

        global $wpdb, $current_user, $post, $wp_query;

        $orderby = isset($orderby) ? $orderby : 'publish_date';
        $orderby = in_array(wpdm_query_var('orderby'), array('title', 'publish_date', 'updates', 'download_count', 'view_count')) ? wpdm_query_var('orderby') : $orderby;

        $order = isset($fnparams['order']) ? $fnparams['order'] : 'desc';
        $order = wpdm_query_var('order') ? wpdm_query_var('order') : $order;
        $operator = isset($operator) ? $operator : 'IN';
        //$cpvid = str_replace(",", "_", $id);
        //$cpvar = 'cp_'.$cids[0];
        $term = get_term_by('slug', $cids[0], 'wpdmcategory');
        if(!$term) return apply_filters("wpdm_category_not_found", __( "Category doesn't exist", 'download-manager' ));
        $cpvar = 'cp_' . $term->term_id;
        $cp = wpdm_query_var($cpvar, 'num');
        if (!$cp) $cp = 1;


        $query = new Query();
        $query->items_per_page($items_per_page);
        $query->paged($cp);
        $query->sort($orderby, $order);
        $query->categories($cids, 'slug', $operator, wpdm_valueof($params, 'include_children', false));


        if (get_option('_wpdm_hide_all', 0) == 1) {
            $query->meta("__wpdm_access", '"guest"');

            if (is_user_logged_in()) {
                foreach ($current_user->roles as $role) {
                    $query->meta("__wpdm_access", $role);
                }
                $query->meta_relation('OR');
            }
        }

        if (wpdm_query_var('skw', 'txt') != '') {
            $query->s(wpdm_query_var('skw', 'txt'));
        }

        $query->process();
        $total = $query->count;
        $packs = $query->packages();
        $pages = ceil($total / $items_per_page);
        $burl = get_permalink();

        $html = '';
        $templates = maybe_unserialize(get_option("_fm_link_templates", true));

        if (isset($templates[$template])) $template = $templates[$template]['content'];

        foreach ($packs as $pack) {
            $pack = (array)$pack;
            $thtml = Package::fetchTemplate($template, $pack);
            $repeater = '';
            if ($thtml != '')
                $repeater = "<div class='{$cwd_class} {$cwdsm_class} {$cwdxs_class}'>" . $thtml . "</div>";
            $html .= $repeater;

        }

        wp_reset_postdata();

        $html = "<div class='row'>{$html}</div>";
        $cname = array();
        foreach ($cids as $cid) {
            $cat = get_term_by('slug', $cid, $taxo);
            if ($cat)
                $cname[] = $cat->name;

        }
        $cats = implode(", ", $cname);

        //Added from v4.2.1
        $desc = '';
        $category = get_term_by('slug', $cids[0], 'wpdmcategory');

        if (isset($fnparams['title']) && $fnparams['title'] != false && intval($fnparams['title']) != 1) $cats = $fnparams['title'];
        if (isset($fnparams['desc']) && $fnparams['desc'] != false && intval($fnparams['desc']) != 1) $desc = $fnparams['desc'];
        if (isset($fnparams['desc']) && (int)$fnparams['desc'] == 1) $desc = $category->description;

        $cimg = '';


        $subcats = '';
        if (function_exists('wpdm_ap_categories') && $subcats == 1) {
            $schtml = wpdm_ap_categories(array('parent' => $id));
            if ($schtml != '') {
                $subcats = "<fieldset class='cat-page-tilte'><legend>" . __("Sub-Categories", "download-manager") . "</legend>" . $schtml . "<div style='clear:both'></div></fieldset>" . "<fieldset class='cat-page-tilte'><legend>" . __("Downloads", "download-manager") . "</legend>";
                $efs = '</fieldset>';
            }
        }
        $pagination = "";
        if (!isset($paging) || intval($paging) == 1) {
            $pag_links = wpdm_paginate_links($total, $items_per_page, $cp, $cpvar, array('container' => '#content_' . $scid, 'async' => (isset($async) && $async == 1 ? 1 : 0), 'next_text' => ' <i style="display: inline-block;width: 8px;height: 8px;border-right: 2px solid;border-top: 2px solid;transform: rotate(45deg);margin-left: -2px;margin-top: -2px;"></i> ', 'prev_text' => ' <i style="display: inline-block;width: 8px;height: 8px;border-right: 2px solid;border-bottom: 2px solid;transform: rotate(135deg);margin-left: 2px;margin-top: -2px;"></i> '));
            $pagination = "<div style='clear:both'></div>" . $pag_links . "<div style='clear:both'></div>";
        } else
            $pgn = "";

        global $post;

        $sap = get_option('permalink_structure') ? '?' : '&';
        $burl = $burl . $sap;
        if (isset($_GET['p']) && $_GET['p'] != '') $burl .= 'p=' . esc_attr($_GET['p']) . '&';
        if (isset($_GET['src']) && $_GET['src'] != '') $burl .= 'src=' . esc_attr($_GET['src']) . '&';
        $order = ucfirst($order);
        $orderby_label = " " . __(ucwords(str_replace("_", " ", $orderby)), "wpdmpro");
        $ttitle = __("Title", "download-manager");
        $tdls = __("Downloads", "download-manager");
        $tcdate = __("Publish Date", "download-manager");
        $tudate = __("Update Date", "download-manager");
        $tasc = __("Asc", "download-manager");
        $tdsc = __("Desc", "download-manager");
        $tsrc = __("Search", "download-manager");
        $ord = __("Order", "download-manager");
        $order_by_label = __("Order By", "download-manager");
        $hasdesc = $desc != '' ? 'has-desc' : '';

        $icon = self::icon($category->term_id);
        if(!isset($iconw)) $iconw = $desc != '' ? 64 : 32;
        if ($icon != '') $icon = "<div class='pull-left mr-3'><img class='category-icon m-0 category-{$category->term_id}' style='max-width: {$iconw}px' src='{$icon}' alt='{$category->name}' /></div>";

        $title = $cats;

        ob_start();
        include Template::locate("shortcodes/category.php", WPDM_TPL_FALLBACK);
        $content = ob_get_clean();
        return $content;

    }


}

