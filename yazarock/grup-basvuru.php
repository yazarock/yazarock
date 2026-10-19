<?php
if(!defined('ABSPATH'))exit;
/*
 * "Grubunu Tanıt": Türkiye'deki bağımsız / underground grupların başvuru formu ve yönetimi
 */

function yr_gb_sehirler(){return ['Adana','Adıyaman','Afyonkarahisar','Ağrı','Aksaray','Amasya','Ankara','Antalya','Ardahan','Artvin','Aydın','Balıkesir','Bartın','Batman','Bayburt','Bilecik','Bingöl','Bitlis','Bolu','Burdur','Bursa','Çanakkale','Çankırı','Çorum','Denizli','Diyarbakır','Düzce','Edirne','Elazığ','Erzincan','Erzurum','Eskişehir','Gaziantep','Giresun','Gümüşhane','Hakkari','Hatay','Iğdır','Isparta','İstanbul','İzmir','Kahramanmaraş','Karabük','Karaman','Kars','Kastamonu','Kayseri','Kilis','Kırıkkale','Kırklareli','Kırşehir','Kocaeli','Konya','Kütahya','Malatya','Manisa','Mardin','Mersin','Muğla','Muş','Nevşehir','Niğde','Ordu','Osmaniye','Rize','Sakarya','Samsun','Şanlıurfa','Siirt','Sinop','Şırnak','Sivas','Tekirdağ','Tokat','Trabzon','Tunceli','Uşak','Van','Yalova','Yozgat','Zonguldak'];}
function yr_gb_linkler(){return ['bandcamp'=>'Bandcamp','spotify'=>'Spotify','youtube'=>'YouTube','instagram'=>'Instagram','web'=>'Web sitesi / diğer'];}
function yr_gb_istekler(){return ['roportaj'=>'Röportaj','inceleme'=>'Albüm / EP incelemesi','konser'=>'Konser duyurusu','tanitim'=>'Grup tanıtımı'];}
function yr_gb_durumlar(){return ['yeni'=>'Yeni','inceleniyor'=>'İnceleniyor','roportaj'=>'Röportaj planlandı','yayinlandi'=>'Yayınlandı','uygun_degil'=>'Uygun değil'];}
function yr_gb($id,$k){return get_post_meta($id,'_gb_'.$k,true);}

add_action('init',function(){
	register_post_type('grup_basvuru',['labels'=>['name'=>'Grup Başvuruları','singular_name'=>'Grup Başvurusu','edit_item'=>'Grup Başvurusu','all_items'=>'Grup Başvuruları','search_items'=>'Başvuru ara','not_found'=>'Henüz başvuru yok.'],'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-megaphone','menu_position'=>7,'supports'=>['title'],'capabilities'=>['create_posts'=>'do_not_allow'],'map_meta_cap'=>true]);
});

