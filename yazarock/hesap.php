<?php
if(!defined('ABSPATH'))exit;
/*
 * Hesaplar: kullanıcı adı ve şifre formda belirlenir, e-postaya yalnızca onay bağlantısı gider.
 * Üye ol, yazarlık başvurusu, e-posta onayı, onaylanmamış hesapların temizliği.
 */

/* ================= Kullanıcı adı ve şifre kuralları ================= */
function yr_kadi_temizle($s){
	$s=strtr(mb_strtolower(trim((string)$s),'UTF-8'),['ç'=>'c','ğ'=>'g','ı'=>'i','i̇'=>'i','ö'=>'o','ş'=>'s','ü'=>'u','â'=>'a','î'=>'i','û'=>'u',' '=>'']);
	return preg_replace('/[^a-z0-9._-]/','',$s);
}
function yr_kadi_hata($k){
	if(!preg_match('/^[a-z0-9._-]{3,24}$/',$k))return 'Kullanıcı adı 3-24 karakter olmalı; yalnızca küçük harf (Türkçe karakter olmadan), rakam, nokta, alt çizgi ve tire kullanılabilir.';
	if(!preg_match('/[a-z]/',$k))return 'Kullanıcı adında en az bir harf olmalı.';
	if(in_array($k,['admin','administrator','yazarock','root','editor','editör','yonetici','yönetici','info','destek','support','mail','webmaster','moderator','system','sistem','iletisim','null'],true)||strpos($k,'yazarock')!==false)return 'Bu kullanıcı adı ayrılmış; başka bir ad seç.';
	if(username_exists($k)||yr_kadi_beklemede($k))return 'Bu kullanıcı adı alınmış; başka bir ad seç.';
	return '';
}
/* onay bekleyen yazar başvurularındaki kullanıcı adları da dolu sayılır */
function yr_kadi_beklemede($k){return (bool)get_posts(['post_type'=>'basvuru','post_status'=>'any','numberposts'=>1,'fields'=>'ids','meta_query'=>[['key'=>'_kadi','value'=>$k],['key'=>'_durum','value'=>'yeni']]]);}
function yr_sifre_hata($p,$k='',$m=''){
	$p=(string)$p;
	if(mb_strlen($p)<8)return 'Şifre en az 8 karakter olmalı.';
	if(mb_strlen($p)>100)return 'Şifre çok uzun.';
	if(!preg_match('/[A-Za-zçğıöşüÇĞİÖŞÜ]/u',$p)||!preg_match('/\d/',$p))return 'Şifrede en az bir harf ve bir rakam olmalı.';
	$l=mb_strtolower($p);if(($k&&$l===mb_strtolower($k))||($m&&$l===mb_strtolower($m))||in_array($l,['12345678','password','sifre123','qwerty123','yazarock1','123456789','11111111','abcd1234','password1'],true))return 'Bu şifre çok kolay tahmin edilir; daha güçlü bir şifre seç.';
	return '';
}
/* Hata mesajını kısa süreliğine sakla (adres çubuğunda metin taşımamak için) */
function yr_hata_kaydet($msg){$k=wp_generate_password(10,false,false);set_transient('yr_err_'.$k,(string)$msg,10*MINUTE_IN_SECONDS);return $k;}
function yr_hata_goster($param='yr_e'){
	$k=preg_replace('/[^A-Za-z0-9]/','',(string)($_GET[$param]??''));if(!$k)return;$m=get_transient('yr_err_'.$k);if(!$m)return;
	echo '<div class="notice err" role="alert">'.esc_html($m).'</div>';
}

