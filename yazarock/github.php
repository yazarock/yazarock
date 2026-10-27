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
		set_site_transient('yr_gh_ver',$v,$v?HOUR_IN_SECONDS:15*MINUTE_IN_SECONDS);
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
/* Güncellemeler, Temalar ya da Başlangıç sayfası açılınca GitHub'a hemen bak; yeni sürüm varsa beklemeden göster */
function yr_gh_hemen_bak(){
	if(!current_user_can('update_themes'))return;
	if(get_transient('yr_gh_bakti'))return;set_transient('yr_gh_bakti',1,2*MINUTE_IN_SECONDS);
	$new=yr_gh_remote_version(true);$slug=get_template();$cur=wp_get_theme($slug)->get('Version');
	if(!$new||!version_compare($new,$cur,'>'))return;
	$t=get_site_transient('update_themes');
	if(is_object($t)&&isset($t->response[$slug]['new_version'])&&$t->response[$slug]['new_version']===$new)return;
	delete_site_transient('update_themes');wp_update_themes();
}
foreach(['load-update-core.php','load-themes.php','load-index.php'] as $h)add_action($h,'yr_gh_hemen_bak');
/* Panoda yeni sürüm uyarısı */
add_action('admin_notices',function(){
	if(!current_user_can('update_themes'))return;$scr=function_exists('get_current_screen')?get_current_screen():null;if($scr&&$scr->id==='update-core')return;
	$t=get_site_transient('update_themes');$slug=get_template();
	if(!is_object($t)||empty($t->response[$slug]['new_version']))return;
	echo '<div class="notice notice-warning"><p><b>Yazarock teması:</b> yeni sürüm hazır ('.esc_html($t->response[$slug]['new_version']).'). <a class="button button-primary" style="margin-left:8px" href="'.esc_url(admin_url('update-core.php')).'">Güncellemeye git</a></p></div>';
});

/* ================= 2) Sabah derlemeleri → taslaklar ================= */
/*
 * Depoda: derlemeler/index.json  {"dosyalar":["2026-10-20.json", ...]}
 *         derlemeler/2026-10-20.json
 *         {"tarih":"2026-10-20","yazilar":[{"id":"2026-10-20-ornek","baslik":"…","ozet":"…","icerik":"<p>…</p>",
 *           "kategori":"Haber","etiketler":["Metallica"],"kaynaklar":[{"ad":"Kaynak","url":"https://…"}]}],
 *          "etkinlikler":[{"id":"2026-10-20-grup-mekan","baslik":"Grup – Mekân","tarih":"2026-10-24","saat":"21:30","sehir":"İstanbul",
 *           "mekan":"Blind İstanbul","tur":"Konser","bilet":"https://…","aciklama":"<p>…</p>","kaynaklar":[…]}]}
 * Etkinlikler de "Taslak" olarak Etkinlikler bölümüne eklenir; yayınlanınca takvimde görünür.
 */