/* ================= Form işleyici ================= */
add_action('admin_post_nopriv_yr_grup_basvuru','yr_gb_kaydet');add_action('admin_post_yr_grup_basvuru','yr_gb_kaydet');
function yr_gb_kaydet(){
	$back=yr_page_url('grubunu-tanit');$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_g',$s,$back).'#basvuru');exit;};
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_gb'))$go('guvenlik');
	if(!empty($_POST['website']))$go('tamam');
	if(!yr_ts_ok())$go('guvenlik');
	$ip=md5(yr_ip());
	if(get_transient('yr_gb_rl_'.$ip))$go('bekle');
	$gun=(int)get_transient('yr_gb_gun_'.$ip);if($gun>=5)$go('limit');
	$t=function($k,$max){return mb_substr(trim(sanitize_text_field(wp_unslash($_POST[$k]??''))),0,$max);};
	$d=['grup'=>$t('grup',80),'sehir'=>$t('sehir',40),'yil'=>$t('yil',4),'tur'=>$t('tur',80),'son'=>$t('son',120),'konser'=>$t('konser',200),'ad'=>$t('ad',60),'rol'=>$t('rol',40),'tel'=>$t('tel',25)];
	$d['uyeler']=mb_substr(sanitize_textarea_field(wp_unslash($_POST['uyeler']??'')),0,600);
	$d['hikaye']=mb_substr(sanitize_textarea_field(wp_unslash($_POST['hikaye']??'')),0,2000);
	$d['email']=sanitize_email(wp_unslash($_POST['email']??''));
	$L=[];foreach(yr_gb_linkler() as $k=>$l){$u=esc_url_raw(trim(wp_unslash($_POST['l_'.$k]??'')),['http','https']);$h=$u?(string)wp_parse_url($u,PHP_URL_HOST):'';if($h&&strpos($h,'.')!==false&&strlen($u)<=300)$L[$k]=$u;}
	$I=array_values(array_intersect(array_keys(yr_gb_istekler()),array_map('sanitize_key',(array)($_POST['istek']??[]))));
	if(!preg_match('/^(19[6-9]\d|20\d\d)$/',$d['yil']))$d['yil']='';
	if(mb_strlen($d['grup'])<2||mb_strlen($d['sehir'])<2||mb_strlen($d['tur'])<2||mb_strlen($d['hikaye'])<80||mb_strlen($d['ad'])<2||!yr_eposta_ok($d['email'])||!$L||empty($_POST['kvkk'])||empty($_POST['hak']))$go('gecersiz');
	/* aynı grup için açık başvuru varsa tekrar alma */
	$ac=get_posts(['post_type'=>'grup_basvuru','post_status'=>'any','numberposts'=>20,'date_query'=>[['after'=>'30 days ago']],'meta_query'=>[['key'=>'_gb_durum','value'=>['yeni','inceleniyor'],'compare'=>'IN']]]);
	foreach($ac as $p)if(mb_strtolower($p->post_title)===mb_strtolower($d['grup']))$go('tekrar');
	set_transient('yr_gb_rl_'.$ip,1,2*MINUTE_IN_SECONDS);set_transient('yr_gb_gun_'.$ip,$gun+1,DAY_IN_SECONDS);
	$meta=['_gb_durum'=>'yeni','_gb_linkler'=>$L,'_gb_istek'=>$I,'_gb_kvkk'=>current_time('mysql')];
	foreach($d as $k=>$v)if($k!=='grup')$meta['_gb_'.$k]=$v;
	$pid=wp_insert_post(['post_type'=>'grup_basvuru','post_status'=>'publish','post_title'=>$d['grup'],'meta_input'=>$meta],true);
	if(is_wp_error($pid))$go('hata');
	/* fotoğraf (isteğe bağlı) */
	if(!empty($_FILES['foto']['name'])&&(int)($_FILES['foto']['error']??1)===UPLOAD_ERR_OK&&(int)$_FILES['foto']['size']<=5*MB_IN_BYTES){
		$mimes=['jpg|jpeg|jpe'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
		$chk=wp_check_filetype_and_ext($_FILES['foto']['tmp_name'],$_FILES['foto']['name'],$mimes);
		if(!empty($chk['type'])&&@getimagesize($_FILES['foto']['tmp_name'])){
			require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
			$_FILES['foto']['name']=sanitize_title($d['grup']).'-'.wp_generate_password(6,false,false).'.'.$chk['ext'];
			$att=media_handle_upload('foto',$pid,['post_title'=>$d['grup'].' (grup fotoğrafı)'],['test_form'=>false,'mimes'=>$mimes]);
			if(!is_wp_error($att))update_post_meta($pid,'_gb_foto',(int)$att);
		}
	}
	$IS=yr_gb_istekler();
	$msg="Yeni grup başvurusu: {$d['grup']}\n\nŞehir: {$d['sehir']}\nTür: {$d['tur']}\nİstek: ".implode(', ',array_map(function($k)use($IS){return $IS[$k];},$I))."\nBağlantılar:\n".implode("\n",$L)."\n\nGrubun hikâyesi:\n{$d['hikaye']}\n\nİletişim: {$d['ad']} <{$d['email']}>\n\nİncele: ".admin_url('post.php?post='.$pid.'&action=edit');
	wp_mail(get_option('admin_email'),'Yeni grup başvurusu: '.$d['grup'],$msg,['Reply-To: '.$d['email']]);
	wp_mail($d['email'],'Yazarock: '.$d['grup'].' başvurunuz alındı',"Merhaba {$d['ad']},\n\n{$d['grup']} için yaptığınız başvuru bize ulaştı. Teşekkürler!\n\nGönderdiğiniz her başvuruyu dinliyoruz. Röportaj, inceleme ya da tanıtım için uygun bulduğumuzda bu e-posta adresinden size yazacağız. Yazarock'ta yer almak tamamen ücretsizdir; sizden hiçbir ücret talep etmeyiz.\n\nSahnede görüşmek üzere,\nYazarock\n".home_url('/'));
	$go('tamam');
}

/* ================= Yönetim ekranı ================= */
add_filter('manage_grup_basvuru_posts_columns',function($c){return ['cb'=>$c['cb'],'title'=>'Grup','gb_sehir'=>'Şehir','gb_tur'=>'Tür','gb_istek'=>'İstek','gb_durum'=>'Durum','date'=>'Tarih'];});
add_action('manage_grup_basvuru_posts_custom_column',function($c,$id){
	if($c==='gb_sehir')echo esc_html(yr_gb($id,'sehir'));
	if($c==='gb_tur')echo esc_html(yr_gb($id,'tur'));
	if($c==='gb_istek'){$IS=yr_gb_istekler();echo esc_html(implode(', ',array_map(function($k)use($IS){return $IS[$k]??$k;},(array)yr_gb($id,'istek'))));}
	if($c==='gb_durum'){$D=yr_gb_durumlar();$s=yr_gb($id,'durum')?:'yeni';$col=['yeni'=>'#c0282d','inceleniyor'=>'#b26a00','roportaj'=>'#2463b0','yayinlandi'=>'#2e7d32','uygun_degil'=>'#777'][$s]??'#777';echo '<span style="display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:'.esc_attr($col).'">'.esc_html($D[$s]??$s).'</span>';}
},10,2);
add_filter('views_edit-grup_basvuru',function($v){
	$base=admin_url('edit.php?post_type=grup_basvuru');
	foreach(yr_gb_durumlar() as $k=>$l){$q=new WP_Query(['post_type'=>'grup_basvuru','post_status'=>'any','meta_key'=>'_gb_durum','meta_value'=>$k,'posts_per_page'=>1,'fields'=>'ids']);
		$v['gb_'.$k]='<a href="'.esc_url(add_query_arg('gb_durum',$k,$base)).'"'.((($_GET['gb_durum']??'')===$k)?' class="current"':'').'>'.esc_html($l).' <span class="count">('.(int)$q->found_posts.')</span></a>';}
	return $v;
});
add_action('pre_get_posts',function($q){
	if(!is_admin()||!$q->is_main_query()||$q->get('post_type')!=='grup_basvuru')return;
	$k=sanitize_key($_GET['gb_durum']??'');if($k&&isset(yr_gb_durumlar()[$k]))$q->set('meta_query',[['key'=>'_gb_durum','value'=>$k]]);
});
add_filter('post_row_actions',function($a,$p){
	if($p->post_type!=='grup_basvuru')return $a;
	unset($a['inline hide-if-no-js']);
	$a['edit']='<a href="'.esc_url(get_edit_post_link($p->ID)).'">İncele</a>';
	$g=(int)yr_gb($p->ID,'grup_id');
	if($g&&get_post($g))$a['gb_grup']='<a href="'.esc_url(get_edit_post_link($g)).'">Grup sayfasını aç</a>';
	elseif(current_user_can('edit_others_posts'))$a['gb_grup']='<a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_gb_grup&id='.$p->ID),'yr_gb_grup'.$p->ID)).'">Gruplara ekle</a>';
	return $a;
},10,2);

