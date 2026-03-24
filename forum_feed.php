<?php
/*
Plugin Name: Tubetus Forum Feed
Description: Näyttää phpBB viimeisimmät keskustelut. Hallinta: Asetukset -> Tubetus Forum.
Version: 1.0
*/

if (!defined('ABSPATH')) exit; // Estää suoran suorituksen


// HALLINTAPANEELIN REKISTERÖINTI

add_action('admin_menu', 'tubetus_forum_add_admin_menu');
function tubetus_forum_add_admin_menu() {
    add_options_page(
        'Tubetus Forum Asetukset', 
        'Tubetus Forum', 
        'manage_options', 
        'tubetus_forum', 
        'tubetus_forum_options_page'
    );
}

add_action('admin_init', 'tubetus_forum_settings_init');
function tubetus_forum_settings_init() {
    // Rekisteröidään asetukset tietokantaan
    register_setting('tubetus_forum_settings_group', 'tubetus_db_host');
    register_setting('tubetus_forum_settings_group', 'tubetus_db_name');
    register_setting('tubetus_forum_settings_group', 'tubetus_db_user');
    register_setting('tubetus_forum_settings_group', 'tubetus_db_pass');
    register_setting('tubetus_forum_settings_group', 'tubetus_table_prefix');
    register_setting('tubetus_forum_settings_group', 'tubetus_forum_url');
    register_setting('tubetus_forum_settings_group', 'tubetus_topic_limit', 'absint'); // absint varmistaa että arvo on aina positiivinen numero
}


// HALLINTAPANEELIN NÄKYMÄ (HTML)


function tubetus_forum_options_page() {
    // Tyhjennetään välimuisti automaattisesti, kun asetuksia tallennetaan
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        delete_transient('tubetus_forum_cache');
    }
    ?>
    <div class="wrap">
        <h2>Tubetus Forum - Asetukset</h2>
        <p>Käytä shortcodea <code>[forum_latest]</code> näyttääksesi listauksen sivuillasi.</p>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('tubetus_forum_settings_group');
            do_settings_sections('tubetus_forum_settings_group');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Tietokannan osoite (Host)</th>
                    <td><input type="text" name="tubetus_db_host" value="<?php echo esc_attr(get_option('tubetus_db_host', '127.0.0.1')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Tietokannan nimi</th>
                    <td><input type="text" name="tubetus_db_name" value="<?php echo esc_attr(get_option('tubetus_db_name', 'jonitiet_phpbb')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Tietokannan käyttäjä</th>
                    <td><input type="text" name="tubetus_db_user" value="<?php echo esc_attr(get_option('tubetus_db_user', 'jonitiet_forum')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Tietokannan salasana</th>
                    <td><input type="password" name="tubetus_db_pass" value="<?php echo esc_attr(get_option('tubetus_db_pass')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Taulujen etuliite (Prefix)</th>
                    <td><input type="text" name="tubetus_table_prefix" value="<?php echo esc_attr(get_option('tubetus_table_prefix', 'phpbbfp_')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Foorumin URL-osoite</th>
                    <td><input type="url" name="tubetus_forum_url" value="<?php echo esc_url(get_option('tubetus_forum_url', 'https://keskustelu.moikka11.fi')); ?>" class="regular-text" placeholder="https://..." /></td>
                </tr>
                <tr>
                    <th scope="row">Näytettävien viestien määrä</th>
                    <td><input type="number" name="tubetus_topic_limit" value="<?php echo esc_attr(get_option('tubetus_topic_limit', 10)); ?>" min="1" max="50" class="small-text" /></td>
                </tr>
            </table>
            <?php submit_button('Tallenna asetukset'); ?>
        </form>
    </div>
    <?php
}


// SHORTCODE JA DATAN HAKU


