<?php
if(!defined('ABSPATH'))exit;
/*
 * Kullanıcı paneli, beğen / beğenme, okunma sayacı ve yazar profilleri
 */

/* ================= Ortak yardımcılar ================= */
function yr_is_writer($u=null){$u=$u?$u:wp_get_current_user();return $u&&$u->exists()&&user_can($u,'edit_posts');}
function yr_rol_adi($u){
	$r=(array)$u->roles;
	if(in_array('administrator',$r,true))return 'Yönetici';
	if(in_array('editor',$r,true))return 'Editör';
	if(in_array('yazarock_yazar',$r,true)||in_array('author',$r,true)||in_array('contributor',$r,true))return 'Yazar';
	if(in_array('yazarock_uye',$r,true))return 'Üye';
	return 'Okur';
}
function yr_panel_url($bolum='',$args=[]){$u=yr_page_url('giris');if($bolum)$args=['bolum'=>$bolum]+$args;return $args?add_query_arg($args,$u):$u;}
function yr_sayi($n){$n=(int)$n;if($n>=1000000)return rtrim(rtrim(number_format($n/1000000,1,',','.'),'0'),',').' Mn';if($n>=10000)return rtrim(rtrim(number_format($n/1000,1,',','.'),'0'),',').' B';return number_format_i18n($n);}
function yr_ikon($n){
	$I=[
		'up'=>'<path d="M7 10v11H3V10zM7 10l4-7c1.7 0 3 1.3 3 3v3h5.5a2 2 0 0 1 2 2.3l-1.4 8A2 2 0 0 1 18.1 21H7"/>',
		'down'=>'<path d="M17 14V3h4v11zM17 14l-4 7c-1.7 0-3-1.3-3-3v-3H4.5a2 2 0 0 1-2-2.3l1.4-8A2 2 0 0 1 5.9 3H17"/>',
		'eye'=>'<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'pen'=>'<path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/>',
		'cmt'=>'<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12z"/>',
		'doc'=>'<path d="M6 2h9l5 5v15H6zM14 2v6h6M9 13h8M9 17h6"/>',
		'heart'=>'<path d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.8-8 11-8 11z"/>',
		'list'=>'<path d="M9 6h12M9 12h12M9 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
		'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
		'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'plus'=>'<path d="M12 5v14M5 12h14"/>',
		'out'=>'<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h11"/>',
		'x'=>'<path d="M4 4l16 16M20 4L4 20"/>',
		'ig'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
		'web'=>'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>',
		'shield'=>'<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
	];
	return isset($I[$n])?'<svg class="ic" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'.$I[$n].'</svg>':'';
}

