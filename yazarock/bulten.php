<?php
if(!defined('ABSPATH'))exit;
/*
 * E-posta gönderimi (SMTP) ve haftalık bülten.
 * Panel: Aboneler → Bülten ve E-posta
 */

/* ---------- Ayarlar ---------- */
function yr_bl_ayar($k=null){
	$v=wp_parse_args((array)get_option('yr_bulten_ayar',[]),[
		'smtp_host'=>'mail.yazarock.com','smtp_port'=>465,'smtp_guv'=>'ssl','smtp_user'=>'info@yazarock.com','smtp_pass'=>'',
		'from'=>'info@yazarock.com','from_ad'=>'Yazarock','aktif'=>1,'gun'=>5,'saat'=>10,'parti'=>30,
	]);
	return $k===null?$v:($v[$k]??null);
}
function yr_bl_smtp_hazir(){$a=yr_bl_ayar();return $a['smtp_host']!==''&&$a['smtp_user']!==''&&$a['smtp_pass']!=='';}

/* ---------- SMTP ---------- */
add_action('phpmailer_init',function($m){
	$a=yr_bl_ayar();
	if(yr_bl_smtp_hazir()){
		$m->isSMTP();$m->Host=$a['smtp_host'];$m->Port=(int)$a['smtp_port'];$m->SMTPAuth=true;
		$m->Username=$a['smtp_user'];$m->Password=$a['smtp_pass'];
		$m->SMTPSecure=$a['smtp_guv']==='tls'?'tls':($a['smtp_guv']==='ssl'?'ssl':'');$m->SMTPAutoTLS=$a['smtp_guv']!=='';
		$m->Timeout=20;
	}
	if(is_email($a['from'])){$m->Sender=$a['from'];}
});
add_filter('wp_mail_from',function($f){$a=yr_bl_ayar('from');return is_email($a)?$a:$f;});
add_filter('wp_mail_from_name',function($n){$a=yr_bl_ayar('from_ad');return $a!==''?$a:$n;});
add_action('wp_mail_failed',function($e){update_option('yr_mail_hata',['zaman'=>time(),'mesaj'=>mb_substr($e->get_error_message(),0,400)],false);});

