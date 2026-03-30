<?php 
/* Plugin Name: Tubetus Forum Feed 
Description: Versio 3.8: Kaikki kentät palautettu + kohdan 3 tekstikentille lisätty omat värivalitsimet. 
Version: 1.0
Author: Tietokettu
*/ 

if (!defined('ABSPATH')) exit; 

// 1. ADMIN SKRIPTIT
add_action('admin_enqueue_scripts', function($hook) { 
    if ($hook == 'settings_page_tubetus_forum') { 
        wp_enqueue_style('wp-color-picker'); 
        wp_enqueue_script('wp-color-picker'); 
        add_action('admin_footer', function() { 
            ?> 
            <script> 
            jQuery(document).ready(function($){ $('.color-field').wpColorPicker(); }); 
            </script> 
            <style>.iris-picker { z-index: 100; }</style>
            <?php 
        }); 
    } 
}); 

// 2. ASETUSTEN REKISTERÖINTI
add_action('admin_init', function() { 
    $settings = [ 
        'tubetus_db_host', 'tubetus_db_name', 'tubetus_db_user', 'tubetus_db_pass', 'tubetus_table_prefix', 
        'tubetus_forum_url', 'tubetus_topic_limit', 'tubetus_outer_bg', 'tubetus_inner_bg', 
        'tubetus_text_color', 'tubetus_link_color', 'tubetus_border_color', 'tubetus_box_title', 
        'tubetus_author_color', 'tubetus_font_family', 'tubetus_border_radius', 'tubetus_title_align', 
        'tubetus_box_style', 'tubetus_outer_border', 'tubetus_outer_shadow', 'tubetus_inner_border', 'tubetus_inner_shadow',
        'tubetus_outer_border_clr', 'tubetus_outer_shadow_clr', 'tubetus_inner_border_clr', 'tubetus_inner_shadow_clr'
    ]; 
    foreach ($settings as $setting) register_setting('tubetus_forum_settings_group', $setting); 
}); 

add_action('admin_menu', function() { 
    add_options_page('Tubetus Forum Asetukset', 'Tubetus Forum', 'manage_options', 'tubetus_forum', 'tubetus_forum_options_page'); 
}); 

