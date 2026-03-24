<?php
/*
Plugin Name: Tubetus Forum Feed
Description: Näyttää phpBB viimeisimmät keskustelut turvallisesti
Version: 2.3
*/

if (!defined('ABSPATH')) exit; // Estää suoran suorituksen

function tubetus_forum_latest_topics() {

    // ⚙️ ASETUKSET
    $forum_url = 'https://keskustelu.moikka11.fi';
    $table_prefix = 'phpbbfp_';

    // ⚡ CACHE (60 sekuntia)
    $cache_key = 'tubetus_forum_cache';
    $cached = get_transient($cache_key);
    if ($cached) return $cached;

    // Varmistetaan, että tunnukset on määritelty wp-config.php:ssä
    if (!defined('TUBETUS_DB_USER') || !defined('TUBETUS_DB_PASS')) {
        return "";
    }

    // Yhdistetään tietokantaan turvallisesti WordPressin wpdb-luokalla
    $forum_db = new wpdb(TUBETUS_DB_USER, TUBETUS_DB_PASS, TUBETUS_DB_NAME, TUBETUS_DB_HOST);

    // Tarkistetaan onko yhteysvirheitä 
    if (!empty($forum_db->error)) {
        return "";
    }

    // Pakotetaan UTF-8 tila oikeiden merkkien (esim. ääkkösten) näyttämiseksi
    $forum_db->query("SET NAMES 'utf8mb4'");

    // 🔥 TURVALLINEN QUERY
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
        LIMIT 10
    ";

    // Haetaan tulokset assosiatiivisena taulukkona (ARRAY_A)
    $topics = $forum_db->get_results($query, ARRAY_A);

    // Tarkistetaan löytyikö keskusteluja
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
                    <a href="<?php echo esc_url($forum_url . '/viewtopic.php?t=' . $topic['topic_id']); ?>">
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
        display: block;
        width: 100%;
        font-weight: bold;
        color: #0073ff;
        text-decoration: none;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
        line-height: 1.4;
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

    // ⚡ CACHE 60 sek
    set_transient($cache_key, $output, 60);

    return $output;
}

add_shortcode('forum_latest', 'tubetus_forum_latest_topics');