/* ================= Beğen / beğenme ================= */
function yr_tepki_sayi($id){return ['l'=>max(0,(int)get_post_meta($id,'_yr_like',true)),'d'=>max(0,(int)get_post_meta($id,'_yr_dislike',true))];}
function yr_tepki_misafir_key($id){return 'yr_tp_'.md5(yr_ip().'|'.(int)$id);}
function yr_tepkilerim($uid=0){$uid=$uid?$uid:get_current_user_id();$m=$uid?get_user_meta($uid,'_yr_tepkiler',true):[];return is_array($m)?$m:[];}
function yr_tepki_benim($id){
	if(is_user_logged_in()){$m=yr_tepkilerim();return (int)($m[$id]??0);}
	return (int)get_transient(yr_tepki_misafir_key($id));
}
function yr_tepki_uygula($id,$v){
	$id=(int)$id;$v=max(-1,min(1,(int)$v));
	$p=get_post($id);
	if(!$p||$p->post_type!=='post'||$p->post_status!=='publish'||post_password_required($p))return new WP_Error('yr_yok','Bu yazıya oy verilemiyor.',['status'=>404]);
	$rk='yr_tpr_'.md5(yr_ip());$n=(int)get_transient($rk);
	if($n>=40)return new WP_Error('yr_bekle','Çok hızlı oy veriyorsun; biraz bekle.',['status'=>429]);
	set_transient($rk,$n+1,10*MINUTE_IN_SECONDS);
	$old=yr_tepki_benim($id);
	if($old!==$v){
		$c=yr_tepki_sayi($id);
		if($old===1)$c['l']--;elseif($old===-1)$c['d']--;
		if($v===1)$c['l']++;elseif($v===-1)$c['d']++;
		update_post_meta($id,'_yr_like',max(0,$c['l']));update_post_meta($id,'_yr_dislike',max(0,$c['d']));
		if(is_user_logged_in()){
			$m=yr_tepkilerim();unset($m[$id]);if($v!==0)$m[$id]=$v;
			if(count($m)>2000)$m=array_slice($m,-2000,null,true);
			update_user_meta(get_current_user_id(),'_yr_tepkiler',$m);
		}else{
			$k=yr_tepki_misafir_key($id);if($v===0)delete_transient($k);else set_transient($k,$v,7*DAY_IN_SECONDS);
		}
	}
	return yr_tepki_sayi($id)+['ben'=>$v];
}
function yr_tepki_kutu($id=0){
	$id=$id?$id:get_the_ID();$c=yr_tepki_sayi($id);$me=is_user_logged_in()?yr_tepki_benim($id):0;
	$f=function($v,$ic,$lab,$n)use($id,$me){
		$on=$me===$v;
		return '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="yr_tepki"><input type="hidden" name="id" value="'.(int)$id.'"><input type="hidden" name="v" value="'.($on?0:$v).'">'.(is_user_logged_in()?wp_nonce_field('yr_tepki','yr_n',false,false):'')
			.'<button type="submit" class="tp-b '.($v===1?'up':'dn').'" data-v="'.$v.'" aria-pressed="'.($on?'true':'false').'" aria-label="'.esc_attr($lab).'" title="'.esc_attr($lab).'">'.yr_ikon($ic).'<span class="tp-n">'.esc_html(yr_sayi($n)).'</span></button></form>';
	};
	echo '<div class="tepki" data-id="'.(int)$id.'" data-api="'.esc_url(rest_url('yazarock/v1/tepki')).'"><span class="tp-q">Bu yazıyı beğendin mi?</span>'.$f(1,'up','Beğendim',$c['l']).$f(-1,'down','Beğenmedim',$c['d']).'<span class="tp-msg" role="status" aria-live="polite"></span></div>';
}
add_action('rest_api_init',function(){
	register_rest_route('yazarock/v1','/tepki',['methods'=>'POST','permission_callback'=>'__return_true','args'=>['id'=>['required'=>true,'type'=>'integer'],'v'=>['required'=>true,'type'=>'integer','enum'=>[-1,0,1]]],'callback'=>function($r){
		$out=yr_tepki_uygula((int)$r['id'],(int)$r['v']);
		if(is_wp_error($out))return $out;
		$res=rest_ensure_response($out);$res->header('Cache-Control','no-store');return $res;
	}]);
	register_rest_route('yazarock/v1','/okundu',['methods'=>'POST','permission_callback'=>'__return_true','args'=>['id'=>['required'=>true,'type'=>'integer']],'callback'=>function($r){
		yr_okundu((int)$r['id']);$res=rest_ensure_response(['ok'=>true]);$res->header('Cache-Control','no-store');return $res;
	}]);
});
/* JavaScript kapalıyken de çalışsın */
add_action('admin_post_nopriv_yr_tepki','yr_tepki_form');add_action('admin_post_yr_tepki','yr_tepki_form');
function yr_tepki_form(){
	$id=(int)($_POST['id']??0);$v=(int)($_POST['v']??0);
	$back=get_permalink($id);if(!$back)$back=home_url('/');
	if(is_user_logged_in()&&(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_tepki'))){wp_safe_redirect($back.'#tepki');exit;}
	yr_tepki_uygula($id,$v);wp_safe_redirect($back.'#tepki');exit;
}

/* ================= Okunma sayacı ================= */
function yr_okunma($id){return max(0,(int)get_post_meta($id,'_yr_okunma',true));}
function yr_okundu($id){
	$p=get_post($id);if(!$p||$p->post_status!=='publish'||!in_array($p->post_type,['post','grup','etkinlik','liste'],true))return;
	if(is_user_logged_in()&&(int)$p->post_author===get_current_user_id())return;/* yazarın kendi okuması sayılmaz */
	$ua=strtolower((string)($_SERVER['HTTP_USER_AGENT']??''));if($ua===''||preg_match('/bot|crawl|spider|slurp|preview|headless|lighthouse/',$ua))return;
	$k='yr_ok_'.md5(yr_ip().'|'.$id);if(get_transient($k))return;set_transient($k,1,6*HOUR_IN_SECONDS);
	$rk='yr_okr_'.md5(yr_ip());$n=(int)get_transient($rk);if($n>=120)return;set_transient($rk,$n+1,HOUR_IN_SECONDS);
	update_post_meta($id,'_yr_okunma',yr_okunma($id)+1);
}

/* ================= İstatistikler ================= */
function yr_meta_toplam($uid,$key){
	global $wpdb;
	return (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)),0) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID=pm.post_id WHERE p.post_author=%d AND p.post_type='post' AND p.post_status='publish' AND pm.meta_key=%s",(int)$uid,$key));
}
function yr_yazar_ist($uid){
	$k='yr_yist_'.(int)$uid;$s=get_transient($k);
	if(!is_array($s)){
		$s=['yazi'=>(int)count_user_posts($uid,'post',true),'okunma'=>yr_meta_toplam($uid,'_yr_okunma'),'begeni'=>yr_meta_toplam($uid,'_yr_like'),'begenmeme'=>yr_meta_toplam($uid,'_yr_dislike'),'yorum'=>(int)get_comments(['post_author'=>$uid,'post_type'=>'post','status'=>'approve','count'=>true])];
		set_transient($k,$s,10*MINUTE_IN_SECONDS);
	}
	return $s;
}
function yr_durum_sayi($uid,$st){$q=new WP_Query(['post_type'=>'post','author'=>(int)$uid,'post_status'=>$st,'posts_per_page'=>1,'fields'=>'ids']);return (int)$q->found_posts;}
function yr_yazar_kategoriler($uid,$n=6){
	$ids=get_posts(['post_type'=>'post','author'=>(int)$uid,'post_status'=>'publish','numberposts'=>200,'fields'=>'ids']);if(!$ids)return [];
	$c=[];foreach(wp_get_object_terms($ids,'category',['fields'=>'all_with_object_id']) as $t){if(!isset($c[$t->term_id]))$c[$t->term_id]=[$t,0];$c[$t->term_id][1]++;}
	uasort($c,function($a,$b){return $b[1]<=>$a[1];});return array_slice($c,0,$n,true);
}
function yr_sosyal($uid){
	$o=[];foreach(['yr_twitter'=>['X','x'],'yr_instagram'=>['Instagram','ig'],'yr_web'=>['Web sitesi','web']] as $k=>$l){$u=get_user_meta($uid,$k,true);if($u&&preg_match('#^https?://#i',$u))$o[]='<a class="soc" href="'.esc_url($u).'" rel="noopener nofollow me" target="_blank" aria-label="'.esc_attr($l[0]).'" title="'.esc_attr($l[0]).'">'.yr_ikon($l[1]).'</a>';}
	return implode('',$o);
}
function yr_yazar_kutu($uid){
	$u=get_userdata($uid);if(!$u)return;$s=yr_yazar_ist($uid);$url=get_author_posts_url($uid);
	echo '<div class="abox2">'.get_avatar($uid,72).'<div class="ab-in"><span class="ab-k">Yazar</span><a class="ab-n" href="'.esc_url($url).'">'.esc_html($u->display_name).'</a>';
	if($u->description)echo '<p>'.esc_html(wp_trim_words($u->description,40)).'</p>';
	echo '<div class="ab-f"><span>'.yr_ikon('doc').esc_html(yr_sayi($s['yazi'])).' yazı</span><span>'.yr_ikon('up').esc_html(yr_sayi($s['begeni'])).' beğeni</span>'.yr_sosyal($uid).'<a class="more" href="'.esc_url($url).'">Tüm yazıları →</a></div></div></div>';
}
/* Yazı kartlarının altında küçük sayaçlar */
function yr_kart_ist($id){
	$c=yr_tepki_sayi($id);$o=yr_okunma($id);$out='';
	if($o)$out.='<span>'.yr_ikon('eye').esc_html(yr_sayi($o)).'</span>';
	if($c['l'])$out.='<span>'.yr_ikon('up').esc_html(yr_sayi($c['l'])).'</span>';
	return $out?'<span class="kst">'.$out.'</span>':'';
}

