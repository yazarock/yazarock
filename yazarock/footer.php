<?php if(!defined('ABSPATH'))exit;?></main>
<footer><div class="wrap"><div><?php $m=get_theme_mod('yr_mail','info@yazarock.com');echo '<a href="mailto:'.esc_attr($m).'">'.esc_html($m).'</a>';foreach(array_filter(array_map('trim',explode("\n",(string)get_theme_mod('yr_social','')))) as $s)echo ' · <a href="'.esc_url($s).'" rel="noopener" target="_blank">'.esc_html(wp_parse_url($s,PHP_URL_HOST)).'</a>';?></div><div><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>">KVKK</a> · <a href="<?php echo esc_url(yr_page_url('gizlilik-politikasi'));?>">Gizlilik</a> · <a href="<?php echo esc_url(yr_page_url('cerez-politikasi'));?>">Çerezler</a> · © <?php echo esc_html(date_i18n('Y'));?> Yazarock.com</div></div></footer>
<?php wp_footer();?>
</body>
</html>
