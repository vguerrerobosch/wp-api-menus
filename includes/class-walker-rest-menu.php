<?php
/**
 * WalkerRestMenu class
 */

class WalkerRestMenu extends \Walker
{
    public $db_fields = array( 'parent' => 'menu_item_parent', 'id' => 'db_id' );

    public function start_lvl(&$output, $depth = 0, $args = array())
    {
        $output = array($output);
    }

    public function end_lvl(&$output, $depth = 0, $args = array())
    {
        $top_lvl = array_shift($output);

        end($top_lvl);
        $parent = &$top_lvl[key($top_lvl)];
        $parent['children'] = $output;

        $output = $top_lvl;
    }

    public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
    {
        if (is_string($output)) {
            $output = array();
        }

        $item = (array) $item;

        $menu_item = array(
            'id'          => abs($item['ID']),
            'order'       => (int) $item['menu_order'],
            'parent'      => abs($item['menu_item_parent']),
            'title'       => $item['title'],
            'url'         => $item['url'],
            'path'        => filter_var($item['url'], FILTER_VALIDATE_URL) ? wp_parse_url($item['url'], PHP_URL_PATH) : $item['url'],
            'attr'        => $item['attr_title'],
            'target'      => $item['target'],
            'classes'     => implode(' ', $item['classes']),
            'xfn'         => $item['xfn'],
            'description' => $item['description'],
            'object_id'   => abs($item['object_id']),
            'object'      => $item['object'],
            'object_slug' => get_post($item['object_id'])->post_name,
            'type'        => $item['type'],
            'type_label'  => $item['type_label'],
            'acf'         => get_fields($item['ID']),
        );

        $output[] = $menu_item;
    }
}
