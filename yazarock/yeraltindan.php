<?php
if(!defined('ABSPATH'))exit;
/* "Yeraltından": her pazartesi Türkiye'den yeni çıkan yeraltı şarkılarının listesi.
   Haftalık yazılar "Yeraltından" kategorisine düşer; Spotify çalma listesi adresi Özelleştir'den girilir. */
function yr_yer_kat(){$t=get_term_by('slug','yeraltindan','category');return $t?(int)$t->term_id:0;}
add_action('init',function(){
	if(get_option('yr_mig_v13'))return;update_option('yr_mig_v13',1);
	if(!yr_yer_kat())wp_insert_term('Yeraltından','category',['slug'=>'yeraltindan','description'=>'Her pazartesi Türkiye\'nin yeraltından yeni çıkan şarkılar.']);
},21);
add_action('customize_register',function($c){
	$c->add_setting('yr_liste_url',['default'=>'','sanitize_callback'=>'esc_url_raw']);
	$c->add_control('yr_liste_url',['label'=>'Yeraltından · Spotify çalma listesi adresi (boşsa yalnızca haftalık yazı görünür)','section'=>'yr_ana','type'=>'url','priority'=>0]);
	$c->add_setting('yr_s_yeraltindan',['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
	$c->add_control('yr_s_yeraltindan',['label'=>'Yeraltından (haftalık liste) bölümünü göster','section'=>'yr','type'=>'checkbox']);
},31);
function yr_yeraltindan_kutu(){
	if(!yr_on('yeraltindan'))return;
	$k=yr_yer_kat();$p=$k?get_posts(['category'=>$k,'numberposts'=>1,'post_status'=>'publish']):[];
	$u=(string)get_theme_mod('yr_liste_url','');$emb=$u?yr_embed_box($u):'';
	if(!$p&&!$emb)return;
	echo '<div class="box yer" style="margin-top:22px"><h2>'.esc_html(yr_t('yer_b')).'</h2><p class="yer-m">'.esc_html(yr_t('yer_m')).'</p>';
	if($p){$p=$p[0];echo '<a class="yer-son" href="'.esc_url(get_permalink($p)).'"><span class="pill">Bu hafta</span><b>'.esc_html(get_the_title($p)).'</b><small>'.esc_html(wp_trim_words(get_the_excerpt($p),24)).'</small></a>';}
	if($emb)echo '<div class="embs">'.$emb.'</div><p style="margin:10px 0 0"><a class="more" href="'.esc_url($u).'" target="_blank" rel="noopener">Listeyi Spotify\'da takip et →</a></p>';
	if($k&&$p)echo '<p style="margin:10px 0 0"><a class="more" href="'.esc_url(get_category_link($k)).'">Önceki haftalar →</a></p>';
	echo '</div>';
}
