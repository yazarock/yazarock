<?php
if(!defined('ABSPATH'))exit;
define('YR_VER','1.20.1');

/* E-posta adresi yazım ve alan adı kontrolü: hatalı adreslere giden mailler geri döner ve sitenin e-posta itibarını düşürür */
function yr_eposta_oneri($m){
	$m=strtolower(trim((string)$m));if(strpos($m,'@')===false)return '';list($u,$d)=explode('@',$m,2);
	$T=['gmaill.com'=>'gmail.com','gmial.com'=>'gmail.com','gamil.com'=>'gmail.com','gmai.com'=>'gmail.com','gmal.com'=>'gmail.com','gmail.co'=>'gmail.com','gmail.con'=>'gmail.com','gmail.cm'=>'gmail.com','gmail.om'=>'gmail.com','gmail.com.tr'=>'gmail.com','gnail.com'=>'gmail.com','gmeil.com'=>'gmail.com','hotmial.com'=>'hotmail.com','hotmal.com'=>'hotmail.com','hotmai.com'=>'hotmail.com','hotmail.con'=>'hotmail.com','hotmail.co'=>'hotmail.com','hotmil.com'=>'hotmail.com','hotmial.com.tr'=>'hotmail.com.tr','outlok.com'=>'outlook.com','outloo.com'=>'outlook.com','outlook.con'=>'outlook.com','yaho.com'=>'yahoo.com','yahooo.com'=>'yahoo.com','yahoo.con'=>'yahoo.com','icloud.con'=>'icloud.com','iclod.com'=>'icloud.com','yandex.con'=>'yandex.com'];
	return isset($T[$d])?$u.'@'.$T[$d]:'';
}
function yr_eposta_ok($m){
	if(!is_email($m)||yr_eposta_oneri($m)!=='')return false;
	$d=strtolower(substr(strrchr($m,'@'),1));
	if(in_array($d,['gmail.com','hotmail.com','hotmail.com.tr','outlook.com','outlook.com.tr','live.com','msn.com','yahoo.com','icloud.com','me.com','yandex.com','yandex.com.tr','proton.me','protonmail.com'],true))return true;
	if(!function_exists('checkdnsrr'))return true;
	$k='yr_mx_'.md5($d);$c=get_transient($k);
	if($c===false){
		if(checkdnsrr($d,'MX')||checkdnsrr($d,'A'))$c='1';
		elseif(!checkdnsrr('gmail.com','MX'))return true;/* sunucunun DNS'i yanıt vermiyorsa kimseyi engelleme */
		else $c='0';
		set_transient($k,$c,$c==='1'?DAY_IN_SECONDS:HOUR_IN_SECONDS);
	}
	return $c==='1';
}

add_action('after_setup_theme',function(){
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('automatic-feed-links');
	add_theme_support('responsive-embeds');
	add_theme_support('html5',['search-form','comment-form','comment-list','gallery','caption','style','script']);
	add_image_size('yr-card',640,360,true);
	register_nav_menus(['primary'=>'Ana Menü']);
});
add_filter('document_title_separator',function(){return '|';});
add_filter('excerpt_more',function(){return '…';});
add_filter('excerpt_length',function(){return 30;});
add_action('wp_enqueue_scripts',function(){
	$d=[];
	if(get_theme_mod('yr_gfonts',true)){wp_enqueue_style('yr-fonts','https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Barlow:wght@400;500;600&display=swap',[],null);$d[]='yr-fonts';}
	wp_enqueue_style('yr-style',get_stylesheet_uri(),$d,YR_VER);
	wp_enqueue_script('yr-js',get_template_directory_uri().'/main.js',[],YR_VER,true);
});

