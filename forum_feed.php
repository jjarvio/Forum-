<?php
/*
Plugin Name: Tubetus Forum Feed
Description: Näyttää phpBB viimeisimmät keskustelut
Version: 2.2
*/

if (!defined('ABSPATH')) exit;

function tubetus_forum_latest_topics() {

    // ⚙️ ASETUKSET
    $db_host = '127.0.0.1';
    $db_name = 'jonitiet_phpbb';
    $db_user = 'jonitiet_forum';
    $db_pass = 'Tietokettu2020';

    $forum_url = 'https://keskustelu.moikka11.fi';
    $table_prefix = 'phpbbfp_';

    // ⚡ CACHE
    $cache_key = 'tubetus_forum_cache';
    $cached = get_transient($cache_key);
    if ($cached) return $cached;

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    $mysqli->set_charset("utf8mb4");
    

    if ($mysqli->connect_error) {
        return "DB virhe: " . $mysqli->connect_error;
    }

    // 🔥 VARMA QUERY (käyttää post_subjectia)
    $result = $mysqli->query("
        SELECT 
            t.topic_id,
            t.topic_title, -- Haetaan suoraan ketjun otsikko
            t.topic_time,
            u.username,
            u.user_avatar
        FROM {$table_prefix}topics t
        LEFT JOIN {$table_prefix}users u ON t.topic_poster = u.user_id
        WHERE t.topic_visibility = 1
        ORDER BY t.topic_time DESC
        LIMIT 10
    ");

    if (!$result) {
        return "Query error: " . $mysqli->error;
    }

    if ($result->num_rows == 0) {
        return "Ei keskusteluja löytynyt";
    }

    ob_start();
    ?>

    <div class="forum-box">
        <h3>Forum keskustelut</h3>

        <?php while ($topic = $result->fetch_assoc()): ?>
            <div class="forum-item">

                <!-- 👤 AVATAR -->
                <div class="avatar">
                    <?php if (!empty($topic['user_avatar'])): ?>
                        <img src="<?php echo $forum_url; ?>/images/avatars/upload/<?php echo esc_attr($topic['user_avatar']); ?>" alt="">
                    <?php endif; ?>
                </div>

                <div class="content">
                    <a href="<?php echo $forum_url; ?>/viewtopic.php?t=<?php echo $topic['topic_id']; ?>">
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
        <?php endwhile; ?>
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
        padding: 15px 10px; /* Lisätty pystysuuntaista tilaa (15px) */
        border-bottom: 2px solid #ddd; /* Paksumpi ja yhtenäinen viiva */
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