/* ================= E-posta onayı ================= */
function yr_onayli($uid){return !get_user_meta((int)$uid,'_yr_onay_hash',true);}
function yr_token(){return wp_generate_password(32,false,false);}
function yr_onay_baslat($uid){
	$u=get_userdata($uid);if(!$u)return false;$t=yr_token();
	update_user_meta($uid,'_yr_onay_hash',wp_hash($t));update_user_meta($uid,'_yr_onay_zaman',time());
	$link=add_query_arg('yr_dogrula',$uid.'.'.$t,home_url('/'));
	return wp_mail($u->user_email,'Yazarock: e-posta adresini onayla',"Merhaba {$u->display_name},\n\nYazarock'a hoş geldin! Hesabını tamamlamak için aşağıdaki bağlantıya tıklayarak e-posta adresini onayla:\n\n$link\n\nKullanıcı adın: {$u->user_login}\n\nBağlantı 7 gün geçerlidir. Bu hesabı sen açmadıysan bu e-postayı yok sayabilirsin; onaylanmayan hesaplar 7 gün sonra otomatik silinir.\n\nYazarock\n".home_url('/'));
}
add_action('template_redirect',function(){
	foreach(['yr_dogrula','yr_bdogrula'] as $p){
		if(empty($_GET[$p]))continue;
		$v=sanitize_text_field(wp_unslash($_GET[$p]));nocache_headers();
		if(!preg_match('/^(\d+)\.([A-Za-z0-9]{32})$/',$v,$m))wp_die('Onay bağlantısı geçersiz. <a href="'.esc_url(home_url('/')).'">Ana sayfaya dön</a>','Yazarock',['response'=>400]);
		$id=(int)$m[1];$t=$m[2];
		if($p==='yr_dogrula'){
			$h=(string)get_user_meta($id,'_yr_onay_hash',true);
			if($h&&hash_equals($h,wp_hash($t))&&time()-(int)get_user_meta($id,'_yr_onay_zaman',true)<7*DAY_IN_SECONDS){
				delete_user_meta($id,'_yr_onay_hash');delete_user_meta($id,'_yr_onay_zaman');update_user_meta($id,'_yr_onay_tarih',current_time('mysql'));
				wp_safe_redirect(add_query_arg('yr_p','onaylandi',yr_panel_url()));exit;
			}
			if(!$h&&get_userdata($id))wp_safe_redirect(add_query_arg('yr_p','onaylandi',yr_panel_url()));else wp_die('Onay bağlantısı geçersiz ya da süresi dolmuş. Giriş yapıp panelinden yeni bir onay e-postası isteyebilirsin. <a href="'.esc_url(yr_panel_url()).'">Giriş yap</a>','Yazarock',['response'=>400]);
			exit;
		}
		/* yazarlık başvurusu onayı */
		$h=(string)get_post_meta($id,'_onay_hash',true);
		if(get_post_type($id)==='basvuru'&&$h&&hash_equals($h,wp_hash($t))){
			delete_post_meta($id,'_onay_hash');update_post_meta($id,'_eposta_onay',current_time('mysql'));yr_basvuru_bildir($id);
			wp_safe_redirect(add_query_arg('yr_y','onaylandi',yr_page_url('yazar-ol')).'#basvuru-form');exit;
		}
		if(get_post_type($id)==='basvuru'&&get_post_meta($id,'_eposta_onay',true)){wp_safe_redirect(add_query_arg('yr_y','onaylandi',yr_page_url('yazar-ol')).'#basvuru-form');exit;}
		wp_die('Onay bağlantısı geçersiz ya da süresi dolmuş. <a href="'.esc_url(yr_page_url('yazar-ol')).'">Yazar Ol sayfasına dön</a>','Yazarock',['response'=>400]);
	}
},2);
add_action('admin_post_yr_onay_tekrar',function(){
	$back=yr_panel_url();
	if(!is_user_logged_in()||!isset($_GET['_wpnonce'])||!wp_verify_nonce($_GET['_wpnonce'],'yr_onay_tekrar')){wp_safe_redirect($back);exit;}
	$uid=get_current_user_id();if(yr_onayli($uid)){wp_safe_redirect($back);exit;}
	$k='yr_ont_'.$uid;$n=(int)get_transient($k);if($n>=3){wp_safe_redirect(add_query_arg('yr_p','onay_bekle',$back));exit;}
	set_transient($k,$n+1,HOUR_IN_SECONDS);yr_onay_baslat($uid);
	wp_safe_redirect(add_query_arg('yr_p','onay_gitti',$back));exit;
});
function yr_onay_banner(){
	if(!is_user_logged_in())return;$u=wp_get_current_user();
	if(yr_onayli($u->ID))return;
	echo '<div class="notice warn onayb" role="status">'.yr_ikon('shield').'<div><b>E-posta adresini onayla.</b> <span>'.esc_html($u->user_email).' adresine bir onay bağlantısı gönderdik (spam klasörünü de kontrol et). Onaylayana kadar liste oluşturamaz ve yorum yazamazsın; onaylanmayan hesaplar 7 gün sonra silinir.</span></div><a class="btn alt" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_onay_tekrar'),'yr_onay_tekrar')).'">Tekrar gönder</a></div>';
}
/* onaylanmamış hesap: yorum ve liste yok */
add_action('pre_comment_on_post',function(){if(is_user_logged_in()&&!yr_onayli(get_current_user_id()))wp_die('Yorum yazabilmek için önce e-posta adresini onaylamalısın. <a href="'.esc_url(yr_panel_url()).'">Panelim</a>','Yazarock',['response'=>403,'back_link'=>true]);},5);

