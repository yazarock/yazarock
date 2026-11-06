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
	if($tur==='i'){$v=['a'=>'ı','â'=>'ı','ı'=>'ı','o'=>'u','u'=>'u','e'=>'i','i'=>'i','ö'=>'ü','ü'=>'ü'][$sesli]??'i';return "'".($unlu?'y':'').$v;}/* belirtme: Metin'i, Saka'yı, Kara Mesai'yi */
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

/* Mevcut yazılarda başlıktaki kategori tekrarını sil (mig v26): "Keşif: X" → "X". Adres (slug) değişmez. */
add_action('init',function(){
	if(get_option('yr_mig_v26')||!function_exists('yr_baslik_kat_sil'))return;
	update_option('yr_mig_v26',1);
	foreach(get_posts(['post_type'=>'post','post_status'=>'any','numberposts'=>-1]) as $p){
		$c=get_the_category($p->ID);if(!$c)continue;
		$yeni=yr_baslik_kat_sil($p->post_title,$c[0]->name);
		if($yeni!==$p->post_title){global $wpdb;$wpdb->update($wpdb->posts,['post_title'=>$yeni],['ID'=>$p->ID]);clean_post_cache($p->ID);}/* içerik filtrelerden geçmesin diye doğrudan */
	}
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},40);

/* Haftanın Keşfi: Anoreksi + Insignificance yazısı (mig v27) */
add_action('init',function(){
	if(get_option('yr_mig_v27'))return;
	$g=get_posts(['post_type'=>'grup','post_status'=>'publish','title'=>'Anoreksi','numberposts'=>1]);if(!$g)return;
	$p=get_posts(['post_type'=>'post','post_status'=>'publish','meta_key'=>'_yr_derleme_id','meta_value'=>'2026-10-30-anoreksi-insignificance','numberposts'=>1]);if(!$p)return;
	update_option('yr_mig_v27',1);
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_yr_kesif']) as $i)delete_post_meta($i,'_yr_kesif');
	update_post_meta($g[0]->ID,'_yr_kesif',time());
	update_post_meta($g[0]->ID,'_yr_kesif_yazi',$p[0]->ID);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},41);

