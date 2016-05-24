<?php
/**
 * Created by michael on 3/2/16.
 */

namespace Util;

function show_template($path_from_root) {
    $abs_path_to_template = \ROOT_DIR . '/templates/' . ltrim( $path_from_root, '/');
    include $abs_path_to_template;
}


function be_array(&$item = '') {
    return is_array($item) ? $item : array($item);
}

function print_pre($res, $return_only = 0) {
    $rv = "<pre>";
    $rv.=print_r($res, 1);
    $rv.="</pre>";
    if ( !$return_only ) {
        echo $rv;
    }
    return $rv;
}

class Utils {

    static function get_index_from($container, $index) {
        return ( isset( $container[ $index ] ) && ! empty( $container[ $index ] ) ) ? $container[ $index ] : null;
    }
}

function get($index) {
    return Utils::get_index_from( $_GET, $index );
}
function session($index) {
    return Utils::get_index_from( $_SESSION, $index );
}
function post($index) {
    return Utils::get_index_from( $_POST, $index );
}
function server($index) {
    return Utils::get_index_from( $_SERVER, $index );
}

/**
 * Print a debug message if debug messages are one via get param
 * @param $msg_or_obj
 */
function debug($msg_or_obj) {
    if ((defined('DEV_DEBUG') && DEV_DEBUG) || !!get('devbug') || !!post('devbug')) {
        if (is_string($msg_or_obj))
            echo $msg_or_obj;
        else
            print_pre($msg_or_obj);
    }
}

function open( $tag='div', $class='', $id = '' ) {
    echo "<{$tag} class='{$class}' id='{$id}'>";
}

function close( $tag='div' ) {
    echo "</{$tag}>";
}


function user_session_expired() {
    $now = time();
    $logged_in_at = session('logged_in_at');
    if ( !$logged_in_at || (int)$logged_in_at > $now ) {
        return false;
    }

    return $now - (int)$logged_in_at < 60;
}



class Table {

    private $cols;
    private $items = array();
    private $init;
    private $complete;
    private $open_body;

    function __construct($col) {
        $this->init = false;
        $this->complete = false;
        $this->open_body = false;
        if (is_array($col)) {
            $this->items = $col;
            $this->cols = count($col);
        }
        else {
            $this->cols = (int)$col;
        }
    }


    private function initialize($classes='') {
        if (!$this->init) {
            echo "<table class='{$classes}'>";
            $this->init = true;
        }
        return $this->init;
    }

    private function fin() {
        if (!$this->complete) {
            echo "</table>";
            $this->complete = true;
        }
        return $this->complete;
    }

    function open() {
        if (!$this->open_body) {
            $this->open_body = true;
            echo "<tbody>";
            return $this->open_body;
        }
        return false;
    }

    function close() {
        if ($this->open_body) {
            $this->open_body = false;
            echo "</tbody>";
            return $this->fin();
        }
        return false;
    }
    /**
     * Prints thead and returns any $header_items not used.
     * @param $header_items
     * @param string $classes
     *
     * @return mixed
     */
    function head($header_items = null, $classes='') {
        if (!is_array($header_items)) {
            $header_items = $this->items;
        }
        $this->initialize();
        if ( $this->complete) {
            return false;
        }
        $len = count($header_items);
        $thead = "<thead class='{$classes}'><tr>";
        for ( $i = 0; $i < $len && $i < $this->cols; $i ++ ) {
            $thead .= "<th>{$header_items[$i]}</th>";
            unset($header_items[$i]);
        }
        $thead .= "</tr></thead>";
        echo $thead;
        return $header_items;
    }



    function row($row_items, $classes='') {
        $this->initialize();
        if ( $this->complete) {
            return false;
        }
        if ( !$this->open_body ) {
            $this->open();
        }
        $len = count($row_items);
        $tbody = "<tr class='{$classes}'>";
        for ( $i = 0; $i < $len && $i < $this->cols; $i ++ ) {
            $tbody .= "<td>{$row_items[$i]}</td>";
            unset($row_items[$i]);
        }
        $tbody .= "</tr>";
        echo $tbody;
        return $row_items;
    }

    function end() {
        $this->fin();
    }

}


function show_login_url($login_url) {
    echo "
    <h3>Please Login to use the calendar application</h3>
    <a href='{$login_url}'>Click here to login.</a>

    ";
}