add_action('add_meta_boxes_grup_basvuru',function(){
	add_meta_box('gb_detay','Başvuru Ayrıntıları','yr_gb_detay_kutu','grup_basvuru','normal','high');
	add_meta_box('gb_durum','Durum ve Not','yr_gb_durum_kutu','grup_basvuru','side','high');
});
function yr_gb_detay_kutu($p){
	$id=$p->ID;$IS=yr_gb_istekler();$LN=yr_gb_linkler();
	$row=function($l,$v){if($v==='')return;echo '<tr><th style="width:170px">'.esc_html($l).'</th><td>'.$v.'</td></tr>';};
	$f=(int)yr_gb($id,'foto');
	if($f)echo '<p><a href="'.esc_url(wp_get_attachment_url($f)).'" target="_blank">'.wp_get_attachment_image($f,'medium',false,['style'=>'max-width:320px;height:auto;border-radius:4px']).'</a></p>';
	echo '<table class="form-table" role="presentation"><tbody>';
	$row('Grup',esc_html($p->post_title));
	$row('Şehir',esc_html(yr_gb($id,'sehir')));
	$row('Kuruluş yılı',esc_html(yr_gb($id,'yil')));
	$row('Tür',esc_html(yr_gb($id,'tur')));
	$row('Üyeler',nl2br(esc_html(yr_gb($id,'uyeler'))));
	$row('Son çıkış',esc_html(yr_gb($id,'son')));
	$row('Yaklaşan konser',esc_html(yr_gb($id,'konser')));
	$lk='';foreach((array)yr_gb($id,'linkler') as $k=>$u)$lk.='<a href="'.esc_url($u).'" target="_blank" rel="noopener noreferrer">'.esc_html($LN[$k]??$k).'</a> &nbsp; ';$row('Dinle / izle',$lk);
	$row('İstek',esc_html(implode(', ',array_map(function($k)use($IS){return $IS[$k]??$k;},(array)yr_gb($id,'istek')))));
	$row('Grubun hikâyesi','<div style="white-space:pre-wrap;max-width:720px">'.esc_html(yr_gb($id,'hikaye')).'</div>');
	$em=yr_gb($id,'email');
	$row('İletişim',esc_html(yr_gb($id,'ad')).(yr_gb($id,'rol')?' ('.esc_html(yr_gb($id,'rol')).')':'').'<br><a href="mailto:'.esc_attr($em).'">'.esc_html($em).'</a>'.(yr_gb($id,'tel')?'<br>'.esc_html(yr_gb($id,'tel')):''));
	$row('KVKK onayı',esc_html(yr_gb($id,'kvkk')));
	echo '</tbody></table>';
	$g=(int)yr_gb($id,'grup_id');
	if($g&&get_post($g))echo '<p><a class="button" href="'.esc_url(get_edit_post_link($g)).'">Grup sayfasını düzenle</a></p>';
	elseif(current_user_can('edit_others_posts'))echo '<p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_gb_grup&id='.$id),'yr_gb_grup'.$id)).'">Gruplara ekle (taslak grup sayfası oluştur)</a></p><p class="description">Bilgiler, bağlantılar ve fotoğrafla doldurulmuş bir taslak grup sayfası açılır; düzenleyip yayınlarsın.</p>';
}
function yr_gb_durum_kutu($p){
	wp_nonce_field('yr_gb_durum','yr_gbn');$s=yr_gb($p->ID,'durum')?:'yeni';
	echo '<p><label for="gb_durum_s"><b>Durum</b></label><br><select name="gb_durum" id="gb_durum_s" style="width:100%">';foreach(yr_gb_durumlar() as $k=>$l)echo '<option value="'.esc_attr($k).'"'.selected($s,$k,false).'>'.esc_html($l).'</option>';echo '</select></p>';
	echo '<p><label for="gb_not_s"><b>Editör notu</b> <small>(yalnızca yönetimde görünür)</small></label><textarea name="gb_not" id="gb_not_s" rows="5" style="width:100%">'.esc_textarea(yr_gb($p->ID,'not')).'</textarea></p>';
}
add_action('save_post_grup_basvuru',function($id){
	if(!isset($_POST['yr_gbn'])||!wp_verify_nonce($_POST['yr_gbn'],'yr_gb_durum')||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id))return;
	$s=sanitize_key($_POST['gb_durum']??'');if(isset(yr_gb_durumlar()[$s]))update_post_meta($id,'_gb_durum',$s);
	update_post_meta($id,'_gb_not',sanitize_textarea_field(wp_unslash($_POST['gb_not']??'')));
});
/* Başvurudan taslak grup sayfası oluştur */
add_action('admin_post_yr_gb_grup',function(){
	$id=(int)($_GET['id']??0);check_admin_referer('yr_gb_grup'.$id);
	if(!current_user_can('edit_others_posts'))wp_die('Yetkin yok.');
	$p=get_post($id);if(!$p||$p->post_type!=='grup_basvuru')wp_die('Başvuru bulunamadı.');
	$g=(int)yr_gb($id,'grup_id');if($g&&get_post($g)){wp_safe_redirect(get_edit_post_link($g,'url'));exit;}
	$L=(array)yr_gb($id,'linkler');$tur=(string)yr_gb($id,'tur');
	$ana=$L['bandcamp']??($L['web']??($L['spotify']??($L['youtube']??($L['instagram']??''))));
	$h=(string)yr_gb($id,'hikaye');
	$gid=wp_insert_post(wp_slash(['post_type'=>'grup','post_status'=>'draft','post_title'=>$p->post_title,'post_content'=>wpautop(htmlspecialchars($h,ENT_NOQUOTES,'UTF-8')),'post_excerpt'=>wp_trim_words($h,28),
		'meta_input'=>['_yr_bolge'=>'tr','_yr_stil'=>$tur,'_yr_kurulus'=>yr_join(yr_gb($id,'sehir'),yr_gb($id,'yil')),'_yr_link'=>$ana,'_yr_spotify'=>$L['spotify']??'','_yr_youtube'=>$L['youtube']??'','_yr_diskografi'=>(string)yr_gb($id,'son'),'_yr_gorsel_kredi'=>yr_gb($id,'foto')?'Grubun izniyle':'','_yr_gb_kaynak'=>$id]]),true);
	if(is_wp_error($gid))wp_die(esc_html($gid->get_error_message()));
	$tl=mb_strtolower($tur);$terms=[];foreach(['metal'=>'Metal','rock'=>'Rock','alternatif'=>'Alternatif','alternative'=>'Alternatif','progresif'=>'Progresif','progressive'=>'Progresif'] as $k=>$v)if(strpos($tl,$k)!==false)$terms[]=$v;
	if($terms)wp_set_object_terms($gid,array_unique($terms),'grup_tur');
	if($f=(int)yr_gb($id,'foto'))set_post_thumbnail($gid,$f);
	update_post_meta($id,'_gb_grup_id',$gid);if(yr_gb($id,'durum')==='yeni')update_post_meta($id,'_gb_durum','inceleniyor');
	wp_safe_redirect(get_edit_post_link($gid,'url'));exit;
});
/* Menüde yeni başvuru sayısı */
add_action('admin_menu',function(){
	global $menu;$q=new WP_Query(['post_type'=>'grup_basvuru','post_status'=>'any','meta_key'=>'_gb_durum','meta_value'=>'yeni','posts_per_page'=>1,'fields'=>'ids']);$n=(int)$q->found_posts;if(!$n)return;
	foreach((array)$menu as $i=>$m)if(($m[2]??'')==='edit.php?post_type=grup_basvuru')$menu[$i][0].=' <span class="awaiting-mod"><span class="pending-count">'.$n.'</span></span>';
},99);
/* KVKK: başvuru kayıtlarını kişisel veri araçlarına bağla */
add_filter('wp_privacy_personal_data_erasers',function($x){
	$x['yazarock_gb']=['eraser_friendly_name'=>'Yazarock grup başvuruları','callback'=>function($e){$n=0;foreach(get_posts(['post_type'=>'grup_basvuru','post_status'=>'any','numberposts'=>-1,'meta_key'=>'_gb_email','meta_value'=>$e]) as $p){if($f=(int)yr_gb($p->ID,'foto'))wp_delete_attachment($f,true);wp_delete_post($p->ID,true);$n++;}return ['items_removed'=>$n>0,'items_retained'=>false,'messages'=>[],'done'=>true];}];
	return $x;
});
add_filter('wp_privacy_personal_data_exporters',function($x){
	$x['yazarock_gb']=['exporter_friendly_name'=>'Yazarock grup başvuruları','callback'=>function($e){$d=[];foreach(get_posts(['post_type'=>'grup_basvuru','post_status'=>'any','numberposts'=>-1,'meta_key'=>'_gb_email','meta_value'=>$e]) as $p)$d[]=['group_id'=>'grup_basvuru','group_label'=>'Grup başvurusu','item_id'=>'gb-'.$p->ID,'data'=>[['name'=>'Grup','value'=>$p->post_title],['name'=>'Ad','value'=>yr_gb($p->ID,'ad')],['name'=>'Telefon','value'=>yr_gb($p->ID,'tel')],['name'=>'Tarih','value'=>$p->post_date]]];return ['data'=>$d,'done'=>true];}];
	return $x;
});

