<?php

/**
 * Created by michael on 2/22/16.
 */

class Markup_Parser {

    public $orig_texts = array();
    public $new_texts = array();

    function __construct($the_texts) {
        if (is_array($the_texts))
            $this->orig_texts = $the_texts;
        elseif (is_string($the_texts))
            $this->orig_texts[] = $the_texts;
    }

    public function convert_texts() {
        return $this->markup_to_html();
    }

    public function wrap_convert_texts($tag_name) {
        $texts = $this->convert_texts();
        $final = '';
        foreach($texts as $text) {
            $final .= "<{$tag_name}>{$text}</{$tag_name}>";
        }
        return $final;
    }
    /**
     * Replace parts of a string using rotating template peices. For example, to change text wrapped in
     * double astricks into text wrapped in <strong></strong> tags you could pass
     *    - toggle_replace("Something **important**!", "|\*\*|", array('<strong>', '</strong>'));
     *        output: "Something <strong>important</strong>!";
     *
     * @param $text
     * @param $search
     * @param $replacements
     * @param int $limit
     *
     * @return mixed
     */
    private function toggle_replace($text, $search, $replacements, $limit = 1000) {
        $i = 0;
        $count_replacements = count($replacements);

        while (preg_match($search, $text)) {
            $text = preg_replace_callback($search, function($matches) use ($replacements, $count_replacements, &$i) {
                $str = $replacements[$i++ % $count_replacements];
                return $str;
            }, (string)$text, (int)$limit);
        }
        return $text;
    }

    private function markup_to_html () {
        $new_texts = array();
        foreach($this->orig_texts as $text) {
            //$text = $this->block_code($text);
            $text = $this->inline_code($text);
            $text = $this->strong_italics($text);
            $text = $this->strong($text);
            $text = $this->italics($text);
            $new_texts[] = $text;
        }

        $this->new_texts = $new_texts;
        return $this->new_texts;
    }

    function strong_italics($text) {
        return $this->toggle_replace($text, '~\*\*\*~', array('<strong class="text-primary"><em>', '</em></strong>'));
    }
    function strong($text) {
        return $this->toggle_replace($text, '~\*\*~', array('<strong class="text-danger">', '</strong>'));
    }
    function italics($text) {
        return $this->toggle_replace($text, '~\*~', array('<em class="text-warning">', '</em>'));
    }
    function block_code($text) {
        return $this->toggle_replace($text, '~```([a-zA-Z0-9]+)?~', array('<pre class="block-code">', '</pre>'));
    }
    function inline_code($text) {
        return $this->toggle_replace($text, '~`~', array('<code class="inline-code">', '</code>'));
    }
}




class HTML_Util {
    function __construct() {}

    static function wrap_elements_in($tag, $array, $as_text = true) {
        $tag_name = 'div';
        $attrs = '';
        if (!is_array($array)) {
            $array = array($array);
        }
        if (is_array($tag)) {
            if (isset($tag['tag_name'])) {
                $tag_name = $tag['tag_name'];
                unset($tag['tag_name']);
            }
            if (count($tag)) {
                foreach($tag as $prop => $value) {
                    $attrs .= " {$prop}=\"{$value}\"";
                }
            }
        }
        elseif (is_string($tag)) {
            $tag_name = $tag;
        }

        // Make a string with all array items joined inside their tags
        $sep = "</{$tag_name}><{$tag_name}{$attrs}>";
        $text = implode($sep, $array);

        // Add tag to front and back of string (or else html will be broke).
        $text = "<{$tag_name}{$attrs}>{$text}</{$tag_name}>";
        if ($as_text) {
            // Return string with each item wrapped in tag.
            return $text;
        }
        $splitter = '-)#@@#(-';
        $text = str_replace( '><', ">$splitter<", $text );
        // Return array with each item wrapped in tag.
        return explode($splitter, $text);

    }
}


