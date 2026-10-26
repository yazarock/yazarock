<?php
if(!defined('ABSPATH'))exit;
/*
 * "Garajdan Sahneye": grupların kendi yanıtladığı 10 soruluk röportaj formu.
 * Gelen yanıtlar "Röportaj Yanıtları" bölümüne düşer; tek tıkla soru-cevap düzeninde röportaj taslağına dönüşür.
 */

function yr_rp_sorular(){return [
	's1'=>['Grubunuzu üç kelimeyle anlatır mısınız? Biraz da açın: müziğinizi hiç dinlememiş birine nasıl tarif edersiniz?',600],
	's2'=>['Nasıl bir araya geldiniz? Grubun adı nereden geliyor?',1200],
	's3'=>['Sizi en çok etkileyen gruplar, albümler ya da insanlar kimler?',1000],
	's4'=>['Son çıkışınızı anlatın: ne anlatıyor, nasıl kaydettiniz?',1500],
	's5'=>['Şarkılarınız nasıl ortaya çıkıyor? Yazarken ve prova ederken nasıl çalışıyorsunuz?',1200],
	's6'=>['Yaşadığınız şehrin sahnesi nasıl? Nerelerde çalıyorsunuz, kimlerle yol arkadaşlığı yapıyorsunuz?',1200],
	's7'=>['Türkiye\'de bağımsız bir grup olmanın en zor ve en güzel tarafı ne?',1200],
	's8'=>['Unutamadığınız bir konser ya da sahne anısı var mı?',1000],
	's9'=>['Önümüzdeki dönemde neler var? Yeni kayıt, konser, turne?',1000],
	's10'=>['Sizi yeni keşfedecek birine hangi şarkıyla başlamasını önerirsiniz, neden?',600],
];}
function yr_rp($id,$k){return get_post_meta($id,'_rp_'.$k,true);}
function yr_rp_durumlar(){return ['yeni'=>'Yeni','inceleniyor'=>'İnceleniyor','taslak'=>'Röportaj taslağı hazır','yayinlandi'=>'Yayınlandı','uygun_degil'=>'Uygun değil'];}

add_action('init',function(){
	register_post_type('rop_basvuru',['labels'=>['name'=>'Röportaj Yanıtları','singular_name'=>'Röportaj Yanıtı','edit_item'=>'Röportaj Yanıtı','all_items'=>'Röportaj Yanıtları','search_items'=>'Yanıt ara','not_found'=>'Henüz yanıt yok.'],'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-microphone','menu_position'=>7,'supports'=>['title'],'capabilities'=>['create_posts'=>'do_not_allow'],'map_meta_cap'=>true]);
});