/* ---------- Bülten içeriği ---------- */
function yr_bl_icerik($gun=7){
	$son=new WP_Query(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>12,'no_found_rows'=>true,'date_query'=>[['after'=>$gun.' days ago']]]);
	$ev=new WP_Query(['post_type'=>'etkinlik','posts_per_page'=>10,'no_found_rows'=>true,'meta_key'=>'_yr_tarih','orderby'=>'meta_value','order'=>'ASC','meta_query'=>[['key'=>'_yr_tarih','value'=>[current_time('Y-m-d'),date('Y-m-d',current_time('timestamp')+14*DAY_IN_SECONDS)],'compare'=>'BETWEEN','type'=>'DATE']]]);
	$gr=new WP_Query(['post_type'=>'grup','post_status'=>'publish','posts_per_page'=>8,'no_found_rows'=>true,'date_query'=>[['after'=>$gun.' days ago']],'meta_key'=>'_yr_bolge','meta_value'=>'tr']);
	return ['yazilar'=>$son->posts,'etkinlikler'=>$ev->posts,'gruplar'=>$gr->posts];
}
function yr_bl_html($c,$cik=''){
	$k='#c0282d';$b='font-family:Arial,Helvetica,sans-serif;';
	$h='<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head><body style="margin:0;background:#f2eeee;'.$b.'">';
	$h.='<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2eeee"><tr><td align="center" style="padding:20px 10px">';
	$h.='<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff">';
	$h.='<tr><td style="background:#0d0809;padding:22px 24px;border-bottom:4px solid '.$k.'"><a href="'.esc_url(home_url('/')).'" style="color:#fff;text-decoration:none;font:700 26px Arial,sans-serif;letter-spacing:2px">YAZAROCK</a><div style="color:#a69a9b;font-size:13px;margin-top:4px">Haftalık bülten · '.esc_html(date_i18n('j F Y')).'</div></td></tr>';
	$bas=function($t)use($k){return '<tr><td style="padding:22px 24px 6px"><div style="font:700 17px Arial,sans-serif;color:#111;border-bottom:2px solid '.$k.';padding-bottom:6px;text-transform:uppercase">'.esc_html($t).'</div></td></tr>';};
	if($c['yazilar']){$h.=$bas('Bu hafta Yazarock\'ta');
		foreach($c['yazilar'] as $i=>$p){$u=get_permalink($p);$img=$i<3?get_the_post_thumbnail_url($p,'medium_large'):'';
			$h.='<tr><td style="padding:12px 24px">'.($img?'<a href="'.esc_url($u).'"><img src="'.esc_url($img).'" width="552" alt="" style="width:100%;max-width:552px;height:auto;display:block;margin-bottom:8px"></a>':'').'<a href="'.esc_url($u).'" style="color:#111;font-weight:700;font-size:16px;text-decoration:none">'.esc_html(get_the_title($p)).'</a><div style="color:#555;font-size:14px;line-height:1.5;margin-top:4px">'.esc_html(wp_trim_words(get_the_excerpt($p),28,'…')).'</div></td></tr>';}}
	if($c['etkinlikler']){$h.=$bas('Önümüzdeki iki hafta sahnede');$h.='<tr><td style="padding:6px 24px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">';
		foreach($c['etkinlikler'] as $e){$t=get_post_meta($e->ID,'_yr_tarih',true);$s=get_post_meta($e->ID,'_yr_sehir',true);
			$h.='<tr><td style="padding:7px 0;border-bottom:1px solid #eee;width:70px;color:'.$k.';font-weight:700;font-size:13px;vertical-align:top">'.esc_html($t?date_i18n('j M',strtotime($t)):'').'</td><td style="padding:7px 0;border-bottom:1px solid #eee;font-size:14px"><a href="'.esc_url(get_permalink($e)).'" style="color:#111;text-decoration:none">'.esc_html(get_the_title($e)).'</a>'.($s?' <span style="color:#888">· '.esc_html($s).'</span>':'').'</td></tr>';}
		$h.='</table><div style="padding:10px 0"><a href="'.esc_url(get_post_type_archive_link('etkinlik')).'" style="color:'.$k.';font-size:14px">Tüm konser takvimi →</a></div></td></tr>';}
	if($c['gruplar']){$h.=$bas('Arşive yeni eklenen gruplar');$l=[];foreach($c['gruplar'] as $g)$l[]='<a href="'.esc_url(get_permalink($g)).'" style="color:#111">'.esc_html(get_the_title($g)).'</a>';$h.='<tr><td style="padding:10px 24px;font-size:14px;line-height:1.7">'.implode(' · ',$l).'</td></tr>';}
	$h.='<tr><td style="padding:22px 24px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0d0809"><tr><td style="padding:18px;color:#ece6e6;font-size:14px;line-height:1.5"><b>Grubun mu var?</b> Röportaj sorularımızı yanıtla, sahnedeki yerini al.<br><a href="'.esc_url(yr_page_url('garajdan-sahneye')).'" style="color:#ff6a6e">Garajdan Sahneye →</a></td></tr></table></td></tr>';
	$h.='<tr><td style="padding:16px 24px 24px;color:#888;font-size:12px;line-height:1.5;border-top:1px solid #eee">Bu e-postayı Yazarock bültenine abone olduğun için alıyorsun.'.($cik?' <a href="'.esc_url($cik).'" style="color:#888">Abonelikten çık</a>':'').'<br>yazarock.com</td></tr>';
	return $h.'</table></td></tr></table></body></html>';
}
function yr_bl_metin($c){
	$t="YAZAROCK HAFTALIK BÜLTEN\n\n";
	foreach($c['yazilar'] as $p)$t.='- '.get_the_title($p)."\n  ".get_permalink($p)."\n";
	if($c['etkinlikler']){$t.="\nSAHNEDE\n";foreach($c['etkinlikler'] as $e)$t.='- '.get_post_meta($e->ID,'_yr_tarih',true).' '.get_the_title($e)."\n";}
	return html_entity_decode($t,ENT_QUOTES,'UTF-8');
}
function yr_bl_konu($c){
	$y=$c['yazilar'];$t='Yazarock haftalık bülten';
	if($y)$t.=': '.wp_strip_all_tags(html_entity_decode(get_the_title($y[0]),ENT_QUOTES,'UTF-8'));
	return mb_strimwidth($t,0,110,'…');
}
function yr_bl_gonder_tek($email,$c,$tk){
	$cik=$tk?add_query_arg('yr_cik',$tk,home_url('/')):'';
	$alt=yr_bl_metin($c);
	$f=function($m)use($alt){$m->AltBody=$alt;};add_action('phpmailer_init',$f,20);
	$h=['Content-Type: text/html; charset=UTF-8'];if($cik)$h[]='List-Unsubscribe: <'.$cik.'>';
	$ok=wp_mail($email,yr_bl_konu($c),yr_bl_html($c,$cik),$h);
	remove_action('phpmailer_init',$f,20);return $ok;
}