/* ZeroSpan yazısı: grubun EPK metni + basın fotoğrafı (Nevzat'ın onayıyla) (mig v28) */
add_action('init',function(){
	if(get_option('yr_mig_v28'))return;
	$p=get_page_by_path('groove-ile-progresif-death-metal-arasinda-ilk-album-zerospan',OBJECT,'post');if(!$p)return;
	update_option('yr_mig_v28',1);
	$c='
<p><strong>Tür:</strong> Groove / Progressive Death Metal</p><p><strong>Şu grupları sevenler için:</strong> Lamb of God, Gojira, Meshuggah, Death</p><p><strong>Lokasyon:</strong> İstanbul / TÜRKİYE</p><p>Temelleri 2018 yılında atılan Groove / Progresif Death Metal grubu ZeroSpan, agresif ve dinamik riffleri, sert tarzı, teknik soloları, alışılagelmişin dışındaki şarkı yapısını sözlerinde polis şiddetini, sosyal eşitsizlikleri yaratan Dünya sistemini ve bağımlılıkları ele alan şarkı sözleriyle dinleyenlerde farkındalık uyandırmayı amaçlıyor.</p><p>Kadroda vokallerde Can Kurtulay, gitarda Yiğit Hiçyılmaz ve Ataberk Denge, bas gitarda Atakan Germiyanoğlu ve davulda Mert Gürkan Demir\' den oluşan grup, yapımının tamamını kendilerinin üstlendiği toplam 8 yıl süren albüm sürecinden sonra albümün ilk teklisi ve müzik klibi olan "Forgot My Pills" şarkısını 5 Haziran 2026’da, ardından grubun ismini taşıyan 42 dakika uzunluğundaki 7 şarkılık albümlerini 12 Haziran 2026 tarihinde müzikseverler ile buluşturuyor.</p><p>Albüm, dijital platformların yanı sıra fiziksel kopya tercih eden dinleyiciler ve koleksiyonerler için CD formatında da müzikseverlerin beğenisine sunuluyor.</p><p><strong>&quot;Forgot My Pills&quot; Müzik Klibi:</strong><br><a href="https://youtu.be/N0W2ro20XcI">https://youtu.be/N0W2ro20XcI</a></p><h3>BİYOGRAFİ</h3><p>İlhamını Death, Lamb of God, Gojira, Meshuggah gibi devlerden alan ZeroSpan hayatına Yiğit Hiçyılmaz, Can Kurtulay ve Çağlar Gümüş tarafından 2018’de kurulan Çanakkale/ Türkiye’ de Headshot isimli cover grubu olarak başladı. Eş güdümlü olarak beste çalışmalarına başlayan ekip 6 şarkılık demosunu 2019’ un Aralık ayında kısıtlı kayıt bilgisi ve sınırlı teknik imkanlarıyla kaydetti. Ancak araya giren pandemi, mesleki zorunluluklar ve şehir değişimleri nedeniyle albüm kaydını 2025’ e kadar rafa kaldırmak zorunda kaldı.</p><p>Çağlar’ın gruptan ayrılması ve Yiğit’ in İstanbul’a yerleşmesi sonrası ekibe Headshot demolarını uzun zamandır severek dinleyen yakın dostları Ataberk Denge ve Atakan Germiyanoğlu, sonrasında müzisyen dostları sayesinde tanıştıkları genç davulcu Mert Gürkan Demir katıldı.</p><p>Albümün potansiyelinin farkında olup bu vizyonu hayata geçirmek için çalışmalara 2025 yılında yeniden başlayan Yiğit, Can ve Atakan, Çağlar’ ın 2019’ da yazdığı davullarla bitirdikten sonra süreç içinde Yiğit’ in birçok deneme yanılmayla Mix ve Mastering öğrenmesi sürecinde 1 yıl içinde yapılan 30 dan fazla master versiyonundan sonra grup albümü kendileri için en otantik ve en mükemmel forma ulaştırdı.</p><p>Albümün görsel dünyasını da müziği kadar iddialı bir seviyeye taşımak isteyen grup, yapım aşamasında görsel sanatçı “LVNA” ile çalıştı. Bu işbirliği kapsamında, albümün CD kitapçığında yer almak üzere albümdeki her bir şarkı için o parçanın atmosferini birebir yansıtan, tamamen ona özel ve eşsiz birer sanat eseri tasarlandı.</p><p>İlk albümlerinin yapım aşamasında 8 yıllık müzikal yolculuğun samimiyetini mümkün mertebe korumaya çalışan, zaman zaman kayıt sürecindeki organik “kusurlara” yer veren grup, ilk albümdeki sonuç ürünün sterilliğinden ziyade süreçteki gelişen vizyonun tam olarak ortaya çıkmasını öncelik edindi.</p><h3>İletişim</h3><p><a href="mailto:zerospanofficial@gmail.com">zerospanofficial@gmail.com</a></p><h3>Tüm bağlantılarımız</h3><p><a href="https://lnk.bio/zerospanofficial">https://lnk.bio/zerospanofficial</a></p><h3>Resmi ürün mağazası</h3><p><a href="https://www.shopier.com/zerospanofficial">https://www.shopier.com/zerospanofficial</a></p><p>Yazıyı bitirmeden ZeroSpan\'ın resmi merch mağazasına da bir uğramanızı öneririz; bağımsız bir grup için şaşırtıcı derecede özenli ve geniş bir koleksiyon. Sınırlı sayıda üretilen box set\'ten albümün fiziksel CD\'sine, "Refusing God" ve "Womb To Tomb" tişörtlerinden nakışlı yamalara kadar 39 ürün var. Şarkıların her biri için ayrı tasarlanmış rozetler ve açacaklı buzdolabı magnetleri, gitaristler için logolu penalar ve hatta bir mühür mumu bile koleksiyonun parçası. Albümünü sekiz yıl boyunca kendi imkânlarıyla yapan bir grubudesteklemenin en doğrudan yolu da bu.</p><h3>CD satın al</h3><p><a href="https://www.opus3a.com/u/zerospan-cd/bb3bba24d99dd6ba411dfb691382541e">https://www.opus3a.com/u/zerospan-cd/bb3bba24d99dd6ba411dfb691382541e</a></p><h3>Sosyal medya linkleri</h3><p><a href="https://instagram.com/zerospanofficial">https://instagram.com/zerospanofficial</a><br><a href="https://youtube.com/@ZeroSpanOfficial">https://youtube.com/@ZeroSpanOfficial</a><br><a href="https://www.tiktok.com/@zerospanofficial">https://www.tiktok.com/@zerospanofficial</a><br><a href="https://www.facebook.com/zerospanofficial/">https://www.facebook.com/zerospanofficial/</a></p><h3>İlk albüm</h3><p><a href="https://open.spotify.com/album/64fqhXYk42auWLTZoku4cS">https://open.spotify.com/album/64fqhXYk42auWLTZoku4cS</a><br><a href="https://music.apple.com/zm/album/zerospan/6770610271">https://music.apple.com/zm/album/zerospan/6770610271</a><br><a href="https://soundcloud.com/zerospan-music">https://soundcloud.com/zerospan-music</a><br><a href="https://www.youtube.com/channel/UC063IOH2Mw7zKbk_zHBkKIQ">https://www.youtube.com/channel/UC063IOH2Mw7zKbk_zHBkKIQ</a></p>';
	global $wpdb;$wpdb->update($wpdb->posts,['post_content'=>$c],['ID'=>$p->ID]);clean_post_cache($p->ID);/* doğrudan: içerik filtrelerinden geçmesin */
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},42);
/* ZeroSpan basın fotoğrafı: indirilemezse sonraki isteklerde en fazla 3 kez yeniden denenir */
add_action('init',function(){
	$n=(int)get_option('yr_mig_v28_gorsel');if($n>=3||!get_option('yr_mig_v28')||!function_exists('yr_derleme_gorsel'))return;
	$p=get_page_by_path('groove-ile-progresif-death-metal-arasinda-ilk-album-zerospan',OBJECT,'post');if(!$p)return;
	if(has_post_thumbnail($p->ID)){update_option('yr_mig_v28_gorsel',3);return;}
	update_option('yr_mig_v28_gorsel',$n+1);
	if(yr_derleme_gorsel($p->ID,'https://raw.githubusercontent.com/yazarock/yazarock/main/derlemeler/gorseller/zerospan.jpg','ZeroSpan')){
		update_post_meta($p->ID,'_yr_gorsel_kredi','ZeroSpan (EPK)');update_option('yr_mig_v28_gorsel',3);
		if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
	}
},43);