/* ================= Kullanıcı adı uygunluk kontrolü (formda anlık) ================= */
add_action('rest_api_init',function(){
	register_rest_route('yazarock/v1','/kadi',['methods'=>'GET','permission_callback'=>'__return_true','args'=>['ad'=>['required'=>true,'type'=>'string']],'callback'=>function($r){
		$rk='yr_kadr_'.md5(yr_ip());$n=(int)get_transient($rk);if($n>=40)return new WP_Error('yr_bekle','Biraz bekle.',['status'=>429]);set_transient($rk,$n+1,10*MINUTE_IN_SECONDS);
		$k=yr_kadi_temizle($r['ad']);$h=yr_kadi_hata($k);
		$res=rest_ensure_response(['ad'=>$k,'uygun'=>$h==='','mesaj'=>$h?:'Bu kullanıcı adı uygun.']);$res->header('Cache-Control','no-store');return $res;
	}]);
});

/* ================= Üye ol ================= */
remove_action('admin_post_nopriv_yr_uye','yr_uye');remove_action('admin_post_yr_uye','yr_uye');
add_action('admin_post_nopriv_yr_uye','yr_uye_kayit');add_action('admin_post_yr_uye','yr_uye_kayit');
function yr_uye_kayit(){
	$back=yr_page_url('uye-ol');
	$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_u',$s,$back).'#uye');exit;};
	$err=function($msg)use($back){wp_safe_redirect(add_query_arg('yr_e',yr_hata_kaydet($msg),$back).'#uye');exit;};
	if(is_user_logged_in()){wp_safe_redirect(yr_panel_url());exit;}
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_uye'))$go('guvenlik');
	if(!empty($_POST['website']))$go('tamam');
	if(!yr_ts_ok())$go('guvenlik');
	$k='yr_rl_u_'.md5(yr_ip());if(get_transient($k))$go('bekle');
	$g='yr_rl_ug_'.md5(yr_ip());$gn=(int)get_transient($g);if($gn>=5)$err('Bugün bu bağlantıdan çok fazla hesap açıldı; yarın tekrar dene.');
	$ad=trim(sanitize_text_field(wp_unslash($_POST['ad']??'')));$m=sanitize_email(wp_unslash($_POST['email']??''));
	$kadi=yr_kadi_temizle(wp_unslash($_POST['kadi']??''));
	$sifre=(string)($_POST['sifre']??'');$sifre2=(string)($_POST['sifre2']??'');
	if(mb_strlen($ad)<2||mb_strlen($ad)>40)$err('Adın 2-40 karakter olmalı.');
	if($h=yr_kadi_hata($kadi))$err($h);
	if(!yr_eposta_ok($m))$err('E-posta adresi geçersiz görünüyor; yazımını kontrol et.');
	if(email_exists($m))$err('Bu e-posta adresiyle zaten bir hesap var. Giriş yapabilir ya da "Şifremi unuttum" ile yeni şifre alabilirsin.');
	if($h=yr_sifre_hata(wp_unslash($sifre),$kadi,$m))$err($h);
	if($sifre!==$sifre2)$err('Şifreler birbiriyle aynı değil.');
	if(empty($_POST['kvkk']))$err('Devam etmek için KVKK Aydınlatma Metni onayını işaretlemelisin.');
	set_transient($k,1,30);set_transient($g,$gn+1,DAY_IN_SECONDS);
	$uid=wp_insert_user(['user_login'=>$kadi,'user_email'=>$m,'user_pass'=>$sifre,'display_name'=>$ad,'nickname'=>$ad,'user_nicename'=>'uye-'.strtolower(wp_generate_password(10,false,false)),'role'=>'yazarock_uye']);
	if(is_wp_error($uid))$err('Hesap oluşturulamadı: '.$uid->get_error_message());
	update_user_meta($uid,'_yr_kvkk',current_time('mysql'));
	yr_onay_baslat($uid);
	wp_set_current_user($uid);wp_set_auth_cookie($uid,true);
	wp_safe_redirect(add_query_arg('yr_p','hosgeldin',yr_panel_url()));exit;
}