/* ================= Form işleyici ================= */
add_action('admin_post_nopriv_yr_rop_basvuru','yr_rp_kaydet');add_action('admin_post_yr_rop_basvuru','yr_rp_kaydet');
function yr_rp_kaydet(){
	$back=yr_page_url('garajdan-sahneye');$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_r',$s,$back).'#form');exit;};
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_rp'))$go('guvenlik');
	if(!empty($_POST['website']))$go('tamam');
	if(!yr_ts_ok())$go('guvenlik');
	$ip=md5(yr_ip());
	if(get_transient('yr_rp_rl_'.$ip))$go('bekle');
	$gun=(int)get_transient('yr_rp_gun_'.$ip);if($gun>=3)$go('limit');
	$t=function($k,$max){return mb_substr(trim(sanitize_text_field(wp_unslash($_POST[$k]??''))),0,$max);};
	$d=['grup'=>$t('grup',80),'sehir'=>$t('sehir',40),'yil'=>$t('yil',4),'tur'=>$t('tur',80),'ad'=>$t('ad',60),'rol'=>$t('rol',40)];
	$d['uyeler']=mb_substr(sanitize_textarea_field(wp_unslash($_POST['uyeler']??'')),0,600);
	$d['email']=sanitize_email(wp_unslash($_POST['email']??''));
	$L=[];foreach(yr_gb_linkler() as $k=>$l){$u=esc_url_raw(trim(wp_unslash($_POST['l_'.$k]??'')),['http','https']);$h=$u?(string)wp_parse_url($u,PHP_URL_HOST):'';if($h&&strpos($h,'.')!==false&&strlen($u)<=300)$L[$k]=$u;}
	$sarki=esc_url_raw(trim(wp_unslash($_POST['sarki']??'')),['http','https']);if($sarki&&strlen($sarki)>300)$sarki='';
	$C=[];$dolu=0;
	foreach(yr_rp_sorular() as $k=>$q){$v=mb_substr(sanitize_textarea_field(wp_unslash($_POST[$k]??'')),0,$q[1]);$C[$k]=$v;if(mb_strlen(trim($v))>=20)$dolu++;}
	if(!preg_match('/^(19[6-9]\d|20\d\d)$/',$d['yil']))$d['yil']='';
	if(mb_strlen($d['grup'])<2||mb_strlen($d['sehir'])<2||mb_strlen($d['tur'])<2||mb_strlen($d['ad'])<2||!yr_eposta_ok($d['email'])||!$L||$dolu<6||empty($_POST['kvkk'])||empty($_POST['hak'])||empty($_POST['duzen'])||empty($_POST['insan']))$go('gecersiz');
	$ac=get_posts(['post_type'=>'rop_basvuru','post_status'=>'any','numberposts'=>20,'date_query'=>[['after'=>'30 days ago']],'meta_query'=>[['key'=>'_rp_durum','value'=>['yeni','inceleniyor'],'compare'=>'IN']]]);
	foreach($ac as $p)if(mb_strtolower($p->post_title)===mb_strtolower($d['grup']))$go('tekrar');
	set_transient('yr_rp_rl_'.$ip,1,2*MINUTE_IN_SECONDS);set_transient('yr_rp_gun_'.$ip,$gun+1,DAY_IN_SECONDS);
	$meta=['_rp_durum'=>'yeni','_rp_linkler'=>$L,'_rp_cevaplar'=>$C,'_rp_sarki'=>$sarki,'_rp_kvkk'=>current_time('mysql')];
	foreach($d as $k=>$v)if($k!=='grup')$meta['_rp_'.$k]=$v;
	$pid=wp_insert_post(wp_slash(['post_type'=>'rop_basvuru','post_status'=>'publish','post_title'=>$d['grup'],'meta_input'=>$meta]),true);
	if(is_wp_error($pid))$go('hata');
	if(!empty($_FILES['foto']['name'])&&(int)($_FILES['foto']['error']??1)===UPLOAD_ERR_OK&&(int)$_FILES['foto']['size']<=5*MB_IN_BYTES){
		$mimes=['jpg|jpeg|jpe'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
		$chk=wp_check_filetype_and_ext($_FILES['foto']['tmp_name'],$_FILES['foto']['name'],$mimes);
		if(!empty($chk['type'])&&@getimagesize($_FILES['foto']['tmp_name'])){
			require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
			$_FILES['foto']['name']=sanitize_title($d['grup']).'-'.wp_generate_password(6,false,false).'.'.$chk['ext'];
			$att=media_handle_upload('foto',$pid,['post_title'=>$d['grup'].' (grup fotoğrafı)'],['test_form'=>false,'mimes'=>$mimes]);
			if(!is_wp_error($att))update_post_meta($pid,'_rp_foto',(int)$att);
		}
	}
	$Q=yr_rp_sorular();$txt='';foreach($C as $k=>$v)if(trim($v)!=='')$txt.=$Q[$k][0]."\n".$v."\n\n";
	wp_mail(get_option('admin_email'),'Yeni röportaj yanıtı: '.$d['grup'],"{$d['grup']} ({$d['sehir']}, {$d['tur']}) Garajdan Sahneye sorularını yanıtladı.\n\n".$txt."İletişim: {$d['ad']} <{$d['email']}>\n\nİncele ve röportaja dönüştür: ".admin_url('post.php?post='.$pid.'&action=edit'),['Reply-To: '.$d['email']]);
	wp_mail($d['email'],'Yazarock: '.$d['grup'].' röportaj yanıtlarınız alındı',"Merhaba {$d['ad']},\n\n{$d['grup']} için Garajdan Sahneye sorularını yanıtladığınız için teşekkürler!\n\nYanıtlarınızı okuyup gerekirse küçük düzeltmeler yapacağız (yazım, uzunluk). Röportaj yayına girdiğinde bu adrese bağlantısını göndereceğiz. Yazarock'ta yer almak tamamen ücretsizdir.\n\nSahnede görüşmek üzere,\nYazarock\n".home_url('/'));
	$go('tamam');
}

/* ================= Yönetim ================= */
add_filter('manage_rop_basvuru_posts_columns',function($c){return ['cb'=>$c['cb'],'title'=>'Grup','rp_sehir'=>'Şehir','rp_tur'=>'Tür','rp_durum'=>'Durum','date'=>'Tarih'];});
add_action('manage_rop_basvuru_posts_custom_column',function($c,$id){
	if($c==='rp_sehir')echo esc_html(yr_rp($id,'sehir'));
	if($c==='rp_tur')echo esc_html(yr_rp($id,'tur'));
	if($c==='rp_durum'){$D=yr_rp_durumlar();$s=yr_rp($id,'durum')?:'yeni';$col=['yeni'=>'#c0282d','inceleniyor'=>'#b26a00','taslak'=>'#2463b0','yayinlandi'=>'#2e7d32','uygun_degil'=>'#777'][$s]??'#777';echo '<span style="display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:'.esc_attr($col).'">'.esc_html($D[$s]??$s).'</span>';}
},10,2);
add_filter('post_row_actions',function($a,$p){
	if($p->post_type!=='rop_basvuru')return $a;
	unset($a['inline hide-if-no-js']);
	$a['edit']='<a href="'.esc_url(get_edit_post_link($p->ID)).'">Oku</a>';
	$r=(int)yr_rp($p->ID,'yazi_id');
	if($r&&get_post($r))$a['rp_yazi']='<a href="'.esc_url(get_edit_post_link($r)).'">Röportajı aç</a>';
	elseif(current_user_can('edit_others_posts'))$a['rp_yazi']='<a href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_rp_yazi&id='.$p->ID),'yr_rp_yazi'.$p->ID)).'">Röportaja dönüştür</a>';
	return $a;
},10,2);
add_action('add_meta_boxes_rop_basvuru',function(){
	add_meta_box('rp_detay','Yanıtlar','yr_rp_detay_kutu','rop_basvuru','normal','high');
	add_meta_box('rp_durum','Durum ve Not','yr_rp_durum_kutu','rop_basvuru','side','high');
});
function yr_rp_detay_kutu($p){
	$id=$p->ID;$LN=yr_gb_linkler();$C=(array)yr_rp($id,'cevaplar');
	$r=(int)yr_rp($id,'yazi_id');
	if($r&&get_post($r))echo '<p><a class="button button-primary" href="'.esc_url(get_edit_post_link($r)).'">Röportaj yazısını aç</a></p>';
	elseif(current_user_can('edit_others_posts'))echo '<p><a class="button button-primary button-hero" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=yr_rp_yazi&id='.$id),'yr_rp_yazi'.$id)).'">Röportaja dönüştür</a></p><p class="description">Yanıtlar soru-cevap düzeninde bir röportaj taslağına dönüşür (Röportaj kategorisi, grup fotoğrafı öne çıkan görsel). Okuyup düzeltir, yayımlarsın.</p>';
	$f=(int)yr_rp($id,'foto');
	if($f)echo '<p><a href="'.esc_url(wp_get_attachment_url($f)).'" target="_blank">'.wp_get_attachment_image($f,'medium',false,['style'=>'max-width:320px;height:auto;border-radius:4px']).'</a></p>';
	echo '<table class="form-table" role="presentation"><tbody>';
	$row=function($l,$v){if($v==='')return;echo '<tr><th style="width:200px">'.esc_html($l).'</th><td>'.$v.'</td></tr>';};
	$row('Grup',esc_html($p->post_title));$row('Şehir',esc_html(yr_rp($id,'sehir')));$row('Kuruluş',esc_html(yr_rp($id,'yil')));$row('Tür',esc_html(yr_rp($id,'tur')));$row('Üyeler',nl2br(esc_html(yr_rp($id,'uyeler'))));
	$lk='';foreach((array)yr_rp($id,'linkler') as $k=>$u)$lk.='<a href="'.esc_url($u).'" target="_blank" rel="noopener noreferrer">'.esc_html($LN[$k]??$k).'</a> &nbsp; ';$row('Dinle / izle',$lk);
	if($s=yr_rp($id,'sarki'))$row('Önerilen şarkı','<a href="'.esc_url($s).'" target="_blank" rel="noopener noreferrer">'.esc_html($s).'</a>');
	foreach(yr_rp_sorular() as $k=>$q)if(trim((string)($C[$k]??''))!=='')$row($q[0],'<div style="white-space:pre-wrap;max-width:720px">'.esc_html($C[$k]).'</div>');
	$em=yr_rp($id,'email');$row('İletişim',esc_html(yr_rp($id,'ad')).(yr_rp($id,'rol')?' ('.esc_html(yr_rp($id,'rol')).')':'').'<br><a href="mailto:'.esc_attr($em).'">'.esc_html($em).'</a>');
	$row('KVKK onayı',esc_html(yr_rp($id,'kvkk')));
	echo '</tbody></table>';
}
function yr_rp_durum_kutu($p){
	wp_nonce_field('yr_rp_durum','yr_rpn');$s=yr_rp($p->ID,'durum')?:'yeni';
	echo '<p><label for="rp_durum_s"><b>Durum</b></label><br><select name="rp_durum" id="rp_durum_s" style="width:100%">';foreach(yr_rp_durumlar() as $k=>$l)echo '<option value="'.esc_attr($k).'"'.selected($s,$k,false).'>'.esc_html($l).'</option>';echo '</select></p>';
	echo '<p><label for="rp_not_s"><b>Editör notu</b></label><textarea name="rp_not" id="rp_not_s" rows="5" style="width:100%">'.esc_textarea(yr_rp($p->ID,'not')).'</textarea></p>';
}
add_action('save_post_rop_basvuru',function($id){
	if(!isset($_POST['yr_rpn'])||!wp_verify_nonce($_POST['yr_rpn'],'yr_rp_durum')||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id))return;
	$s=sanitize_key($_POST['rp_durum']??'');if(isset(yr_rp_durumlar()[$s]))update_post_meta($id,'_rp_durum',$s);
	update_post_meta($id,'_rp_not',sanitize_textarea_field(wp_unslash($_POST['rp_not']??'')));
});
/* Yanıtlardan röportaj taslağı */
function yr_rp_yazi_olustur($id){
	$p=get_post($id);if(!$p||$p->post_type!=='rop_basvuru')return 0;
	$r=(int)yr_rp($id,'yazi_id');if($r&&get_post($r))return $r;
	$e=function($s){return nl2br(esc_html(trim((string)$s)));};
	$grup=$p->post_title;$sehir=(string)yr_rp($id,'sehir');$tur=(string)yr_rp($id,'tur');$yil=(string)yr_rp($id,'yil');
	$C=(array)yr_rp($id,'cevaplar');$L=(array)yr_rp($id,'linkler');$LN=yr_gb_linkler();
	$giris=esc_html($sehir).' merkezli '.esc_html($grup).', '.esc_html(mb_strtolower($tur)).' çizgisinde müzik yapan bir grup'.($yil?' ('.esc_html($yil).'\'den beri)':'').'. Garajdan Sahneye serisinde grubu kendi ağzından dinliyoruz: nasıl bir araya geldiler, neyi anlatıyorlar ve sırada ne var?';
	$h='<!-- wp:paragraph {"className":"rop-giris"} --><p class="rop-giris">'.$giris.'</p><!-- /wp:paragraph -->';
	foreach(yr_rp_sorular() as $k=>$q){$v=trim((string)($C[$k]??''));if($v==='')continue;
		$h.='<!-- wp:paragraph {"className":"soru"} --><p class="soru"><strong>'.esc_html($q[0]).'</strong></p><!-- /wp:paragraph -->';
		$h.='<!-- wp:paragraph {"className":"cevap"} --><p class="cevap">'.$e($v).'</p><!-- /wp:paragraph -->';}
	$sm=[];if($s=(string)yr_rp($id,'sarki')){$hs=(string)wp_parse_url($s,PHP_URL_HOST);if(strpos($hs,'spotify')!==false)$sm['_yr_spotify']=$s;elseif(strpos($hs,'youtu')!==false)$sm['_yr_youtube']=$s;else $L['sarki']=$s;}/* şarkı yazının altındaki "Dinle & İzle" kutusunda çıkar */
	$LN['sarki']='Önerilen şarkı';
	$lk=[];foreach($L as $k=>$u)$lk[]='<a href="'.esc_url($u).'" target="_blank" rel="noopener">'.esc_html($LN[$k]??$k).'</a>';
	$h.='<!-- wp:paragraph {"className":"rop-kapanis"} --><p class="rop-kapanis">'.esc_html($grup).'\'ı dinlemek ve takip etmek için: '.implode(' · ',$lk).'. Siz de grubunuzla Garajdan Sahneye\'de yer almak isterseniz <a href="'.esc_url(yr_page_url('garajdan-sahneye')).'">soruları yanıtlayın</a>.</p><!-- /wp:paragraph -->';
	$kat=yr_roportaj_kat();if(!$kat){$t=wp_insert_term('Röportaj','category',['slug'=>'roportaj']);$kat=is_wp_error($t)?0:(int)$t['term_id'];}
	$gid=0;global $wpdb;$gid=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='grup' AND post_status IN ('publish','draft') AND post_title=%s LIMIT 1",$grup));
	$pid=wp_insert_post(wp_slash(['post_type'=>'post','post_status'=>'draft','post_title'=>'Garajdan Sahneye: '.$grup,'post_name'=>sanitize_title('garajdan-sahneye-'.$grup),'post_content'=>$h,'post_excerpt'=>$sehir.'\'den '.$grup.', müziklerini, sahnelerini ve planlarını Yazarock\'a anlattı.','post_category'=>$kat?[$kat]:[],'tags_input'=>array_filter([$grup,$sehir,'Garajdan Sahneye']),'meta_input'=>['_yr_rp_kaynak'=>$id]+$sm+($gid?['_yr_roportaj_grup'=>$gid]:[])]),true);
	if(is_wp_error($pid))return 0;
	if($f=(int)yr_rp($id,'foto'))set_post_thumbnail($pid,$f);
	update_post_meta($id,'_rp_yazi_id',$pid);update_post_meta($id,'_rp_durum','taslak');
	return $pid;
}
add_action('admin_post_yr_rp_yazi',function(){
	$id=(int)($_GET['id']??0);check_admin_referer('yr_rp_yazi'.$id);
	if(!current_user_can('edit_others_posts'))wp_die('Yetkin yok.');
	$pid=yr_rp_yazi_olustur($id);if(!$pid)wp_die('Röportaj oluşturulamadı.');
	wp_safe_redirect(get_edit_post_link($pid,'url'));exit;
});
/* Röportaj yayımlanınca gruba haber ver, durumu güncelle */
add_action('transition_post_status',function($new,$old,$post){
	if($new!=='publish'||$old==='publish'||$post->post_type!=='post')return;
	$id=(int)get_post_meta($post->ID,'_yr_rp_kaynak',true);if(!$id)return;
	update_post_meta($id,'_rp_durum','yayinlandi');
	$em=yr_rp($id,'email');if(!$em||get_post_meta($id,'_rp_bildirildi',true))return;
	update_post_meta($id,'_rp_bildirildi',1);
	wp_mail($em,'Yazarock: '.get_the_title($id).' röportajı yayında!','Merhaba '.yr_rp($id,'ad').",\n\nGarajdan Sahneye röportajınız yayına girdi:\n".get_permalink($post)."\n\nPaylaşırsanız seviniriz; ne kadar çok kişi okursa grubunuzu o kadar çok kişi keşfeder.\n\nSahnede görüşmek üzere,\nYazarock");
},10,3);
add_action('admin_menu',function(){
	global $menu;$q=new WP_Query(['post_type'=>'rop_basvuru','post_status'=>'any','meta_key'=>'_rp_durum','meta_value'=>'yeni','posts_per_page'=>1,'fields'=>'ids']);$n=(int)$q->found_posts;if(!$n)return;
	foreach((array)$menu as $i=>$m)if(($m[2]??'')==='edit.php?post_type=rop_basvuru')$menu[$i][0].=' <span class="awaiting-mod"><span class="pending-count">'.$n.'</span></span>';
},99);
add_filter('wp_privacy_personal_data_erasers',function($x){
	$x['yazarock_rp']=['eraser_friendly_name'=>'Yazarock röportaj yanıtları','callback'=>function($e){$n=0;foreach(get_posts(['post_type'=>'rop_basvuru','post_status'=>'any','numberposts'=>-1,'meta_key'=>'_rp_email','meta_value'=>$e]) as $p){if($f=(int)yr_rp($p->ID,'foto'))wp_delete_attachment($f,true);wp_delete_post($p->ID,true);$n++;}return ['items_removed'=>$n>0,'items_retained'=>false,'messages'=>[],'done'=>true];}];
	return $x;
});

