<?php
/**
 * Plugin Name: Custom Draft Function
 * Description: Runs a custom function on draft posts.
 * Version: 1.0
 * Author: mzughbor
 */

function remove_custom_paragraphs($content) {

    // Define the ID of the div you want to remove
    $div_id_to_remove = 'After_F_Paragraph';

    // Create a DOMDocument object to parse the post content
    $dom = new DOMDocument();
    //$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    // Load the content using UTF-8 encoding
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Find the div with the specified ID
    $divToRemove = $dom->getElementById($div_id_to_remove);

    // If the div is found, remove it
    if ($divToRemove) {
        $divParent = $divToRemove->parentNode;
        $divParent->removeChild($divToRemove);
    }

    // Save the modified HTML back to the post content
    $content = $dom->saveHTML();
    
    // Pattern to match the unwanted paragraph with a strong tag    
    // Array of unwanted patterns
    $unwanted_patterns = array(
        '/أقرأ ايضًا:/u',
        '/أخبار متعلقة/u',
        '/طالع أيضًا:/u',
        '/أخبار متعلقة/u',
        '/أقرأ ايضًا:/u',
        '/طالع أيضًا:/u',
    );
    //  sometimes there is two ones in articles '/أخبار متعلقة/u',

    // Loop through patterns and remove them from content
    foreach ($unwanted_patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }

    // Pattern to match paragraphs with links
    //$pattern_with_links = '/<p>.*<a.*<\/p>/';
    
    // Pattern to match paragraphs or h3 elements with links
    $pattern_with_links = '/<(p|h3)>.*<a.*<\/(p|h3)>/u';

    // Find paragraphs or h3 elements with links
    preg_match_all($pattern_with_links, $content, $matches);
    
    // If there are paragraphs with links
    if (!empty($matches[0])) {
        foreach ($matches[0] as $match) {
            // Remove the paragraph
            $content = str_replace($match, '', $content);
            
            // If the removed paragraph doesn't have a link anymore, stop
            if (!strpos($match, '<a')) {
                break;
            }
        }
    }
    
    return $content;
}

function schedule_draft_function() {
    if (!wp_next_scheduled('custom_draft_function_event')) {
        wp_schedule_event(time(), 'ten_minutes', 'custom_draft_function_event');
    }
}
add_action('wp', 'schedule_draft_function');

function custom_draft_function() {

    // Retrieve up to 5 draft posts
    $args = array(
        'post_type' => 'post',
        'post_status' => 'draft',
        'posts_per_page' => 5,
    );

    $draft_posts = get_posts($args);

    foreach ($draft_posts as $post) {
        $content = remove_custom_paragraphs($post->post_content);
        wp_update_post(array(
            'ID' => $post->ID,
            'post_content' => $content,
        ));
    }
}
add_action('custom_draft_function_event', 'custom_draft_function');

function ten_minutes_interval($schedules) {
    $schedules['ten_minutes'] = array(
        'interval' => 600, // 10 minutes in seconds
        'display' => __('Every 10 Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'ten_minutes_interval');