/* ================= Yazarlık başvurusu ================= */
add_action('admin_post_nopriv_yr_yazar_basvuru','yr_yazar_basvuru');add_action('admin_post_yr_yazar_basvuru','yr_yazar_basvuru');
function yr_yazar_basvuru(){
	$back=yr_page_url('yazar-ol');
	$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_y',$s,$back).'#basvuru-form');exit;};
	$err=function($msg)use($back){wp_safe_redirect(add_query_arg('yr_e',yr_hata_kaydet($msg),$back).'#basvuru-form');exit;};
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_yazar'))$go('guvenlik');
	if(!empty($_POST['website']))$go('tamam');
	if(!yr_ts_ok())$go('guvenlik');
	if(!is_user_logged_in())$go('uye');
	$rk='yr_rl_y_'.md5(yr_ip());if(get_transient($rk))$go('bekle');
	$alan=sanitize_text_field(wp_unslash($_POST['alan']??''));$lk=esc_url_raw(wp_unslash($_POST['link']??''),['http','https']);$ms=mb_substr(sanitize_textarea_field(wp_unslash($_POST['msg']??'')),0,1000);
	if(mb_strlen($ms)<40)$err('Kendini ve yazmak istediğin konuları en az 40 karakterle anlat.');
	if(empty($_POST['ok'])||empty($_POST['kvkk']))$err('Devam etmek için iki onay kutusunu da işaretlemelisin.');
	$meta=['_alan'=>$alan,'_link'=>$lk,'_durum'=>'yeni','_kvkk'=>current_time('mysql')];
	$u=wp_get_current_user();
	if(yr_is_writer($u))$err('Zaten yazarsın; panelinden yeni yazı yazabilirsin.');
	if(get_posts(['post_type'=>'basvuru','post_status'=>'any','numberposts'=>1,'fields'=>'ids','meta_query'=>[['key'=>'_uid','value'=>$u->ID],['key'=>'_durum','value'=>'yeni']]]))$err('Değerlendirmede olan bir başvurun zaten var.');
	$ad=$u->display_name;$m=$u->user_email;$meta['_uid']=$u->ID;$meta['_email']=$m;$meta['_kadi']=$u->user_login;
	if(!yr_onayli($u->ID))$err('Başvurmadan önce e-posta adresini onaylamalısın. Onay bağlantısını panelinden tekrar isteyebilirsin.');
	$meta['_eposta_onay']=current_time('mysql');
	set_transient($rk,1,60);
	$pid=wp_insert_post(['post_type'=>'basvuru','post_status'=>'publish','post_title'=>$ad,'post_content'=>$ms,'meta_input'=>$meta],true);
	if(is_wp_error($pid))$err('Başvuru kaydedilemedi, tekrar dene.');
	yr_basvuru_bildir($pid);
	$go('tamam');
}
function yr_basvuru_bildir($pid){
	$m=get_post_meta($pid,'_email',true);$lk=get_post_meta($pid,'_link',true);
	wp_mail(get_option('admin_email'),'Yeni yazarlık başvurusu: '.get_the_title($pid),"Ad: ".get_the_title($pid)."\nE-posta: $m (onaylı)\nKullanıcı adı: ".get_post_meta($pid,'_kadi',true)."\nAlan: ".get_post_meta($pid,'_alan',true)."\nÖrnek yazı: ".($lk?$lk:'-')."\n\n".get_post_field('post_content',$pid)."\n\nYönetim: ".admin_url('edit.php?post_type=basvuru'),['Reply-To: '.$m]);
}
/* Başvuruyu onayla: hesap aç ya da üyeyi yazara yükselt; şifre e-postası yok */
remove_all_actions('admin_post_yr_onayla');
add_action('admin_post_yr_onayla',function(){
	global $wpdb;
	$id=(int)($_GET['id']??0);check_admin_referer('yr_onayla'.$id);
	if(!current_user_can('create_users'))wp_die('Yetkin yok.');
	if(get_post_type($id)!=='basvuru')wp_die('Başvuru bulunamadı.');
	$m=(string)get_post_meta($id,'_email',true);$ad=get_the_title($id);$uid=(int)get_post_meta($id,'_uid',true);
	if(!$uid&&($ex=email_exists($m))){$eu=get_userdata($ex);if($eu&&in_array('yazarock_uye',(array)$eu->roles,true))$uid=(int)$ex;else wp_die('Bu e-posta adresiyle zaten bir yazar/yönetici hesabı var.');}
	$giris=yr_panel_url();
	if($uid){
		$u=get_userdata($uid);if(!$u)wp_die('Kullanıcı bulunamadı.');
		$u->set_role('yazarock_yazar');
		wp_mail($u->user_email,'Yazarock: yazarlık başvurun onaylandı',"Merhaba {$u->display_name},\n\nTebrikler, Yazarock yazarlık başvurun onaylandı! Hesabın artık yazar hesabı.\n\nKullanıcı adın: {$u->user_login}\nMevcut şifrenle giriş yapıp panelinden ilk yazını yazabilirsin:\n$giris\n\nYazılar yayına alınmadan önce editörlerimiz tarafından incelenir.\n\nAramıza hoş geldin!\nYazarock");
	}else{
		if(!is_email($m))wp_die('Başvurudaki e-posta adresi geçersiz.');
		$k=(string)get_post_meta($id,'_kadi',true);$hash=(string)get_post_meta($id,'_sifre_hash',true);
		if(!$k)$k=yr_kadi_temizle($ad);if(strlen($k)<3)$k='yazar';$base=$k;$i=1;while(username_exists($k))$k=$base.(++$i);
		$uid=wp_insert_user(['user_login'=>$k,'user_email'=>$m,'display_name'=>$ad,'nickname'=>$ad,'user_pass'=>wp_generate_password(32),'role'=>'yazarock_yazar']);
		if(is_wp_error($uid))wp_die(esc_html($uid->get_error_message()));
		if($hash){$wpdb->update($wpdb->users,['user_pass'=>$hash],['ID'=>$uid]);clean_user_cache($uid);delete_post_meta($id,'_sifre_hash');}
		if(!get_post_meta($id,'_eposta_onay',true))yr_onay_baslat($uid);
		update_user_meta($uid,'_yr_kvkk',(string)get_post_meta($id,'_kvkk',true));
		if($hash){
			wp_mail($m,'Yazarock: yazarlık başvurun onaylandı',"Merhaba $ad,\n\nTebrikler, Yazarock yazarlık başvurun onaylandı!\n\nKullanıcı adın: $k".($k!==$base?" (seçtiğin ad bu arada alındığı için sonuna rakam eklendi)":"")."\nBaşvuruda belirlediğin şifreyle giriş yapabilirsin:\n$giris\n\nŞifreni unuttuysan: ".wp_lostpassword_url()."\n\nYazılar yayına alınmadan önce editörlerimiz tarafından incelenir.\n\nAramıza hoş geldin!\nYazarock");
		}else wp_new_user_notification($uid,null,'user');/* eski başvurular: şifre yoktu */
	}
	update_post_meta($id,'_durum','onaylandi');update_post_meta($id,'_uid',$uid);
	wp_safe_redirect(admin_url('edit.php?post_type=basvuru'));exit;
});
/* Başvuru listesinde e-posta onayı ve kullanıcı adı */
add_filter('manage_basvuru_posts_columns',function($c){$c['yr_kadi']='Kullanıcı adı';return $c;},20);
add_action('manage_basvuru_posts_custom_column',function($c,$id){
	if($c==='yr_mail')echo get_post_meta($id,'_eposta_onay',true)?' <span title="E-posta onaylı" style="color:#2e7d32">✓ onaylı</span>':(get_post_meta($id,'_onay_hash',true)?' <span style="color:#b26a00">onay bekliyor</span>':'');
	if($c==='yr_kadi')echo esc_html(get_post_meta($id,'_kadi',true));
},20,2);

