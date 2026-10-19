<?php
if(!defined('ABSPATH'))exit;
/*
 * GitHub bağlantısı
 * 1) Tema güncellemeleri: GitHub'daki yeni sürüm WordPress'te normal tema güncellemesi gibi görünür.
 * 2) Sabah derlemeleri: derlemeler/ klasöründeki haberler WordPress'e "Taslak" olarak eklenir.
 * Depo: github.com/yazarock/yazarock (herkese açık; anahtar gerekmez)
 */
if(!defined('YR_GH_REPO'))define('YR_GH_REPO','yazarock/yazarock');
if(!defined('YR_GH_BRANCH'))define('YR_GH_BRANCH','main');
if(!defined('YR_GH_THEME_DIR'))define('YR_GH_THEME_DIR','yazarock');/* depodaki tema klasörü */

function yr_gh_raw($path){return (string)apply_filters('yr_gh_raw_url','https://raw.githubusercontent.com/'.YR_GH_REPO.'/'.YR_GH_BRANCH.'/'.ltrim($path,'/'),$path);}
function yr_gh_get($path,$max=2000000){
	$r=wp_remote_get(add_query_arg('t',(string)floor(time()/300),yr_gh_raw($path)),['timeout'=>15,'limit_response_size'=>$max,'headers'=>['Accept'=>'text/plain, application/json']]);
	if(is_wp_error($r)||(int)wp_remote_retrieve_response_code($r)!==200)return null;
	return (string)wp_remote_retrieve_body($r);
}

/* ================= 1) Tema güncellemeleri ================= */
function yr_gh_remote_version($force=false){
	$v=$force?false:get_site_transient('yr_gh_ver');
	if($v===false){
		$css=yr_gh_get(YR_GH_THEME_DIR.'/style.css',20000);
		$v=($css&&preg_match('/^[ \t\/*#@]*Version:\s*([0-9][0-9A-Za-z.\-]*)/mi',$css,$m))?$m[1]:'';
		set_site_transient('yr_gh_ver',$v,$v?6*HOUR_IN_SECONDS:HOUR_IN_SECONDS);
	}
	return (string)$v;
}
add_filter('pre_set_site_transient_update_themes',function($t){
	if(!is_object($t))$t=new stdClass();
	$slug=get_template();$cur=wp_get_theme($slug)->get('Version');
	$force=is_admin()&&!empty($_GET['force-check']);
	$new=yr_gh_remote_version($force);
	$item=['theme'=>$slug,'new_version'=>$new,'url'=>'https://github.com/'.YR_GH_REPO,'package'=>(string)apply_filters('yr_gh_package_url','https://github.com/'.YR_GH_REPO.'/archive/refs/heads/'.YR_GH_BRANCH.'.zip'),'requires'=>'6.0','requires_php'=>'7.4'];
	if($new&&version_compare($new,$cur,'>')){
		if(!isset($t->response)||!is_array($t->response))$t->response=[];
		$t->response[$slug]=$item;
	}else{
		if(!isset($t->no_update)||!is_array($t->no_update))$t->no_update=[];
		$t->no_update[$slug]=$item;/* otomatik güncelleme anahtarı görünsün */
		if(isset($t->response[$slug]))unset($t->response[$slug]);
	}
	return $t;
});
/* GitHub ZIP'i "yazarock-main/" klasörüyle gelir; içinden yalnızca tema klasörünü al ve doğru adla kur */
add_filter('upgrader_source_selection',function($source,$remote,$upgrader,$extra=[]){
	if(empty($extra['theme'])||$extra['theme']!==get_template())return $source;
	global $wp_filesystem;if(!$wp_filesystem)return $source;
	$inner=trailingslashit($source).YR_GH_THEME_DIR;
	if(!$wp_filesystem->is_dir($inner)||!$wp_filesystem->exists($inner.'/style.css'))return $source;/* zaten düz tema ZIP'i */
	$dest=trailingslashit($remote).get_template();
	if($wp_filesystem->exists($dest))$wp_filesystem->delete($dest,true);
	if(!$wp_filesystem->move($inner,$dest))return new WP_Error('yr_gh','Tema klasörü güncelleme paketinden çıkarılamadı.');
	return trailingslashit($dest);
},10,4);
add_action('upgrader_process_complete',function(){delete_site_transient('yr_gh_ver');});

