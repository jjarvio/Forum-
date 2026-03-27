<?php
/*
Plugin Name: Tubetus Forum Feed
Description: Näyttää phpBB viimeisimmät keskustelut Widgetinä tai Shortcodena. Hallinta: Asetukset -> Tubetus Forum.
Version: 1.3
*/

if (!defined('ABSPATH')) exit;

// 1. HALLINTAPANEELIN SKRIPTIT
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook == 'settings_page_tubetus_forum') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        add_action('admin_footer', function() {
            ?>
            <script>
            jQuery(document).ready(function($){
                $('.color-field').wpColorPicker();
            });
            </script>
            <?php
        });
    }
});

// 2. REKISTERÖIDÄÄN ASETUKSET
add_action('admin_menu', function() {
    add_options_page('Tubetus Forum Asetukset', 'Tubetus Forum', 'manage_options', 'tubetus_forum', 'tubetus_forum_options_page');
});

add_action('admin_init', function() {
    $settings = [
        'tubetus_db_host', 'tubetus_db_name', 'tubetus_db_user', 'tubetus_db_pass', 
        'tubetus_table_prefix', 'tubetus_forum_url', 'tubetus_topic_limit',
        'tubetus_outer_bg', 'tubetus_inner_bg', 'tubetus_text_color', 'tubetus_link_color', 
        'tubetus_border_color', 'tubetus_box_title', 'tubetus_author_color',
        'tubetus_font_family', 'tubetus_border_radius'
    ];
    foreach ($settings as $setting) {
        register_setting('tubetus_forum_settings_group', $setting);
    }
});

// 3. HALLINTAPANEELIN NÄKYMÄ
function tubetus_forum_options_page() {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        delete_transient('tubetus_forum_cache');
    }
    ?>
    <div class="wrap">
        <h1>Tubetus Forum - Asetukset</h1>
        <form action="options.php" method="post">
            <?php settings_fields('tubetus_forum_settings_group'); ?>
            
            <h3>1. Tietokantayhteys</h3>
            <table class="form-table">
                <tr><th>Host</th><td><input type="text" name="tubetus_db_host" value="<?php echo esc_attr(get_option('tubetus_db_host', '127.0.0.1')); ?>" class="regular-text" /></td></tr>
                <tr><th>Tietokannan nimi</th><td><input type="text" name="tubetus_db_name" value="<?php echo esc_attr(get_option('tubetus_db_name')); ?>" class="regular-text" /></td></tr>
                <tr><th>Käyttäjätunnus</th><td><input type="text" name="tubetus_db_user" value="<?php echo esc_attr(get_option('tubetus_db_user')); ?>" class="regular-text" /></td></tr>
                <tr><th>Salasana</th><td><input type="password" name="tubetus_db_pass" value="<?php echo esc_attr(get_option('tubetus_db_pass')); ?>" class="regular-text" /></td></tr>
                <tr><th>Prefix</th><td><input type="text" name="tubetus_table_prefix" value="<?php echo esc_attr(get_option('tubetus_table_prefix', 'phpbb_')); ?>" class="regular-text" /></td></tr>
            </table>

            <h3>2. Ulkoasu & Tyyli</h3>
            <table class="form-table">
                <tr><th>Foorumin URL</th><td><input type="url" name="tubetus_forum_url" value="<?php echo esc_url(get_option('tubetus_forum_url')); ?>" class="regular-text" /></td></tr>
                <tr><th>Otsikko</th><td><input type="text" name="tubetus_box_title" value="<?php echo esc_attr(get_option('tubetus_box_title', 'Forum keskustelut')); ?>" class="regular-text" /></td></tr>
                <tr><th>Fontti</th><td><input type="text" name="tubetus_font_family" value="<?php echo esc_attr(get_option('tubetus_font_family', 'inherit')); ?>" class="regular-text" placeholder="esim. 'Montserrat', sans-serif" /></td></tr>
                <tr><th>Pyöristys (px)</th><td><input type="number" name="tubetus_border_radius" value="<?php echo esc_attr(get_option('tubetus_border_radius', '15')); ?>" class="small-text" /> px</td></tr>
                <tr><th>Viestien määrä</th><td><input type="number" name="tubetus_topic_limit" value="<?php echo esc_attr(get_option('tubetus_topic_limit', 10)); ?>" class="small-text" /></td></tr>
            </table>

            <h3>3. Värit</h3>
            <table class="form-table">
                <tr><th>Ulompi tausta</th><td><input type="text" name="tubetus_outer_bg" value="<?php echo esc_attr(get_option('tubetus_outer_bg', '#f5f5f5')); ?>" class="color-field" /></td></tr>
                <tr><th>Sisempi tausta</th><td><input type="text" name="tubetus_inner_bg" value="<?php echo esc_attr(get_option('tubetus_inner_bg', '#ffffff')); ?>" class="color-field" /></td></tr>
                <tr><th>Linkit</th><td><input type="text" name="tubetus_link_color" value="<?php echo esc_attr(get_option('tubetus_link_color', '#0073ff')); ?>" class="color-field" /></td></tr>
                <tr><th>Kirjoittaja</th><td><input type="text" name="tubetus_author_color" value="<?php echo esc_attr(get_option('tubetus_author_color', '#ff0000')); ?>" class="color-field" /></td></tr>
                <tr><th>Metateksti (aika)</th><td><input type="text" name="tubetus_text_color" value="<?php echo esc_attr(get_option('tubetus_text_color', '#666666')); ?>" class="color-field" /></td></tr>
                <tr><th>Väliviiva</th><td><input type="text" name="tubetus_border_color" value="<?php echo esc_attr(get_option('tubetus_border_color', '#eeeeee')); ?>" class="color-field" /></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// 4. LOGIIKKA