/* ================= Temizlik: onaylanmayan hesap ve başvurular 7 gün sonra silinir ================= */
add_action('init',function(){if(!wp_next_scheduled('yr_hesap_temizlik'))wp_schedule_event(time()+300,'daily','yr_hesap_temizlik');});
add_action('switch_theme',function(){wp_clear_scheduled_hook('yr_hesap_temizlik');});
add_action('yr_hesap_temizlik',function(){
	require_once ABSPATH.'wp-admin/includes/user.php';
	foreach(get_users(['role'=>'yazarock_uye','meta_key'=>'_yr_onay_zaman','meta_value'=>time()-7*DAY_IN_SECONDS,'meta_compare'=>'<','meta_type'=>'NUMERIC','number'=>200]) as $u){
		if(yr_onayli($u->ID)||count((array)$u->roles)!==1)continue;
		foreach(yr_tepkilerim($u->ID) as $pid=>$v){$c=yr_tepki_sayi($pid);if($v===1)update_post_meta($pid,'_yr_like',max(0,$c['l']-1));elseif($v===-1)update_post_meta($pid,'_yr_dislike',max(0,$c['d']-1));}
		wp_delete_user($u->ID);
	}
	foreach(get_posts(['post_type'=>'basvuru','post_status'=>'any','numberposts'=>200,'date_query'=>[['before'=>'7 days ago']],'meta_key'=>'_onay_hash','meta_compare'=>'EXISTS']) as $p)wp_delete_post($p->ID,true);
});

/* Menüde "Yazar Ol" yalnızca yazar olmayan üyelere görünür */
add_filter('wp_nav_menu_objects',function($items){
	$goster=is_user_logged_in()&&!yr_is_writer();$yo=untrailingslashit(yr_page_url('yazar-ol'));
	if($goster)return $items;
	return array_filter($items,function($it)use($yo){return !(untrailingslashit((string)$it->url)===$yo||$it->title==='Yazar Ol');});
},20);