/* ================= 2) Sabah derlemeleri → taslaklar ================= */
/*
 * Depoda: derlemeler/index.json  {"dosyalar":["2026-10-20.json", ...]}
 *         derlemeler/2026-10-20.json
 *         {"tarih":"2026-10-20","yazilar":[{"id":"2026-10-20-ornek","baslik":"…","ozet":"…","icerik":"<p>…</p>",
 *           "kategori":"Haber","etiketler":["Metallica"],"kaynaklar":[{"ad":"Kaynak","url":"https://…"}]}]}
 */
function yr_derleme_cek($manual=false){
	if(get_transient('yr_derleme_kilit'))return ['durum'=>'mesgul'];
	set_transient('yr_derleme_kilit',1,5*MINUTE_IN_SECONDS);
	$out=['eklenen'=>0,'durum'=>'tamam'];
	$idx=json_decode((string)yr_gh_get('derlemeler/index.json',200000),true);
	if(!is_array($idx)||!isset($idx['dosyalar'])||!is_array($idx['dosyalar'])){delete_transient('yr_derleme_kilit');update_option('yr_derleme_son',['zaman'=>time(),'eklenen'=>0,'hata'=>'Derleme listesi okunamadı'],false);return ['durum'=>'hata'];}
	$alinan=(array)get_option('yr_derleme_alinan',[]);
	$dosyalar=array_slice(array_values(array_filter($idx['dosyalar'],function($f){return is_string($f)&&preg_match('/^\d{4}-\d{2}-\d{2}[A-Za-z0-9_-]*\.json$/',$f);})),-14);
	foreach($dosyalar as $f){
		if(in_array($f,$alinan,true))continue;
		$d=json_decode((string)yr_gh_get('derlemeler/'.$f,2000000),true);
		if(!is_array($d)||!isset($d['yazilar'])||!is_array($d['yazilar']))continue;
		foreach(array_slice($d['yazilar'],0,30) as $y)if(is_array($y)&&yr_derleme_ekle($y))$out['eklenen']++;
		$alinan[]=$f;
	}
	update_option('yr_derleme_alinan',array_slice($alinan,-60),false);
	update_option('yr_derleme_son',['zaman'=>time(),'eklenen'=>$out['eklenen'],'hata'=>''],false);
	delete_transient('yr_derleme_kilit');
	return $out;
}
function yr_derleme_ekle($y){
	$id=sanitize_key($y['id']??'');$baslik=sanitize_text_field((string)($y['baslik']??''));$icerik=wp_kses_post((string)($y['icerik']??''));
	if($id===''||mb_strlen($baslik)<5||trim(wp_strip_all_tags($icerik))==='')return false;
	if(get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>$id,'numberposts'=>1,'fields'=>'ids']))return false;
	$k=[];
	foreach(array_slice((array)($y['kaynaklar']??[]),0,8) as $s){
		$u=esc_url_raw((string)($s['url']??''));if(!$u||!preg_match('#^https?://#',$u))continue;
		$k[]='<li><a href="'.esc_url($u).'" rel="noopener nofollow" target="_blank">'.esc_html(sanitize_text_field((string)($s['ad']??wp_parse_url($u,PHP_URL_HOST)))).'</a></li>';
	}
	if($k)$icerik.="\n<h3>Kaynaklar</h3>\n<ul class=\"kaynaklar\">".implode('',$k).'</ul>';
	$kat=sanitize_text_field((string)($y['kategori']??'Haber'));if($kat==='')$kat='Haber';
	$t=get_term_by('name',$kat,'category');if(!$t){$r=wp_insert_term($kat,'category');$tid=is_wp_error($r)?0:(int)$r['term_id'];}else $tid=(int)$t->term_id;
	$yazar=(int)get_option('yr_derleme_yazar',0);
	if(!$yazar||!get_userdata($yazar)){$a=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID','orderby'=>'ID']);$yazar=$a?(int)$a[0]:0;}
	$pid=wp_insert_post(['post_type'=>'post','post_status'=>'draft','post_title'=>$baslik,'post_content'=>$icerik,'post_excerpt'=>sanitize_textarea_field((string)($y['ozet']??'')),'post_author'=>$yazar,'post_category'=>$tid?[$tid]:[],'tags_input'=>array_slice(array_map('sanitize_text_field',(array)($y['etiketler']??[])),0,8),'meta_input'=>['_yr_derleme_id'=>$id]],true);
	return !is_wp_error($pid);
}
/* Saatlik kontrol (WordPress zamanlayıcısı) */
add_action('init',function(){if(!wp_next_scheduled('yr_derleme_saatlik'))wp_schedule_event(time()+120,'hourly','yr_derleme_saatlik');});
add_action('yr_derleme_saatlik','yr_derleme_cek');
add_action('switch_theme',function(){wp_clear_scheduled_hook('yr_derleme_saatlik');});
/* Panele girildiğinde 30 dakikada bir arka planda kontrol */
add_action('admin_init',function(){
	if(!current_user_can('edit_others_posts')||wp_doing_ajax())return;
	$s=get_option('yr_derleme_son',[]);
	if((empty($s['zaman'])||time()-(int)$s['zaman']>30*MINUTE_IN_SECONDS)&&!wp_next_scheduled('yr_derleme_simdi')){
		wp_schedule_single_event(time(),'yr_derleme_simdi');spawn_cron();/* paneli bekletmeden arka planda */
	}
});
add_action('yr_derleme_simdi','yr_derleme_cek');
/* "Şimdi çek" düğmesi */
add_action('admin_post_yr_derleme_cek',function(){
	check_admin_referer('yr_derleme_cek');if(!current_user_can('edit_others_posts'))wp_die('Yetkin yok.');
	delete_transient('yr_derleme_kilit');$r=yr_derleme_cek(true);
	wp_safe_redirect(add_query_arg(['yr_dc'=>$r['durum'],'yr_de'=>(int)($r['eklenen']??0)],admin_url('edit.php?post_status=draft&yr_derleme=1')));exit;
});
/* Taslak listesinde yalnızca derleme taslaklarını süz */
add_action('pre_get_posts',function($q){
	if(is_admin()&&$q->is_main_query()&&!empty($_GET['yr_derleme'])&&($q->get('post_type')===''||$q->get('post_type')==='post'))$q->set('meta_query',[['key'=>'_yr_derleme_id','compare'=>'EXISTS']]);
});
/* Bildirim */
add_action('admin_notices',function(){
	if(!current_user_can('edit_others_posts'))return;
	if(isset($_GET['yr_dc'])){
		$d=sanitize_key($_GET['yr_dc']);$n=(int)($_GET['yr_de']??0);
		$m=$d==='tamam'?($n?$n.' yeni taslak eklendi.':'Yeni derleme yok; her şey güncel.'):($d==='mesgul'?'Bir kontrol zaten sürüyor, birazdan tekrar dene.':'GitHub\'a ulaşılamadı. Biraz sonra tekrar dene.');
		echo '<div class="notice notice-'.($d==='tamam'?'success':'warning').' is-dismissible"><p>'.esc_html($m).'</p></div>';
	}
	$q=new WP_Query(['post_type'=>'post','post_status'=>'draft','meta_key'=>'_yr_derleme_id','posts_per_page'=>1,'fields'=>'ids']);
	$n=(int)$q->found_posts;$scr=function_exists('get_current_screen')?get_current_screen():null;
	if(!$n||($scr&&$scr->id==='edit-post'&&!empty($_GET['yr_derleme'])))return;
	echo '<div class="notice notice-info"><p><b>Sabah derlemesi:</b> incelemeni bekleyen '.(int)$n.' haber taslağı var. <a class="button button-primary" style="margin-left:8px" href="'.esc_url(admin_url('edit.php?post_status=draft&yr_derleme=1')).'">Taslakları incele</a></p></div>';
});
add_filter('views_edit-post',function($v){
	$s=get_option('yr_derleme_son',[]);
	$v['yr_derleme']='<a href="'.esc_url(admin_url('edit.php?post_status=draft&yr_derleme=1')).'"'.(!empty($_GET['yr_derleme'])?' class="current"':'').'>Sabah derlemeleri</a> · <a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_derleme_cek'),'yr_derleme_cek')).'">Şimdi çek</a>'.(!empty($s['zaman'])?' <span style="color:#777">(son kontrol: '.esc_html(human_time_diff((int)$s['zaman'])).' önce)</span>':'');
	return $v;
});