/* ================= v1.6 geçişi: sayfa, menü, kategoriler, KVKK ================= */
add_action('admin_init',function(){
	if(get_option('yr_mig_v6')||!current_user_can('manage_options'))return;
	update_option('yr_mig_v6',1);
	yr_page('Grubunu Tanıt','grubunu-tanit');
	foreach(['Keşif','Yeni Çıkış','Konser Duyurusu','Röportaj'] as $c)if(!term_exists($c,'category'))wp_insert_term($c,'category');
	$m=wp_get_nav_menu_object('Ana Menü');
	if($m){
		$items=(array)wp_get_nav_menu_items($m->term_id);$has=false;$pos=0;
		foreach($items as $it){if($it->title==='Grubunu Tanıt')$has=true;if($it->title==='Yazar Ol')$pos=(int)$it->menu_order;}
		if(!$has){
			if($pos)foreach($items as $it)if((int)$it->menu_order>=$pos)wp_update_post(['ID'=>$it->ID,'menu_order'=>(int)$it->menu_order+1]);
			wp_update_nav_menu_item($m->term_id,0,['menu-item-title'=>'Grubunu Tanıt','menu-item-url'=>yr_page_url('grubunu-tanit'),'menu-item-type'=>'custom','menu-item-status'=>'publish','menu-item-classes'=>'gt','menu-item-position'=>$pos?$pos:0]);
		}
	}
	$k=get_page_by_path('kvkk-aydinlatma-metni',OBJECT,'page');
	if($k&&strpos($k->post_content,'Grup Başvuruları')===false)wp_update_post(['ID'=>$k->ID,'post_content'=>wp_slash($k->post_content.'<h2>Grup Başvuruları</h2><p>"Grubunu Tanıt" formuyla başvurduğunuzda; grup bilgileri, bağlantılar ve isteğe bağlı grup fotoğrafı ile iletişim kişisinin adı, e-posta adresi ve (verilirse) telefon numarası işlenir. Bu bilgiler başvurunun değerlendirilmesi, sizinle iletişim kurulması ve uygun görülürse grubun sitede tanıtılması amacıyla kullanılır. İletişim bilgileri yayımlanmaz. Başvuru kayıtları en fazla iki yıl saklanır; silinmesini istediğinizde info@yazarock.com adresine yazmanız yeterlidir.</p>')]);
	flush_rewrite_rules();
});