function tubetus_forum_latest_topics() {
    // ⚡ CACHE (60 sekuntia)
    $cache_key = 'tubetus_forum_cache';
    $cached = get_transient($cache_key);
    if ($cached) return $cached;

    // Haetaan asetukset tietokannasta
    $db_host = get_option('tubetus_db_host', '');
    $db_name = get_option('tubetus_db_name', '');
    $db_user = get_option('tubetus_db_user', '');
    $db_pass = get_option('tubetus_db_pass', '');
    $table_prefix = get_option('tubetus_table_prefix', 'phpbbfp_');
    $forum_url = get_option('tubetus_forum_url', '');
    $limit = absint(get_option('tubetus_topic_limit', 10));

    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        return "<p>Foorumin asetuksia ei ole määritetty. Tarkista WordPressin hallintapaneeli.</p>";
    }

    // Yhdistetään tietokantaan turvallisesti WordPressin wpdb-luokalla
    $forum_db = new wpdb($db_user, $db_pass, $db_name, $db_host);

    if (!empty($forum_db->error)) {
        return "";
    }

    // Pakotetaan UTF-8 tila oikeiden merkkien näyttämiseksi
    $forum_db->query("SET NAMES 'utf8mb4'");

    // TURVALLINEN QUERY (Limit haetaan asetuksista)
    $query = "
        SELECT 
            t.topic_id,
            t.topic_title,
            t.topic_time,
            u.username,
            u.user_avatar
        FROM {$table_prefix}topics t
        LEFT JOIN {$table_prefix}users u ON t.topic_poster = u.user_id
        WHERE t.topic_visibility = 1
        ORDER BY t.topic_time DESC
        LIMIT {$limit}
    ";

    $topics = $forum_db->get_results($query, ARRAY_A);

    if (empty($topics)) {
        return "<p>Ei uusia keskusteluja.</p>";
    }

    ob_start();
    ?>

    <div class="forum-box">
        <h3>Forum keskustelut</h3>

        <?php foreach ($topics as $topic): ?>
            <div class="forum-item">
                <div class="avatar">
                    <?php if (!empty($topic['user_avatar'])): ?>
                        <img src="<?php echo esc_url($forum_url . '/images/avatars/upload/' . $topic['user_avatar']); ?>" alt="">
                    <?php endif; ?>
                </div>

                <div class="content">
                    <a href="<?php echo esc_url($forum_url . '/viewtopic.php?t=' . $topic['topic_id']); ?>" title="<?php echo esc_attr($topic['topic_title']); ?>">
                        <?php echo esc_html($topic['topic_title'] ?: 'Ei otsikkoa'); ?>
                    </a>

                    <div class="meta">
                        Kirjoittaja 
                        <span class="site">
                            <?php echo esc_html($topic['username'] ?: 'Tuntematon'); ?>
                        </span>
                        •
                        <?php echo human_time_diff($topic['topic_time'], current_time('timestamp')); ?> sitten
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <style>
    .forum-box {
        background: #f5f5f5;
        border-radius: 12px;
        padding: 15px;
    }

    .forum-box h3 {
        margin-bottom: 10px;
    }

    .forum-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px 10px;
        border-bottom: 2px solid #ddd;
        transition: 0.2s;
    }

    .forum-item:last-child {
        border-bottom: none;
    }

    .forum-item:hover { 
        background: #eee;
        border-radius: 8px;
    }

    .avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;
        background: #ccc;
        border-radius: 50%;
        overflow: hidden;
        flex: 0 0 40px;
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .content {
        flex: 1;
        min-width: 0;
    }

    
    .content a {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        
        width: 100%;
        font-weight: bold;
        color: #0073ff;
        text-decoration: none;
        line-height: 1.4;
        word-break: break-word;
    }

    .content a:hover {
        text-decoration: underline;
    }

    .meta {
        font-size: 12px;
        color: #555;
        margin-top: 4px;
    }

    .site {
        color: red;
    }
    </style>

    <?php
    $output = ob_get_clean();

    // ⚡ Tallenna välimuistiin
    set_transient($cache_key, $output, 60);

    return $output;
}

add_shortcode('forum_latest', 'tubetus_forum_latest_topics');