/* Draconian: İstanbul (28 Kasım) ve Ankara (29 Kasım) konserleri iptal — taslak yazı güncellenip yayımlanır, takvimdeki iki etkinliğe "İptal" eklenir (mig v29) */
add_action('init',function(){
	if(get_option('yr_mig_v29'))return;
	$p=get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>'2026-11-01-draconian-istanbul-belirsizlik','numberposts'=>1]);if(!$p)return;
	update_option('yr_mig_v29',1);
	$c='<p>İsveçli gotik doom metal grubu <strong>Draconian</strong>\'ın Türkiye\'de vereceği iki konser de iptal edildi. Grup 28 Kasım\'da İstanbul\'da IF Performance Hall Beşiktaş\'ta, 29 Kasım\'da ise Ankara\'da Jolly Joker Ankara\'da sahneye çıkacaktı. Bilet satış platformunda iki gece de artık "iptal edildi" olarak görünüyor.</p>'
	.'<p>Draconian\'ın sosyal medya hesabından yaptığı açıklamaya göre karar grubun kendisine ait değil. Organizatör firma, Türkiye\'deki mevcut ekonomik koşulları gerekçe göstererek organizasyondan çekildi. Grup, iptalin düşük bilet satışlarından, lojistik sorunlardan ya da resmî bir yasaktan kaynaklanmadığının da altını çizdi.</p>'
	.'<p>Bilet satın alanlar, ücret iadesi için biletlerini aldıkları platformun iade sürecini takip etmeli. Draconian\'ın Ankara gecesinde açılışı Bulgaristan\'dan The A.X.E. Project yapacaktı.</p>'
	.'<p>Kasım ayında Türkiye\'de iptal ya da ertelenen tek konser bu değil; son haftalarda başka konserlerde de iptal, erteleme ve organizatör değişiklikleri yaşandı. Konser takvimimizi güncel tutmaya devam ediyoruz.</p>';
	if(function_exists('yr_derleme_kaynak_html'))$c.=yr_derleme_kaynak_html([
		['ad'=>'Milliyet Sanat – Kasım konserlerinde iptal, erteleme ve organizatör değişiklikleri','url'=>'https://www.milliyetsanat.com/haberler/diger/kasim-konserlerinde-iptal-erteleme-ve-organizator-degisiklikleri/83738'],
		['ad'=>'Draconian – resmî açıklama (Instagram)','url'=>'https://www.instagram.com/p/DegnMh1ikLE/'],
		['ad'=>'Kapak Magazin – Draconian İstanbul','url'=>'https://kapakmagazin.com/etkinlik-takvimi/draconian-istanbul/'],
	]);
	wp_update_post(['ID'=>$p[0]->ID,'post_title'=>'Draconian\'ın İstanbul ve Ankara konserleri iptal edildi','post_excerpt'=>'Draconian\'ın 28 Kasım İstanbul ve 29 Kasım Ankara konserleri, organizatörün ekonomik koşullar gerekçesiyle çekilmesi üzerine iptal edildi.','post_content'=>$c,'post_status'=>'publish']);
	foreach(['2026-10-29-draconian-if-besiktas-2811','2026-10-23-draconian-jolly-joker-ankara-2911'] as $eid){
		$e=get_posts(['post_type'=>'etkinlik','post_status'=>'any','meta_key'=>'_yr_derleme_id','meta_value'=>$eid,'numberposts'=>1]);if(!$e)continue;$e=$e[0];
		if(strpos($e->post_title,'İptal')===0)continue;
		wp_update_post(['ID'=>$e->ID,'post_title'=>'İptal edildi: '.$e->post_title,'post_content'=>'<p><strong>Bu konser iptal edildi.</strong> Organizatör ekonomik koşulları gerekçe göstererek organizasyondan çekildi. Bilet iadesi için biletin alındığı platformun iade sürecini takip edin. <a href="'.esc_url(get_permalink($p[0]->ID)).'">Haberin ayrıntıları</a></p>'.$e->post_content]);
	}
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},44);