function tubetus_forum_render_output() {
    $cache_key = 'tubetus_forum_cache';
    if ($cached = get_transient($cache_key)) return $cached;

    $db_host = get_option('tubetus_db_host');
    $db_name = get_option('tubetus_db_name');
    $db_user = get_option('tubetus_db_user');
    $db_pass = get_option('tubetus_db_pass');
    $table_prefix = get_option('tubetus_table_prefix', 'phpbb_');
    $forum_url = rtrim(get_option('tubetus_forum_url'), '/');
    $limit = absint(get_option('tubetus_topic_limit', 10));
    $box_title = get_option('tubetus_box_title', 'Forum keskustelut');
    
    // Tyyliasetukset
    $font_f = get_option('tubetus_font_family', 'inherit');
    $b_radius = absint(get_option('tubetus_border_radius', 15));
    $outer_bg = get_option('tubetus_outer_bg', '#f5f5f5');
    $inner_bg = get_option('tubetus_inner_bg', '#ffffff');
    $ln_c = get_option('tubetus_link_color', '#0073ff');
    $au_c = get_option('tubetus_author_color', '#ff0000');
    $tx_c = get_option('tubetus_text_color', '#666666');
    $br_c = get_option('tubetus_border_color', '#eeeeee');

    if (empty($db_name)) return "<p>Määritä tietokanta-asetukset.</p>";

    $forum_db = new wpdb($db_user, $db_pass, $db_name, $db_host);
    $forum_db->query("SET NAMES 'utf8mb4'");

    $query = "SELECT t.topic_id, t.topic_title, t.topic_last_post_time, u.username, u.user_avatar, u.user_avatar_type 
              FROM {$table_prefix}topics t 
              LEFT JOIN {$table_prefix}users u ON t.topic_last_poster_id = u.user_id 
              WHERE t.topic_visibility = 1 
              ORDER BY t.topic_last_post_time DESC 
              LIMIT {$limit}";

    $topics = $forum_db->get_results($query, ARRAY_A);
    if (empty($topics)) return "<p>Ei viestejä.</p>";

    ob_start();
    ?>
    <style>
        .forum-outer-container { 
            background-color: <?php echo esc_attr($outer_bg); ?>; 
            padding: 25px; 
            border-radius: <?php echo $b_radius; ?>px; 
            font-family: <?php echo esc_attr($font_f); ?>; 
            margin: 20px 0; 
        }
        .forum-outer-container h2.forum-main-title { margin: 0 0 20px 0; font-size: 20px; font-weight: bold; color: #000; }
        .forum-inner-box { 
            background: <?php echo esc_attr($inner_bg); ?>; 
            border-radius: <?php echo max(0, $b_radius - 5); ?>px; 
            padding: 5px 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }
        .forum-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid <?php echo esc_attr($br_c); ?>; }
        .forum-item:last-child { border-bottom: none; }
        .avatar { width: 40px; height: 40px; flex: 0 0 40px; background: #eee; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .content a { color: <?php echo esc_attr($ln_c); ?>; text-decoration: none; font-weight: 600; font-size: 15px; display: block; line-height: 1.3; }
        .meta { font-size: 12px; color: <?php echo esc_attr($tx_c); ?>; margin-top: 3px; }
        .site-author { color: <?php echo esc_attr($au_c); ?>; font-weight: bold; }
    </style>

    <div class="forum-outer-container">
        <h2 class="forum-main-title"><?php echo esc_html($box_title); ?></h2>
        <div class="forum-inner-box">
            <?php foreach ($topics as $topic): ?>
                <div class="forum-item">
                    <div class="avatar">
                        <?php 
                        $avatar_url = '';
                        if ($topic['user_avatar']) {
                            if ($topic['user_avatar_type'] == 1 || strpos($topic['user_avatar_type'], 'upload') !== false) {
                                $avatar_url = $forum_url . '/download/file.php?avatar=' . $topic['user_avatar'];
                            } elseif (strpos($topic['user_avatar'], 'http') === 0) {
                                $avatar_url = $topic['user_avatar'];
                            } else {
                                $avatar_url = $forum_url . '/images/avatars/gallery/' . $topic['user_avatar'];
                            }
                        }
                        if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="">
                        <?php else: ?>
                            <span style="color:#ccc; font-weight:bold; font-size:18px;"><?php echo esc_html(substr($topic['username'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="content">
                        <a href="<?php echo esc_url($forum_url.'/viewtopic.php?t=' . $topic['topic_id']); ?>">
                            <?php echo esc_html($topic['topic_title']); ?>
                        </a>
                        <div class="meta">
                            Viimeisin: <span class="site-author"><?php echo esc_html($topic['username']); ?></span> 
                            • <?php echo human_time_diff($topic['topic_last_post_time'], current_time('timestamp')); ?> sitten
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    $output = ob_get_clean();
    set_transient($cache_key, $output, 60);
    return $output;
}

// 5. WIDGET-LUOKKA
class Tubetus_Forum_Widget extends WP_Widget {
    function __construct() {
        parent::__construct('tubetus_forum_widget', 'Tubetus Forum Feed', array('description' => 'Näyttää foorumin uusimmat viestit'));
    }
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo tubetus_forum_render_output();
        echo $args['after_widget'];
    }
}

add_action('widgets_init', function() {
    register_widget('Tubetus_Forum_Widget');
});

add_shortcode('forum_latest', 'tubetus_forum_render_output');