// 3. HALLINTAPANEELIN NÄKYMÄ
function tubetus_forum_options_page() { 
    if (isset($_GET['settings-updated'])) delete_transient('tubetus_forum_cache'); 
    ?> 
    <div class="wrap"> 
        <h1>Tubetus Forum - Asetukset</h1> 
        <form action="options.php" method="post"> 
            <?php settings_fields('tubetus_forum_settings_group'); ?> 
             
            <h3>1. Tietokantayhteys (mysqli)</h3> 
            <table class="form-table"> 
                <tr><th>Host</th><td><input type="text" name="tubetus_db_host" value="<?php echo esc_attr(get_option('tubetus_db_host', '127.0.0.1')); ?>" class="regular-text" /></td></tr> 
                <tr><th>Tietokannan nimi</th><td><input type="text" name="tubetus_db_name" value="<?php echo esc_attr(get_option('tubetus_db_name')); ?>" class="regular-text" /></td></tr> 
                <tr><th>Käyttäjätunnus</th><td><input type="text" name="tubetus_db_user" value="<?php echo esc_attr(get_option('tubetus_db_user')); ?>" class="regular-text" /></td></tr> 
                <tr><th>Salasana</th><td><input type="password" name="tubetus_db_pass" value="<?php echo esc_attr(get_option('tubetus_db_pass')); ?>" class="regular-text" /></td></tr> 
                <tr><th>Taulun etuliite (Prefix)</th><td><input type="text" name="tubetus_table_prefix" value="<?php echo esc_attr(get_option('tubetus_table_prefix', 'phpbbfp_')); ?>" class="regular-text" /></td></tr> 
            </table> 

            <h3>2. Ulkoasu & Yleiset</h3> 
            <table class="form-table"> 
                <tr><th>Foorumin URL</th><td><input type="url" name="tubetus_forum_url" value="<?php echo esc_url(get_option('tubetus_forum_url')); ?>" class="regular-text" /></td></tr> 
                <tr><th>Boksin otsikko</th><td><input type="text" name="tubetus_box_title" value="<?php echo esc_attr(get_option('tubetus_box_title', 'Forum keskustelut')); ?>" class="regular-text" /></td></tr> 
                <tr><th>Otsikon tasaus</th><td>
                    <select name="tubetus_title_align">
                        <option value="left" <?php selected(get_option('tubetus_title_align', 'left'), 'left'); ?>>Vasen</option>
                        <option value="center" <?php selected(get_option('tubetus_title_align'), 'center'); ?>>Keskitetty</option>
                        <option value="right" <?php selected(get_option('tubetus_title_align'), 'right'); ?>>Oikea</option>
                    </select>
                </td></tr>
                <tr><th>Fontti (CSS family)</th><td><input type="text" name="tubetus_font_family" value="<?php echo esc_attr(get_option('tubetus_font_family', 'inherit')); ?>" class="regular-text" /></td></tr>
                <tr><th>Pyöristys (px)</th><td><input type="number" name="tubetus_border_radius" value="<?php echo esc_attr(get_option('tubetus_border_radius', '24')); ?>" class="small-text" /> px</td></tr> 
                <tr><th>Viestien määrä</th><td><input type="number" name="tubetus_topic_limit" value="<?php echo esc_attr(get_option('tubetus_topic_limit', 10)); ?>" class="small-text" /> kpl</td></tr>
            </table> 

            <h3>3. Reunukset ja Varjot (Tyyli + Väri)</h3>
            <table class="form-table">
                <tr>
                    <th>Ulomman laatikon reuna</th>
                    <td>
                        <input type="text" name="tubetus_outer_border" value="<?php echo esc_attr(get_option('tubetus_outer_border', 'none')); ?>" placeholder="esim. 1px solid" class="regular-text" style="width:150px;" />
                        <input type="text" name="tubetus_outer_border_clr" value="<?php echo esc_attr(get_option('tubetus_outer_border_clr', '#e2e8f0')); ?>" class="color-field" />
                    </td>
                </tr>
                <tr>
                    <th>Ulomman laatikon varjo</th>
                    <td>
                        <input type="text" name="tubetus_outer_shadow" value="<?php echo esc_attr(get_option('tubetus_outer_shadow', '0 10px 30px')); ?>" placeholder="esim. 0 4px 6px" class="regular-text" style="width:150px;" />
                        <input type="text" name="tubetus_outer_shadow_clr" value="<?php echo esc_attr(get_option('tubetus_outer_shadow_clr', 'rgba(0,0,0,0.05)')); ?>" class="color-field" />
                    </td>
                </tr>
                <tr>
                    <th>Sisemmän laatikon reuna</th>
                    <td>
                        <input type="text" name="tubetus_inner_border" value="<?php echo esc_attr(get_option('tubetus_inner_border', '1px solid')); ?>" placeholder="esim. 1px solid" class="regular-text" style="width:150px;" />
                        <input type="text" name="tubetus_inner_border_clr" value="<?php echo esc_attr(get_option('tubetus_inner_border_clr', '#e2e8f0')); ?>" class="color-field" />
                    </td>
                </tr>
                <tr>
                    <th>Sisemmän laatikon varjo</th>
                    <td>
                        <input type="text" name="tubetus_inner_shadow" value="<?php echo esc_attr(get_option('tubetus_inner_shadow', 'none')); ?>" placeholder="esim. 0 2px 4px" class="regular-text" style="width:150px;" />
                        <input type="text" name="tubetus_inner_shadow_clr" value="<?php echo esc_attr(get_option('tubetus_inner_shadow_clr', 'rgba(0,0,0,0.02)')); ?>" class="color-field" />
                    </td>
                </tr>
            </table>

            <h3>4. Värit</h3> 
            <table class="form-table"> 
                <tr><th>Ulompi taustaväri</th><td><input type="text" name="tubetus_outer_bg" value="<?php echo esc_attr(get_option('tubetus_outer_bg', '#f8fafc')); ?>" class="color-field" /></td></tr> 
                <tr><th>Sisempi taustaväri</th><td><input type="text" name="tubetus_inner_bg" value="<?php echo esc_attr(get_option('tubetus_inner_bg', '#ffffff')); ?>" class="color-field" /></td></tr> 
                <tr><th>Linkkien väri</th><td><input type="text" name="tubetus_link_color" value="<?php echo esc_attr(get_option('tubetus_link_color', '#0073ff')); ?>" class="color-field" /></td></tr> 
                <tr><th>Kirjoittajan väri</th><td><input type="text" name="tubetus_author_color" value="<?php echo esc_attr(get_option('tubetus_author_color', '#ff0000')); ?>" class="color-field" /></td></tr> 
                <tr><th>Metatekstin väri</th><td><input type="text" name="tubetus_text_color" value="<?php echo esc_attr(get_option('tubetus_text_color', '#666666')); ?>" class="color-field" /></td></tr>
                <tr><th>Viestien väliviiva</th><td><input type="text" name="tubetus_border_color" value="<?php echo esc_attr(get_option('tubetus_border_color', '#f1f5f9')); ?>" class="color-field" /></td></tr> 
            </table> 
            <?php submit_button(); ?> 
        </form> 
    </div> 
    <?php 
} 

