Reading time calculations plugin

Features:

- Admin Settings Page
Located in the "Settings" tab of WP Dashboard and has settings for:
1) No. of Words Per Minute
2) Choosing the Post types (from All post types in the system)
3) How to round reading time (if 4.3 round-up = 5; round-down = 4)
4) Set shortcode label - sets h3 text for reading time representation in the shortcode
5) Content meta fields: multi-select from all post meta fields in the system. Chosen meta fields support shortcode.
6) Clear Previous calculations - recalculate all reading time values

Reading time recalculation and saving is done on
- creating and updating of post
- when shortcode ( [reading_time] ) or functions "the_reading_time" and "get_reading_time" is used
- when the admin changed the "No. of Words Per Minute" setting

Embedding the “Reading Time” value in a theme:
1) Using the shortcode "[reading_time]" in post content or text meta field
2) By calling a php function named "the_reading_time( $post_id )" (optional parameter  $post_id - ID of post)
3) By echoing the return value of a php function named "get_reading_time(  $post_id )" (optional parameter  $post_id - ID of post)
4) If post type is not selected - functionality is switched off
5) When embedded with shortcode - value is wrapped in HTML h3 + p tags
   -- h3 has CSS class "default-heading-class" and can be filtered using  't_shortcode_reading_heading_class' filter
   -- p has CSS class "default-text-class" and can be filtered using  't_shortcode_reading_text_class' filter

The plugin can be translated.