/* Ziyaretçi IP adresi: site Cloudflare arkasındaysa gerçek IP'yi kullan (yalnızca istek gerçekten Cloudflare'dan geliyorsa) */
function yr_ip_in($ip,$cidr){
	list($net,$bits)=explode('/',$cidr);$a=@inet_pton($ip);$b=@inet_pton($net);if($a===false||$b===false||strlen($a)!==strlen($b))return false;
	$bits=(int)$bits;$bytes=intdiv($bits,8);$rest=$bits%8;
	if(substr($a,0,$bytes)!==substr($b,0,$bytes))return false;
	if($rest===0)return true;$mask=chr((0xff<<(8-$rest))&0xff);return (ord($a[$bytes])&ord($mask))===(ord($b[$bytes])&ord($mask));
}
function yr_ip(){
	static $ip=null;if($ip!==null)return $ip;
	$ip=(string)($_SERVER['REMOTE_ADDR']??'');
	$cf=isset($_SERVER['HTTP_CF_CONNECTING_IP'])?trim(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP'])):'';
	if($cf!==''&&filter_var($cf,FILTER_VALIDATE_IP)){
		foreach(['173.245.48.0/20','103.21.244.0/22','103.22.200.0/22','103.31.4.0/22','141.101.64.0/18','108.162.192.0/18','190.93.240.0/20','188.114.96.0/20','197.234.240.0/22','198.41.128.0/17','162.158.0.0/15','104.16.0.0/13','104.24.0.0/14','172.64.0.0/13','131.0.72.0/22','2400:cb00::/32','2606:4700::/32','2803:f800::/32','2405:b500::/32','2405:8100::/32','2a06:98c0::/29','2c0f:f248::/32'] as $r){if(yr_ip_in($ip,$r)){$ip=$cf;break;}}
	}
	return $ip=(string)apply_filters('yr_client_ip',$ip);
}
/* CSV'de formül olarak çalışabilecek değerleri etkisizleştir */
function yr_csv_safe($v){$v=(string)$v;return ($v!==''&&strpos("=+-@\t\r",$v[0])!==false)?"'".$v:$v;}

/* Menü yürüteci: düz <a> bağlantıları */
class YR_Walker extends Walker_Nav_Menu{
	function start_lvl(&$output,$depth=0,$args=null){}
	function end_lvl(&$output,$depth=0,$args=null){}
	function end_el(&$output,$item,$depth=0,$args=null){}
	function start_el(&$output,$item,$depth=0,$args=null,$id=0){
		$c=(in_array('current-menu-item',$item->classes)||in_array('current-menu-ancestor',$item->classes))?' on':'';
		if(in_array('login',$item->classes))$c.=' login';if(in_array('gt',$item->classes))$c.=' gt';
		$output.='<a class="'.esc_attr(trim($c)).'" href="'.esc_url($item->url).'">'.esc_html($item->title).'</a>';
	}
}
add_filter('wp_nav_menu_objects',function($items,$args){
	if(($args->theme_location??'')!=='primary')return $items;
	$o=[];$l=[];foreach($items as $it){if(in_array('login',(array)$it->classes,true)||untrailingslashit(wp_parse_url($it->url,PHP_URL_PATH)?:'')==='/giris')$l[]=$it;else $o[]=$it;}
	foreach($l as $it){if(is_user_logged_in())$it->title='Hesabım';$it->classes=array_merge((array)$it->classes,['login']);}
	return array_merge($o,$l);
},10,2);
function yr_fallback_menu(){
	echo '<nav aria-label="Ana menü"><a href="'.esc_url(home_url('/')).'">Ana Sayfa</a><a href="'.esc_url(yr_haber_url()).'">Haberler</a><a href="'.esc_url(get_post_type_archive_link('grup')).'">Gruplar</a><a href="'.esc_url(get_post_type_archive_link('etkinlik')).'">Etkinlikler</a></nav>';
}
function yr_haber_url(){$p=get_option('page_for_posts');return $p?get_permalink($p):home_url('/');}

/* Yazar rolü: yazı yazar, yayına editör/yönetici alır */
add_action('init',function(){
	if(!get_role('yazarock_yazar'))add_role('yazarock_yazar','Yazarock Yazarı',['read'=>true,'edit_posts'=>true,'delete_posts'=>true,'upload_files'=>true]);
});

/* İçerik türleri */
add_action('init',function(){
	register_post_type('grup',['labels'=>['name'=>'Gruplar','singular_name'=>'Grup','add_new_item'=>'Yeni Grup Ekle','edit_item'=>'Grubu Düzenle','all_items'=>'Tüm Gruplar'],'public'=>true,'has_archive'=>'gruplar','rewrite'=>['slug'=>'grup'],'menu_icon'=>'dashicons-groups','menu_position'=>5,'supports'=>['title','editor','thumbnail','excerpt','comments'],'show_in_rest'=>true]);
	register_post_type('etkinlik',['labels'=>['name'=>'Etkinlikler','singular_name'=>'Etkinlik','add_new_item'=>'Yeni Etkinlik Ekle','edit_item'=>'Etkinliği Düzenle','all_items'=>'Tüm Etkinlikler'],'public'=>true,'has_archive'=>'etkinlikler','rewrite'=>['slug'=>'etkinlik'],'menu_icon'=>'dashicons-calendar-alt','menu_position'=>6,'supports'=>['title','editor','thumbnail','excerpt'],'show_in_rest'=>true]);
	register_post_type('basvuru',['labels'=>['name'=>'Başvurular','singular_name'=>'Başvuru'],'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-id','menu_position'=>7,'supports'=>['title','editor'],'capabilities'=>['create_posts'=>'do_not_allow'],'map_meta_cap'=>true]);
	register_post_type('abone',['labels'=>['name'=>'Aboneler','singular_name'=>'Abone'],'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-email-alt','menu_position'=>8,'supports'=>['title'],'capabilities'=>['create_posts'=>'do_not_allow'],'map_meta_cap'=>true]);
	register_taxonomy('grup_tur','grup',['labels'=>['name'=>'Müzik Türleri','singular_name'=>'Müzik Türü'],'hierarchical'=>true,'public'=>true,'rewrite'=>['slug'=>'grup-turu'],'show_in_rest'=>true,'show_admin_column'=>true]);
});
add_action('pre_get_posts',function($q){
	if(is_admin()||!$q->is_main_query())return;
	if($q->is_search())$q->set('post_type',['post','grup','etkinlik']);
	if($q->is_post_type_archive('grup')){$q->set('posts_per_page',-1);$q->set('orderby','title');$q->set('order','ASC');}
	if($q->is_post_type_archive('etkinlik')){
		$past=isset($_GET['gecmis']);
		$q->set('posts_per_page',-1);$q->set('meta_key','_yr_tarih');$q->set('orderby','meta_value');$q->set('order',$past?'DESC':'ASC');
		$q->set('meta_query',[['key'=>'_yr_tarih','value'=>current_time('Y-m-d'),'compare'=>$past?'<':'>=','type'=>'DATE']]);
	}
});

/* Grup ve Etkinlik alanları */
function yr_fields(){return [
	'grup'=>[['bolge','Bölge','select',['tr'=>'Türkiye','world'=>'Dünya']],['stil','Müzik stili (ör. Thrash Metal)','text'],['kurulus','Ülke · kuruluş yılı (ör. İngiltere · 1968)','text'],['link','Resmî site veya müzik bağlantısı','url'],['spotify','Spotify bağlantısı (sanatçı, albüm veya çalma listesi)','url'],['youtube','YouTube video veya çalma listesi bağlantısı','url'],['uyeler','Kadro (her satıra bir kişi: Ad – enstrüman)','textarea'],['diskografi','Diskografi (her satıra bir albüm; yılı parantezle yaz: Albüm Adı (1999))','textarea'],['gorsel_kredi','Öne çıkan görselin kaynağı / telif sahibi','text'],['sabit','Bu grubu Haftanın Grubu olarak sabitle','checkbox']],
	'etkinlik'=>[['tarih','Tarih','date'],['saat','Saat','time'],['sehir','Şehir','text'],['mekan','Mekân','text'],['tur','Tür','select',['Konser'=>'Konser','Festival'=>'Festival','Kulüp Gecesi'=>'Kulüp Gecesi']],['bilet','Bilet bağlantısı','url'],['harita','Harita arama metni (boşsa mekân ve şehir kullanılır)','text']],'post'=>[['youtube','YouTube video bağlantısı (isteğe bağlı)','url']]
];}
function yr_join(...$a){return implode(' · ',array_filter($a));}
function yr_meta($k,$id=0){return get_post_meta($id?$id:get_the_ID(),'_yr_'.$k,true);}
add_action('add_meta_boxes',function(){
	foreach(array_keys(yr_fields()) as $pt){
		add_meta_box('yr_'.$pt,'Ayrıntılar',function($p){
			wp_nonce_field('yr_save','yr_n');echo '<table class="form-table">';
			foreach(yr_fields()[$p->post_type] as $f){
				$v=get_post_meta($p->ID,'_yr_'.$f[0],true);$n='yr_'.$f[0];
				echo '<tr><th><label for="'.esc_attr($n).'">'.esc_html($f[1]).'</label></th><td>';
				if($f[2]==='select'){echo '<select name="'.esc_attr($n).'" id="'.esc_attr($n).'">';foreach($f[3] as $k=>$l)echo '<option value="'.esc_attr($k).'"'.selected($v,$k,false).'>'.esc_html($l).'</option>';echo '</select>';}
				elseif($f[2]==='textarea')echo '<textarea class="large-text" rows="7" name="'.esc_attr($n).'" id="'.esc_attr($n).'">'.esc_textarea($v).'</textarea>';elseif($f[2]==='checkbox')echo '<input type="checkbox" name="'.esc_attr($n).'" id="'.esc_attr($n).'" value="1"'.checked($v,'1',false).'>';else echo '<input class="regular-text" type="'.esc_attr($f[2]).'" name="'.esc_attr($n).'" id="'.esc_attr($n).'" value="'.esc_attr($v).'">';
				echo '</td></tr>';
			}
			echo '</table>';
		},$pt,'normal','high');
	}
});
add_action('save_post',function($id){
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_save')||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id))return;
	$F=yr_fields();$pt=get_post_type($id);if(!isset($F[$pt]))return;
	foreach($F[$pt] as $f){$v=wp_unslash($_POST['yr_'.$f[0]]??'');update_post_meta($id,'_yr_'.$f[0],$f[2]==='url'?esc_url_raw($v):($f[2]==='textarea'?sanitize_textarea_field($v):sanitize_text_field($v)));}
});
add_filter('manage_etkinlik_posts_columns',function($c){$c['yr_tarih']='Tarih';$c['yr_sehir']='Şehir';return $c;});
add_action('manage_etkinlik_posts_custom_column',function($c,$id){if($c==='yr_tarih')echo esc_html(get_post_meta($id,'_yr_tarih',true));if($c==='yr_sehir')echo esc_html(get_post_meta($id,'_yr_sehir',true));},10,2);
add_filter('manage_basvuru_posts_columns',function($c){$c['yr_mail']='E-posta';$c['yr_alan']='Alan';$c['yr_durum']='Durum';return $c;});
add_action('manage_basvuru_posts_custom_column',function($c,$id){if($c==='yr_mail')echo esc_html(get_post_meta($id,'_email',true));if($c==='yr_alan')echo esc_html(get_post_meta($id,'_alan',true));if($c==='yr_durum')echo esc_html(get_post_meta($id,'_durum',true)==='onaylandi'?'Onaylandı':'Yeni');},10,2);

/* Form işleyici: bülten ve yazarlık başvurusu */
add_action('admin_post_nopriv_yr_form','yr_form');
add_action('admin_post_yr_form','yr_form');
function yr_form(){
	$t=sanitize_key($_POST['t']??'');
	$back=remove_query_arg(['yr','f'],wp_get_referer()?wp_get_referer():home_url('/'));
	$go=function($s)use($back,$t){wp_safe_redirect(add_query_arg(['yr'=>$s,'f'=>$t],$back).($t==='abone'?'#bulten':'#basvuru-form'));exit;};
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_form'))$go('guvenlik');
	if(!empty($_POST['website']))$go('tamam');
	if(!yr_ts_ok())$go('guvenlik');
	$k='yr_rl_'.md5(yr_ip());if(get_transient($k))$go('bekle');set_transient($k,1,20);
	if($t==='abone'){
		$m=sanitize_email(wp_unslash($_POST['email']??''));if(!yr_eposta_ok($m)||empty($_POST['onay']))$go('gecersiz');
		$ex=get_posts(['post_type'=>'abone','post_status'=>'any','meta_key'=>'_email','meta_value'=>$m,'numberposts'=>1]);
		if($ex){$id=$ex[0]->ID;if(get_post_meta($id,'_onay',true)==='1')$go('tamam');}
		else $id=wp_insert_post(['post_type'=>'abone','post_status'=>'publish','post_title'=>$m,'meta_input'=>['_email'=>$m,'_onay'=>'0','_kvkk'=>current_time('mysql')]]);
		$tk=wp_generate_password(24,false);update_post_meta($id,'_token',$tk);
		wp_mail($m,'Yazarock bülten aboneliğini onayla',"Merhaba,\n\nYazarock.com bültenine abone olmak için aşağıdaki bağlantıya tıkla:\n".add_query_arg('yr_onay',$tk,home_url('/'))."\n\nBu isteği sen yapmadıysan bu e-postayı yok sayabilirsin.\n");
		yr_ab_cookie(1);$go('onay');
	}
	if($t==='basvuru'){
		$ad=sanitize_text_field(wp_unslash($_POST['ad']??''));$m=sanitize_email(wp_unslash($_POST['email']??''));
		$alan=sanitize_text_field(wp_unslash($_POST['alan']??''));$lk=esc_url_raw(wp_unslash($_POST['link']??''));$ms=mb_substr(sanitize_textarea_field(wp_unslash($_POST['msg']??'')),0,600);
		if(mb_strlen($ad)<3||!yr_eposta_ok($m)||mb_strlen($ms)<40||empty($_POST['ok'])||empty($_POST['kvkk']))$go('gecersiz');
		wp_insert_post(['post_type'=>'basvuru','post_status'=>'publish','post_title'=>$ad,'post_content'=>$ms,'meta_input'=>['_email'=>$m,'_alan'=>$alan,'_link'=>$lk,'_durum'=>'yeni','_kvkk'=>current_time('mysql')]]);
		wp_mail(get_option('admin_email'),'Yeni yazarlık başvurusu: '.$ad,"Ad: $ad\nE-posta: $m\nAlan: $alan\nÖrnek yazı: ".($lk?$lk:'-')."\n\n$ms\n\nYönetim: ".admin_url('edit.php?post_type=basvuru'),['Reply-To: '.$m]);
		$go('tamam');
	}
	$go('gecersiz');
}
function yr_ab_cookie($on){
	if(headers_sent())return;
	setcookie('yr_ab',$on?'1':'',['expires'=>$on?time()+31536000:time()-3600,'path'=>'/','secure'=>is_ssl(),'samesite'=>'Lax']);
}
function yr_notice($t){
	if(empty($_GET['yr'])||($_GET['f']??'')!==$t)return;
	$M=['tamam'=>[$t==='abone'?'Teşekkürler, bülten kaydın alındı.':'Başvurun alındı; en kısa sürede dönüş yapacağız.','ok'],'onay'=>['Onay e-postası gönderildi; gelen kutunu ve spam klasörünü kontrol et.','ok'],'gecersiz'=>['Lütfen alanları kontrol edip tekrar dene.','err'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenile.','err']];
	$k=sanitize_key($_GET['yr']);if(!isset($M[$k]))return;
	echo '<div class="notice '.esc_attr($M[$k][1]).'" role="status">'.esc_html($M[$k][0]).'</div>';
}
function yr_newsletter(){
	if(!empty($_COOKIE['yr_ab']))return;/* abone olanlara gösterilmez */
	echo '<section class="box nl-box" id="bulten" style="margin-top:22px"><h2>'.esc_html(yr_t('bulten_b')).'</h2><p style="margin:0;color:var(--mute)">'.esc_html(yr_t('bulten_m')).'</p>';yr_notice('abone');
	echo '<form class="news-l" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="yr_form"><input type="hidden" name="t" value="abone">';wp_nonce_field('yr_form','yr_n');
	echo '<input class="hp" tabindex="-1" autocomplete="off" name="website"><input type="email" name="email" placeholder="E-posta adresin" aria-label="E-posta" required>';yr_ts_widget();echo '<button class="btn" type="submit">'.esc_html(yr_t('bulten_btn')).'</button>';
	echo '<label class="chk"><input type="checkbox" name="onay" value="1" required> <span>Ticari elektronik ileti almayı kabul ediyorum; <a href="'.esc_url(yr_page_url('kvkk-aydinlatma-metni')).'" target="_blank">KVKK Aydınlatma Metni</a>\'ni okudum. Dilediğim zaman abonelikten çıkabilirim.</span></label></form></section>';
}
/* Aboneleri CSV indir */
add_filter('views_edit-abone',function($v){$v['yr_csv']='<a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_export'),'yr_export')).'">CSV indir</a>';return $v;});
add_action('admin_post_yr_export',function(){
	check_admin_referer('yr_export');if(!current_user_can('manage_options'))wp_die('Yetkin yok.');
	header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename=aboneler.csv');
	$o=fopen('php://output','w');fputcsv($o,['E-posta','Onay tarihi','Çıkış bağlantısı']);
	foreach(get_posts(['post_type'=>'abone','numberposts'=>-1,'meta_key'=>'_onay','meta_value'=>'1']) as $p)fputcsv($o,array_map('yr_csv_safe',[get_post_meta($p->ID,'_email',true),get_post_meta($p->ID,'_onay_tarih',true),add_query_arg('yr_cik',get_post_meta($p->ID,'_token',true),home_url('/'))]));
	exit;
});
/* Başvuruyu onayla: yazar hesabı oluştur ve şifre belirleme e-postası gönder */
add_filter('post_row_actions',function($a,$p){
	if($p->post_type==='basvuru'&&current_user_can('create_users'))$a['yr_onay']='<a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_onayla&id='.$p->ID),'yr_onayla'.$p->ID)).'">Yazar Olarak Onayla</a>';
	return $a;
},10,2);
add_action('admin_post_yr_onayla',function(){
	$id=(int)($_GET['id']??0);check_admin_referer('yr_onayla'.$id);
	if(!current_user_can('create_users'))wp_die('Yetkin yok.');
	$m=get_post_meta($id,'_email',true);
	if(!is_email($m)||email_exists($m))wp_die('E-posta geçersiz ya da bu adresle zaten bir hesap var.');
	$b=sanitize_user(strstr($m,'@',true),true);if(!$b)$b='yazar';$l=$b;$i=1;while(username_exists($l))$l=$b.(++$i);
	$uid=wp_insert_user(['user_login'=>$l,'user_email'=>$m,'display_name'=>get_the_title($id),'user_pass'=>wp_generate_password(20),'role'=>'yazarock_yazar']);
	if(is_wp_error($uid))wp_die(esc_html($uid->get_error_message()));
	wp_new_user_notification($uid,null,'user');update_post_meta($id,'_durum','onaylandi');
	wp_safe_redirect(admin_url('edit.php?post_type=basvuru'));exit;
});

