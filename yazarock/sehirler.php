<?php
if(!defined('ABSPATH'))exit;
/*
 * Grup haritası: Türkiye'deki grupları il (ve İstanbul için semt) bazında toplar.
 * Taksonomi: grup_sehir (il → semt). Adres: /sehir/izmir/
 */
function yr_semtler(){return ['Kadıköy'=>'İstanbul','Beyoğlu'=>'İstanbul','Beşiktaş'=>'İstanbul','Üsküdar'=>'İstanbul','Şişli'=>'İstanbul','Bakırköy'=>'İstanbul','Moda'=>'İstanbul','Taksim'=>'İstanbul','Karşıyaka'=>'İzmir','Bornova'=>'İzmir','Alsancak'=>'İzmir','Buca'=>'İzmir','Çankaya'=>'Ankara','Kızılay'=>'Ankara'];}
function yr_il_slug($ad){return sanitize_title(str_replace(['İ','I','ı','â','Â'],['i','i','i','a','a'],$ad));}

add_action('init',function(){
	register_taxonomy('grup_sehir','grup',['labels'=>['name'=>'Şehirler','singular_name'=>'Şehir','search_items'=>'Şehir ara','all_items'=>'Tüm şehirler','parent_item'=>'İl','edit_item'=>'Şehri düzenle','add_new_item'=>'Yeni şehir ekle','menu_name'=>'Şehirler'],'hierarchical'=>true,'public'=>true,'show_in_rest'=>true,'show_admin_column'=>true,'rewrite'=>['slug'=>'sehir','hierarchical'=>false]]);
},5);

/* Kuruluş bilgisinden şehir bul: "Kadıköy, İstanbul · 2010" → [İstanbul, Kadıköy] */
function yr_sehir_bul($metin){
	$m=' '.mb_strtolower((string)$metin).' ';if(trim($m)==='')return [];
	foreach(yr_semtler() as $semt=>$il)if(mb_strpos($m,mb_strtolower($semt))!==false)return [$il,$semt];
	foreach(yr_gb_sehirler() as $il)if(preg_match('/(^|[^\p{L}])'.preg_quote(mb_strtolower($il),'/').'($|[^\p{L}])/u',$m))return [$il,''];
	return [];
}
/* Kuruluş bilgisinde şehir yazmayan bilinen gruplar */
function yr_sehir_bilinen(){return ['Duman'=>'İstanbul','Mor ve Ötesi'=>'İstanbul','maNga'=>'Ankara','Pentagram (Mezarkabul)'=>'İstanbul','Pentagram'=>'İstanbul','Mezarkabul'=>'İstanbul','Moğollar'=>'İstanbul','Kurtalan Ekspres'=>'İstanbul','Bulutsuzluk Özlemi'=>'İstanbul','Kurban'=>'İstanbul','Adamlar'=>'İstanbul','Dolu Kadehi Ters Tut'=>'İstanbul','Burial Invocation'=>'Ankara','Cem Karaca'=>'İstanbul','Erkin Koray'=>'İstanbul','Barış Manço'=>'İstanbul','Şebnem Ferah'=>'İstanbul','Teoman'=>'İstanbul','Gripin'=>'İstanbul','Athena'=>'İstanbul','Badem'=>'İstanbul','Kargo'=>'İstanbul','Hayko Cepkin'=>'İstanbul','Replikas'=>'İstanbul','Yüksek Sadakat'=>'İstanbul','Pinhani'=>'İstanbul'];}
function yr_sehir_ata($pid,$zorla=false){
	if(get_post_type($pid)!=='grup'||get_post_meta($pid,'_yr_bolge',true)!=='tr')return false;
	if(!$zorla){$v=wp_get_object_terms($pid,'grup_sehir',['fields'=>'ids']);if($v&&!is_wp_error($v))return false;}
	$s=yr_sehir_bul(get_post_meta($pid,'_yr_kurulus',true));
	if(!$s&&($gb=(int)get_post_meta($pid,'_yr_gb_kaynak',true)))$s=yr_sehir_bul(get_post_meta($gb,'_gb_sehir',true));
	if(!$s){$b=yr_sehir_bilinen();$ad=get_the_title($pid);if(isset($b[$ad]))$s=[$b[$ad],''];}
	if(!$s)return false;
	$il=term_exists($s[0],'grup_sehir');if(!$il)$il=wp_insert_term($s[0],'grup_sehir',['slug'=>yr_il_slug($s[0])]);
	if(is_wp_error($il))return false;$ilid=(int)$il['term_id'];$ids=[$ilid];
	if($s[1]!==''){$se=term_exists($s[1],'grup_sehir',$ilid);if(!$se)$se=wp_insert_term($s[1],'grup_sehir',['parent'=>$ilid,'slug'=>yr_il_slug($s[1])]);if(!is_wp_error($se))$ids[]=(int)$se['term_id'];}
	wp_set_object_terms($pid,$ids,'grup_sehir');return true;
}
/* Yeni ya da güncellenen gruplarda kendiliğinden ata (elle seçilmişse dokunmaz) */
add_action('save_post_grup',function($pid){if(!wp_is_post_revision($pid)&&!wp_is_post_autosave($pid))yr_sehir_ata($pid);},30);
add_action('updated_post_meta',function($mid,$pid,$k){if($k==='_yr_bolge'||$k==='_yr_kurulus')yr_sehir_ata($pid);},10,3);
/* Mevcut grupları bir kez eşle */
add_action('init',function(){
	if(get_option('yr_mig_v15'))return;update_option('yr_mig_v15',1);
	foreach(get_posts(['post_type'=>'grup','post_status'=>['publish','draft'],'numberposts'=>-1,'fields'=>'ids']) as $id)yr_sehir_ata($id);
	flush_rewrite_rules(false);
},25);