/* ================= Kurulum: sayfa, KVKK notu (bir kez) ================= */
add_action('init',function(){
	if(get_option('yr_mig_v12'))return;update_option('yr_mig_v12',1);
	$pg=yr_page('Garajdan Sahneye','garajdan-sahneye');
	if($pg&&trim((string)get_post_field('post_content',$pg))===''&&function_exists('yr_sayfa_varsayilanlari')){$D=yr_sayfa_varsayilanlari();if(!empty($D['garajdan-sahneye']))wp_update_post(['ID'=>$pg,'post_content'=>wp_slash($D['garajdan-sahneye'])]);}
	if(!term_exists('Röportaj','category'))wp_insert_term('Röportaj','category',['slug'=>'roportaj']);
	$k=get_page_by_path('kvkk-aydinlatma-metni',OBJECT,'page');
	if($k&&strpos($k->post_content,'Röportaj Yanıtları')===false)wp_update_post(['ID'=>$k->ID,'post_content'=>wp_slash($k->post_content.'<h2>Röportaj Yanıtları</h2><p>"Garajdan Sahneye" formunu doldurduğunuzda grup bilgileri, yanıtlarınız, bağlantılar ve isteğe bağlı grup fotoğrafı ile iletişim kişisinin adı ve e-posta adresi işlenir. Yanıtlar ve grup bilgileri düzenlenerek röportaj olarak yayımlanabilir; iletişim bilgileri yayımlanmaz. Kayıtlar en fazla iki yıl saklanır; silinmesini istediğinizde info@yazarock.com adresine yazmanız yeterlidir.</p>')]);
	flush_rewrite_rules(false);
},20);
