<?php

/**
 * Exit if accessed directly
 */

if (!defined('ABSPATH')) {
    exit();
}

class CCETGB_Convert_Classic_to_GB_Blocks
{

    /**
     * Adding WordPress hook
     */
    public function __construct()
    {
        add_action('admin_action_ccetgb_copy_as_new_draft', array(__CLASS__, 'ccetgb_copy_as_new_draft'));
    }

    /**
     * Create new post as a draft mode
     */
    public static function ccetgb_copy_as_new_draft()
    {

        $copy_get_id = filter_input(INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT);
        $copy_post_id = filter_input(INPUT_POST, 'post', FILTER_SANITIZE_NUMBER_INT);
        $copy_get_action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

        if (!(isset($copy_get_id) || isset($copy_post_id) || (isset($copy_get_action) && 'ccetgb_copy_as_new_draft' === $copy_get_action))) {
            wp_die(esc_html__('No post to duplicate has been supplied!', 'convert-classic-editor-to-gutenberg-blocks'));
        }

        $post_id = (isset($copy_get_id) ? absint($copy_get_id) : absint($copy_post_id));
        check_admin_referer('ccetgb_clone_post_' . $post_id);
        $post = get_post($post_id);
        $current_user = wp_get_current_user();
        $new_post_author = $current_user->ID;

        if (isset($post) && $post !== null) {
            $post_name = $post->post_name . '-with-gb';
            $post_title = $post->post_title . ' with GB';
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status' => $post->ping_status,
                'post_author' => $new_post_author,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_name' => $post_name,
                'post_parent' => $post->post_parent,
                'post_password' => $post->post_password,
                'post_status' => 'draft',
                'post_title' => $post_title,
                'post_type' => $post->post_type,
                'to_ping' => $post->to_ping,
                'menu_order' => $post->menu_order,
            );

            $new_post_id = wp_insert_post($args);
            self::ccetgb_convert_classic_post_to_gb_blocks($post->post_content, $new_post_id);

            $taxonomies = get_object_taxonomies($post->post_type);
            if (!empty($taxonomies) && $taxonomies > 0) {
                foreach ($taxonomies as $taxonomy) {
                    $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                    wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                }
            }

            $meta_blacklist = array("_wp_page_template", "_edit_lock", "_edit_last");
            $post_meta_info = get_post_meta($post_id, false, true);

            foreach ($meta_blacklist as $blacklist) {
                unset($post_meta_info[$blacklist]);
            }

            foreach ($post_meta_info as $meta_key => $meta_value) {
                add_post_meta($new_post_id, $meta_key, addslashes($meta_value[0]));
            }

            $new_post_editor_url = add_query_arg('success', '1', admin_url('post.php?action=edit&post=' . $new_post_id));
            wp_safe_redirect($new_post_editor_url);
            exit();

        } else {
            wp_die(esc_html__('Post creation failed, could not find original post: ' . $post_id, 'convert-classic-editor-to-gutenberg-blocks'));
        }
    }

    public static function ccegbt_wrap_childNodes($tag = array(), $dom = '', $element = '')
    {
        $childNodes = $tag->childNodes;
        foreach ($childNodes as $childNode) {
            // Div has direct text then that text conver to p tag
            $nodeName = $childNode->nodeName;
            $nodeValue = $childNode->nodeValue;
            $childNodes = $childNode->childNodes;
            $parentNode = $childNode->parentNode;
            if (is_object($parentNode)) {
                $parentNodeName = $childNode->parentNode->tagName;
            }

            if ('#text' === $nodeName && !empty(trim($nodeValue))) {
                $element = $dom->createElement('p', $nodeValue);
                $tag->replaceChild($element, $childNode);
            }

            if ('span' === $nodeName && !empty($parentNodeName) && $element === $parentNodeName) {
                $element = $dom->createElement('p', $nodeValue);
                $tag->appendChild($element);
                $tag->removeChild($childNode);
            }

            if ('strong' === $nodeName && !empty($parentNodeName) && $element === $parentNodeName) {
                $element = $dom->createElement('p', '');
                $tag->replaceChild($element, $childNode);
                $element->appendChild($childNode);
            }

            if ('i' === $nodeName && !empty($parentNodeName) && $element === $parentNodeName) {
                $element = $dom->createElement('p', '');
                $tag->replaceChild($element, $childNode);
                $element->appendChild($childNode);
            }

            if ('br' === $nodeName && !empty($parentNodeName) && $element === $parentNodeName) {
                $element = $dom->createElement('p', '');
                $tag->replaceChild($element, $childNode);
                $element->appendChild($childNode);
            }

            if ('a' === $nodeName && !empty($parentNodeName) && $element === $parentNodeName) {
                $element = $dom->createElement('p', '');
                $tag->replaceChild($element, $childNode);
                $element->appendChild($childNode);
            }

            if ('button' === $nodeName && !empty($parentNodeName) && $element === $parentNodeName) {
                $element = $dom->createElement('p', '');
                $tag->replaceChild($element, $childNode);
                $element->appendChild($childNode);
            }

        }
    }

    /**
     * remove custom attributes and add class and id attributes to headings tags
     * @param string $string
     */
    public static function ccetgb_heading_add_class_id_attr($content = '', $tag = '', $level = '')
    {
        if ('' !== $tag) {

            $dom = new DOMDocument(null, 'UTF-8');
            $dom->encoding = 'utf-8';
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $dom->removeChild($dom->doctype);
            $tags = $dom->getElementsByTagName($tag);
            foreach ($tags as $tag) {
                $class = $tag->getAttribute('class');
                $id = $tag->getAttribute('id');
                $attributes = $tag->attributes;

                while ($attributes->length) {
                    $tag->removeAttribute($attributes->item(0)->name);
                }

                if ('' !== $id) {
                    $tag->setAttribute('id', $id);
                }
                if ('' !== $class) {
                    $tag->setAttribute('class', $class);
                }
                if ('' !== $class) {
                    $comment = $dom->createComment(' wp:heading {"level":' . $level . ',"className": "' . $class . '"} ');
                } else {
                    $comment = $dom->createComment(' wp:heading {"level":' . $level . '} ');
                }
                $tag->parentNode->insertBefore($comment, $tag);

            }

            return $dom->saveHTML();
        }
    }

    /**
     * remove custom attributes and add class attributes to elements
     * @param string $string
     */
    public static function ccetgb_add_class_id_attr($content = '', $htmlTag = '', $namespace = '', $preClass = '')
    {
        if ('' !== $htmlTag) {
            $dom = new DOMDocument(null, 'UTF-8');
            $dom->encoding = 'utf-8';
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $dom->removeChild($dom->doctype);
            $tags = $dom->getElementsByTagName($htmlTag);

            foreach ($tags as $tag) {

                $class = $tag->getAttribute('class');
                $attributes = $tag->attributes;
                while ($attributes->length) {
                    $tag->removeAttribute($attributes->item(0)->name);
                }

                if ( '' !== $preClass && '' !== $class ) {

                    $tag->setAttribute('class', $preClass . ' ' . $class);
                } elseif ('' !== $preClass) {
                    $tag->setAttribute('class', $preClass);
                } elseif ('' !== $class) {
                    $tag->setAttribute('class', $class);
                }

                if ('' !== $class) {
                    if ( 'ol' === $htmlTag ) {

                        $comment = $dom->createComment(' wp:' . $namespace . ' {"ordered":true,"className": "' . $class . '"} ');
                    } else {
                        $comment = $dom->createComment(' wp:' . $namespace . ' {"className": "' . $class . '"} ');
                    }

                } else {
                    if ( 'ol' === $htmlTag ) {
                        $comment = $dom->createComment(' wp:' . $namespace . ' {"ordered":true} ');
                    } else {
                        $comment = $dom->createComment(' wp:' . $namespace . ' ');
                    }

                }
                $tag->parentNode->insertBefore($comment, $tag);
            }

            return $dom->saveHTML();
        }
    }

    /**
     * Get src attribute of given tag string
     * @param string $string
     */
    public static function ccetgb_get_src_value($string = '')
    {
        preg_match('/src="(.+?)"/', $string, $input);
        return $input[1];
    }

    /**
     * Get class attribute of given tag string
     * @param string $string
     */
    public static function ccetgb_get_class_value($string = '')
    {
        preg_match('/class="(.+?)"/', $string, $input);
        return $input[1];
    }

    /**
     * get iframe src and find provider and other details
     * @param string $src
     */
    public static function ccetgb_filter_iframeProvider($src = '')
    {
        $provider = '';
        $type = '';
        $provider_slug = '';

        $result = [];
        if ('' !== $src) {

            if (strpos($src, "youtube")) {
                $provider = 'youtube';
                $type = 'video';
                $src = str_replace("embed/", "watch?v=", $src);
                $provider_slug = 'youtube';
            } elseif (strpos($src, "vimeo")) {
                $provider = 'vimeo';
                $type = 'video';
                $src = str_replace("player.vimeo.com/video/", "vimeo.com/", $src);
                $provider_slug = 'vimeo';
            } elseif (strpos($src, "facebook")) {
                $provider = 'facebook';
                $type = 'rich';
                $src = str_replace("https://www.facebook.com/plugins/post.php?href=", "", $src);
                $src = rawurldecode($src);
                $provider_slug = 'facebook';
            } elseif (strpos($src, "videopress")) {
                $provider = 'videopress';
                $type = 'video';
                $src = str_replace("embed/", "v/", $src);
                $src = rawurldecode($src);
                $provider_slug = 'videopress';
            } elseif (strpos($src, "dailymotion")) {
                $provider = 'dailymotion';
                $type = 'video';
                $src = str_replace("embed/", "", $src);
                $src = rawurldecode($src);
                $provider_slug = 'dailymotion';
            } else {
                $provider = 'wordpress';
                $type = 'wp-embed';
                $src = rawurldecode($src);
                $provider_slug = 'plugin-directory';
            }
            $result['type'] = $type;
            $result['provider'] = $provider;
            $result['src'] = $src;
            $result['provider-slug'] = $provider_slug;

            return $result;

        }
    }
    /**
     * generate iframe embed block code
     * @param array $embed_provider
     * @param string $content
     */
    public static function ccetgb_convert_provider($embed_provider = array(), $content = '', $class = '')
    {
        if (!empty($embed_provider)) {
            $temp_string_store = '';
            $tag_str = '<!-- wp:core-embed/' . $embed_provider['provider'] . ' {"url":"' . $embed_provider['src'] . '","type":"' . $embed_provider['type'] . '","providerNameSlug":"' . $embed_provider['provider-slug'] . '","className":"wp-embed-aspect-16-9 wp-has-aspect-ratio ' . $class . '"} --><figure class="wp-block-embed-' . $embed_provider['provider'] . ' wp-block-embed is-type-' . $embed_provider['type'] . ' is-provider-' . $embed_provider['provider-slug'] . ' is-is-provider-' . $embed_provider['provider-slug'] . ' provider-' . $embed_provider['provider'] . ' wp-embed-aspect-16-9 wp-has-aspect-ratio">
            <div class="wp-block-embed__wrapper">' . $embed_provider['src'] . ' </div> </figure><!-- /wp:core-embed/' . $embed_provider['provider'] . ' -->';
            $temp_string_store .= $tag_str;
            return $temp_string_store;
        }

    }

    /**
     * Convert classic element to block
     * @param string $content
     * @param int $post_id
     */
    public static function ccetgb_convert_classic_post_to_gb_blocks($content = '', $post_id = 0)
    {

        if (!is_plugin_active('multipurpose-block/index.php')) {
            $error_msg = sprintf('This plugin required <a href="https://wordpress.org/plugins/multipurpose-block/">Multipurpose Gutenberg Block</a> plugin to work correctly.');
            $elment_array = array('a' => array('href' => array()));
            wp_die(wp_kses($error_msg, $elment_array));
        }

        if (!empty($content) && 0 !== $post_id) {

            $short_code_arr = self::ccetgb_is_shortcode_in_content($content);
            if (strpos($content, '<!-- wp:') === false) {

                $dom = new DOMDocument(null, 'UTF-8');
                $dom->encoding = 'utf-8';
                libxml_use_internal_errors(true);
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->removeChild($dom->doctype);
                $dom->encoding = 'utf-8';

                // Section tag Dom Manuplation
                $sections = $dom->getElementsByTagName('section');
                foreach ($sections as $section) {

                    self::ccegbt_wrap_childNodes($section, $dom, 'section');

                    $sclass = $section->getAttribute('class');
                    if (!empty($sclass)) {
                        $classToAdd = 'wp-block-md-multipurpose-gutenberg-block is-block-center ' . $sclass;
                    } else {
                        $classToAdd = 'wp-block-md-multipurpose-gutenberg-block is-block-center';
                    }

                    $attributes = $section->attributes;
                    while ($attributes->length) {
                        $section->removeAttribute($attributes->item(0)->name);
                    }
                    $section->setAttribute('class', $classToAdd);
                    $comment = $dom->createComment(' wp:md/multipurpose-gutenberg-block {"ElementTag":"section"} ');
                    $section->parentNode->insertBefore($comment, $section);

                }
                $content = $dom->saveHTML();

                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->removeChild($dom->doctype);
                $dom->encoding = 'utf-8';
                unset($sections);

                // Header tag Dom Manuplation
                $headers = $dom->getElementsByTagName('header');
                foreach ($headers as $header) {

                    self::ccegbt_wrap_childNodes($header, $dom, 'header');

                    $sclass = $header->getAttribute('class');
                    if (!empty($sclass)) {
                        $classToAdd = 'wp-block-md-multipurpose-gutenberg-block is-block-center ' . $sclass;
                    } else {
                        $classToAdd = 'wp-block-md-multipurpose-gutenberg-block is-block-center';
                    }
                    $attributes = $header->attributes;
                    while ($attributes->length) {
                        $header->removeAttribute($attributes->item(0)->name);
                    }
                    $header->setAttribute('class', $classToAdd);
                    $comment = $dom->createComment(' wp:md/multipurpose-gutenberg-block {"ElementTag":"header"} ');
                    $header->parentNode->insertBefore($comment, $header);
                }
                $content = $dom->saveHTML();
                
                //img tag dom manipulation
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->removeChild($dom->doctype);
                $dom->encoding = 'utf-8';
                unset($headers);

                $img_array = $dom->getElementsByTagName('img');
                $img_src_alt_arr = [];
                foreach ($img_array as $img) {
                    $src = $img->getAttribute('src');
                    $alt = $img->getAttribute('alt');
                    if (empty($img_src_alt_arr[$src])) {
                        $img_src_alt_arr[$src] = $alt;
                    }
                }

                // Div tag Dom Manuplation
                $divs = $dom->getElementsByTagName('div');
                foreach ($divs as $div) {
                    $dclass = $div->getAttribute('class');
                    $childNodes = $div->childNodes;
                    $value = $div->nodeValue;
                    if (!empty($short_code_arr[0])) {
                        $is = array_search($value, $short_code_arr[0], true);
                        if ($is !== false) {
                            continue;
                        }
                    }

                    foreach ($childNodes as $childNode) {
                        // Div has direct text then that text conver to p tag
                        $nodeName = $childNode->nodeName;
                        $nodeValue = $childNode->nodeValue;
                        $childNodes = $childNode->childNodes;
                        $value = trim($childNode->nodeValue);
                        if (!empty($short_code_arr[0])) {
                            $is = array_search($value, $short_code_arr[0], true);
                            if ($is !== false) {
                                continue;
                            }
                        }

                        $parentNode = $childNode->parentNode;
                        if (is_object($parentNode)) {
                            $parentNodeName = $childNode->parentNode->tagName;
                        }

                        if ('#text' === $nodeName && !empty(trim($nodeValue))) {
                            $element = $dom->createElement('p', $nodeValue);
                            $div->replaceChild($element, $childNode);

                        }

                        if ('span' === $nodeName && !empty($parentNodeName) && 'div' === $parentNodeName) {
                            $element = $dom->createElement('p', $nodeValue);
                            $div->appendChild($element);
                            $div->removeChild($childNode);
                        }

                        //If div tag containg directly strong tag then add p tag and inside that p tag added strong tag.
                        if ('strong' === $nodeName && !empty($parentNodeName) && 'div' === $parentNodeName) {
                            $element = $dom->createElement('p', '');
                            $div->replaceChild($element, $childNode);
                            $element->appendChild($childNode);
                        }

                        if ('i' === $nodeName && !empty($parentNodeName) && 'div' === $parentNodeName) {
                            $element = $dom->createElement('p', '');
                            $div->replaceChild($element, $childNode);
                            $element->appendChild($childNode);
                        }

                        if ('br' === $nodeName && !empty($parentNodeName) && 'div' === $parentNodeName) {
                            $element = $dom->createElement('p', '');
                            $div->replaceChild($element, $childNode);
                            $element->appendChild($childNode);
                        }

                        if ('a' === $nodeName && !empty($parentNodeName) && 'div' === $parentNodeName) {
                            $element = $dom->createElement('p', '');
                            $div->replaceChild($element, $childNode);
                            $element->appendChild($childNode);
                        }

                        if ('button' === $nodeName && !empty($parentNodeName) && 'div' === $parentNodeName) {
                            $element = $dom->createElement('p', '');
                            $div->replaceChild($element, $childNode);
                            $element->appendChild($childNode);
                        }

                    }

                    if (!empty($dclass)) {
                        $classToAdd = 'wp-block-md-multipurpose-gutenberg-block is-block-center ' . $dclass;
                    } else {
                        $classToAdd = 'wp-block-md-multipurpose-gutenberg-block is-block-center';
                    }
                    $attributes = $div->attributes;
                    while ($attributes->length) {
                        $div->removeAttribute($attributes->item(0)->name);
                    }

                    $div->setAttribute('class', $classToAdd);
                    $comment = $dom->createComment(' wp:md/multipurpose-gutenberg-block ');

                    $div->parentNode->insertBefore($comment, $div);
                }

                $content = $dom->saveHTML();

                // Iframe tag Dom Manuplation

                $dom->loadHTML($content);
                $dom->removeChild($dom->doctype);
                $domXPath = new DOMXPath($dom);
                $iframes = $domXPath->query('//iframe');
                foreach ($iframes as $iframe) {

                    $iframe_class = $iframe->getAttribute('class');
                    $iframe_src = $iframe->getAttribute('src');
                    $embed_provider = self::ccetgb_filter_iframeProvider($iframe_src);
                    $convert_content = self::ccetgb_convert_provider($embed_provider, '', $iframe_class);
                    $iframe->parentNode->replaceChild(
                        $dom->createElement('myiframe', $convert_content),
                        $iframe
                    );
                }
                $content = $dom->saveHTML();

                // Wrape p tag for plain text
                $xpath = new DOMXPath($dom);
                $nodeList = $xpath->query('//text()[not(node())]');

                foreach ($nodeList as $node) {

                    if ($node->parentNode->tagName === 'body' && !empty(trim($node->nodeValue))) {
                        $p = $dom->createElement('p', htmlspecialchars(trim($node->textContent)));
                        $node->parentNode->replaceChild($p, $node);
                    }

                }
                $content = $dom->saveHTML();

                // remove extra p tags for shortcode tags
                $xpath = new DOMXPath($dom);
                $nodeList = $xpath->query('//text()[not(node())]');

                foreach ($nodeList as $node) {

                    if (preg_match_all('/\[/', $node->nodeValue, $result)) {
                        if ($node->parentNode->tagName === 'p' && !empty(trim($node->nodeValue))) {

                            $node->parentNode->parentNode->replaceChild($node, $node->parentNode);
                        }
                    }
                }
                $content = $dom->saveHTML();

                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->removeChild($dom->doctype);
                $dom->encoding = 'utf-8';
                unset($divs);

                $contentArr = explode('<body>', $content);

                if (count($contentArr) > 1) {
                    $content = $contentArr[1];
                    $contentArr = explode('</body>', $content);
                    $content = $contentArr[0];
                }
                if (strpos($content, '<div') !== false) {
                    $content = str_replace('</div>', '</div><!-- /wp:md/multipurpose-gutenberg-block -->', $content);
                }

                if (strpos($content, '<section') !== false) {
                    $content = str_replace('</section>', '</section><!-- /wp:md/multipurpose-gutenberg-block -->', $content);
                }

                if (strpos($content, '<header') !== false) {
                    $content = str_replace('</header>', '</header><!-- /wp:md/multipurpose-gutenberg-block -->', $content);
                }

                if (strpos($content, '<script') !== false) {
                    $content = str_replace('<script', '<!-- wp:html --> <script', $content);
                    $content = str_replace('</script>', '</script><!-- /wp:html -->', $content);
                }

                if (strpos($content, '<style') !== false) {
                    $content = str_replace('<style', '<!-- wp:html --> <style', $content);
                    $content = str_replace('</style>', '</style><!-- /wp:html -->', $content);
                }

                if (strpos($content, '<myiframe') !== false) {
                    $content = str_replace('<myiframe>', '', $content);
                    $content = str_replace('</myiframe>', '', $content);
                }

                if (strpos($content, '<p') !== false) {
                    $content = self::ccetgb_add_class_id_attr($content, "p", "paragraph");
                    $content = preg_replace("/<\/p>/i", '</p><!-- /wp:paragraph -->', $content);
                }

                if (strpos($content, '<span') !== false) {
                    $content = preg_replace("/<span(|( [a-z][a-z0-9]*)[^>]*?(\/?))>/i", '<!-- wp:paragraph --> <p>', $content);
                    $content = preg_replace("/<\/span>/i", '</p><!-- /wp:paragraph -->', $content);
                }

                if (strpos($content, '<pre') !== false) {
                    $content = self::ccetgb_add_class_id_attr($content, "pre", "preformatted", "wp-block-preformatted");
                    $content = preg_replace("/<\/pre>/i", '</pre><!-- /wp:preformatted -->', $content);
                }

                if (strpos($content, '<h1') !== false) {
                    $content = self::ccetgb_heading_add_class_id_attr($content, "h1", "1");
                    $content = preg_replace("/<\/h1>/i", '</h1><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h2') !== false) {
                    $content = self::ccetgb_heading_add_class_id_attr($content, "h2", "2");
                    $content = preg_replace("/<\/h2>/i", '</h2><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h3') !== false) {
                    $content = self::ccetgb_heading_add_class_id_attr($content, "h3", "3");
                    $content = preg_replace("/<\/h3>/i", '</h3><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h4') !== false) {
                    $content = self::ccetgb_heading_add_class_id_attr($content, "h4", "4");
                    $content = preg_replace("/<\/h4>/i", '</h4><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h5') !== false) {
                    $content = self::ccetgb_heading_add_class_id_attr($content, "h5", "5");
                    $content = preg_replace("/<\/h5>/i", '</h5><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h6') !== false) {
                    $content = self::ccetgb_heading_add_class_id_attr($content, "h6", "6");
                    $content = preg_replace("/<\/h6>/i", '</h6><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<ul') !== false) {
                    $content = self::ccetgb_add_class_id_attr($content, "ul", "list");
                    $content = preg_replace("/<\/ul>/i", '</ul><!-- /wp:list -->', $content);

                }
                if (strpos($content, '<ol') !== false) {
                    $content = self::ccetgb_add_class_id_attr($content, "ol", "list");
                    $content = preg_replace("/<\/ol>/i", '</ol><!-- /wp:list -->', $content);
                }
                if (strpos($content, '<table') !== false) {
                    $content = self::ccetgb_add_class_id_attr($content, "table", "html");
                    $content = preg_replace("/<\/table>/i", '</table><!-- /wp:html -->', $content);
                }

                if (strpos($content, '[') !== false && strpos($content, ']') !== false) {
                    $special_chr_end = htmlentities(']<!-- /wp:shortcode -->');
                    $content = str_replace(']', $special_chr_end, $content);
                    $special_chr_start = htmlentities('<!-- wp:shortcode -->[');
                    $content = str_replace('[', $special_chr_start, $content);
                    $content = html_entity_decode($content);
                }

                if (strpos($content, '<blockquote') !== false) {
                    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                    $dom->removeChild($dom->doctype);
                    $dom->encoding = 'utf-8';

                    $blockquotes = $dom->getElementsByTagName('blockquote');
                    foreach ($blockquotes as $blockquote) {
                        $bclass = $blockquote->getAttribute('class');
                        $value = $blockquote->nodeValue;
                        $childNodes = $blockquote->childNodes;
                        foreach ($childNodes as $ch) {
                            $ch->parentNode->removeChild($ch);

                        }
                        $blockquote->nodeValue = '<p>' . $value . '</p>';
                        if (!empty($bclass)) {
                            $classToAdd = 'wp-block-quote ' . $bclass;
                        } else {
                            $classToAdd = 'wp-block-quote';
                        }
                        $attributes = $blockquote->attributes;
                        while ($attributes->length) {
                            $blockquote->removeAttribute($attributes->item(0)->name);
                        }
                        $blockquote->setAttribute('class', $classToAdd);
                        $comment = $dom->createComment(' wp:quote ');

                        $blockquote->parentNode->insertBefore($comment, $blockquote);
                    }
                    $content = $dom->saveHTML();
                    $content = str_replace('&lt;', '<', $content);
                    $content = str_replace('&gt;', '>', $content);
                    $content = str_replace('</blockquote>', '</blockquote><!-- /wp:quote -->', $content);

                }

                if (strpos($content, '<hr') !== false) {
                    $content = preg_replace('/<hr[^>]*?(\/?)>/i', '<!-- wp:separator --><hr class="wp-block-separator"/><!-- /wp:separator -->', $content);
                }

                if (strpos($content, '<img') !== false) {

                    $regex = '/src="([^"]*)"/';
                    // we want all matches

                    preg_match_all($regex, $content, $matches);

                    // reversing the matches array
                    $matches = array_reverse($matches);

                    // we've reversed the array, so index 0 returns the result
                    $img_path = $matches[0];

                    $image_urls = array();
                    $image_urls = $img_path;

                    preg_match_all('/<img[^>]*?(\/?)>/i', $content, $results);

                    $a1 = $results[0];
                    $a2 = $image_urls;

                    $min = min(count($a1), count($a2));
                    $image_urls = array_combine(array_slice($a1, 0, $min), array_slice($a2, 0, $min));
                    foreach ($image_urls as $image_url => $value) {
                        $image_class = self::ccetgb_get_class_value($image_url);
                        $alt = !empty($img_src_alt_arr[$value]) ? $img_src_alt_arr[$value] : '-';
                        $img_id = self::ccetgb_upload_remote_image_attach($value, $post_id);
                        $content = preg_replace($image_url, '!-- wp:image {"id":' . $img_id . '} --><figure class="wp-block-image ' . $image_class . '"><img src="' . esc_url($value) . '" alt="' . esc_attr($alt) . '" class="wp-image-' . esc_attr($img_id) . '"/></figure><!-- /wp:image --', $content);

                    }

                }

                $content = str_replace('<html>', '', $content);
                $content = str_replace('<body>', '', $content);
                $content = str_replace('</html>', '', $content);
                $content = str_replace('</body>', '', $content);
                $content = str_replace('&lt;', '<', $content);
                $content = str_replace('&gt;', '>', $content);

                $post_args = array(
                    'ID' => $post_id,
                    'post_content' => $content,
                );

                wp_update_post($post_args, true);
            }
        } else {
            wp_die(esc_html__('Please add some content before convert classic post content to gutenberg blocks', 'convert-classic-editor-to-gutenberg-blocks'));
        }

    }

    /**
     * @param string $image_url
     * @param int $parent_id
     * @return bool|int|WP_Error
     */
    public static function ccetgb_upload_remote_image_attach($image_url = '', $parent_id = 0)
    {
        $image = $image_url;
        $get = wp_remote_get($image);
        $type = wp_remote_retrieve_header($get, 'content-type');
        if (!$type) {
            return false;
        }

        $mirror = wp_upload_bits(basename($image), '', wp_remote_retrieve_body($get));
        $attachment = array(
            'post_title' => basename($image),
            'post_mime_type' => $type,
        );

        $media_page = self::ccetgb_get_attachment_url_by_slug(basename($image));

        if (!empty($media_page) && $media_page->ID !== '' && $media_page->ID !== '0') {
            return $media_page->ID;
        } else {
            $attach_id = wp_insert_attachment($attachment, $mirror['file'], $parent_id);
            $attach_data = wp_generate_attachment_metadata($attach_id, $mirror['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
        }

        return $attach_id;
    }

    /**
     * Find image is exist in media
     * @param string $slug
     * @return array|bool
     */
    public static function ccetgb_get_attachment_url_by_slug($slug)
    {
        $args = array(
            'post_type' => 'attachment',
            'name' => sanitize_title($slug),
            'posts_per_page' => 1,
            'post_status' => 'inherit',
        );
        $_header = get_posts($args);
        $header = $_header ? array_pop($_header) : null;
        return $header;
    }

    /**
     * @param string $content
     * @return array|bool
     */
    public static function ccetgb_is_shortcode_in_content($content = '')
    {
        preg_match_all('/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return false;
        }
        $matches = array_values(array_filter($matches));

        return $matches;
    }
}