/* Harita verisi: il slug → [ad, grup sayısı (semtler dahil), adres] */
function yr_sehir_harita_verisi(){
	$v=get_transient('yr_sehir_harita');if(is_array($v))return $v;$v=[];
	foreach(get_terms(['taxonomy'=>'grup_sehir','parent'=>0,'hide_empty'=>false]) as $t){
		$n=(int)(new WP_Query(['post_type'=>'grup','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','tax_query'=>[['taxonomy'=>'grup_sehir','terms'=>$t->term_id,'include_children'=>true]]]))->found_posts;
		if($n)$v[$t->slug]=['ad'=>$t->name,'n'=>$n,'url'=>get_term_link($t)];
	}
	uasort($v,function($a,$b){return $b['n']<=>$a['n']?:strcmp($a['ad'],$b['ad']);});
	set_transient('yr_sehir_harita',$v,6*HOUR_IN_SECONDS);return $v;
}
foreach(['save_post_grup','set_object_terms','edited_grup_sehir','created_grup_sehir','delete_grup_sehir'] as $h)add_action($h,function(){delete_transient('yr_sehir_harita');});

/* Gruplar sayfasındaki harita bölümü */
function yr_sehir_harita(){
	$v=yr_sehir_harita_verisi();if(!$v)return;$top=array_sum(array_column($v,'n'));
	echo '<section class="box harita" aria-labelledby="harita-b"><div class="hr-bas"><h2 id="harita-b">Şehir şehir yeraltı</h2><p>Türkiye\'den '.(int)$top.' grup, '.count($v).' şehir. Haritada bir ile dokun, o şehrin gruplarını ve yaklaşan konserlerini gör.</p></div>';
	echo '<div class="hr-map" data-harita="'.esc_attr(wp_make_link_relative(get_template_directory_uri()).'/assets/turkiye.svg?v='.YR_VER).'" data-iller="'.esc_attr(wp_json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'"><div class="hr-ipucu" hidden></div></div>';
	echo '<ul class="hr-liste">';foreach($v as $s=>$i)echo '<li><a href="'.esc_url($i['url']).'">'.esc_html($i['ad']).' <b>'.(int)$i['n'].'</b></a></li>';echo '</ul>';
	echo '<p class="hr-kredi">Harita: svg-turkiye-haritasi (MIT)</p></section>';
}

/* Sayfa başlığı: "İzmir rock ve metal grupları" */
add_filter('document_title_parts',function($t){if(is_tax('grup_sehir')){$t['title']=single_term_title('',false).' rock ve metal grupları';}return $t;});
add_filter('pre_get_posts',function($q){if(!is_admin()&&$q->is_main_query()&&$q->is_tax('grup_sehir')){$q->set('posts_per_page',60);$q->set('orderby','title');$q->set('order','ASC');}return $q;});
/* Türkçe ek: yr_ek('İzmir','den') → "'den", yr_ek('Ankara','de') → "'da", yr_ek('İstanbul','in') → "'un" */
function yr_ek($ad,$tur){
	$s=mb_strtolower(trim((string)$ad));$son=mb_substr($s,-1);$sesli='';
	for($i=mb_strlen($s)-1;$i>=0;$i--){$c=mb_substr($s,$i,1);if(mb_strpos('aeıioöuüâ',$c)!==false){$sesli=$c;break;}}
	$kalin=mb_strpos('aıouâ',$sesli)!==false;$sert=mb_strpos('fstkçşhp',$son)!==false;$unlu=mb_strpos('aeıioöuüâ',$son)!==false;
	if($tur==='in'){$v=['a'=>'ı','â'=>'ı','ı'=>'ı','o'=>'u','u'=>'u','e'=>'i','i'=>'i','ö'=>'ü','ü'=>'ü'][$sesli]??'i';return "'".($unlu?'n':'').$v.'n';}
	$e=($sert?'t':'d').($kalin?'a':'e');return "'".$e.($tur==='den'?'n':'');
}

/* Echoes of Tomorrow yazısına yapay zekâ notu (mig v16) */
add_action('init',function(){
	if(get_option('yr_mig_v16'))return;
	$p=get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>'2026-10-26-kesif-echoes-of-tomorrow','numberposts'=>1]);
	if(!$p)return;/* yazı henüz yoksa sonra tekrar dene */
	update_option('yr_mig_v16',1);
	if(strpos($p[0]->post_content,'yr-ai-not')!==false)return;
	$not='<div class="yr-ai-not"><p><strong>Yazarock notu:</strong> Bu yazı yayımlandıktan sonra, Echoes of Tomorrow\'nun müziğinde ve görsellerinde yapay zekâ araçlarının kullanıldığını fark ettik. Yazarock, insan emeğiyle üretilen müziği öne çıkarmayı ilke ediniyor; bu yazıyı şeffaflık için bu notla birlikte yayında bırakıyoruz. Bundan sonra yapay zekâ ile üretilmiş müziğe sitemizde yer vermeyeceğiz.</p></div>';
	wp_update_post(['ID'=>$p[0]->ID,'post_content'=>wp_slash($not.$p[0]->post_content)]);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},30);