/* Haftanın Keşfi: ZeroSpan + EPK yazısı; grubun kadrosu (mig v30) */
add_action('init',function(){
	if(get_option('yr_mig_v30'))return;
	$g=get_posts(['post_type'=>'grup','post_status'=>'publish','title'=>'ZeroSpan','numberposts'=>1]);if(!$g)return;
	$p=get_page_by_path('groove-ile-progresif-death-metal-arasinda-ilk-album-zerospan',OBJECT,'post');if(!$p||$p->post_status!=='publish')return;
	update_option('yr_mig_v30',1);
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_yr_kesif']) as $i)delete_post_meta($i,'_yr_kesif');
	update_post_meta($g[0]->ID,'_yr_kesif',time());
	update_post_meta($g[0]->ID,'_yr_kesif_yazi',$p->ID);
	if(!get_post_meta($g[0]->ID,'_yr_uyeler',true))update_post_meta($g[0]->ID,'_yr_uyeler',"Can Kurtulay – vokal\nYiğit Hiçyılmaz – gitar\nAtaberk Denge – gitar\nAtakan Germiyanoğlu – bas gitar\nMert Gürkan Demir – davul");
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},45);

/* Röportajlardaki ek hatalarını düzelt: "Kara Mesai'ı" → "Kara Mesai'yi", "Muğla'den" → "Muğla'dan", "türkçe rock" → "Türkçe rock" (mig v32) */
add_action('init',function(){
	if(get_option('yr_mig_v32'))return;update_option('yr_mig_v32',1);
	global $wpdb;
	foreach(get_posts(['post_type'=>'post','post_status'=>'any','numberposts'=>-1,'s'=>'Garajdan Sahneye']) as $p){
		if(strpos($p->post_title,'Garajdan Sahneye: ')!==0)continue;
		$g=html_entity_decode(trim(substr($p->post_title,strlen('Garajdan Sahneye: '))),ENT_QUOTES,'UTF-8');if($g==='')continue;
		$c=$p->post_content;$x=$p->post_excerpt;
		$c=preg_replace('/'.preg_quote($g,'/').'[\'’]ı dinlemek ve takip etmek/u',$g.yr_ek($g,'i').' dinlemek ve takip etmek',$c);
		$c=preg_replace('/(\s)türk(çe)? (rock|metal|punk)/u','$1Türk$2 $3',$c);
		if(preg_match('/^(.+?)[\'’]den (.+)$/us',$x,$m))$x=$m[1].yr_ek($m[1],'den').' '.$m[2];
		if($c!==$p->post_content||$x!==$p->post_excerpt){$wpdb->update($wpdb->posts,['post_content'=>$c,'post_excerpt'=>$x],['ID'=>$p->ID]);clean_post_cache($p->ID);}
	}
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},47);