/* Giriş akışı: hatalı girişte özel sayfaya dön, yazarı panele yönlendir */
function yr_login_back($r){return add_query_arg('login','failed',strtok($r,'?'));}
add_action('wp_login_failed',function(){$r=wp_get_referer();if($r&&strpos($r,'wp-login')===false&&strpos($r,'wp-admin')===false){wp_safe_redirect(yr_login_back($r));exit;}});
add_filter('authenticate',function($u,$n,$p){if(isset($_POST['log'])&&(empty($n)||empty($p))){$r=wp_get_referer();if($r&&strpos($r,'wp-login')===false){wp_safe_redirect(yr_login_back($r));exit;}}return $u;},1,3);
add_filter('login_redirect',function($to,$req,$user){if($user&&!is_wp_error($user)&&in_array('yazarock_yazar',(array)$user->roles))return ($req&&strpos($req,admin_url())!==0&&untrailingslashit($req)!==untrailingslashit(home_url()))?$to:yr_panel_url();return $to;},10,3);
add_action('login_enqueue_scripts',function(){echo '<style>@import url("https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Barlow:wght@400;500&display=swap");body.login{background:radial-gradient(ellipse at 50% -10%,#3a0f13,#0d0809 60%);font-family:Barlow,sans-serif}.login h1 a{background:none!important;text-indent:0!important;width:auto!important;height:auto!important;font:700 34px Oswald,Impact,sans-serif;color:#f3eded;text-transform:uppercase;overflow:visible}.login form{background:#170e10;border:1px solid #3a2024;color:#ece6e6}.login label{color:#a69a9b}.login input[type=text],.login input[type=password]{background:#0d0809;color:#ece6e6;border-color:#3a2024}.wp-core-ui .button-primary{background:#c0282d;border:0;text-shadow:none;box-shadow:none}.login #nav a,.login #backtoblog a{color:#a69a9b}</style>';});
add_filter('login_headerurl',function(){return home_url('/');});
add_filter('login_headertext',function(){return 'Yazarock.com';});