/* ================= Profil, şifre ve hesap işlemleri ================= */
add_action('admin_post_yr_profil',function(){
	$back=yr_panel_url('profil');$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_p',$s,$back).'#profil');exit;};
	if(!is_user_logged_in())$go('giris');
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_profil'))$go('guvenlik');
	$uid=get_current_user_id();
	$ad=trim(sanitize_text_field(wp_unslash($_POST['ad']??'')));
	if(mb_strlen($ad)<2||mb_strlen($ad)>40)$go('ad');
	$bio=mb_substr(sanitize_textarea_field(wp_unslash($_POST['bio']??'')),0,400);
	$r=wp_update_user(['ID'=>$uid,'display_name'=>$ad,'nickname'=>$ad,'description'=>$bio]);
	if(is_wp_error($r))$go('hata');
	foreach(['yr_twitter','yr_instagram','yr_web'] as $k){$v=esc_url_raw(trim(wp_unslash($_POST[$k]??'')),['http','https']);update_user_meta($uid,$k,$v);}
	if(!empty($_POST['foto_sil'])){yr_foto_temizle($uid);}
	if(!empty($_FILES['foto']['name'])&&(int)($_FILES['foto']['error']??1)!==UPLOAD_ERR_NO_FILE){
		$f=$_FILES['foto'];
		if((int)$f['error']!==UPLOAD_ERR_OK||(int)$f['size']>2*MB_IN_BYTES)$go('foto');
		$mimes=['jpg|jpeg|jpe'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
		$chk=wp_check_filetype_and_ext($f['tmp_name'],$f['name'],$mimes);
		if(empty($chk['type'])||!@getimagesize($f['tmp_name']))$go('foto');
		require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
		$_FILES['foto']['name']='profil-'.$uid.'-'.wp_generate_password(6,false,false).'.'.$chk['ext'];
		$att=media_handle_upload('foto',0,['post_title'=>'Profil fotoğrafı: '.$ad],['test_form'=>false,'mimes'=>$mimes]);
		if(is_wp_error($att))$go('foto');
		yr_foto_temizle($uid);
		$url=wp_get_attachment_image_url($att,'medium');if(!$url)$url=wp_get_attachment_url($att);
		update_user_meta($uid,'yr_foto',$url);update_user_meta($uid,'_yr_foto_att',(int)$att);
	}
	delete_transient('yr_yist_'.$uid);
	$go('tamam');
});
function yr_foto_temizle($uid){
	$a=(int)get_user_meta($uid,'_yr_foto_att',true);
	if($a&&(int)get_post_field('post_author',$a)===(int)$uid)wp_delete_attachment($a,true);
	delete_user_meta($uid,'_yr_foto_att');delete_user_meta($uid,'yr_foto');
}
add_action('admin_post_yr_sifre',function(){
	$back=yr_panel_url('profil');$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_p',$s,$back).'#sifre');exit;};
	if(!is_user_logged_in())$go('giris');
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_sifre'))$go('guvenlik');
	$u=wp_get_current_user();$rk='yr_sfr_'.$u->ID;$n=(int)get_transient($rk);if($n>=5)$go('bekle');
	$cur=(string)($_POST['eski']??'');$new=(string)($_POST['yeni']??'');$new2=(string)($_POST['yeni2']??'');
	if(!wp_check_password($cur,$u->user_pass,$u->ID)){set_transient($rk,$n+1,15*MINUTE_IN_SECONDS);$go('eski');}
	if(strlen($new)<8||$new!==$new2||$new===$cur)$go('yeni');
	wp_set_password($new,$u->ID);wp_set_current_user($u->ID);wp_set_auth_cookie($u->ID,true);delete_transient($rk);
	$go('sifre_ok');
});
add_action('admin_post_yr_hesap_sil',function(){
	$back=yr_panel_url('profil');$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_p',$s,$back).'#hesap');exit;};
	if(!is_user_logged_in())$go('giris');
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_hesap_sil'))$go('guvenlik');
	$u=wp_get_current_user();
	if(!yr_is_member()||count((array)$u->roles)!==1)$go('yetki');/* yazar ve yönetici hesapları buradan silinmez */
	$rk='yr_sfr_'.$u->ID;$n=(int)get_transient($rk);if($n>=5)$go('bekle');
	if(empty($_POST['onay'])||!wp_check_password((string)($_POST['sifre']??''),$u->user_pass,$u->ID)){set_transient($rk,$n+1,15*MINUTE_IN_SECONDS);$go('sil_hata');}
	/* beğenileri sayaçlardan düş */
	foreach(yr_tepkilerim($u->ID) as $pid=>$v){$c=yr_tepki_sayi($pid);if($v===1)update_post_meta($pid,'_yr_like',max(0,$c['l']-1));elseif($v===-1)update_post_meta($pid,'_yr_dislike',max(0,$c['d']-1));}
	yr_foto_temizle($u->ID);
	require_once ABSPATH.'wp-admin/includes/user.php';
	wp_logout();wp_delete_user($u->ID);
	wp_die('Hesabın ve sana ait listeler silindi. Görüşmek üzere! <a href="'.esc_url(home_url('/')).'">Ana sayfaya dön</a>','Yazarock',['response'=>200]);
});

/* ================= Yönlendirmeler ve menü ================= */
/* Menüde "Giriş Yap" giriş yapınca "Panelim" olur */
add_filter('wp_nav_menu_objects',function($items){if(is_user_logged_in())foreach($items as $it)if(in_array('login',(array)$it->classes,true))$it->title='Panelim';return $items;});

/* ================= Yazar sayfası: yapılandırılmış veri ================= */
add_action('wp_head',function(){
	if(!is_author())return;$u=get_queried_object();if(!$u instanceof WP_User)return;
	$same=[];foreach(['yr_twitter','yr_instagram','yr_web'] as $k){$v=get_user_meta($u->ID,$k,true);if($v)$same[]=esc_url_raw($v);}
	$j=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>array_filter(['@type'=>'Person','name'=>$u->display_name,'description'=>$u->description?wp_strip_all_tags($u->description):null,'image'=>get_avatar_url($u->ID,['size'=>256]),'url'=>get_author_posts_url($u->ID),'sameAs'=>$same?:null])];
	echo '<script type="application/ld+json">'.wp_json_encode($j,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
},6);
/* Panel ve yazı sayfalarında oturum açmış kullanıcıya REST anahtarı ver (önbelleğe alınmayan sayfalar) */
add_action('wp_footer',function(){echo '<script>window.YR_API='.wp_json_encode(rest_url()).';</script>';},1);
add_action('wp_footer',function(){
	if(!is_user_logged_in())return;
	echo '<script>window.YR_NONCE='.wp_json_encode(wp_create_nonce('wp_rest')).';</script>';
},1);
/* Panel sayfası önbelleğe alınmasın */
add_action('template_redirect',function(){if(is_page('giris')&&is_user_logged_in())nocache_headers();});

/* ================= v1.5 geçişi: Yazarlar sayfası, menü ve gizlilik notu ================= */
add_action('admin_init',function(){
	if(get_option('yr_mig_v5')||!current_user_can('manage_options'))return;
	update_option('yr_mig_v5',1);
	yr_page('Yazarlar','yazarlar');
	$m=wp_get_nav_menu_object('Ana Menü');
	if($m){
		$items=(array)wp_get_nav_menu_items($m->term_id);$has=false;$pos=0;
		foreach($items as $it){if(untrailingslashit($it->url)===untrailingslashit(yr_page_url('yazarlar'))||$it->title==='Yazarlar')$has=true;if($it->title==='Yazar Ol')$pos=(int)$it->menu_order;}
		if(!$has){
			if($pos)foreach($items as $it)if((int)$it->menu_order>=$pos)wp_update_post(['ID'=>$it->ID,'menu_order'=>(int)$it->menu_order+1]);
			wp_update_nav_menu_item($m->term_id,0,['menu-item-title'=>'Yazarlar','menu-item-url'=>yr_page_url('yazarlar'),'menu-item-type'=>'custom','menu-item-status'=>'publish','menu-item-position'=>$pos?$pos:0]);
		}
	}
	$k=get_page_by_path('kvkk-aydinlatma-metni',OBJECT,'page');
	if($k&&strpos($k->post_content,'Beğeni ve Okunma Sayaçları')===false)wp_update_post(['ID'=>$k->ID,'post_content'=>wp_slash($k->post_content.'<h2>Beğeni ve Okunma Sayaçları</h2><p>Yazılara verdiğiniz beğen / beğenme oyları ve okunma sayıları istatistik amacıyla tutulur. Üye değilseniz, aynı yazıya tekrar tekrar oy verilmesini ve sahte okunmaları önlemek için IP adresiniz geri döndürülemez şekilde özetlenerek (hash) en fazla 7 gün saklanır; IP adresinizin kendisi kaydedilmez. Üyeyseniz verdiğiniz oylar, panelinizdeki "Beğendiklerim" bölümünü oluşturmak için hesabınızla ilişkilendirilir ve hesabınızı sildiğinizde silinir.</p><h2>Profil Bilgileri</h2><p>Panelinizden eklediğiniz profil fotoğrafı, kısa biyografi ve sosyal medya bağlantıları; yazarsanız yazar sayfanızda herkese açık olarak gösterilir. Üyeler hesaplarını panelden kendileri silebilir.</p>')]);
	flush_rewrite_rules();
});