/* Echoes of Tomorrow notu: Jeremy Capo'nun açıklamasına göre güncellendi (mig v17) */
add_action('init',function(){
	if(get_option('yr_mig_v17'))return;
	$p=get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>'2026-10-26-kesif-echoes-of-tomorrow','numberposts'=>1]);
	if(!$p||strpos($p[0]->post_content,'yr-ai-not')===false)return;/* v16 notu henüz eklenmediyse bekle */
	update_option('yr_mig_v17',1);
	$not='<div class="yr-ai-not"><p><strong>Yazarock notu:</strong> Bu yazıyı yayımladıktan sonra Echoes of Tomorrow\'un üretim sürecini Jeremy Capo\'ya sorduk. Capo\'nun açıklamasına göre "Le Dernier Matsuri"nin bestesi ve vokali kendisine ait; miks ve mastering ile bazı Japon enstrüman örneklerinde ise yapay zekâ araçları kullanıldı. Projenin animasyonlu videosu ve görsellerinde yapay zekâ kullanımı daha belirgin. Açık ve dürüst yanıtı için Capo\'ya teşekkür ediyoruz. Yazarock, insan emeğiyle üretilen müziği öne çıkarmayı ilke ediniyor; yapay zekâ kullanılan çalışmalarda bunu okurlarımıza açıkça belirteceğiz.</p></div>';
	$c=preg_replace('#<div class="yr-ai-not">.*?</div>#s',$not,$p[0]->post_content,1);
	wp_update_post(['ID'=>$p[0]->ID,'post_content'=>wp_slash($c)]);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},31);

/* Grup diskografisine yazılmış tek bağlantıyı dinleme bağlantısına taşı (mig v18) */
add_action('init',function(){
	if(get_option('yr_mig_v18'))return;update_option('yr_mig_v18',1);
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_query'=>[['key'=>'_yr_diskografi','value'=>'http','compare'=>'LIKE']]]) as $id){
		$d=trim((string)get_post_meta($id,'_yr_diskografi',true));if(!preg_match('#^https?://\S+$#i',$d))continue;
		$k=strpos($d,'spotify.')!==false?'_yr_spotify':(preg_match('#youtu\.?be#',$d)?'_yr_youtube':'_yr_link');
		if(!get_post_meta($id,$k,true))update_post_meta($id,$k,esc_url_raw($d));
		update_post_meta($id,'_yr_diskografi','');
	}
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},32);

/* Başvurudan eklenen gruplara kadro bilgisini taşı (mig v19) */
add_action('init',function(){
	if(get_option('yr_mig_v19'))return;update_option('yr_mig_v19',1);
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_yr_gb_kaynak']) as $id){
		if(get_post_meta($id,'_yr_uyeler',true))continue;$gb=(int)get_post_meta($id,'_yr_gb_kaynak',true);
		$u=$gb?(string)get_post_meta($gb,'_gb_uyeler',true):'';if($u!=='')update_post_meta($id,'_yr_uyeler',$u);
	}
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},33);