/* Özelleştir: Görünüm → Özelleştir → Yazarock Ayarları */
add_action('customize_register',function($c){
	$c->add_section('yr',['title'=>'Yazarock Ayarları','priority'=>30]);
	$f=[['yr_mail','İletişim e-postası','info@yazarock.com','text'],['yr_social','Sosyal medya adresleri (her satıra bir adres)','','textarea'],['yr_quotes','Günün sözleri (her satıra bir söz)','','textarea']];
	foreach($f as $x){
		$c->add_setting($x[0],['default'=>$x[2],'sanitize_callback'=>$x[3]==='url'?'esc_url_raw':($x[3]==='textarea'?'sanitize_textarea_field':'sanitize_text_field')]);
		$c->add_control($x[0],['label'=>$x[1],'section'=>'yr','type'=>$x[3]]);
	}
	foreach(['etkinlik'=>'Yaklaşan Etkinlikler','grup'=>'Gruplar','yazar'=>'Öne Çıkan Yazarlar','yorum'=>'Son Yorumlar','soz'=>'Günün Sözü','bulten'=>'Haber Bülteni'] as $k=>$l){
		$c->add_setting('yr_s_'.$k,['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
		$c->add_control('yr_s_'.$k,['label'=>$l.' bölümünü göster','section'=>'yr','type'=>'checkbox']);
	}
	$c->add_setting('yr_gfonts',['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
	$c->add_control('yr_gfonts',['label'=>'Google yazı tiplerini kullan (kapatırsan sistem yazı tipleri kullanılır; ziyaretçi IP adresi Google\'a gitmez)','section'=>'yr','type'=>'checkbox']);
});
function yr_on($k){return (bool)get_theme_mod('yr_s_'.$k,true);}

/* Ana sayfa widget alanları: Görünüm → Widget'lar */
add_action('widgets_init',function(){
	foreach([['home-top','Ana Sayfa: Manşet Altı'],['home-side','Ana Sayfa: Yan Sütun'],['home-bottom','Ana Sayfa: Alt Bölüm']] as $s)
		register_sidebar(['id'=>$s[0],'name'=>$s[1],'description'=>'Buraya blok ekleyerek ana sayfayı düzenleyebilirsin.','before_widget'=>'<div id="%1$s" class="box widget %2$s">','after_widget'=>'</div>','before_title'=>'<h2>','after_title'=>'</h2>']);
});

/* Hazır bloklar: sayfa ve widget düzenlerken + düğmesi → Desenler → Yazarock */
add_action('init',function(){
	if(!function_exists('register_block_pattern'))return;
	register_block_pattern_category('yazarock',['label'=>'Yazarock']);
	register_block_pattern('yazarock/konser-raporu',['title'=>'Konser Raporu','categories'=>['yazarock'],'content'=>'<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Konser Raporu: Grup Adı – Mekan</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Gecenin genel havasını anlat: kapı saati, açılış grubu, sahne enerjisi.</p><!-- /wp:paragraph --><!-- wp:gallery {"linkTo":"none"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped"></figure><!-- /wp:gallery --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Set Listesi</h3><!-- /wp:heading --><!-- wp:list {"ordered":true} --><ol><li>Şarkı 1</li><li>Şarkı 2</li></ol><!-- /wp:list --><!-- wp:paragraph --><p>Fotoğraflar: Ad Soyad</p><!-- /wp:paragraph -->']);
	register_block_pattern('yazarock/gunun-sozu',['title'=>'Günün Sözü Kutusu','categories'=>['yazarock'],'content'=>'<!-- wp:group {"className":"box"} --><div class="wp-block-group box"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Günün Sözü</h2><!-- /wp:heading --><!-- wp:quote --><blockquote class="wp-block-quote"><p>Buraya sözünü yaz.</p><cite>Kaynak</cite></blockquote><!-- /wp:quote --></div><!-- /wp:group -->']);
	register_block_pattern('yazarock/duyuru',['title'=>'Duyuru Kutusu','categories'=>['yazarock'],'content'=>'<!-- wp:group {"className":"box"} --><div class="wp-block-group box"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Duyuru</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Duyuru metnini buraya yaz.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Devamı</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->']);
	register_block_pattern('yazarock/gorsel-metin',['title'=>'Görsel ve Metin','categories'=>['yazarock'],'content'=>'<!-- wp:group {"className":"box"} --><div class="wp-block-group box"><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:image --><figure class="wp-block-image"><img alt=""/></figure><!-- /wp:image --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Baslik</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Metni buraya yaz.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->']);
});

/* Günün sözü */
function yr_quotes(){$q=array_values(array_filter(array_map('trim',explode("\n",(string)get_theme_mod('yr_quotes',implode("\n",yr_quotes_default()))))));return $q?$q:yr_quotes_default();}
function yr_quotes_default(){return ['Riff, bir şarkının kapısını çalan ilk cümledir.','Sessizlik de bir enstrümandır; ne zaman susacağını bilen gitarist kazanır.','Sahne büyük olabilir ama hikâye hep ilk provada başlar.','Distorsiyon sesi bozmaz; duyguyu açığa çıkarır.','İyi bir albüm, baştan sona dinlenince anlam kazanan bir yolculuktur.','Her büyük grup bir garajda, küçük bir amfiyle başladı.'];}

/* Şablon yardımcıları */
function yr_read(){return max(1,(int)ceil(str_word_count(wp_strip_all_tags(get_the_content()))/200));}
function yr_card(){
	$id=get_the_ID();$c=get_the_category();$cn=$c?$c[0]->name:'Yazı';
	echo '<article class="card"><a class="thumb" href="'.esc_url(get_permalink()).'" tabindex="-1" aria-hidden="true">'.yr_post_cover($id).'<span>'.esc_html($cn).'</span></a><div class="b"><h3><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></h3><p>'.esc_html(wp_trim_words(get_the_excerpt(),20)).'</p><p class="mt">'.yr_read().' dk okuma · '.esc_html(get_the_date('j F Y')).(function_exists('yr_kart_ist')?yr_kart_ist($id):'').'</p></div></article>';
}
function yr_band_card(){
	$id=get_the_ID();$t=get_the_terms($id,'grup_tur');$tr=($t&&!is_wp_error($t))?$t[0]->slug:'';
	echo '<a class="band fi" href="'.esc_url(get_permalink()).'" data-r="'.esc_attr(yr_meta('bolge')).'" data-g="'.esc_attr($tr).'"><div class="bcov">'.yr_cover($id,'medium_large',true).'</div><div class="bb"><h3>'.esc_html(get_the_title()).'</h3><em>'.esc_html(yr_join(yr_meta('stil'),yr_meta('kurulus'))).'</em><p>'.esc_html(wp_trim_words(get_the_excerpt(),18)).'</p></div></a>';
}
function yr_event_card(){
	$M=['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];$d=yr_meta('tarih');$ts=$d?strtotime($d.' 12:00'):0;
	echo '<a class="ev fi" href="'.esc_url(get_permalink()).'" data-y="'.esc_attr(yr_meta('tur')).'" data-c="'.esc_attr(yr_meta('sehir')).'"><div class="date"><b>'.($ts?date('j',$ts):'?').'</b>'.($ts?$M[date('n',$ts)-1]:'').'<small>'.($ts?date('Y',$ts):'').'</small></div><div><h3>'.esc_html(get_the_title()).'</h3><p>'.esc_html(yr_join(yr_meta('sehir'),yr_meta('mekan'))).'</p><p><span class="pill">'.esc_html(yr_meta('tur')?yr_meta('tur'):'Etkinlik').'</span>'.esc_html(wp_trim_words(get_the_excerpt(),12)).'</p></div></a>';
}
function yr_events($n=-1){return new WP_Query(['post_type'=>'etkinlik','posts_per_page'=>$n,'meta_key'=>'_yr_tarih','orderby'=>'meta_value','order'=>'ASC','no_found_rows'=>true,'meta_query'=>[['key'=>'_yr_tarih','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE']]]);}
function yr_title(){
	if(is_home())return 'Haberler';
	if(is_category()||is_tag())return single_term_title('',false);
	if(is_search())return 'Arama: '.get_search_query();
	if(is_author())return get_the_author_meta('display_name',get_queried_object_id());
	if(is_date())return get_the_archive_title();
	return 'Haberler';
}

/* SEO: açıklama, Open Graph ve yapılandırılmış veri */
add_action('wp_head',function(){
	$d=get_bloginfo('description');$u=home_url(add_query_arg([]));$img='';$type='website';$j=null;
	if(is_singular()){
		$p=get_queried_object();$d=has_excerpt($p)?get_the_excerpt($p):wp_trim_words(wp_strip_all_tags($p->post_content),30);
		$img=get_the_post_thumbnail_url($p,'large');$u=get_permalink($p);$type=$p->post_type==='post'?'article':'website';
		if($p->post_type==='etkinlik')$j=['@context'=>'https://schema.org','@type'=>'Event','name'=>get_the_title($p),'startDate'=>yr_meta('tarih',$p->ID).(yr_meta('saat',$p->ID)?'T'.yr_meta('saat',$p->ID):''),'eventAttendanceMode'=>'https://schema.org/OfflineEventAttendanceMode','location'=>['@type'=>'Place','name'=>yr_meta('mekan',$p->ID),'address'=>yr_meta('sehir',$p->ID)],'url'=>$u];
		if($p->post_type==='post')$j=['@context'=>'https://schema.org','@type'=>'Article','headline'=>get_the_title($p),'datePublished'=>get_the_date('c',$p),'dateModified'=>get_the_modified_date('c',$p),'author'=>['@type'=>'Person','name'=>get_the_author_meta('display_name',$p->post_author)],'image'=>$img?$img:null,'mainEntityOfPage'=>$u];
	}
	if(is_front_page())$j=['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'Yazarock.com','url'=>home_url('/'),'potentialAction'=>['@type'=>'SearchAction','target'=>home_url('/?s={search_term_string}'),'query-input'=>'required name=search_term_string']];
	$d=wp_strip_all_tags($d);
	echo '<meta name="description" content="'.esc_attr($d).'"><meta property="og:site_name" content="Yazarock.com"><meta property="og:type" content="'.esc_attr($type).'"><meta property="og:title" content="'.esc_attr(wp_get_document_title()).'"><meta property="og:description" content="'.esc_attr($d).'"><meta property="og:url" content="'.esc_url($u).'"><meta name="twitter:card" content="'.($img?'summary_large_image':'summary').'">';
	if($img)echo '<meta property="og:image" content="'.esc_url($img).'">';
	if($j)echo '<script type="application/ld+json">'.wp_json_encode($j,JSON_UNESCAPED_UNICODE).'</script>';
},5);

/* Tema etkinleştirilince otomatik kurulum */
function yr_page($t,$s){$p=get_page_by_path($s);if($p)return $p->ID;return wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$t,'post_name'=>$s,'post_content'=>'']);}
add_action('after_switch_theme',function(){
	update_option('permalink_structure','/%postname%/');update_option('blogname','Yazarock.com');
	update_option('blogdescription','Türkiye ve dünyadan rock ve metal müzik haberleri, konser ve festival takvimi, grup biyografileri.');
	$home=yr_page('Ana Sayfa','ana-sayfa');$haber=yr_page('Haberler','haberler');$yaz=yr_page('Yazar Ol','yazar-ol');$gir=yr_page('Giriş Yap','giris');
	update_option('show_on_front','page');update_option('page_on_front',$home);update_option('page_for_posts',$haber);
	foreach(['Kültür & Sanat','Rehber','Röportaj','Konser Raporu'] as $c)if(!term_exists($c,'category'))wp_insert_term($c,'category');
	foreach(['Rock','Metal','Alternatif','Progresif'] as $c)if(!term_exists($c,'grup_tur'))wp_insert_term($c,'grup_tur');
	if(!wp_get_nav_menu_object('Ana Menü')){
		$m=wp_create_nav_menu('Ana Menü');$kc=get_term_by('slug','kultur-sanat','category');
		$it=[['Ana Sayfa',home_url('/'),''],['Haberler',get_permalink($haber),''],['Kültür & Sanat',$kc?get_term_link($kc):get_permalink($haber),''],['Gruplar',get_post_type_archive_link('grup'),''],['Etkinlikler',get_post_type_archive_link('etkinlik'),''],['Yazar Ol',get_permalink($yaz),''],['Giriş Yap',get_permalink($gir),'login']];
		foreach($it as $x)wp_update_nav_menu_item($m,0,['menu-item-title'=>$x[0],'menu-item-url'=>$x[1],'menu-item-type'=>'custom','menu-item-status'=>'publish','menu-item-classes'=>$x[2]]);
		$loc=get_theme_mod('nav_menu_locations',[]);$loc['primary']=$m;set_theme_mod('nav_menu_locations',$loc);
	}
	flush_rewrite_rules();
});

/* ===== KVKK, güvenlik ve ek özellikler ===== */
function yr_page_url($s){$p=get_page_by_path($s);return $p?get_permalink($p):home_url('/'.$s.'/');}

/* Abonelik onayı ve çıkış bağlantıları */
add_action('template_redirect',function(){
	foreach(['yr_onay','yr_cik'] as $k){
		if(empty($_GET[$k]))continue;
		$tk=sanitize_text_field(wp_unslash($_GET[$k]));
		$p=get_posts(['post_type'=>'abone','post_status'=>'any','meta_key'=>'_token','meta_value'=>$tk,'numberposts'=>1]);
		if($p){
			if($k==='yr_onay'){update_post_meta($p[0]->ID,'_onay','1');update_post_meta($p[0]->ID,'_onay_tarih',current_time('mysql'));$m='Aboneliğin onaylandı.';yr_ab_cookie(1);}
			elseif(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
				/* E-posta tarayıcıları linki otomatik açtığında abonelik silinmesin: önce onay iste */
				nocache_headers();
				wp_die('<p>Yazarock bülten aboneliğinden çıkmak istediğine emin misin?</p><form method="post" action="'.esc_url(add_query_arg('yr_cik',$tk,home_url('/'))).'"><button type="submit" class="button button-primary">Evet, abonelikten çık</button></form><p><a href="'.esc_url(home_url('/')).'">Vazgeç, ana sayfaya dön</a></p>','Yazarock',['response'=>200]);
			}
			else{wp_delete_post($p[0]->ID,true);$m='Abonelikten çıkarıldın.';yr_ab_cookie(0);}
		}else $m='Bağlantı geçersiz ya da süresi dolmuş.';
		wp_die(esc_html($m).' <a href="'.esc_url(home_url('/')).'">Ana sayfaya dön</a>','Yazarock',['response'=>200]);
	}
});

/* Güvenlik */
if(!defined('DISALLOW_FILE_EDIT'))define('DISALLOW_FILE_EDIT',true);
remove_action('wp_head','wp_generator');
add_filter('xmlrpc_enabled','__return_false');
add_filter('login_errors',function($e){return strpos($e,'Çok fazla')!==false?$e:'Kullanıcı adı veya şifre hatalı.';});
add_action('send_headers',function(){
	header('X-Content-Type-Options: nosniff');header('X-Frame-Options: SAMEORIGIN');header('Referrer-Policy: strict-origin-when-cross-origin');header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
	if(is_ssl())header('Strict-Transport-Security: max-age=15552000');
});
add_action('template_redirect',function(){if(!is_user_logged_in()&&isset($_GET['author'])&&is_numeric($_GET['author'])){wp_safe_redirect(home_url('/'),301);exit;}},1);
add_filter('rest_endpoints',function($e){if(!is_user_logged_in()){unset($e['/wp/v2/users'],$e['/wp/v2/users/(?P<id>[\d]+)']);}return $e;});
add_filter('wp_robots',function($r){if(is_search()||is_404())$r['noindex']=true;return $r;});
/* Giriş denemesi sınırı: 5 hatalı denemede 15 dakika kilit */
function yr_lk($n){return 'yr_ll_'.md5(yr_ip().strtolower((string)$n));}
add_filter('authenticate',function($u,$n,$p){if(empty($n))return $u;if((int)get_transient(yr_lk($n))>=5)return new WP_Error('yr_kilit','Çok fazla hatalı deneme. 15 dakika sonra tekrar dene.');return $u;},30,3);
add_action('wp_login_failed',function($n){set_transient(yr_lk($n),(int)get_transient(yr_lk($n))+1,900);},5);
add_action('wp_login',function($n){delete_transient(yr_lk($n));});

/* WordPress "Kişisel veri dışa aktar / sil" araçlarına başvuru ve abone kayıtlarını bağla */
function yr_kayitlar($e){return get_posts(['post_type'=>['basvuru','abone'],'post_status'=>'any','meta_key'=>'_email','meta_value'=>$e,'numberposts'=>-1]);}
add_filter('wp_privacy_personal_data_exporters',function($x){
	$x['yazarock']=['exporter_friendly_name'=>'Yazarock başvuru ve abonelik kayıtları','callback'=>function($e){
		$d=[];foreach(yr_kayitlar($e) as $p)$d[]=['group_id'=>$p->post_type,'group_label'=>$p->post_type==='abone'?'Bülten aboneliği':'Yazarlık başvurusu','item_id'=>'yr-'.$p->ID,'data'=>[['name'=>'Ad veya e-posta','value'=>$p->post_title],['name'=>'Mesaj','value'=>wp_strip_all_tags($p->post_content)],['name'=>'Tarih','value'=>$p->post_date]]];
		return ['data'=>$d,'done'=>true];}];
	return $x;
});
add_filter('wp_privacy_personal_data_erasers',function($x){
	$x['yazarock']=['eraser_friendly_name'=>'Yazarock başvuru ve abonelik kayıtları','callback'=>function($e){
		$n=0;foreach(yr_kayitlar($e) as $p){wp_delete_post($p->ID,true);$n++;}
		return ['items_removed'=>$n>0,'items_retained'=>false,'messages'=>[],'done'=>true];}];
	return $x;
});

/* Çerez bildirimi (sadece gerekli çerezler için bilgilendirme) */
add_action('wp_footer',function(){
	echo '<div class="ckb" id="ckb" role="dialog" aria-label="Çerez bildirimi" hidden><p>Bu site yalnızca çalışması için gerekli çerezleri kullanır. Ayrıntı: <a href="'.esc_url(yr_page_url('cerez-politikasi')).'">Çerez Politikası</a></p><button class="btn" id="ckok" type="button">Anladım</button></div>';
});

add_action('admin_notices',function(){
	if(!current_user_can('manage_options'))return;
	foreach(['kvkk-aydinlatma-metni','cerez-politikasi','gizlilik-politikasi'] as $s){$p=get_page_by_path($s,OBJECT,'page');
		if($p&&$p->post_status!=='publish'){echo '<div class="notice notice-warning"><p><b>Yazarock:</b> KVKK ve gizlilik sayfaları henüz yayında değil. <a href="'.esc_url(admin_url('edit.php?post_type=page&post_status=draft')).'">Taslakları düzenle ve yayımla</a>.</p></div>';return;}}
});

/* İlk içerik: tema güncellendikten sonra panele ilk girişte bir kez eklenir (seed.json) */
add_action('admin_init',function(){
	if(get_option('yr_seed_v1')||!current_user_can('manage_options'))return;
	$f=get_template_directory().'/seed.json';if(!is_readable($f))return;
	$d=json_decode(file_get_contents($f),true);if(!is_array($d))return;
	update_option('yr_seed_v1',1);
	$uid=get_current_user_id();
	foreach(['Rock','Metal'] as $t)if(!term_exists($t,'grup_tur'))wp_insert_term($t,'grup_tur');
	foreach(['Kültür & Sanat','Rehber','Röportaj','Konser Raporu'] as $t)if(!term_exists($t,'category'))wp_insert_term($t,'category');
	foreach(($d['yazilar']??[]) as $p){
		if(get_page_by_path($p['slug'],OBJECT,'post'))continue;
		$c=get_term_by('slug',sanitize_title($p['kategori']),'category');
		wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>$p['baslik'],'post_name'=>$p['slug'],'post_excerpt'=>$p['ozet'],'post_content'=>$p['icerik'],'post_author'=>$uid,'post_date'=>$p['tarih'],'post_category'=>$c?[$c->term_id]:[]]);
	}
	foreach(($d['gruplar']??[]) as $g){
		if(get_page_by_path($g['slug'],OBJECT,'grup'))continue;
		$id=wp_insert_post(['post_type'=>'grup','post_status'=>'publish','post_title'=>$g['ad'],'post_name'=>$g['slug'],'post_excerpt'=>$g['ozet'],'post_content'=>$g['icerik'],'post_author'=>$uid,'meta_input'=>['_yr_bolge'=>$g['bolge'],'_yr_stil'=>$g['stil'],'_yr_kurulus'=>$g['kurulus'],'_yr_link'=>'']]);
		if($id&&!is_wp_error($id))wp_set_object_terms($id,[$g['tur']],'grup_tur');
	}
	foreach(($d['etkinlikler']??[]) as $e){
		if(get_page_by_path($e['slug'],OBJECT,'etkinlik'))continue;
		wp_insert_post(['post_type'=>'etkinlik','post_status'=>'publish','post_title'=>$e['baslik'],'post_name'=>$e['slug'],'post_excerpt'=>$e['ozet'],'post_content'=>$e['icerik'],'post_author'=>$uid,'meta_input'=>['_yr_tarih'=>$e['tarih'],'_yr_saat'=>$e['saat'],'_yr_sehir'=>$e['sehir'],'_yr_mekan'=>$e['mekan'],'_yr_tur'=>$e['tur'],'_yr_bilet'=>$e['bilet']]]);
	}
	foreach(($d['sayfalar']??[]) as $x){
		$p=get_page_by_path($x['slug'],OBJECT,'page');
		if($p)wp_update_post(['ID'=>$p->ID,'post_content'=>$x['icerik'],'post_status'=>'publish']);
		else wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$x['baslik'],'post_name'=>$x['slug'],'post_content'=>$x['icerik']]);
	}
});