/* ---------- Haftalık gönderim (parti parti) ---------- */
add_action('init',function(){if(!wp_next_scheduled('yr_bl_saatlik'))wp_schedule_event(time()+300,'hourly','yr_bl_saatlik');});
add_action('switch_theme',function(){wp_clear_scheduled_hook('yr_bl_saatlik');});
add_action('yr_bl_saatlik','yr_bl_zamani_mi');
function yr_bl_zamani_mi(){
	$a=yr_bl_ayar();if(!$a['aktif']||!yr_bl_smtp_hazir())return;
	$now=current_time('timestamp');$hafta=date('o-W',$now);
	if((int)date('N',$now)<(int)$a['gun']||((int)date('N',$now)===(int)$a['gun']&&(int)date('G',$now)<(int)$a['saat']))return;
	$son=get_option('yr_bl_hafta');if(!$son){update_option('yr_bl_hafta',$hafta,false);return;}/* ilk kurulumda bu haftayı atla */
	if($son===$hafta)return;
	update_option('yr_bl_hafta',$hafta,false);yr_bl_baslat();
}
function yr_bl_baslat(){
	$c=yr_bl_icerik();if(count($c['yazilar'])<2){update_option('yr_bl_son',['zaman'=>time(),'durum'=>'Bu hafta yeterli yazı yoktu, gönderilmedi.'],false);return 0;}
	$ids=get_posts(['post_type'=>'abone','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_onay','meta_value'=>'1']);
	update_option('yr_bl_kuyruk',['ids'=>array_values($ids),'gonderilen'=>0,'hata'=>0,'basla'=>time(),'icerik'=>array_map(function($l){return wp_list_pluck($l,'ID');},$c)],false);
	if(!wp_next_scheduled('yr_bl_parti'))wp_schedule_single_event(time()+5,'yr_bl_parti');
	return count($ids);
}
add_action('yr_bl_parti',function(){
	$q=get_option('yr_bl_kuyruk');if(!is_array($q)||empty($q['ids']))return;
	$c=array_map(function($ids){return array_values(array_filter(array_map('get_post',$ids)));},$q['icerik']);
	$parti=array_splice($q['ids'],0,max(5,(int)yr_bl_ayar('parti')));
	foreach($parti as $id){
		$em=get_post_meta($id,'_email',true);if(!is_email($em))continue;
		$tk=get_post_meta($id,'_token',true);if(!$tk){$tk=wp_generate_password(24,false);update_post_meta($id,'_token',$tk);}
		if(yr_bl_gonder_tek($em,$c,$tk))$q['gonderilen']++;else $q['hata']++;
	}
	if($q['ids']){update_option('yr_bl_kuyruk',$q,false);wp_schedule_single_event(time()+300,'yr_bl_parti');}
	else{delete_option('yr_bl_kuyruk');update_option('yr_bl_son',['zaman'=>time(),'durum'=>$q['gonderilen'].' aboneye gönderildi'.($q['hata']?', '.$q['hata'].' hata':'').'.'],false);}
});

/* ---------- Onaylanmamış abonelere onay e-postasını yeniden gönder ---------- */
function yr_bl_onay_tekrar(){
	$n=0;foreach(get_posts(['post_type'=>'abone','post_status'=>'any','numberposts'=>100,'meta_key'=>'_onay','meta_value'=>'0']) as $p){
		$m=get_post_meta($p->ID,'_email',true);if(!is_email($m))continue;
		$tk=wp_generate_password(24,false);update_post_meta($p->ID,'_token',$tk);
		if(wp_mail($m,'Yazarock bülten aboneliğini onayla',"Merhaba,\n\nYazarock.com bültenine abone olmak için aşağıdaki bağlantıya tıkla:\n".add_query_arg('yr_onay',$tk,home_url('/'))."\n\nHer hafta yeni yazılar, yeraltı grupları ve konser takvimi e-postana gelecek.\nBu isteği sen yapmadıysan bu e-postayı yok sayabilirsin.\n"))$n++;
	}
	return $n;
}