function yr_derleme_cek($manual=false){
	if(get_transient('yr_derleme_kilit'))return ['durum'=>'mesgul'];
	set_transient('yr_derleme_kilit',1,5*MINUTE_IN_SECONDS);
	$out=['eklenen'=>0,'etkinlik'=>0,'grup'=>0,'durum'=>'tamam'];
	$idx=json_decode((string)yr_gh_get('derlemeler/index.json',200000),true);
	if(!is_array($idx)||!isset($idx['dosyalar'])||!is_array($idx['dosyalar'])){delete_transient('yr_derleme_kilit');update_option('yr_derleme_son',['zaman'=>time(),'eklenen'=>0,'hata'=>'Derleme listesi okunamadı'],false);return ['durum'=>'hata'];}
	$alinan=(array)get_option('yr_derleme_alinan',[]);
	$dosyalar=array_slice(array_values(array_filter($idx['dosyalar'],function($f){return is_string($f)&&preg_match('/^\d{4}-\d{2}-\d{2}[A-Za-z0-9_-]*\.json$/',$f);})),-14);
	foreach($dosyalar as $f){
		if(in_array($f,$alinan,true))continue;
		$d=json_decode((string)yr_gh_get('derlemeler/'.$f,2000000),true);
		if(!is_array($d))continue;
		if(isset($d['yazilar'])&&is_array($d['yazilar']))foreach(array_slice($d['yazilar'],0,30) as $y)if(is_array($y)&&yr_derleme_ekle($y))$out['eklenen']++;
		if(isset($d['etkinlikler'])&&is_array($d['etkinlikler']))foreach(array_slice($d['etkinlikler'],0,60) as $e)if(is_array($e)&&yr_derleme_etkinlik_ekle($e))$out['etkinlik']++;
		if(isset($d['gruplar'])&&is_array($d['gruplar']))foreach(array_slice($d['gruplar'],0,150) as $g)if(is_array($g)&&yr_derleme_grup_ekle($g))$out['grup']++;
		$alinan[]=$f;
	}
	update_option('yr_derleme_alinan',array_slice($alinan,-60),false);
	/* Grup listeleri ayrı tutulur (index.json → "grup_dosyalari"); eski tema sürümleri bunları görmez */
	$galinan=(array)get_option('yr_grup_alinan',[]);
	$gdos=isset($idx['grup_dosyalari'])&&is_array($idx['grup_dosyalari'])?array_slice(array_values(array_filter($idx['grup_dosyalari'],function($f){return is_string($f)&&preg_match('/^[A-Za-z0-9_-]+\.json$/',$f);})),-20):[];
	foreach($gdos as $f){
		if(in_array($f,$galinan,true))continue;
		$d=json_decode((string)yr_gh_get('derlemeler/'.$f,3000000),true);
		if(!is_array($d)||!isset($d['gruplar'])||!is_array($d['gruplar']))continue;
		foreach(array_slice($d['gruplar'],0,150) as $g)if(is_array($g)&&yr_derleme_grup_ekle($g))$out['grup']++;
		$galinan[]=$f;
	}
	update_option('yr_grup_alinan',array_slice($galinan,-60),false);
	/* Grup görselleri (index.json → "gorsel_dosyalari"): mevcut gruplara öne çıkan görsel ekler (tema 1.18.0+) */
	$out['gorsel']=yr_derleme_grup_gorselleri($idx);
	$out['yayin']=yr_derleme_yayinla();
	update_option('yr_derleme_son',['zaman'=>time(),'eklenen'=>$out['eklenen'],'etkinlik'=>$out['etkinlik'],'grup'=>$out['grup'],'hata'=>''],false);
	delete_transient('yr_derleme_kilit');
	return $out;
}
function yr_derleme_ekle($y){
	$id=sanitize_key($y['id']??'');$baslik=sanitize_text_field((string)($y['baslik']??''));$icerik=wp_kses_post((string)($y['icerik']??''));
	if($id===''||mb_strlen($baslik)<5||trim(wp_strip_all_tags($icerik))==='')return false;
	if(get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>$id,'numberposts'=>1,'fields'=>'ids']))return false;
	$icerik.=yr_derleme_kaynak_html($y['kaynaklar']??[]);
	$kat=sanitize_text_field((string)($y['kategori']??'Haber'));if($kat==='')$kat='Haber';
	$t=get_term_by('name',$kat,'category');if(!$t){$r=wp_insert_term($kat,'category');$tid=is_wp_error($r)?0:(int)$r['term_id'];}else $tid=(int)$t->term_id;
	$yazar=(int)get_option('yr_derleme_yazar',0);
	if(!$yazar||!get_userdata($yazar)){$a=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID','orderby'=>'ID']);$yazar=$a?(int)$a[0]:0;}
	$pid=wp_insert_post(['post_type'=>'post','post_status'=>(($y['durum']??'')==='yayin'?'publish':'draft'),'post_title'=>$baslik,'post_content'=>$icerik,'post_excerpt'=>sanitize_textarea_field((string)($y['ozet']??'')),'post_author'=>$yazar,'post_category'=>$tid?[$tid]:[],'tags_input'=>array_slice(array_map('sanitize_text_field',(array)($y['etiketler']??[])),0,8),'meta_input'=>['_yr_derleme_id'=>$id]],true);
	if(!is_wp_error($pid)&&$pid&&!empty($y['gorsel']))yr_derleme_gorsel($pid,(string)$y['gorsel'],(string)($y['gorsel_aciklama']??''));
	return !is_wp_error($pid);
}
/* "gorsel": https://… bağlantısındaki resmi indirip yazının öne çıkan görseli yapar (tema 1.16.1+) */
function yr_derleme_gorsel($pid,$url,$alt=''){
	$url=esc_url_raw($url);if(!$url||!preg_match('#^https://[^\s]+\.(jpe?g|png|webp|gif)(\?.*)?$#i',$url))return false;
	require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/image.php';
	$aid=media_sideload_image($url,$pid,sanitize_text_field($alt),'id');
	if(is_wp_error($aid))return false;
	if($alt!=='')update_post_meta($aid,'_wp_attachment_image_alt',sanitize_text_field($alt));
	return set_post_thumbnail($pid,$aid);
}
function yr_derleme_grup_gorselleri($idx){
	$dos=isset($idx['gorsel_dosyalari'])&&is_array($idx['gorsel_dosyalari'])?array_slice($idx['gorsel_dosyalari'],-10):[];
	$bitti=(array)get_option('yr_gorsel_alinan',[]);$n=0;$hak=12;/* bir çalışmada en fazla 12 indirme */
	foreach($dos as $f){
		if(!is_string($f)||!preg_match('/^[A-Za-z0-9_-]+\.json$/',$f)||in_array($f,$bitti,true))continue;
		$d=json_decode((string)yr_gh_get('derlemeler/'.$f,1000000),true);if(!is_array($d)||empty($d['gorseller']))continue;
		$kalan=0;
		foreach(array_slice((array)$d['gorseller'],0,300) as $g){
			if(!is_array($g)||empty($g['ad'])||empty($g['gorsel']))continue;
			$p=get_posts(['post_type'=>'grup','post_status'=>'any','title'=>sanitize_text_field($g['ad']),'numberposts'=>1]);
			if(!$p||has_post_thumbnail($p[0]->ID)||get_post_meta($p[0]->ID,'_yr_gorsel_denendi',true))continue;
			if($hak<=0){$kalan++;continue;}$hak--;
			update_post_meta($p[0]->ID,'_yr_gorsel_denendi',1);
			if(yr_derleme_gorsel($p[0]->ID,(string)$g['gorsel'],(string)$g['ad'])){
				$n++;update_post_meta($p[0]->ID,'_yr_gorsel_kredi',sanitize_text_field((string)($g['kredi']??'')));
				if(!empty($g['kaynak']))update_post_meta($p[0]->ID,'_yr_gorsel_kaynak',esc_url_raw((string)$g['kaynak']));
			}
		}
		if($kalan){if(!wp_next_scheduled('yr_derleme_simdi'))wp_schedule_single_event(time()+120,'yr_derleme_simdi');}
		else $bitti[]=$f;
	}
	update_option('yr_gorsel_alinan',array_slice($bitti,-30),false);
	if($n&&function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
	return $n;
}
function yr_derleme_kaynak_html($list){
	$k=[];
	foreach(array_slice((array)$list,0,8) as $s){
		if(!is_array($s))continue;$u=esc_url_raw((string)($s['url']??''));if(!$u||!preg_match('#^https?://#',$u))continue;
		$k[]='<li><a href="'.esc_url($u).'" rel="noopener nofollow" target="_blank">'.esc_html(sanitize_text_field((string)($s['ad']??wp_parse_url($u,PHP_URL_HOST)))).'</a></li>';
	}
	return $k?"\n<h3>Kaynaklar</h3>\n<ul class=\"kaynaklar\">".implode('',$k).'</ul>':'';
}
/* Derlemedeki konserler → Etkinlikler (taslak). Geçmiş tarihliler ve aynı gün/aynı adlı mevcut etkinlikler atlanır. */
function yr_derleme_etkinlik_ekle($e){
	$id=sanitize_key($e['id']??'');$baslik=sanitize_text_field((string)($e['baslik']??''));$tarih=(string)($e['tarih']??'');
	if($id===''||mb_strlen($baslik)<3||!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$tarih,$m)||!checkdate((int)$m[2],(int)$m[3],(int)$m[1]))return false;
	if($tarih<current_time('Y-m-d'))return false;
	if(get_posts(['post_type'=>'etkinlik','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>$id,'numberposts'=>1,'fields'=>'ids']))return false;
	foreach(get_posts(['post_type'=>'etkinlik','post_status'=>'any','numberposts'=>20,'meta_key'=>'_yr_tarih','meta_value'=>$tarih]) as $p)
		if(mb_strtolower(trim($p->post_title))===mb_strtolower($baslik))return false;
	$saat=(string)($e['saat']??'');if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$saat))$saat='';
	$tur=sanitize_text_field((string)($e['tur']??'Konser'));if(!in_array($tur,['Konser','Festival','Kulüp Gecesi'],true))$tur='Konser';
	$bilet=esc_url_raw((string)($e['bilet']??''));if($bilet&&!preg_match('#^https?://#',$bilet))$bilet='';
	$icerik=wp_kses_post((string)($e['aciklama']??''));$icerik.=yr_derleme_kaynak_html($e['kaynaklar']??[]);
	$yazar=(int)get_option('yr_derleme_yazar',0);
	if(!$yazar||!get_userdata($yazar)){$a=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID','orderby'=>'ID']);$yazar=$a?(int)$a[0]:0;}
	$pid=wp_insert_post(['post_type'=>'etkinlik','post_status'=>(($e['durum']??'')==='yayin'?'publish':'draft'),'post_title'=>$baslik,'post_content'=>$icerik,'post_excerpt'=>sanitize_textarea_field((string)($e['ozet']??'')),'post_author'=>$yazar,'meta_input'=>[
		'_yr_derleme_id'=>$id,'_yr_tarih'=>$tarih,'_yr_saat'=>$saat,'_yr_sehir'=>sanitize_text_field((string)($e['sehir']??'')),'_yr_mekan'=>sanitize_text_field((string)($e['mekan']??'')),'_yr_tur'=>$tur,'_yr_bilet'=>$bilet]],true);
	return !is_wp_error($pid);
}
/* Derlemedeki gruplar → Gruplar (taslak). Aynı adlı grup sitede varsa (taslak dahil) atlanır. */
function yr_derleme_grup_ekle($g){
	$id=sanitize_key($g['id']??'');$ad=sanitize_text_field((string)($g['ad']??''));$icerik=wp_kses_post((string)($g['icerik']??''));
	if($id===''||mb_strlen($ad)<2||trim(wp_strip_all_tags($icerik))==='')return false;
	if(get_posts(['post_type'=>'grup','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>$id,'numberposts'=>1,'fields'=>'ids']))return false;
	$norm=function($t){return trim(preg_replace('/\s*\(.*?\)\s*/u',' ',mb_strtolower(wp_strip_all_tags(html_entity_decode($t,ENT_QUOTES,'UTF-8')))));};
	$adn=$norm($ad);
	global $wpdb;foreach($wpdb->get_col("SELECT post_title FROM {$wpdb->posts} WHERE post_type='grup' AND post_status NOT IN ('trash','auto-draft')") as $t)if($norm($t)===$adn)return false;
	$bolge=(($g['bolge']??'')==='tr')?'tr':'world';
	$tur=sanitize_text_field((string)($g['tur']??''));if(!in_array($tur,['Rock','Metal','Alternatif','Progresif'],true))$tur='';
	$dis=$g['diskografi']??'';if(is_array($dis))$dis=implode("\n",array_map('strval',$dis));
	$url=function($k)use($g){$u=esc_url_raw((string)($g[$k]??''));return ($u&&preg_match('#^https?://#',$u))?$u:'';};
	$icerik.=yr_derleme_kaynak_html($g['kaynaklar']??[]);
	$yazar=(int)get_option('yr_derleme_yazar',0);
	if(!$yazar||!get_userdata($yazar)){$a=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID','orderby'=>'ID']);$yazar=$a?(int)$a[0]:0;}
	$pid=wp_insert_post(wp_slash(['post_type'=>'grup','post_status'=>(($g['durum']??'')==='yayin'?'publish':'draft'),'post_title'=>$ad,'post_name'=>sanitize_title($ad),'post_content'=>$icerik,'post_excerpt'=>sanitize_textarea_field((string)($g['ozet']??'')),'post_author'=>$yazar,'meta_input'=>[
		'_yr_derleme_id'=>$id,'_yr_bolge'=>$bolge,'_yr_stil'=>sanitize_text_field((string)($g['stil']??'')),'_yr_kurulus'=>sanitize_text_field((string)($g['kurulus']??'')),
		'_yr_link'=>$url('link'),'_yr_spotify'=>$url('spotify'),'_yr_youtube'=>$url('youtube'),'_yr_diskografi'=>sanitize_textarea_field((string)$dis)]]),true);
	if(is_wp_error($pid))return false;
	if($tur){if(!term_exists($tur,'grup_tur'))wp_insert_term($tur,'grup_tur');wp_set_object_terms($pid,[$tur],'grup_tur');}
	return true;
}
/* derlemeler/yayinla.json → {"idler":["2026-10-20-ornek", ...]}: bu kimlikle GitHub'dan gelmiş TASLAKLAR yayımlanır.
   Yalnızca derlemeden gelen içerik; her içerik bir kez (sonradan elle taslağa alınan tekrar yayımlanmaz). */
function yr_derleme_yayinla(){
	$d=json_decode((string)yr_gh_get('derlemeler/yayinla.json',200000),true);
	if(!is_array($d)||empty($d['idler'])||!is_array($d['idler']))return 0;
	$n=0;
	foreach(array_slice($d['idler'],0,300) as $id){
		$id=sanitize_key((string)$id);if($id==='')continue;
		foreach(get_posts(['post_type'=>['post','grup','etkinlik'],'post_status'=>'draft','meta_key'=>'_yr_derleme_id','meta_value'=>$id,'numberposts'=>1]) as $p){
			if(get_post_meta($p->ID,'_yr_yayinlandi',true))continue;
			if($p->post_type==='etkinlik'&&(string)get_post_meta($p->ID,'_yr_tarih',true)<current_time('Y-m-d'))continue;
			$r=wp_update_post(['ID'=>$p->ID,'post_status'=>'publish','post_name'=>$p->post_name?:sanitize_title($p->post_title)],true);
			if(!is_wp_error($r)){update_post_meta($p->ID,'_yr_yayinlandi',time());$n++;}
		}
	}
	return $n;
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
	wp_safe_redirect(add_query_arg(['yr_dc'=>$r['durum'],'yr_de'=>(int)($r['eklenen']??0),'yr_dk'=>(int)($r['etkinlik']??0),'yr_dg'=>(int)($r['grup']??0)],admin_url('edit.php?post_status=draft&yr_derleme=1')));exit;
});
/* Taslak listesinde yalnızca derleme taslaklarını süz */
add_action('pre_get_posts',function($q){
	if(is_admin()&&$q->is_main_query()&&!empty($_GET['yr_derleme'])&&in_array($q->get('post_type'),['','post','etkinlik','grup'],true))$q->set('meta_query',[['key'=>'_yr_derleme_id','compare'=>'EXISTS']]);
});
/* Bildirim */
add_action('admin_notices',function(){
	if(!current_user_can('edit_others_posts'))return;
	if(isset($_GET['yr_dc'])){
		$d=sanitize_key($_GET['yr_dc']);$n=(int)($_GET['yr_de']??0);$ek=(int)($_GET['yr_dk']??0);$eg=(int)($_GET['yr_dg']??0);
		$pp=array_filter([$n?$n.' yeni haber taslağı':'',$ek?$ek.' yeni etkinlik taslağı':'',$eg?$eg.' yeni grup taslağı':'']);
		$m=$d==='tamam'?($pp?implode(', ',$pp).' eklendi.':'Yeni derleme yok; her şey güncel.'):($d==='mesgul'?'Bir kontrol zaten sürüyor, birazdan tekrar dene.':'GitHub\'a ulaşılamadı. Biraz sonra tekrar dene.');
		echo '<div class="notice notice-'.($d==='tamam'?'success':'warning').' is-dismissible"><p>'.esc_html($m).'</p></div>';
	}
	$q=new WP_Query(['post_type'=>'post','post_status'=>'draft','meta_key'=>'_yr_derleme_id','posts_per_page'=>1,'fields'=>'ids']);
	$n=(int)$q->found_posts;$scr=function_exists('get_current_screen')?get_current_screen():null;
	$qe=new WP_Query(['post_type'=>'etkinlik','post_status'=>'draft','meta_key'=>'_yr_derleme_id','posts_per_page'=>1,'fields'=>'ids']);$ne=(int)$qe->found_posts;
	$qg=new WP_Query(['post_type'=>'grup','post_status'=>'draft','meta_key'=>'_yr_derleme_id','posts_per_page'=>1,'fields'=>'ids']);$ng=(int)$qg->found_posts;
	if($scr&&in_array($scr->id,['edit-post','edit-etkinlik','edit-grup'],true)&&!empty($_GET['yr_derleme']))return;
	if(!$n&&!$ne&&!$ng)return;
	$p=[];if($n)$p[]=(int)$n.' haber taslağı <a class="button button-primary" style="margin-left:6px" href="'.esc_url(admin_url('edit.php?post_status=draft&yr_derleme=1')).'">Haberleri incele</a>';
	if($ne)$p[]=(int)$ne.' konser/etkinlik taslağı <a class="button" style="margin-left:6px" href="'.esc_url(admin_url('edit.php?post_type=etkinlik&post_status=draft&yr_derleme=1')).'">Etkinlikleri incele</a>';
	if($ng)$p[]=(int)$ng.' grup taslağı <a class="button" style="margin-left:6px" href="'.esc_url(admin_url('edit.php?post_type=grup&post_status=draft&yr_derleme=1')).'">Grupları incele</a>';
	echo '<div class="notice notice-info"><p><b>Sabah derlemesi:</b> incelemeni bekleyen '.implode(' &nbsp;·&nbsp; ',$p).'</p></div>';
});
add_filter('views_edit-post',function($v){
	$s=get_option('yr_derleme_son',[]);
	$v['yr_derleme']='<a href="'.esc_url(admin_url('edit.php?post_status=draft&yr_derleme=1')).'"'.(!empty($_GET['yr_derleme'])?' class="current"':'').'>Sabah derlemeleri</a> · <a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_derleme_cek'),'yr_derleme_cek')).'">Şimdi çek</a>'.(!empty($s['zaman'])?' <span style="color:#777">(son kontrol: '.esc_html(human_time_diff((int)$s['zaman'])).' önce)</span>':'');
	return $v;
});

add_filter('views_edit-etkinlik',function($v){
	$v['yr_derleme']='<a href="'.esc_url(admin_url('edit.php?post_type=etkinlik&post_status=draft&yr_derleme=1')).'"'.(!empty($_GET['yr_derleme'])?' class="current"':'').'>Sabah derlemesinden gelenler</a> · <a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_derleme_cek'),'yr_derleme_cek')).'">Şimdi çek</a>';
	return $v;
});
add_filter('views_edit-grup',function($v){
	$v['yr_derleme']='<a href="'.esc_url(admin_url('edit.php?post_type=grup&post_status=draft&yr_derleme=1')).'"'.(!empty($_GET['yr_derleme'])?' class="current"':'').'>GitHub\'dan gelen taslaklar</a> · <a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_derleme_cek'),'yr_derleme_cek')).'">Şimdi çek</a>';
	return $v;
});

