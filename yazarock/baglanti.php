<?php
if(!defined('ABSPATH'))exit;
/*
 * İç bağlantılar ve SEO eklentileri (tema 1.26.0):
 * etkinlik sayfası bilgileri, yazıda geçen gruplar, haberlerin altında yerli grup önerisi,
 * etkinlik ve grup yapısal verisi, boş alt metinler
 */

/* ================= Grup adları (eşleştirme için, 12 saat önbellek) ================= */
function yr_grup_adlari(){
	$v=get_transient('yr_grup_adlari');if(is_array($v))return $v;
	$v=[];
	foreach(get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','no_found_rows'=>true]) as $id){
		$t=trim(html_entity_decode(wp_strip_all_tags(get_the_title($id)),ENT_QUOTES,'UTF-8'));
		if(mb_strlen($t)>=3)$v[(int)$id]=$t;
	}
	uasort($v,function($a,$b){return mb_strlen($b)-mb_strlen($a);});/* uzun adlar önce: "Black Sabbath" "Black"ten önce */
	set_transient('yr_grup_adlari',$v,12*HOUR_IN_SECONDS);
	return $v;
}
function yr_grup_onbellek_sil(){delete_transient('yr_grup_adlari');delete_transient('yr_yerli_havuz');}
add_action('save_post_grup','yr_grup_onbellek_sil');
add_action('save_post_post','yr_grup_onbellek_sil');
add_action('trashed_post','yr_grup_onbellek_sil');

/* Metinde adı geçen gruplar. Büyük/küçük harfe duyarlı ("duman" kelimesi Duman grubuyla eşleşmesin), kelime sınırıyla */
function yr_metindeki_gruplar($metin,$max=3,$haric=[]){
	$metin=' '.html_entity_decode(wp_strip_all_tags((string)$metin),ENT_QUOTES,'UTF-8').' ';$bul=[];$gor=[];
	foreach(yr_grup_adlari() as $id=>$ad){
		$an=mb_strtolower($ad);if(in_array($id,$haric,true)||isset($gor[$an]))continue;/* aynı adlı iki grup sayfası varsa biri yeter */
		if(preg_match('/(?<![\p{L}\p{N}])'.preg_quote($ad,'/').'(?![\p{L}\p{N}])/u',$metin)){$bul[]=$id;$gor[$an]=1;if(count($bul)>=$max)break;}
	}
	return $bul;
}

/* ================= Etkinlik sayfası: bilgiler, sahnedeki gruplar, aynı şehirdeki diğer etkinlikler ================= */
function yr_etkinlik_gruplari($id){return yr_metindeki_gruplar(get_the_title($id).' '.get_post_field('post_content',$id),2);}
function yr_etkinlik_ek($id){
	$d=(string)yr_meta('tarih',$id);$ts=$d?strtotime($d.' 12:00'):0;$sehir=trim((string)yr_meta('sehir',$id));$mekan=trim((string)yr_meta('mekan',$id));
	if($ts&&$d<current_time('Y-m-d'))echo '<p class="notice">Bu etkinlik geride kaldı.</p>';
	$sat=[];
	if($ts)$sat['Tarih']=date_i18n('j F Y, l',$ts);
	if(yr_meta('saat',$id))$sat['Saat']=yr_meta('saat',$id);
	if($sehir)$sat['Şehir']=$sehir;
	if($mekan)$sat['Mekân']=$mekan;
	$sat['Tür']=yr_meta('tur',$id)?yr_meta('tur',$id):'Etkinlik';
	echo '<h2>Etkinlik bilgileri</h2><ul class="ebilgi">';
	foreach($sat as $k=>$v)echo '<li><b>'.esc_html($k).':</b> '.esc_html($v).'</li>';
	echo '</ul>';
	$gr=yr_etkinlik_gruplari($id);
	if($gr){echo '<h2>Sahnede</h2>';foreach($gr as $g)yr_grup_kart($g,false,'Sahnedeki grup');}
	if($sehir){
		$q=new WP_Query(['post_type'=>'etkinlik','post_status'=>'publish','posts_per_page'=>5,'no_found_rows'=>true,'post__not_in'=>[$id],'meta_key'=>'_yr_tarih','orderby'=>'meta_value','order'=>'ASC','meta_query'=>[['key'=>'_yr_sehir','value'=>$sehir],['key'=>'_yr_tarih','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE']]]);
		if($q->have_posts()){
			echo '<h2>'.esc_html($sehir.yr_ek($sehir,'de')).' yaklaşan diğer etkinlikler</h2><ul class="ebilgi">';
			foreach($q->posts as $p){$t2=strtotime(get_post_meta($p->ID,'_yr_tarih',true).' 12:00');echo '<li><a href="'.esc_url(get_permalink($p)).'">'.esc_html(get_the_title($p)).'</a>'.($t2?' · '.esc_html(date_i18n('j F',$t2)):'').'</li>';}
			echo '</ul>';
		}
	}
}

