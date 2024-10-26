<?php
// Bileşen içeriği
$args = array(
    'post_type' => 'post',
    'posts_per_page' => 5, // Gösterilecek maksimum yazı sayısı
);

$latest_posts = new WP_Query($args);

if ($latest_posts->have_posts()) {
    echo '<h2>Yeni Trendyol İndirim Kuponları</h2>';
    echo '<ul>';
    while ($latest_posts->have_posts()) {
        $latest_posts->the_post();
        echo '<li><a href="'.get_permalink().'" target="_blank">'.get_the_title().'</a></li>';
    }
    echo '</ul>';
    echo '<p><a href="https://enuygunfirmalar.com/forums/trendyol-indirim-kuponu.49" target="_blank">Tüm Kuponları Görüntüle</a></p>';
} else {
    echo '<p>Henüz yeni indirim kuponu yok.</p>';
}
wp_reset_postdata();
?>