// 4. LATAUSLOGIIKKA
function tubetus_forum_render_output() { 
    if ($cached = get_transient('tubetus_forum_cache')) return $cached; 

    $db_host = get_option('tubetus_db_host'); 
    $db_name = get_option('tubetus_db_name'); 
    $db_user = get_option('tubetus_db_user'); 
    $db_pass = get_option('tubetus_db_pass'); 
    $table_prefix = get_option('tubetus_table_prefix', 'phpbbfp_'); 
    $forum_url = rtrim(get_option('tubetus_forum_url'), '/'); 
    $limit = absint(get_option('tubetus_topic_limit', 10)); 
     
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) return "";
    $mysqli->set_charset("utf8mb4");

    $query = "SELECT t.topic_id, t.topic_title, t.topic_time, u.username, u.user_avatar, u.user_avatar_type 
              FROM {$table_prefix}topics t LEFT JOIN {$table_prefix}users u ON t.topic_poster = u.user_id 
              WHERE t.topic_visibility = 1 ORDER BY t.topic_time DESC LIMIT {$limit}";

    $result = $mysqli->query($query);
    if (!$result || $result->num_rows == 0) return ""; 

    $b_radius = absint(get_option('tubetus_border_radius', 24));
    
    // CSS-Yhdistely (Teksti + Värivalitsin)
    $outer_border = get_option('tubetus_outer_border', 'none') . ' ' . get_option('tubetus_outer_border_clr', '#e2e8f0');
    $outer_shadow = get_option('tubetus_outer_shadow', '0 10px 30px') . ' ' . get_option('tubetus_outer_shadow_clr', 'rgba(0,0,0,0.05)');
    $inner_border = get_option('tubetus_inner_border', '1px solid') . ' ' . get_option('tubetus_inner_border_clr', '#e2e8f0');
    $inner_shadow = get_option('tubetus_inner_shadow', 'none') . ' ' . get_option('tubetus_inner_shadow_clr', 'rgba(0,0,0,0.02)');

    ob_start(); 
    ?> 
    <style> 
        .forum-outer-container { 
            font-family: <?php echo esc_attr(get_option('tubetus_font_family', 'inherit')); ?>; 
            margin: 20px 0; box-sizing: border-box;
            background-color: <?php echo esc_attr(get_option('tubetus_outer_bg', '#f8fafc')); ?>;
            padding: 24px; border-radius: <?php echo $b_radius; ?>px;
            border: <?php echo esc_attr($outer_border); ?> !important;
            box-shadow: <?php echo esc_attr($outer_shadow); ?> !important;
        } 
        .forum-main-title { 
            margin: 0 0 20px 0; font-size: 20px; font-weight: 800; color: #000; 
            text-align: <?php echo esc_attr(get_option('tubetus_title_align', 'center')); ?>; letter-spacing: -0.5px;
        } 
        .forum-inner-box {  
            background: <?php echo esc_attr(get_option('tubetus_inner_bg', '#ffffff')); ?>;  
            border-radius: <?php echo max(0, $b_radius - 8); ?>px;  
            padding: 5px 20px;  
            border: <?php echo esc_attr($inner_border); ?> !important;
            box-shadow: <?php echo esc_attr($inner_shadow); ?> !important;  
            box-sizing: border-box;
        } 
        .forum-item { display: flex; align-items: center; gap: 15px; padding: 18px 0; border-bottom: 1px solid <?php echo esc_attr(get_option('tubetus_border_color', '#f1f5f9')); ?>; } 
        .forum-item:last-child { border-bottom: none; } 
        .avatar { width: 45px; height: 45px; flex: 0 0 45px; background: #f0f0f0; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; } 
        .avatar img { width: 100%; height: 100%; object-fit: cover; } 
        .content a { color: <?php echo esc_attr(get_option('tubetus_link_color', '#0073ff')); ?>; text-decoration: none; font-weight: 700; font-size: 16px; line-height: 1.3; display: block; } 
        .meta { font-size: 13px; color: <?php echo esc_attr(get_option('tubetus_text_color', '#666666')); ?>; margin-top: 4px; } 
        .site-author { color: <?php echo esc_attr(get_option('tubetus_author_color', '#ff0000')); ?>; font-weight: bold; } 
    </style> 

    <div class="forum-outer-container"> 
        <h2 class="forum-main-title"><?php echo esc_html(get_option('tubetus_box_title', 'Forum keskustelut')); ?></h2> 
        <div class="forum-inner-box"> 
            <?php while ($topic = $result->fetch_assoc()): ?> 
                <div class="forum-item"> 
                    <div class="avatar"> 
                        <?php 
                        $avatar_img = ''; $user_avatar = $topic['user_avatar']; $avatar_type = $topic['user_avatar_type'];
                        if ($user_avatar) {
                            if ($avatar_type == 1 || strpos($avatar_type, 'upload') !== false) $avatar_img = $forum_url . '/download/file.php?avatar=' . $user_avatar;
                            elseif ($avatar_type == 2 || strpos($avatar_type, 'remote') !== false) $avatar_img = $user_avatar;
                            else $avatar_img = $forum_url . '/images/avatars/gallery/' . $user_avatar;
                        }
                        if ($avatar_img): ?> <img src="<?php echo esc_url($avatar_img); ?>"> 
                        <?php else: ?> <span style="color:#bbb; font-weight:bold; font-size:18px;"><?php echo esc_html(substr($topic['username'], 0, 1)); ?></span> <?php endif; ?>
                    </div> 
                    <div class="content"> 
                        <a href="<?php echo esc_url($forum_url.'/viewtopic.php?t=' . $topic['topic_id']); ?>"><?php echo esc_html($topic['topic_title']); ?></a> 
                        <div class="meta">Kirjoittaja: <span class="site-author"><?php echo esc_html($topic['username']); ?></span> • <?php echo human_time_diff($topic['topic_time'], current_time('timestamp')); ?> sitten</div> 
                    </div> 
                </div> 
            <?php endwhile; ?> 
        </div> 
    </div> 
    <?php 
    $output = ob_get_clean(); 
    set_transient('tubetus_forum_cache', $output, 60); 
    return $output; 
} 

add_shortcode('forum_latest', 'tubetus_forum_render_output');
class Tubetus_Forum_Widget extends WP_Widget { 
    function __construct() { parent::__construct('tubetus_forum_widget', 'Tubetus Forum Feed'); } 
    public function widget($args, $instance) { echo $args['before_widget'] . tubetus_forum_render_output() . $args['after_widget']; } 
} 
add_action('widgets_init', function() { register_widget('Tubetus_Forum_Widget'); });