/* Haftanın Keşfi: Kara Mesai + Garajdan Sahneye röportajı; grup başlığı "karamesai" → "Kara Mesai" (mig v36) */
add_action('init',function(){
	if(get_option('yr_mig_v36'))return;
	$g=get_page_by_path('karamesai',OBJECT,'grup');if(!$g||$g->post_status!=='publish')return;
	$p=get_page_by_path('garajdan-sahneye-kara-mesai',OBJECT,'post');
	update_option('yr_mig_v36',1);
	if($g->post_title==='karamesai')wp_update_post(['ID'=>$g->ID,'post_title'=>'Kara Mesai']);
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_yr_kesif']) as $i)delete_post_meta($i,'_yr_kesif');
	update_post_meta($g->ID,'_yr_kesif',time());
	if($p&&$p->post_status==='publish')update_post_meta($g->ID,'_yr_kesif_yazi',$p->ID);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},50);

/* Tiran röportajı: Instagram ekran görüntüsü yerine grubun kendi tanıtım fotoğrafı (Instagram, 12 Ekim 2026) — içerikteki ilk resim + öne çıkan görsel (mig v37, en fazla 3 deneme) */
add_action('init',function(){
	$n=(int)get_option('yr_mig_v37');if($n>=3)return;
	$p=get_page_by_path('garajdan-sahneye-tiran',OBJECT,'post');if(!$p)return;
	update_option('yr_mig_v37',$n+1);
	require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/image.php';
	$aid=media_sideload_image('https://raw.githubusercontent.com/yazarock/yazarock/main/derlemeler/gorseller/tiran.jpg',$p->ID,'Tiran','id');
	if(is_wp_error($aid)||!$aid)return;
	update_post_meta($aid,'_wp_attachment_image_alt','Tiran');
	$src=wp_get_attachment_image_src($aid,'large');$w=$src?$src[1]:1024;$h=$src?$src[2]:683;
	$blok='<!-- wp:image {"id":'.$aid.',"sizeSlug":"large","linkDestination":"none"} -->'."\n".'<figure class="wp-block-image size-large"><img src="'.esc_url($src?$src[0]:wp_get_attachment_url($aid)).'" alt="Tiran" class="wp-image-'.$aid.'" width="'.$w.'" height="'.$h.'"/></figure>'."\n".'<!-- /wp:image -->';
	$c=$p->post_content;
	$c2=preg_replace('#(<!--\s*wp:image[^>]*-->\s*)?<figure[^>]*>\s*<img[^>]*wp-image-\d+[^>]*>.*?</figure>(\s*<!--\s*/wp:image\s*-->)?#s',$blok,$c,1,$say);
	if(!$say)$c2=$blok."\n\n".$c;
	global $wpdb;$wpdb->update($wpdb->posts,['post_content'=>$c2],['ID'=>$p->ID]);clean_post_cache($p->ID);
	update_post_meta($p->ID,'_yr_one_icerikten',1);set_post_thumbnail($p->ID,$aid);
	update_post_meta($p->ID,'_yr_gorsel_kredi','Tiran (grubun resmi Instagram hesabı)');update_post_meta($p->ID,'_yr_gorsel_kaynak','https://www.instagram.com/tiranofficial_/p/DeR_wBtCv5X/');
	update_option('yr_mig_v37',3);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},51);
