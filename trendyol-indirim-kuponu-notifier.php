<?php
/**
 * Plugin Name: Trendyol İndirim Kuponu 
 * Plugin URI: https://enuygunfirmalar.com/forums/trendyol-indirim-kuponu.49/
 * Description: Trendyol indirim kuponlarını RSS akışından alır ve bildirim gönderir.
 * Version: 1.0
 * Author: [MAVE Yazılım]
 * Author URI: https://mave.net.tr/
 * License: GPL2
 * Text Domain: trendyol-indirim-kuponu-notifier
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Do not allow direct access
}

// Eklenti ayarları
function trendyol_notifier_settings_page() {
    add_menu_page(
        'Trendyol Kupon Ayarları',
        'Trendyol Kuponları',
        'manage_options',
        'trendyol-notifier',
        'trendyol_notifier_settings_page_html',
        'dashicons-cart'
    );
}
add_action('admin_menu', 'trendyol_notifier_settings_page');

// Ayar sayfası içeriği
function trendyol_notifier_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>Trendyol İndirim Kuponu Ayarları</h1>
        <form method="post" action="">
            <label for="rss_url">RSS URL:</label>
            <input type="text" id="rss_url" name="rss_url" value="<?php echo esc_attr(get_option('trendyol_rss_url', 'https://enuygunfirmalar.com/forums/trendyol-indirim-kuponu.49/index.rss')); ?>" />
            <input type="submit" class="button button-primary" value="Kaydet" />
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            update_option('trendyol_rss_url', sanitize_text_field($_POST['rss_url']));
            echo '<p>Ayarlar kaydedildi!</p>';
        }
        ?>
    </div>
    <?php
}

// Cron job ekleme
function trendyol_schedule_rss_check() {
    if (!wp_next_scheduled('trendyol_rss_check_event')) {
        wp_schedule_event(time(), 'hourly', 'trendyol_rss_check_event');
    }
}
add_action('wp', 'trendyol_schedule_rss_check');

// Cron job tetikleyici
add_action('trendyol_rss_check_event', 'trendyol_check_rss_feed');

// RSS akışını kontrol etme
function trendyol_check_rss_feed() {
    $rss_url = get_option('trendyol_rss_url', 'https://enuygunfirmalar.com/forums/trendyol-indirim-kuponu.49/index.rss');
    
    $response = wp_remote_get($rss_url);

    if (is_wp_error($response)) {
        error_log('RSS akışı alınamadı: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $xml = simplexml_load_string($body);
    
    if ($xml === false) {
        error_log('RSS XML yüklenemedi');
        return;
    }

    foreach ($xml->channel->item as $item) {
        $title = (string) $item->title;
        $link = (string) $item->link;
        $description = (string) $item->description;

        // Yeni kuponu paylaşma
        if (!trendyol_coupon_exists($link)) {
            // Yazı oluşturma
            $post_data = array(
                'post_title'   => wp_strip_all_tags($title),
                'post_content' => $description . '<br><a href="' . esc_url($link) . '">Detayları Görüntüle</a>',
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_category'=> array(1) // 1 numaralı kategoriye ekle
            );

            wp_insert_post($post_data);
        }
    }
}

// Daha önce paylaşılmış olan kuponları kontrol etme
function trendyol_coupon_exists($link) {
    $args = array(
        'post_type'   => 'post',
        'meta_query'  => array(
            array(
                'key'   => 'trendyol_coupon_link',
                'value' => esc_url($link),
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);
    return $query->have_posts();
}

// Meta değerini kaydet
add_action('save_post', 'trendyol_save_coupon_link');
function trendyol_save_coupon_link($post_id) {
    if (get_post_type($post_id) === 'post') {
        $link = get_post_meta($post_id, 'trendyol_coupon_link', true);
        if (empty($link)) {
            add_post_meta($post_id, 'trendyol_coupon_link', esc_url($_POST['trendyol_coupon_link']), true);
        }
    }
}

// Bileşen için HTML çıktısı
function trendyol_widget() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/trendyol-widget.php';
    return ob_get_clean();
}

// Kısa kod ekleme
add_shortcode('trendyol_widget', 'trendyol_widget');

// Bileşen kaydetme
function trendyol_register_widget() {
    register_sidebar( array(
        'name'          => 'Trendyol İndirim Kuponları',
        'id'            => 'trendyol_widget',
        'before_widget' => '<div class="trendyol-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'trendyol_register_widget');
