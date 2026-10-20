<?php if(!defined('ABSPATH'))exit; get_header();
$us=get_users(['role__in'=>['administrator','editor','author','contributor','yazarock_yazar'],'has_published_posts'=>['post'],'orderby'=>'post_count','order'=>'DESC','number'=>100]);?>
<div class="ph"><h1>Yazarlar</h1><p>Yazarock'ta rock ve metal üzerine yazanlar. <a href="<?php echo esc_url(is_user_logged_in()?yr_page_url('yazar-ol'):yr_page_url('uye-ol'));?>" style="color:var(--blue);text-decoration:underline">Sen de aramıza katıl</a></p></div>
<?php while(have_posts()){the_post();if(trim(get_the_content())){echo '<div class="box entry" style="margin-bottom:22px"><div class="content">';the_content();echo '</div></div>';}}?>
<?php if($us):?><div class="ygrid"><?php foreach($us as $u){$s=yr_yazar_ist($u->ID);$son=get_posts(['post_type'=>'post','author'=>$u->ID,'post_status'=>'publish','numberposts'=>1]);$url=get_author_posts_url($u->ID);
	echo '<article class="ycard"><a class="yc-top" href="'.esc_url($url).'">'.get_avatar($u->ID,88).'<h2>'.esc_html($u->display_name).'</h2><span class="pill">'.esc_html(yr_rol_adi($u)).'</span></a>';
	echo '<p class="yc-bio">'.($u->description?esc_html(wp_trim_words($u->description,22)):'<span class="mute">Rock ve metal tutkunu bir Yazarock yazarı.</span>').'</p>';
	echo '<div class="yc-st"><span>'.yr_ikon('doc').'<b>'.esc_html(yr_sayi($s['yazi'])).'</b> yazı</span><span>'.yr_ikon('eye').'<b>'.esc_html(yr_sayi($s['okunma'])).'</b> okunma</span><span>'.yr_ikon('up').'<b>'.esc_html(yr_sayi($s['begeni'])).'</b> beğeni</span></div>';
	if($son)echo '<p class="yc-son"><small>Son yazısı</small><a href="'.esc_url(get_permalink($son[0])).'">'.esc_html(get_the_title($son[0])).'</a></p>';
	echo '<div class="yc-f">'.yr_sosyal($u->ID).'<a class="more" href="'.esc_url($url).'">Yazıları →</a></div></article>';}?></div>
<?php else:?><div class="box"><p class="empty-s">Henüz yazar yok.</p></div><?php endif;?>
<?php get_footer();