/* ================= Yazıda geçen gruplar (sitedeki grup sayfalarına bağlantı) ================= */
function yr_yazidaki_gruplar($pid){
	$rg=(int)get_post_meta($pid,'_yr_roportaj_grup',true);
	$gr=yr_metindeki_gruplar(get_the_title($pid).' '.get_post_field('post_content',$pid),5,$rg?[$rg]:[]);
	if(!$gr)return;
	echo '<p class="tags yr-gecen"><span>Yazıda geçen gruplar:</span> ';
	foreach($gr as $g)echo '<a href="'.esc_url(get_permalink($g)).'">'.esc_html(get_the_title($g)).'</a> ';
	echo '</p>';
}

/* ================= Haberlerin altında yerli grup önerisi ================= */
/* Havuz: röportajı yapılmış, Haftanın Keşfi seçilmiş ya da başvurusuyla gelmiş Türkiye grupları; yoksa tüm Türkiye grupları */
function yr_yerli_havuz(){
	$v=get_transient('yr_yerli_havuz');if(is_array($v))return $v;
	global $wpdb;
	$ids=array_map('intval',$wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID=pm.post_id WHERE pm.meta_key='_yr_roportaj_grup' AND p.post_status='publish' AND p.post_type='post'"));
	$ids=array_merge($ids,get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','meta_query'=>['relation'=>'OR',['key'=>'_yr_kesif','compare'=>'EXISTS'],['key'=>'_yr_gb_kaynak','compare'=>'EXISTS']]]));
	$ids=array_values(array_unique(array_filter($ids,function($g){return get_post_status($g)==='publish'&&get_post_type($g)==='grup'&&get_post_meta($g,'_yr_bolge',true)==='tr';})));
	if(!$ids)$ids=array_map('intval',get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_yr_bolge','meta_value'=>'tr']));
	sort($ids);
	set_transient('yr_yerli_havuz',$ids,6*HOUR_IN_SECONDS);
	return $ids;
}
/* İlk kurulumda eklenen tanınmış gruplar (seed.json, seed2.json): "Yeraltından" kutusunda gösterilmez */
function yr_unlu_grup_adlari(){
	static $v=null;if($v!==null)return $v;$v=[];
	foreach(['seed.json','seed2.json'] as $f){
		$d=json_decode((string)@file_get_contents(get_template_directory().'/'.$f),true);
		foreach((array)($d['gruplar']??[]) as $g)if(!empty($g['ad']))$v[mb_strtolower(trim((string)$g['ad']))]=1;
	}
	return $v;
}
function yr_unlu_mu($g){return isset(yr_unlu_grup_adlari()[mb_strtolower(trim(html_entity_decode(get_the_title($g),ENT_QUOTES,'UTF-8')))]);}
function yr_yerli_oneriler($pid,$adet=6){
	if(yr_roportaj_mi($pid))return [];
	$gecen=yr_metindeki_gruplar(get_post_field('post_content',$pid),5);
	$h=array_values(array_diff(yr_yerli_havuz(),$gecen));
	/* Havuz azsa diğer Türkiye gruplarıyla tamamla */
	if(count($h)<$adet){
		$ek=get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>60,'fields'=>'ids','orderby'=>'date','order'=>'DESC','meta_key'=>'_yr_bolge','meta_value'=>'tr','post__not_in'=>array_merge($h,$gecen,[0])]);
		$h=array_merge($h,array_map('intval',$ek));
	}
	$h=array_values(array_filter(array_unique($h),function($g){return !yr_unlu_mu($g);}));
	if(!$h)return [];
	/* Yazının türüne uyan gruplar başa (ör. thrash haberine thrash grubu) */
	$metin=mb_strtolower(wp_strip_all_tags(get_the_title($pid).' '.get_post_field('post_content',$pid)));
	$uyan=[];$diger=[];
	foreach($h as $g){
		$ok=false;
		foreach(preg_split('/[^\p{L}]+/u',mb_strtolower((string)get_post_meta($g,'_yr_stil',true)),-1,PREG_SPLIT_NO_EMPTY) as $k){
			if(mb_strlen($k)<4||in_array($k,['metal','rock','müzik','grubu'],true))continue;
			if(mb_strpos($metin,$k)!==false){$ok=true;break;}
		}
		if($ok)$uyan[]=$g;else $diger[]=$g;
	}
	/* Her yazı farklı gruptan başlasın (aynı yazıda hep aynı sıra, önbellekle uyumlu) */
	$dondur=function($l)use($pid){$n=count($l);if($n<2)return $l;$o=$pid%$n;return array_merge(array_slice($l,$o),array_slice($l,0,$o));};
	return array_slice(array_merge($dondur($uyan),$dondur($diger)),0,$adet);
}
function yr_yerli_kutu($pid){
	$gl=yr_yerli_oneriler($pid);if(!$gl)return;
	echo '<div class="box yr-oneri"><h2>Yeraltından bir öneri</h2><div class="yr-donen" data-sure="3000" aria-live="off">';
	foreach($gl as $i=>$g){echo '<div class="yr-dk'.($i?'':' on').'"'.($i?' aria-hidden="true"':'').'>';yr_grup_kart($g,false,'Türkiye\'den bağımsız bir grup');echo '</div>';}
	echo '</div>';
	if(count($gl)>1){echo '<div class="yr-dn" role="group" aria-label="Öneriler">';foreach($gl as $i=>$g)echo '<button type="button" class="'.($i?'':'on').'" aria-label="'.esc_attr(($i+1).'. öneri: '.get_the_title($g)).'"></button>';echo '</div>';}
	echo '</div>';
}

/* ================= Yapısal veri: etkinlik ve grup ================= */
function yr_etkinlik_ld($p,$u,$d,$img){
	$tarih=(string)yr_meta('tarih',$p->ID);$saat=(string)yr_meta('saat',$p->ID);
	$j=['@context'=>'https://schema.org','@type'=>'Event','name'=>get_the_title($p),'startDate'=>$tarih.($saat?'T'.$saat:''),'eventStatus'=>'https://schema.org/EventScheduled','eventAttendanceMode'=>'https://schema.org/OfflineEventAttendanceMode',
		'location'=>['@type'=>'Place','name'=>(string)yr_meta('mekan',$p->ID),'address'=>['@type'=>'PostalAddress','addressLocality'=>(string)yr_meta('sehir',$p->ID)]],
		'image'=>[$img?$img:yr_varsayilan_gorsel()],'description'=>$d,'url'=>$u];
	$pf=[];foreach(yr_etkinlik_gruplari($p->ID) as $g)$pf[]=['@type'=>'MusicGroup','name'=>get_the_title($g),'url'=>get_permalink($g)];
	if($pf)$j['performer']=$pf;
	$b=(string)yr_meta('bilet',$p->ID);if($b)$j['offers']=['@type'=>'Offer','url'=>$b];
	return $j;
}
function yr_grup_ld($p,$u,$d,$img){
	$j=['@context'=>'https://schema.org','@type'=>'MusicGroup','name'=>get_the_title($p),'url'=>$u,'description'=>$d];
	if($img)$j['image']=$img;
	$st=trim((string)yr_meta('stil',$p->ID));if($st)$j['genre']=$st;
	$k=(string)yr_meta('kurulus',$p->ID);
	if(preg_match('/\b(1[89]\d\d|20\d\d)\b/',$k,$m))$j['foundingDate']=$m[1];
	$yer=trim((string)preg_replace('/[\s·,\-–]*\b(1[89]\d\d|20\d\d)\b.*$/u','',$k));if($yer!=='')$j['foundingLocation']=['@type'=>'Place','name'=>$yer];
	$uy=[];foreach(preg_split('/\r\n|\r|\n/',(string)yr_meta('uyeler',$p->ID)) as $s){$ad=trim((string)preg_replace('/\s*[–—\-:].*$/u','',$s));if($ad!==''&&mb_strlen($ad)<=60)$uy[]=['@type'=>'Person','name'=>$ad];}
	if($uy)$j['member']=array_slice($uy,0,12);
	$sa=array_values(array_filter([(string)yr_meta('link',$p->ID),(string)yr_meta('spotify',$p->ID),(string)yr_meta('youtube',$p->ID)]));
	if($sa)$j['sameAs']=$sa;
	return $j;
}

/* ================= Boş alt metinler: görselin bağlı olduğu yazının başlığı kullanılır ================= */
add_filter('wp_get_attachment_image_attributes',function($a,$att){
	if(isset($a['alt'])&&trim((string)$a['alt'])!=='')return $a;
	if(isset($a['class'])&&strpos((string)$a['class'],'avatar')!==false)return $a;
	$t='';
	if(in_the_loop()||is_singular())$t=get_the_title();
	if($t===''&&$att&&$att->post_parent)$t=get_the_title($att->post_parent);
	if($t===''&&$att)$t=get_the_title($att);
	$a['alt']=wp_strip_all_tags(html_entity_decode((string)$t,ENT_QUOTES,'UTF-8'));
	return $a;
},10,2);
add_filter('wp_content_img_tag',function($img){
	if(preg_match('/\balt=(["\'])\s*\1/',$img)||!preg_match('/\balt=/',$img)){
		$t=esc_attr(wp_strip_all_tags(html_entity_decode((string)get_the_title(),ENT_QUOTES,'UTF-8')));
		if($t==='')return $img;
		$img=preg_match('/\balt=/',$img)?preg_replace('/\balt=(["\'])\s*\1/','alt="'.$t.'"',$img,1):preg_replace('/^<img\b/','<img alt="'.$t.'"',$img,1);
	}
	return $img;
});