/* Onarım: kategorisi atanamayan ilk yazılara kategorilerini ver (bir kez çalışır) */
add_action('admin_init',function(){
	if(get_option('yr_fix_kat1')||!current_user_can('manage_options'))return;
	$f=get_template_directory().'/seed.json';if(!is_readable($f))return;
	$d=json_decode(file_get_contents($f),true);if(!is_array($d))return;
	update_option('yr_fix_kat1',1);
	$def=(int)get_option('default_category');
	foreach(($d['yazilar']??[]) as $p){
		$x=get_page_by_path($p['slug'],OBJECT,'post');$c=get_term_by('slug',sanitize_title($p['kategori']),'category');
		if(!$x||!$c)continue;
		$cur=wp_get_post_categories($x->ID);
		if(!$cur||$cur===[$def])wp_set_post_categories($x->ID,[$c->term_id]);
	}
});
require_once get_template_directory().'/ozellikler.php';
require_once get_template_directory().'/github.php';
require_once get_template_directory().'/panel.php';
require_once get_template_directory().'/grup-basvuru.php';
require_once get_template_directory().'/hesap.php';
require_once get_template_directory().'/vitrin.php';
require_once get_template_directory().'/metinler.php';
require_once get_template_directory().'/logo.php';
require_once get_template_directory().'/roportaj-form.php';
require_once get_template_directory().'/yeraltindan.php';
require_once get_template_directory().'/sehirler.php';
require_once get_template_directory().'/bulten.php';