/* ---------- Panel sayfası ---------- */
add_action('admin_menu',function(){add_submenu_page('edit.php?post_type=abone','Bülten ve E-posta','Bülten ve E-posta','manage_options','yr-bulten','yr_bl_sayfa');});
add_action('admin_post_yr_bl',function(){
	if(!current_user_can('manage_options'))wp_die('Yetkin yok.');check_admin_referer('yr_bl');
	$is=sanitize_key($_POST['is']??'');$r='';
	if($is==='kaydet'){
		$a=yr_bl_ayar();
		foreach(['smtp_host','smtp_user','from','from_ad'] as $k)$a[$k]=sanitize_text_field(wp_unslash($_POST[$k]??''));
		$a['smtp_port']=absint($_POST['smtp_port']??465);$a['smtp_guv']=in_array($_POST['smtp_guv']??'',['ssl','tls',''],true)?$_POST['smtp_guv']:'ssl';
		if(isset($_POST['smtp_pass'])&&$_POST['smtp_pass']!=='')$a['smtp_pass']=wp_unslash($_POST['smtp_pass']);
		$a['aktif']=empty($_POST['aktif'])?0:1;$a['gun']=min(7,max(1,absint($_POST['gun']??5)));$a['saat']=min(23,absint($_POST['saat']??10));$a['parti']=min(100,max(5,absint($_POST['parti']??30)));
		update_option('yr_bulten_ayar',$a,false);$r='kaydedildi';
	}
	if($is==='test'){delete_option('yr_mail_hata');$u=wp_get_current_user();$r=wp_mail($u->user_email,'Yazarock e-posta testi','Bu bir deneme e-postasıdır. Bunu görüyorsan sitenin e-posta gönderimi çalışıyor.')?'test_ok':'test_hata';}
	if($is==='onizle'){$u=wp_get_current_user();$r=yr_bl_gonder_tek($u->user_email,yr_bl_icerik(),'')?'onizle_ok':'test_hata';}
	if($is==='simdi'){$n=yr_bl_baslat();update_option('yr_bl_hafta',date('o-W',current_time('timestamp')),false);$r='basladi_'.(int)$n;}
	if($is==='onay'){$r='onay_'.yr_bl_onay_tekrar();}
	wp_safe_redirect(add_query_arg('r',$r,admin_url('edit.php?post_type=abone&page=yr-bulten')));exit;
});
function yr_bl_sayfa(){
	$a=yr_bl_ayar();$r=sanitize_text_field($_GET['r']??'');$hata=get_option('yr_mail_hata');
	$onayli=count(get_posts(['post_type'=>'abone','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_onay','meta_value'=>'1']));
	$bekleyen=count(get_posts(['post_type'=>'abone','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_onay','meta_value'=>'0']));
	$M=['kaydedildi'=>'Ayarlar kaydedildi.','test_ok'=>'Deneme e-postası gönderildi; gelen kutunu (ve spam klasörünü) kontrol et.','onizle_ok'=>'Bülten önizlemesi e-posta adresine gönderildi.','test_hata'=>'E-posta gönderilemedi. Aşağıdaki hata mesajına bak.'];
	if(strpos($r,'basladi_')===0)$M[$r]='Bülten gönderimi başladı: '.(int)substr($r,8).' onaylı abone. Parti parti gönderilir (5 dakikada bir '.(int)$a['parti'].' kişi).';
	if(strpos($r,'onay_')===0)$M[$r]=(int)substr($r,5).' kişiye onay e-postası yeniden gönderildi.';
	$f=function($is,$t,$cls='button')use($a){return '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin:0 6px 6px 0"><input type="hidden" name="action" value="yr_bl"><input type="hidden" name="is" value="'.esc_attr($is).'">'.wp_nonce_field('yr_bl','_wpnonce',true,false).'<button class="'.esc_attr($cls).'">'.esc_html($t).'</button></form>';};
	$gunler=[1=>'Pazartesi',2=>'Salı',3=>'Çarşamba',4=>'Perşembe',5=>'Cuma',6=>'Cumartesi',7=>'Pazar'];
	echo '<div class="wrap"><h1>Bülten ve E-posta</h1>';
	if(isset($M[$r]))echo '<div class="notice notice-'.($r==='test_hata'?'error':'success').'"><p>'.esc_html($M[$r]).'</p></div>';
	if(!yr_bl_smtp_hazir())echo '<div class="notice notice-warning"><p><b>Önce e-posta şifresini gir.</b> Site e-postalarını (abonelik onayı, bülten, başvuru bildirimleri) info@yazarock.com hesabından göndermesi için cPanel\'deki e-posta hesabının şifresini aşağıya yazıp kaydet, sonra “Deneme e-postası gönder”e bas.</p></div>';
	if($hata)echo '<div class="notice notice-error"><p><b>Son e-posta hatası</b> ('.esc_html(human_time_diff($hata['zaman'])).' önce): '.esc_html($hata['mesaj']).'</p></div>';
	echo '<p><b>'.$onayli.'</b> onaylı abone · <b>'.$bekleyen.'</b> onay bekleyen. ';$s=get_option('yr_bl_son');if($s)echo 'Son bülten: '.esc_html(date_i18n('j F Y H:i',$s['zaman']+(int)(get_option('gmt_offset')*3600))).' — '.esc_html($s['durum']);$q=get_option('yr_bl_kuyruk');if($q)echo ' <b>Şu an gönderiliyor:</b> '.(int)$q['gonderilen'].' gönderildi, '.count($q['ids']).' kaldı.';echo '</p>';
	echo '<p>'.$f('test','Deneme e-postası gönder').$f('onizle','Bülten önizlemesini bana gönder').($bekleyen?$f('onay','Onay bekleyenlere onay e-postasını yeniden gönder'):'').$f('simdi','Bülteni şimdi tüm abonelere gönder','button button-primary').'</p>';
	echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="yr_bl"><input type="hidden" name="is" value="kaydet">';wp_nonce_field('yr_bl');
	echo '<h2>Haftalık bülten</h2><table class="form-table"><tr><th>Otomatik gönderim</th><td><label><input type="checkbox" name="aktif" value="1" '.checked($a['aktif'],1,false).'> Her hafta otomatik gönder</label></td></tr>';
	echo '<tr><th>Gün ve saat</th><td><select name="gun">';foreach($gunler as $k=>$g)echo '<option value="'.$k.'" '.selected($a['gun'],$k,false).'>'.$g.'</option>';echo '</select> saat <input type="number" name="saat" min="0" max="23" value="'.(int)$a['saat'].'" style="width:60px">:00 <p class="description">Bülten son 7 günün yazılarını, önümüzdeki iki haftanın konserlerini ve arşive yeni eklenen Türk gruplarını içerir. O hafta 2\'den az yazı varsa gönderilmez.</p></td></tr>';
	echo '<tr><th>Parti büyüklüğü</th><td><input type="number" name="parti" min="5" max="100" value="'.(int)$a['parti'].'" style="width:70px"> kişi / 5 dakika <p class="description">Paylaşımlı hostinglerde saatlik e-posta sınırı olur; 30 güvenli bir değerdir.</p></td></tr></table>';
	echo '<h2>E-posta sunucusu (SMTP)</h2><table class="form-table">';
	echo '<tr><th>Sunucu</th><td><input class="regular-text" name="smtp_host" value="'.esc_attr($a['smtp_host']).'"> port <input type="number" name="smtp_port" value="'.(int)$a['smtp_port'].'" style="width:80px"> <select name="smtp_guv"><option value="ssl" '.selected($a['smtp_guv'],'ssl',false).'>SSL</option><option value="tls" '.selected($a['smtp_guv'],'tls',false).'>TLS</option><option value="" '.selected($a['smtp_guv'],'',false).'>Yok</option></select><p class="description">cPanel → E-posta Hesapları → “Connect Devices” bölümündeki “Outgoing Server” bilgisi. Genellikle mail.yazarock.com, 465, SSL.</p></td></tr>';
	echo '<tr><th>Kullanıcı adı</th><td><input class="regular-text" name="smtp_user" value="'.esc_attr($a['smtp_user']).'"></td></tr>';
	echo '<tr><th>Şifre</th><td><input class="regular-text" type="password" name="smtp_pass" value="" autocomplete="new-password" placeholder="'.($a['smtp_pass']!==''?'•••••••• (kayıtlı; değiştirmek için yaz)':'E-posta hesabının şifresi').'"></td></tr>';
	echo '<tr><th>Gönderen</th><td><input class="regular-text" name="from" value="'.esc_attr($a['from']).'"> ad <input name="from_ad" value="'.esc_attr($a['from_ad']).'"></td></tr></table>';
	submit_button('Kaydet');echo '</form></div>';
}
