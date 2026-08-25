<?php
class Top_Nav extends Walker_Nav_Menu {

    // Start a submenu level
    public function start_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class='dropdown-menu'>\n";
    }

    // End a submenu level
    public function end_lvl( &$output, $depth = 0, $args = array() ) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }

    // Start a menu item
    public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

        $classes = empty( $item->classes ) ? array() : (array) $item->classes;

        $classes[] = 'nav-item';
        if ( in_array('menu-item-has-children', $classes) ) {
            $classes[] = 'dropdown';
        }

        $class_names = implode( ' ', array_filter( $classes ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $atts = [];
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel']    = !empty($item->xfn) ? $item->xfn : '';
        $atts['href']   = !empty($item->url) ? $item->url : '#';

        if ( in_array('menu-item-has-children', $classes) ) {
            $atts['class']         = 'nav-link dropdown-toggle';
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['aria-haspopup'] = 'true';
            $atts['aria-expanded'] = 'false';
            $atts['role']          = 'button';
        } else {
            $atts['class'] = ($depth > 0) ? 'dropdown-item' : 'nav-link';
        }

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters( 'the_title', $item->title, $item->ID );

        $output .= $indent . '<li' . $class_names . '>';
        $output .= '<a' . $attributes . '>' . $title . '</a>';
    }

    // End a menu item
    public function end_el( &$output, $item, $depth = 0, $args = array() ) {
        $output .= "</li>\n";
    }
}
