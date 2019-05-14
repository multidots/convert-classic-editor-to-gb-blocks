<?php

Class CCETGB_Convert_Classic_to_GB_Blocks {

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
            $post_name = $post->post_name.'-with-gb';
            $post_title = $post->post_title.' with GB';
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status'    => $post->ping_status,
                'post_author'    => $new_post_author,
                'post_content'   => $post->post_content,
                'post_excerpt'   => $post->post_excerpt,
                'post_name'      => $post_name,
                'post_parent'    => $post->post_parent,
                'post_password'  => $post->post_password,
                'post_status'    => 'draft',
                'post_title'     => $post_title,
                'post_type'      => $post->post_type,
                'to_ping'        => $post->to_ping,
                'menu_order'     => $post->menu_order
            );

            $new_post_id = wp_insert_post($args);
            self::ccetgb_convert_classic_post_to_gb_blocks($post->post_content, $new_post_id);

            $taxonomies = get_object_taxonomies($post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
            }

            $meta_blacklist = array("_wp_page_template", "_edit_lock", "_edit_last");
            $post_meta_info = get_post_meta($post_id, false, true);

            foreach ($meta_blacklist as $blacklist) {
                unset($post_meta_info[$blacklist]);
            }

            foreach ($post_meta_info as $meta_key => $meta_value) {
                add_post_meta($new_post_id, $meta_key, addslashes($meta_value[0]));
            }

            $new_post_editor_url = add_query_arg( 'success', '1', admin_url('post.php?action=edit&post=' . $new_post_id ) );
            wp_safe_redirect( $new_post_editor_url );
            exit();

        } else {
            wp_die(esc_html__('Post creation failed, could not find original post: ' . $post_id, 'convert-classic-editor-to-gutenberg-blocks'));
        }
    }

    /**
     * Get style attribute of given tag string
     * @param string $string
     */
    function ccetgb_get_style_value($string=''){
        preg_match('/style="(.+?)"/', $string, $input);
        return $input[1];
    }
    
    /**
     * Get Id attribute of given tag string
     * @param string $string
     */
    function ccetgb_get_id_value($string=''){
        preg_match('/id="(.+?)"/', $string, $input);
        return $input[1];
    }
    /**
     * Get custom attribute's of given tag string
     * @param string $string
     */
    function ccetgb_get_custom_attr($string=''){
    
        $common_tags = array('id','class','style','type','value','name','src','href',
                             );
        $pattern = '/(\w+-\w+|\w+)=[\'"]([^\'"]*)/';
        preg_match_all($pattern,$string,$matches,PREG_SET_ORDER);
    
        $result = [];
        foreach($matches as $match){
            $attrName = $match[1];
    
            //parse the string value into an integer if it's numeric,
            // leave it as a string if it's not numeric,
            $attrValue = is_numeric($match[2])? (int)$match[2]: trim($match[2]);
    
            $result[$attrName] = $attrValue; //add match to results
        }
        $custome_attributes = array_values(array_diff(array_keys($result),$common_tags));
        $cust = array();
        foreach($custome_attributes as $val){
            $cust[$val] = $result[$val];
        }
    
        return $cust;
    
    }
    
    /**
     * Convert headings tags to gutenberg block with style,id and custom attributes
     * @param string $seperator
     * @param string $content
     * @param string $level
     */
    function ccetgb_convert_heading_tags($seperator='',$content='',$level=''){
        
        $exp_separator = "<".$seperator;
        $str = explode($exp_separator,$content);
        $i = 0;
        $tags = array();
        foreach ( $str as $s ) {
                if($s !== '' ) {
                $tags[$i] = $exp_separator.$s;
                }
                $i++;    
        }
        $temp_string_store = '';
            foreach($tags as $tag){
               
                $style = self::ccetgb_get_style_value($tag);
                $id = self::ccetgb_get_id_value($tag);
                $custom_attr = self::ccetgb_get_custom_attr($tag);
                $custom_attr_string_json = '';
                $c_a_s = '';
                $i = 1;
                if(is_array($custom_attr) && !empty($custom_attr)){
                    foreach($custom_attr as $key => $ca){
                    $custom_attr_string_json .= '"'.$key.'"="'.$ca.'" '; 
                    $c_a_s .=  $key.'='.$ca;
                    if(count($custom_attr) !== $i) {
                        $c_a_s .=  ',';
                    }
                    $tag = str_replace($key.'="'.$ca.'"',"",$tag);
                    $i++;
    
    
                    }
                }  
                
                $i = 0;
    
                 
            $tag_str = str_replace('<'.$seperator,'<'.$seperator.' custom_attributes="'.$c_a_s.'"',$tag);
          
            if($style!=='' || $id!=='' || $c_a_s !=='') {
    
                $tag_str = str_replace("<".$seperator, '<!-- wp:heading {"level":'.$level.',"style":"'.$style.'","id":"'.$id.'","custom_attributes":"'.$c_a_s.'"} -->'.'<'.$seperator, $tag_str);
                
            }else{
                $tag_str = str_replace("<".$seperator, '<!-- wp:heading {"level":'.$level.'} -->'.'<'.$seperator, $tag_str);
            }
            
            $tag_str = str_replace("</".$seperator.">", '</'.$seperator.'><!-- /wp:heading -->',$tag_str);
            
            $temp_string_store .= $tag_str;
            
            }
            
            return $temp_string_store;
    }
    

    /**
     * Convert Table tag to gutenberg block with style,id and custom attributes
     * @param string $seperator
     * @param string $content
     */

    function ccetgb_convert_table($seperator='',$content=''){
        
        $exp_separator = "<".$seperator;
        $str = explode($exp_separator,$content);
        $i = 0;
        $tags = array();
        foreach ( $str as $s ) {
                if($s !== '' ) {
                $tags[$i] = $exp_separator.$s;
                }
                $i++;    
        }
        $temp_string_store = '';
            foreach($tags as $tag){
               
                $style = self::ccetgb_get_style_value($tag);
                $id = self::ccetgb_get_id_value($tag);
                $custom_attr = self::ccetgb_get_custom_attr($tag);
                $custom_attr_string_json = '';
                $c_a_s = '';
                $i = 1;
                if(is_array($custom_attr) && !empty($custom_attr)){
                    foreach($custom_attr as $key => $ca){
                    $custom_attr_string_json .= '"'.$key.'"="'.$ca.'" '; 
                    $c_a_s .=  $key.'='.$ca;
                    if(count($custom_attr) !== $i) {
                        $c_a_s .=  ',';
                    }
                    $tag = str_replace($key.'="'.$ca.'"',"",$tag);
                    $i++;
    
    
                    }
                }  
                
                $i = 0;
    
                 
            $tag_str = str_replace('<'.$seperator,'<'.$seperator.' class="wp-block-table" custom_attributes="'.$c_a_s.'"',$tag);
         
            if($style!=='' || $id!=='' || $c_a_s !=='') {
    
                $tag_str = str_replace("<".$seperator, '<!-- wp:table {"style":"'.$style.'","id":"'.$id.'","custom_attributes":"'.$c_a_s.'"} -->'.'<'.$seperator, $tag_str);
                
            }else{
                $tag_str = str_replace("<".$seperator, '<!-- wp:table  -->'.'<'.$seperator, $tag_str);
            }
            
            $tag_str = str_replace("</".$seperator.">", '</'.$seperator.'><!-- /wp:table -->',$tag_str);
           
            $temp_string_store .= $tag_str;
            
            }
            
            return $temp_string_store;
    }


    /**
     * Convert classic element to block
     * @param string $content
     * @param int $post_id
     */
    public static function ccetgb_convert_classic_post_to_gb_blocks($content = '', $post_id = 0) {

        if (!is_plugin_active('multipurpose-block/index.php')) {
            $error_msg = sprintf('This plugin required <a href="https://wordpress.org/plugins/multipurpose-block/">Multipurpose Gutenberg Block</a> plugin to work correctly.');
            $elment_array = array('a' => array('href' => array()));
            wp_die(wp_kses($error_msg, $elment_array));
        }

        if (!empty($content) && 0 !== $post_id) {

            $short_code_arr = self::ccetgb_is_shortcode_in_content($content);

            if (strpos($content, '<!-- wp:') === false) {

                if (strpos($content, '&lt;p&gt;') === false && strpos($content, "<p" === false)) {
                    $content = wpautop($content, false);
                } else {
                    $content = wpautop($content, false);
                }

                $dom = new DOMDocument(null, 'UTF-8');
                $dom->encoding = 'utf-8';
                libxml_use_internal_errors(true);
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->encoding = 'utf-8';

                // Section tag Dom Manuplation
                $sections = $dom->getElementsByTagName('section');
                foreach ($sections as $section) {
                    $sclass = $section->getAttribute('class');
                    if (!empty($sclass)) {
                        $classToAdd = 'wp-block-md-multipurpose-block is-block-center ' . $sclass;
                    } else {
                        $classToAdd = 'wp-block-md-multipurpose-block is-block-center';
                    }
                }
                $content = $dom->saveHTML();
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->encoding = 'utf-8';
                unset($sections);

                // Header tag Dom Manuplation
                $headers = $dom->getElementsByTagName('header');
                foreach ($headers as $header) {
                    $sclass = $header->getAttribute('class');
                    if (!empty($sclass)) {
                        $classToAdd = 'wp-block-md-multipurpose-block is-block-center ' . $sclass;
                    } else {
                        $classToAdd = 'wp-block-md-multipurpose-block is-block-center';
                    }
                    $header->setAttribute('class', $classToAdd);
                }
                $content = $dom->saveHTML();
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
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

                $divs = $dom->getElementsByTagName('div');
                foreach ($divs as $div) {
                    $dclass = $div->getAttribute('class');
                    $div_id = $div->getAttribute('id');
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

                        if ( '#text' === $nodeName && !empty(trim($nodeValue))) {
                            $element = $dom->createElement('p', $nodeValue);
                            $div->replaceChild($element, $childNode);
                        }

                        if ( 'span' === $nodeName && !empty($parentNodeName) && 'div' === $parentNodeName) {
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
                        $classToAdd = 'wp-block-md-multipurpose-block is-block-center ' . $dclass;
                    } else {
                        $classToAdd = 'wp-block-md-multipurpose-block is-block-center';
                    }

                    $div->setAttribute('class', $classToAdd);

                    if (!empty($div_id)) {
                        $comment = $dom->createComment(' wp:md/multipurpose-block {"elementID": "' . $div_id . '"} ');
                    } else {
                        $comment = $dom->createComment(' wp:md/multipurpose-block ');
                    }
                    $div->parentNode->insertBefore($comment, $div);
                }

                $content = $dom->saveHTML();

                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->encoding = 'utf-8';
                unset($divs);

                // Table tag Dom Manuplation Table tag converted to the classic table
                $tables = $dom->getElementsByTagName('table');
                foreach ($tables as $table) {
                    $sclass = $table->getAttribute('class');
                    $comment = $dom->createComment(' wp:html ');
                    $table->parentNode->insertBefore($comment, $table);
                    $table->setAttribute('class', $classToAdd);
                }
                $content = $dom->saveHTML();

                $contentArr = explode('<body>', $content);

                if (count($contentArr) > 1) {
                    $content = $contentArr[1];
                    $contentArr = explode('</body>', $content);
                    $content = $contentArr[0];
                }
                if (strpos($content, '<div') !== false) {
                    $content = str_replace('</div>', '</div><!-- /wp:md/multipurpose-block -->', $content);
                }

                if (strpos($content, '<section') !== false) {
                    $content = str_replace('<section', '<!-- wp:md/multipurpose-block {"ElementTag":"section"} --> <section', $content);
                    $content = str_replace('</section>', '</section><!-- /wp:md/multipurpose-block -->', $content);
                }

                if (strpos($content, '<header') !== false) {
                    $content = str_replace('<header', '<!-- wp:md/multipurpose-block {"ElementTag":"header"} --> <header', $content);
                    $content = str_replace('</header>', '</header><!-- /wp:md/multipurpose-block -->', $content);
                }

                if (strpos($content, '<script') !== false) {
                    $content = str_replace('<script', '<!-- wp:html --> <script', $content);
                    $content = str_replace('</script>', '</script><!-- /wp:html -->', $content);
                }

                if (strpos($content, '<style') !== false) {
                    $content = str_replace('<style', '<!-- wp:html --> <style', $content);
                    $content = str_replace('</style>', '</style><!-- /wp:html -->', $content);
                }

                if (strpos($content, '<iframe') !== false) {
                    $content = str_replace('<iframe', '<!-- wp:html --> <iframe', $content);
                    $content = str_replace('</iframe>', '</iframe><!-- /wp:html -->', $content);
                }

                if (strpos($content, '<p') !== false) {
                    $content = str_replace('<p', '<!-- wp:paragraph --> <p', $content);
                    $content = str_replace('</p>', '</p><!-- /wp:paragraph -->', $content);
                }

                if (strpos($content, '<pre') !== false) {
                    $content = str_replace('<pre', '<!-- wp:preformatted --> <pre', $content);
                    $content = str_replace('</pre>', '</p><!-- /wp:preformatted -->', $content);
                }

                if (strpos($content, '<h1') !== false) {

                    $converted_tag = self::ccetgb_convert_heading_tags("h1",$content,'1');

                    $content = $converted_tag;
                }
               
                if (strpos($content, '<h2') !== false) {

                    $converted_tag = self::ccetgb_convert_heading_tags("h2",$content,'2');

                    $content = $converted_tag;
                }
                if (strpos($content, '<h3') !== false) {
                    $converted_tag = self::ccetgb_convert_heading_tags("h3",$content,'3');

                    $content = $converted_tag;
}
                if (strpos($content, '<h4') !== false) {

                    $converted_tag = self::ccetgb_convert_heading_tags("h4",$content,'4');

                    $content = $converted_tag;
                }
                if (strpos($content, '<h5') !== false) {

                    $converted_tag = self::ccetgb_convert_heading_tags("h5",$content,'5');

                    $content = $converted_tag;
                }
                if (strpos($content, '<h6') !== false) {

                    $converted_tag = self::ccetgb_convert_heading_tags("h6",$content,'6');

                    $content = $converted_tag;
                }
                if (strpos($content, '<ul') !== false) {
                    $content = str_replace('<ul', '<!-- wp:list --><ul', $content);
                    $content = str_replace("</ul>", '</ul><!-- /wp:list -->', $content);
                }
                if (strpos($content, '<ol') !== false) {
                    $content = str_replace('<ol', '<!-- wp:list --><ul', $content);
                    $content = str_replace("</ol>", '</ul><!-- /wp:list -->', $content);
                }
                if (strpos($content, '<table ') !== false) {
                      
                    $content = str_replace('<!-- wp:html -->', '', $content);
                    $converted_table = self::ccetgb_convert_table("table",$content);
                    $content = $converted_table;
                }
                if (strpos($content, '[') !== false && strpos($content, ']') !== false) {
                    $content = preg_replace('/\[ ?/', '<!-- wp:shortcode -->[', $content);
                    $content = preg_replace('/]/', ']<!-- /wp:shortcode -->', $content);
                }
                if (strpos($content, '<blockquote') !== false) {
                    $content = str_replace('<blockquote', '<!-- wp:quote --><blockquote class="wp-block-quote"><p>', $content);
                    $content = str_replace("</blockquote>", '</p></blockquote><!-- /wp:quote -->', $content);
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
                    $image_urls = array_combine($a1, $a2);
                    foreach ($image_urls as $image_url => $value) {
                        $alt = !empty($img_src_alt_arr[$value]) ? $img_src_alt_arr[$value] : '-';
                        $img_id = self::ccetgb_upload_remote_image_attach($value, $post_id);
                        $content = preg_replace($image_url, '!-- wp:image {"id":' . $img_id . '} --><figure class="wp-block-image"><img src="' . $value . '" alt="' . $alt . '" class="wp-image-' . $img_id . '"/></figure><!-- /wp:image --', $content);
                    }
                }

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
    public static function ccetgb_upload_remote_image_attach($image_url = '', $parent_id = 0 ){
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

        $attach_id = wp_insert_attachment($attachment, $mirror['file'], $parent_id);
        $attach_data = wp_generate_attachment_metadata($attach_id, $mirror['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    /**
     * @param string $content
     * @return array|bool
     */
    public static function ccetgb_is_shortcode_in_content($content = ''){
        preg_match_all('/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return false;
        }
        $matches = array_values(array_filter($matches));

        return $matches;
    }
}