/* Ayberk Saka röportajı: kuruluş yılı 1983 değil, ilk sahne 2000 (mig v20) */
add_action('init',function(){
	if(get_option('yr_mig_v20'))return;
	$p=get_posts(['post_type'=>'post','post_status'=>'any','name'=>'garajdan-sahneye-ayberk-saka','numberposts'=>1]);if(!$p)return;
	update_option('yr_mig_v20',1);
	$c=preg_replace("/\\(1983(?:'|’|&#8217;)[dt]en beri\\)/u","(2000'den beri)",$p[0]->post_content);
	if($c!==$p[0]->post_content)wp_update_post(['ID'=>$p[0]->ID,'post_content'=>wp_slash($c)]);
	$g=get_posts(['post_type'=>'grup','post_status'=>'any','title'=>'Ayberk Saka','numberposts'=>1]);
	if($g){$k=(string)get_post_meta($g[0]->ID,'_yr_kurulus',true);if(strpos($k,'1983')!==false)update_post_meta($g[0]->ID,'_yr_kurulus',str_replace('1983','2000',$k));}
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},34);

/* Haftanın Keşfi: Tahrip (mig v21) */
add_action('init',function(){
	if(get_option('yr_mig_v21'))return;
	$t=get_posts(['post_type'=>'grup','post_status'=>'publish','title'=>'Tahrip','numberposts'=>1]);if(!$t)return;
	update_option('yr_mig_v21',1);
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_yr_sabit','meta_value'=>'1']) as $i)delete_post_meta($i,'_yr_sabit');
	update_post_meta($t[0]->ID,'_yr_sabit','1');
	if(!get_post_meta($t[0]->ID,'_yr_diskografi',true))update_post_meta($t[0]->ID,'_yr_diskografi',"Araf (2011)\nEsaret (EP)\nLabirent (2026)");
	delete_transient('yr_week_'.yr_week_key());
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},35);

/* Tahrip "Labirent" yazısına YouTube bağlantısı (mig v22) */
add_action('init',function(){
	if(get_option('yr_mig_v22'))return;
	$p=get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>'2026-10-29-haftanin-sarkisi-tahrip-labirent','numberposts'=>1]);if(!$p)return;
	update_option('yr_mig_v22',1);
	$yt='https://www.youtube.com/watch?v=Ps8xFQWgNmc';
	update_post_meta($p[0]->ID,'_yr_youtube',$yt);/* yazının altındaki Dinle & İzle bölümünde görünür */
	$t=get_posts(['post_type'=>'grup','post_status'=>'any','title'=>'Tahrip','numberposts'=>1]);
	if($t&&!get_post_meta($t[0]->ID,'_yr_youtube',true))update_post_meta($t[0]->ID,'_yr_youtube',$yt);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},36);

/* Ana sayfa Haftanın Keşfi: Tahrip + Labirent incelemesi (mig v23) */
add_action('init',function(){
	if(get_option('yr_mig_v23'))return;
	$t=get_posts(['post_type'=>'grup','post_status'=>'publish','title'=>'Tahrip','numberposts'=>1]);if(!$t)return;
	update_option('yr_mig_v23',1);
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_yr_kesif']) as $i)delete_post_meta($i,'_yr_kesif');
	update_post_meta($t[0]->ID,'_yr_kesif',time());
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},37);
add_action('init',function(){/* yazı sonradan çekilirse de bağlansın */
	if(get_option('yr_mig_v24'))return;
	$p=get_posts(['post_type'=>'post','post_status'=>'publish','meta_key'=>'_yr_derleme_id','meta_value'=>'2026-10-29-haftanin-sarkisi-tahrip-labirent','numberposts'=>1]);if(!$p)return;
	$t=get_posts(['post_type'=>'grup','post_status'=>'publish','title'=>'Tahrip','numberposts'=>1]);if(!$t)return;
	update_option('yr_mig_v24',1);
	update_post_meta($t[0]->ID,'_yr_kesif_yazi',$p[0]->ID);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},38);

/* Anoreksi yazısına 7 Kasım konseri (mig v25) */
add_action('init',function(){
	if(get_option('yr_mig_v25'))return;
	$p=get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>'2026-10-30-anoreksi-insignificance','numberposts'=>1]);if(!$p)return;
	update_option('yr_mig_v25',1);
	$c=$p[0]->post_content;if(strpos($c,'The Wall Saloon')!==false)return;
	$c.="\n<h3>7 Kasım'da sahnede</h3><p>Anoreksi, <em>Insignificance</em>'ı ilk kez <strong>7 Kasım Cumartesi</strong> günü Kadıköy'deki <strong>The Wall Saloon</strong>'da canlı çalacak. Grubun 9 yıl aradan sonraki ilk konseri olan bu gecede sahneyi Damocles ile paylaşacaklar. Kapılar 20:00'de açılıyor, 18 yaş sınırı var. <a href=\"https://biletino.com/tr/e-1d5u/anoreksi-damocles-the-wall-saloon-kadikoy/\" target=\"_blank\" rel=\"noopener\">Biletler Biletino'da</a>.</p>";
	wp_update_post(['ID'=>$p[0]->ID,'post_content'=>$c]);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},39);
