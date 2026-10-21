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