/* ================= Tema güncellenince önbellekleri bir kez temizle ================= */
/* Sunucu (LiteSpeed) ve yaygın önbellek eklentileri eski sayfaları göstermeye devam etmesin */
add_action('init',function(){
	if(get_option('yr_son_ver')===YR_VER)return;
	update_option('yr_son_ver',YR_VER);
	yr_onbellek_temizle();
},1);
function yr_onbellek_temizle(){
	if(!headers_sent())header('X-LiteSpeed-Purge: *');
	do_action('litespeed_purge_all');
	if(function_exists('rocket_clean_domain'))rocket_clean_domain();
	if(function_exists('w3tc_flush_all'))w3tc_flush_all();
	if(function_exists('wp_cache_clear_cache'))wp_cache_clear_cache();
	if(function_exists('sg_cachepress_purge_cache'))sg_cachepress_purge_cache();
	wp_cache_flush();
}
/* Özelleştir'de "Yayınla" denince de temizle: değişiklik hemen görünsün */
add_action('customize_save_after','yr_onbellek_temizle');

/* Onaylı yeni yorumda ve yorum onaylanınca önbelleği temizle: "Son Yorumlar" hemen güncellensin */
add_action('comment_post',function($id,$ok){if($ok===1||$ok==='1')yr_onbellek_temizle();},10,2);
add_action('transition_comment_status',function($new,$old){if($new==='approved'&&$old!=='approved')yr_onbellek_temizle();},10,2);
