<?php
if(!defined('ABSPATH'))exit;

/* ================= Yardımcılar ================= */
function yr_upper($s){return mb_strtoupper(strtr((string)$s,['i'=>'İ','ı'=>'I']),'UTF-8');}
function yr_is_member(){$u=wp_get_current_user();return $u->exists()&&in_array('yazarock_uye',(array)$u->roles,true);}
function yr_flash($param,$map,$tag=''){
	if(empty($_GET[$param]))return;$k=sanitize_key($_GET[$param]);if(!isset($map[$k]))return;
	echo '<div class="notice '.esc_attr($map[$k][1]).'" role="status"'.($tag?' id="'.esc_attr($tag).'"':'').'>'.esc_html($map[$k][0]).'</div>';
}
function yr_parse_disco($t){
	$o=[];foreach(preg_split('/\R/u',(string)$t) as $l){$l=trim($l);if($l==='')continue;
		if(preg_match('/^(.*?)\s*\((\d{4})\)\s*$/u',$l,$m))$o[]=[trim($m[1]),$m[2]];else $o[]=[$l,''];}
	return $o;
}

/* ================= Kapak görseli (fotoğraf yoksa otomatik tasarım) ================= */
function yr_motif($k,$col){
	$f='fill="none" stroke="'.$col.'" stroke-linejoin="round" stroke-linecap="round"';
	switch($k){
	case 'metal':return '<path d="M520 10 L440 170 L490 176 L400 350" '.$f.' stroke-width="10" opacity=".5"/><path d="M590 60 L540 160 L575 164" '.$f.' stroke-width="5" opacity=".35"/><path d="M360 30 L330 120 L352 124" '.$f.' stroke-width="4" opacity=".3"/>';
	case 'gitar':return '<g transform="rotate(32 500 180)" '.$f.' opacity=".42" stroke-width="5"><path d="M470 210c-30 4-48 30-30 56 14 20 40 22 56 8 8 10 28 10 40-6 18-26 6-52-20-58-8-2-10-10-6-18l-6-78h-18l-4 78c-4 8-4 14-12 18z"/><circle cx="494" cy="246" r="12"/><path d="M482 40h34v26h-34zM482 76h34M482 100h34M482 124h34"/></g>';
	case 'davul':return '<g '.$f.' opacity=".42" stroke-width="5"><ellipse cx="500" cy="170" rx="95" ry="30"/><path d="M405 170v95c0 17 42 30 95 30s95-13 95-30v-95"/><path d="M430 190v90M470 197v95M530 197v95M570 190v90"/><path d="M400 40l90 100M610 30l-95 110"/></g>';
	case 'sahne':{$o='';foreach([[380,50],[470,20],[560,50],[620,20]] as $i=>$p)$o.='<path d="M'.$p[0].' 0L'.($p[0]-70+$i*18).' 300L'.($p[0]+50+$i*18).' 300z" fill="'.$col.'" opacity=".12"/>';
		for($i=0;$i<16;$i++)$o.='<circle cx="'.(360+$i*19).'" cy="'.(330+($i%3)*8).'" r="'.(14+($i%4)*3).'" fill="#000" opacity=".5"/>';return $o;}
	case 'mik':return '<g '.$f.' opacity=".45" stroke-width="5"><rect x="465" y="60" width="70" height="110" rx="35"/><path d="M465 100h70M465 125h70M440 150q0 70 60 70t60-70M500 220v70M460 295h80"/></g>';
	default:return '<g fill="none" stroke="'.$col.'" opacity=".35"><circle cx="500" cy="150" r="120" stroke-width="2"/><circle cx="500" cy="150" r="95" stroke-width="2"/><circle cx="500" cy="150" r="70" stroke-width="2"/><circle cx="500" cy="150" r="42" stroke-width="10"/></g><circle cx="500" cy="150" r="7" fill="'.$col.'" opacity=".6"/>';
	}
}
/* Başlık ve kategoriden konuya uygun motif seç */
function yr_motif_key($text){
	$t=mb_strtolower((string)$text,'UTF-8');
	foreach(['sahne'=>'konser|festival|sahne|turne|tur |canlı','davul'=>'davul|baterist','gitar'=>'gitar|riff|solo','mik'=>'röportaj|vokal|söyleşi|açıklama|sözcü','metal'=>'metal|thrash|death|black|doom|trash|hard rock|heavy|amon|slayer|megadeth|mayhem|opeth|sabaton'] as $k=>$re)if(preg_match('/'.$re.'/u',$t))return $k;
	return 'plak';
}
function yr_cover_svg($title,$slug='',$text=true,$key=''){
	static $n=0;$n++;
	$name=trim(preg_replace('/\s*\(.*?\)/u','',(string)$title));$h=crc32($name.$slug);
	$P=[['#5a1519','#e5393f'],['#1a2a44','#6aa8ff'],['#3d1a35','#d24a9a'],['#5a3a12','#f0b43c'],['#14382b','#35c58a'],['#2a2a48','#8f8fff']];
	$c=$P[$h%6];$metal=($slug==='metal');
	if($key==='')$key=$metal?'metal':'plak';
	$bars='';for($i=0;$i<12;$i++){$bh=30+(($h>>($i%16))&7)*18+($i%3)*10;$bars.='<rect x="'.(380+$i*20).'" y="'.(350-$bh).'" width="12" height="'.$bh.'" fill="'.$c[1].'" opacity=".16"/>';}
	$len=mb_strlen($name);$fs=$len>18?40:($len>12?52:68);
	$tx='';
	if($text){
		$txt=esc_html(yr_upper($name));
		$tx=$metal
			?'<text x="32" y="318" font-family="Impact,Oswald,sans-serif" font-weight="900" font-size="'.$fs.'" letter-spacing="3" fill="#f4eeee" stroke="'.$c[1].'" stroke-width="1.5" paint-order="stroke" style="filter:drop-shadow(0 0 8px '.$c[1].')">'.$txt.'</text>'
			:'<text x="32" y="316" font-family="Oswald,Impact,sans-serif" font-weight="700" font-size="'.$fs.'" fill="#fff">'.$txt.'</text>';
	}
	return '<svg class="cover" viewBox="0 0 640 360" preserveAspectRatio="xMidYMid slice" role="img" aria-label="'.esc_attr($name).'"><defs><linearGradient id="yrg'.$n.'" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="'.$c[0].'"/><stop offset="1" stop-color="#0d0809"/></linearGradient><linearGradient id="yrs'.$n.'" x1="0" y1="0" x2="0" y2="1"><stop offset=".45" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".7"/></linearGradient></defs><rect width="640" height="360" fill="url(#yrg'.$n.')"/>'.yr_motif($key,$c[1]).$bars.'<rect width="640" height="360" fill="url(#yrs'.$n.')"/>'.$tx.'</svg>';
}
/* Yazılar için kapak: öne çıkan görsel yoksa konuya uygun otomatik görsel */
/* Görsel hiçbir yerde kırpılmaz: kutuya tam sığar, boşluklar aynı görselin bulanık hâliyle dolar */
function yr_fit_img($id,$size,$alt=''){
	$aid=get_post_thumbnail_id($id);if(!$aid)return '';
	$bg=wp_get_attachment_image_url($aid,'thumbnail');
	return '<span class="yfit"><span class="yfit-bg" style="background-image:url(\''.esc_url($bg).'\')"></span>'.get_the_post_thumbnail($id,$size,['class'=>'cover yfit-img','loading'=>'lazy','alt'=>$alt]).'</span>';
}
function yr_post_cover($id=0,$size='medium_large'){
	$id=$id?$id:get_the_ID();
	if(has_post_thumbnail($id))return get_the_post_thumbnail($id,$size,['class'=>'cover','loading'=>'lazy','alt'=>'']);
	$cs=get_the_category($id);$cn=$cs?$cs[0]->name:'';
	return yr_cover_svg(get_the_title($id).' '.$cn,'',false,yr_motif_key(get_the_title($id).' '.$cn));
}
function yr_cover($id=0,$size='medium_large',$fit=false){/* $fit: yalnızca grup kartı ve grup sayfası, görsel kırpılmaz */
	$id=$id?$id:get_the_ID();
	if(has_post_thumbnail($id))return $fit?yr_fit_img($id,$size,get_the_title($id)):get_the_post_thumbnail($id,$size,['class'=>'cover','loading'=>'lazy','alt'=>get_the_title($id)]);
	$t=get_the_terms($id,'grup_tur');$sl=($t&&!is_wp_error($t))?$t[0]->slug:'';
	return yr_cover_svg(get_the_title($id),$sl);
}
function yr_credit(){if(!has_post_thumbnail()||!yr_meta('gorsel_kredi'))return;$k=esc_html(yr_meta('gorsel_kredi'));$u=yr_meta('gorsel_kaynak');echo '<p class="credit">Görsel: '.($u?'<a href="'.esc_url($u).'" rel="noopener nofollow" target="_blank">'.$k.'</a>':$k).'</p>';}

/* ================= Diskografi ================= */
function yr_disco($id){
	$d=yr_parse_disco(yr_meta('diskografi',$id));if(!$d)return;
	echo '<h2>Diskografi</h2><ul class="disco">';
	foreach($d as $x)echo '<li><span class="y">'.($x[1]?esc_html($x[1]):'—').'</span><b>'.esc_html($x[0]).'</b></li>';
	echo '</ul>';
}

/* ================= Haftanın albümü / grubu ================= */
function yr_week_key(){return (int)floor(current_time('timestamp')/604800);}
function yr_week_pick(){
	$k=yr_week_key();$c=get_transient('yr_week_'.$k);if($c!==false)return $c?$c:null;
	$ids=get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','orderby'=>'title','order'=>'ASC','meta_query'=>[['key'=>'_yr_diskografi','value'=>'','compare'=>'!=']]]);
	if(!$ids){set_transient('yr_week_'.$k,0,HOUR_IN_SECONDS);return null;}
	$id=0;foreach($ids as $i)if(get_post_meta($i,'_yr_sabit',true)==='1'){$id=$i;break;}
	if(!$id)$id=$ids[$k%count($ids)];
	$d=yr_parse_disco(get_post_meta($id,'_yr_diskografi',true));$r=['id'=>$id,'album'=>$d?$d[$k%count($d)]:null];
	set_transient('yr_week_'.$k,$r,WEEK_IN_SECONDS);return $r;
}
add_action('save_post_grup',function(){delete_transient('yr_week_'.yr_week_key());});
function yr_week_box(){
	$w=yr_week_pick();if(!$w)return;$id=$w['id'];
	echo '<div class="box wk"><h2>'.esc_html(yr_t('hafta_b')).'</h2><div class="wkb"><a class="wkc" href="'.esc_url(get_permalink($id)).'">'.yr_cover($id).'</a><div>'.(yr_t('hafta_pill')!==''?'<span class="pill">'.esc_html(yr_t('hafta_pill')).'</span>':'').'<h3>'.esc_html(get_the_title($id)).'</h3>';
	if($w['album'])echo '<p class="wka">'.esc_html($w['album'][0]).($w['album'][1]?' <span>('.esc_html($w['album'][1]).')</span>':'').'</p>';
	echo '<p>'.esc_html(wp_trim_words(get_the_excerpt($id),26)).'</p><a class="btn" href="'.esc_url(get_permalink($id)).'">'.esc_html(yr_t('hafta_btn')).'</a></div></div></div>';
}

/* ================= Anket ================= */
add_action('init',function(){
	register_post_type('anket',['labels'=>['name'=>'Anketler','singular_name'=>'Anket','add_new_item'=>'Yeni Anket Ekle','edit_item'=>'Anketi Düzenle','all_items'=>'Tüm Anketler'],'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-chart-bar','menu_position'=>9,'supports'=>['title'],'capability_type'=>'page','map_meta_cap'=>true]);
	register_post_type('liste',['labels'=>['name'=>'Okuyucu Listeleri','singular_name'=>'Liste','edit_item'=>'Listeyi Düzenle','all_items'=>'Tüm Listeler'],'public'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'liste'],'menu_icon'=>'dashicons-editor-ol','menu_position'=>10,'supports'=>['title','editor','author'],'capability_type'=>'page','map_meta_cap'=>true,'show_in_rest'=>false]);
});
add_filter('use_block_editor_for_post_type',function($u,$pt){return $pt==='liste'?false:$u;},10,2);
add_action('add_meta_boxes',function(){add_meta_box('yr_anket','Anket seçenekleri',function($p){
	wp_nonce_field('yr_anket_s','yr_an');$o=get_post_meta($p->ID,'_yr_secenekler',true);$a=get_post_meta($p->ID,'_yr_aktif',true);$v=get_post_meta($p->ID,'_yr_oylar',true);
	echo '<p>Her satıra bir seçenek yaz (en az 2, en fazla 10). Ankete oy verildikten sonra seçenekleri değiştirmemeni öneririm.</p><textarea name="yr_secenekler" rows="8" class="large-text">'.esc_textarea((string)$o).'</textarea>';
	echo '<p><label><input type="checkbox" name="yr_aktif" value="1"'.checked($a==='1'||$a==='',true,false).'> Oylamaya açık</label></p>';
	if(is_array($v)&&$v){echo '<p><b>Mevcut oylar:</b> ';foreach(yr_poll_opts($p->ID) as $i=>$t)echo esc_html($t).': '.(int)($v[$i]??0).' · ';echo '</p>';}
},'anket','normal','high');});
add_action('save_post_anket',function($id){
	if(!isset($_POST['yr_an'])||!wp_verify_nonce($_POST['yr_an'],'yr_anket_s')||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id))return;
	update_post_meta($id,'_yr_secenekler',sanitize_textarea_field(wp_unslash($_POST['yr_secenekler']??'')));
	update_post_meta($id,'_yr_aktif',empty($_POST['yr_aktif'])?'0':'1');
});
function yr_poll_opts($id){$o=[];foreach(preg_split('/\R/u',(string)get_post_meta($id,'_yr_secenekler',true)) as $l){$l=trim($l);if($l!=='')$o[]=$l;}return array_slice($o,0,10);}
function yr_poll_box(){
	$p=get_posts(['post_type'=>'anket','post_status'=>'publish','numberposts'=>1,'orderby'=>'date','order'=>'DESC']);if(!$p)return;$p=$p[0];$id=$p->ID;
	$o=yr_poll_opts($id);if(count($o)<2)return;
	$open=get_post_meta($id,'_yr_aktif',true)!=='0';$v=get_post_meta($id,'_yr_oylar',true);if(!is_array($v))$v=[];
	$voted=isset($_COOKIE['yr_oy_'.$id])||in_array($_GET['yr_a']??'',['tamam','zaten'],true);
	$show=$voted||!$open||isset($_GET['sonuc']);
	echo '<div class="box" id="anket"><h2>'.esc_html(yr_t('anket_b')).'</h2>';
	yr_flash('yr_a',['tamam'=>['Oyun kaydedildi, teşekkürler.','ok'],'zaten'=>['Bu ankete zaten oy verdin.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenile.','err'],'kapali'=>['Bu anket kapalı.','err'],'gecersiz'=>['Lütfen bir seçenek seç.','err']]);
	echo '<p class="kq" style="font-size:19px">'.esc_html(get_the_title($id)).'</p>';
	if($show){
		$t=0;foreach($o as $i=>$_){$t+=(int)($v[$i]??0);}
		foreach($o as $i=>$x){$n=(int)($v[$i]??0);$pc=$t?round($n*100/$t):0;echo '<div class="pr"><div class="pl"><span>'.esc_html($x).'</span><b>'.$pc.'%</b></div><div class="pb"><i style="width:'.$pc.'%"></i></div></div>';}
		echo '<small style="color:var(--mute)">Toplam '.(int)$t.' oy'.(!$open?' · Anket kapandı':'').'</small>';
	}else{
		echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="yr_oy"><input type="hidden" name="aid" value="'.(int)$id.'">';wp_nonce_field('yr_oy','yr_n');
		echo '<input class="hp" tabindex="-1" autocomplete="off" name="website">';
		foreach($o as $i=>$x)echo '<label class="chk"><input type="radio" name="sec" value="'.$i.'" required> <span>'.esc_html($x).'</span></label>';
		yr_ts_widget();echo '<div class="row"><button class="btn" type="submit">Oy Ver</button><a class="btn alt" href="'.esc_url(add_query_arg('sonuc','1').'#anket').'">Sonuçlar</a></div></form>';
	}
	echo '</div>';
}
add_action('admin_post_nopriv_yr_oy','yr_oy');add_action('admin_post_yr_oy','yr_oy');
function yr_oy(){
	$id=(int)($_POST['aid']??0);$ref=remove_query_arg(['yr_a','sonuc'],wp_get_referer()?wp_get_referer():home_url('/'));
	$go=function($s)use($ref){wp_safe_redirect(add_query_arg('yr_a',$s,$ref).'#anket');exit;};
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_oy'))$go('guvenlik');
	if(!empty($_POST['website']))$go('tamam');
	if(!yr_ts_ok())$go('guvenlik');
	$p=get_post($id);if(!$p||$p->post_type!=='anket'||$p->post_status!=='publish'||get_post_meta($id,'_yr_aktif',true)==='0')$go('kapali');
	$o=yr_poll_opts($id);$i=isset($_POST['sec'])?(int)$_POST['sec']:-1;if(!isset($o[$i]))$go('gecersiz');
	$ck='yr_oy_'.$id;$tk='yr_oyk_'.md5(yr_ip().'|'.$id);
	if(isset($_COOKIE[$ck])||get_transient($tk))$go('zaten');
	$v=get_post_meta($id,'_yr_oylar',true);if(!is_array($v))$v=[];$v[$i]=(int)($v[$i]??0)+1;update_post_meta($id,'_yr_oylar',$v);
	set_transient($tk,1,HOUR_IN_SECONDS);
	setcookie($ck,'1',['expires'=>time()+30*DAY_IN_SECONDS,'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Lax']);
	$go('tamam');
}

/* ================= Spotify / YouTube / Harita (tıklayınca yüklenir) ================= */
function yr_embed_info($u){
	$u=trim((string)$u);if($u==='')return null;$p=wp_parse_url($u);if(empty($p['host']))return null;
	$h=strtolower(preg_replace('/^www\./','',$p['host']));$path=$p['path']??'';parse_str($p['query']??'',$q);
	if($h==='open.spotify.com'&&preg_match('#^/(?:intl-[a-z]{2}/)?(artist|album|playlist|track|show|episode)/([A-Za-z0-9]{10,30})#',$path,$m))
		return ['Spotify','https://open.spotify.com/embed/'.$m[1].'/'.$m[2],in_array($m[1],['track','episode'],true)?152:352];
	$id='';$list=!empty($q['list'])?preg_replace('/[^A-Za-z0-9_-]/','',(string)$q['list']):'';
	if($h==='youtu.be'&&preg_match('#^/([A-Za-z0-9_-]{6,20})#',$path,$m))$id=$m[1];
	elseif(in_array($h,['youtube.com','m.youtube.com','music.youtube.com'],true)){
		if($path==='/watch'&&!empty($q['v']))$id=preg_replace('/[^A-Za-z0-9_-]/','',(string)$q['v']);
		elseif(preg_match('#^/(?:embed|shorts|live)/([A-Za-z0-9_-]{6,20})#',$path,$m))$id=$m[1];
		elseif($path==='/playlist'&&$list!=='')return ['YouTube','https://www.youtube-nocookie.com/embed/videoseries?list='.$list,0];
	}
	if($id!=='')return ['YouTube','https://www.youtube-nocookie.com/embed/'.$id.($list?'?list='.$list:''),0];
	return null;
}
function yr_embed_box($u){
	$i=yr_embed_info($u);if(!$i)return '';
	return '<div class="emb'.($i[2]?'':' vid').'" data-src="'.esc_url($i[1]).'"'.($i[2]?' data-h="'.(int)$i[2].'"':'').' data-t="'.esc_attr($i[0]).'"><button class="btn alt" type="button">▶ '.esc_html($i[0]).' içeriğini yükle</button><small>Yüklediğinde '.esc_html($i[0]).' çerez ve ziyaretçi verisi işleyebilir.</small></div>';
}
add_filter('embed_oembed_html',function($h,$url){$b=yr_embed_box($url);return $b?$b:$h;},10,2);
function yr_embeds($id){
	$out='';foreach(['spotify','youtube'] as $k)$out.=yr_embed_box(yr_meta($k,$id));
	if($out!=='')echo '<h2>Dinle &amp; İzle</h2><div class="embs">'.$out.'</div>';
}
function yr_map($id){
	$q=yr_meta('harita',$id);if(!$q)$q=implode(', ',array_filter([yr_meta('mekan',$id),yr_meta('sehir',$id)]));if(!$q)return;
	echo '<h2>Konum</h2><p>'.esc_html($q).'</p><div class="emb" data-src="'.esc_url('https://maps.google.com/maps?q='.rawurlencode($q).'&output=embed').'" data-h="320" data-t="Google Haritalar"><button class="btn alt" type="button">▶ Haritayı yükle</button><small>Yüklediğinde Google çerez ve ziyaretçi verisi işleyebilir.</small></div><p><a class="more" href="'.esc_url('https://www.google.com/maps/search/?api=1&query='.rawurlencode($q)).'" target="_blank" rel="noopener">Google Haritalar\'da aç →</a></p>';
}

/* ================= Takvim görünümü ================= */
function yr_cal($ym){
	if(!preg_match('/^(\d{4})-(\d{2})$/',$ym,$mm)||(int)$mm[2]<1||(int)$mm[2]>12||(int)$mm[1]<2000||(int)$mm[1]>2100){$mm=[0,current_time('Y'),current_time('m')];}
	$y=(int)$mm[1];$m=(int)$mm[2];$first=gmmktime(0,0,0,$m,1,$y);$days=(int)gmdate('t',$first);$off=(int)gmdate('N',$first)-1;
	$MN=['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
	$q=new WP_Query(['post_type'=>'etkinlik','posts_per_page'=>-1,'no_found_rows'=>true,'meta_key'=>'_yr_tarih','orderby'=>'meta_value','order'=>'ASC','meta_query'=>[['key'=>'_yr_tarih','value'=>[sprintf('%04d-%02d-01',$y,$m),sprintf('%04d-%02d-%02d',$y,$m,$days)],'compare'=>'BETWEEN','type'=>'DATE']]]);
	$by=[];foreach($q->posts as $p){$d=(int)substr((string)get_post_meta($p->ID,'_yr_tarih',true),8,2);$by[$d][]=$p;}
	$base=get_post_type_archive_link('etkinlik');
	$pv=gmdate('Y-m',gmmktime(0,0,0,$m-1,1,$y));$nx=gmdate('Y-m',gmmktime(0,0,0,$m+1,1,$y));
	$today=current_time('Y-m-d');
	echo '<div class="calh"><a class="btn alt" href="'.esc_url(add_query_arg(['gorunum'=>'takvim','ay'=>$pv],$base)).'" aria-label="Önceki ay">←</a><h2>'.esc_html($MN[$m-1].' '.$y).'</h2><a class="btn alt" href="'.esc_url(add_query_arg(['gorunum'=>'takvim','ay'=>$nx],$base)).'" aria-label="Sonraki ay">→</a></div><div class="cal">';
	foreach(['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $d)echo '<div class="dh">'.$d.'</div>';
	for($i=0;$i<$off;$i++)echo '<div class="d emp"></div>';
	for($d=1;$d<=$days;$d++){
		$iso=sprintf('%04d-%02d-%02d',$y,$m,$d);
		echo '<div class="d'.(!empty($by[$d])?' has':'').($iso===$today?' today':'').'"><span class="n">'.$d.' <small>'.esc_html($MN[$m-1]).'</small></span>';
		if(!empty($by[$d]))foreach($by[$d] as $p)echo '<a class="ce" href="'.esc_url(get_permalink($p)).'">'.esc_html(get_the_title($p)).'</a>';
		echo '</div>';
	}
	echo '</div>';if(!$q->posts)echo '<p class="note" style="margin-top:12px">Bu ay için kayıtlı etkinlik yok.</p>';
}

/* ================= Yazar profilleri ================= */
function yr_user_fields($u){
	echo '<h2>Yazarock profili</h2><table class="form-table">';
	foreach(['yr_twitter'=>'X (Twitter) adresi','yr_instagram'=>'Instagram adresi','yr_web'=>'Web sitesi','yr_foto'=>'Profil fotoğrafı (Ortam Kitaplığı\'na yüklediğin görselin adresi)'] as $k=>$l)
		echo '<tr><th><label for="'.esc_attr($k).'">'.esc_html($l).'</label></th><td><input type="url" class="regular-text" id="'.esc_attr($k).'" name="'.esc_attr($k).'" value="'.esc_attr(get_user_meta($u->ID,$k,true)).'"></td></tr>';
	echo '</table>';
}
add_action('show_user_profile','yr_user_fields');add_action('edit_user_profile','yr_user_fields');
function yr_user_save($id){
	if(!current_user_can('edit_user',$id))return;
	foreach(['yr_twitter','yr_instagram','yr_web'] as $k)update_user_meta($id,$k,esc_url_raw(wp_unslash($_POST[$k]??'')));
	$f=esc_url_raw(wp_unslash($_POST['yr_foto']??''));
	update_user_meta($id,'yr_foto',($f&&(strpos($f,content_url())===0||strpos($f,home_url('/'))===0))?$f:'');
}
add_action('personal_options_update','yr_user_save');add_action('edit_user_profile_update','yr_user_save');
add_filter('pre_get_avatar_data',function($a,$ie){
	$u=null;
	if($ie instanceof WP_User)$u=$ie;elseif(is_numeric($ie))$u=get_user_by('id',(int)$ie);elseif(is_string($ie)&&is_email($ie))$u=get_user_by('email',$ie);
	elseif($ie instanceof WP_Post)$u=get_user_by('id',(int)$ie->post_author);elseif($ie instanceof WP_Comment&&$ie->user_id)$u=get_user_by('id',(int)$ie->user_id);
	if($u){$f=get_user_meta($u->ID,'yr_foto',true);if($f){$a['url']=$f;$a['found_avatar']=true;}}
	return $a;
},10,2);
add_action('template_redirect',function(){
	if(is_author()){$u=get_queried_object();if($u&&in_array('yazarock_uye',(array)$u->roles,true)){global $wp_query;$wp_query->set_404();status_header(404);nocache_headers();}}
},2);

/* ================= Üyelik ve okuyucu listeleri ================= */
add_action('init',function(){if(!get_role('yazarock_uye'))add_role('yazarock_uye','Yazarock Üyesi',['read'=>true]);});
add_filter('show_admin_bar',function($s){return yr_is_member()?false:$s;});
add_action('admin_init',function(){if(yr_is_member()&&!wp_doing_ajax()&&!(defined('DOING_CRON')&&DOING_CRON)&&basename($_SERVER['PHP_SELF']??'')!=='admin-post.php'){wp_safe_redirect(yr_panel_url());exit;}});
add_filter('login_redirect',function($to,$req,$u){if($u&&!is_wp_error($u)&&in_array('yazarock_uye',(array)$u->roles,true))return ($req&&strpos($req,admin_url())!==0&&untrailingslashit($req)!==untrailingslashit(home_url()))?$to:yr_panel_url();return $to;},20,3);

add_action('admin_post_nopriv_yr_uye','yr_uye');add_action('admin_post_yr_uye','yr_uye');
function yr_uye(){
	$go=function($s){wp_safe_redirect(add_query_arg('yr_u',$s,yr_page_url('uye-ol')).'#uye');exit;};
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_uye'))$go('guvenlik');
	if(!empty($_POST['website']))$go('tamam');
	if(!yr_ts_ok())$go('guvenlik');
	$k='yr_rl_u_'.md5(yr_ip());if(get_transient($k))$go('bekle');set_transient($k,1,30);
	$ad=sanitize_text_field(wp_unslash($_POST['ad']??''));$m=sanitize_email(wp_unslash($_POST['email']??''));
	if(mb_strlen($ad)<2||mb_strlen($ad)>40||!yr_eposta_ok($m)||empty($_POST['kvkk']))$go('gecersiz');
	if(email_exists($m))$go('tamam');
	$b=sanitize_user(strstr($m,'@',true),true);if(strlen($b)<3)$b='uye';$l=$b;$i=1;while(username_exists($l))$l=$b.(++$i);
	$uid=wp_insert_user(['user_login'=>$l,'user_email'=>$m,'user_pass'=>wp_generate_password(24),'display_name'=>$ad,'nickname'=>$ad,'user_nicename'=>'uye-'.strtolower(wp_generate_password(10,false,false)),'role'=>'yazarock_uye']);
	if(is_wp_error($uid))$go('gecersiz');
	update_user_meta($uid,'_yr_kvkk',current_time('mysql'));
	wp_new_user_notification($uid,null,'user');$go('tamam');
}
add_action('admin_post_yr_liste','yr_liste');
function yr_liste(){
	$back=yr_page_url('listelerim');$go=function($s)use($back){wp_safe_redirect(add_query_arg('yr_l',$s,$back).'#liste');exit;};
	if(!is_user_logged_in())$go('giris');
	if(!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_liste'))$go('guvenlik');
	if(!yr_ts_ok())$go('guvenlik');
	$u=get_current_user_id();
	if(function_exists('yr_onayli')&&!yr_onayli($u))$go('onay');
	if(count(get_posts(['post_type'=>'liste','post_status'=>['publish','pending'],'author'=>$u,'numberposts'=>11,'fields'=>'ids']))>=10)$go('limit');
	$k='yr_rl_l_'.$u;if(get_transient($k))$go('bekle');set_transient($k,1,20);
	$t=mb_substr(sanitize_text_field(wp_unslash($_POST['baslik']??'')),0,80);$it=[];
	foreach((array)($_POST['m']??[]) as $x){$x=mb_substr(sanitize_text_field(wp_unslash($x)),0,100);if($x!=='')$it[]=$x;}
	$it=array_slice($it,0,10);
	if(mb_strlen($t)<3||count($it)<3)$go('gecersiz');
	wp_insert_post(['post_type'=>'liste','post_status'=>'pending','post_title'=>$t,'post_content'=>implode("\n",$it),'post_author'=>$u]);
	$go('tamam');
}
add_action('admin_post_yr_liste_sil','yr_liste_sil');
function yr_liste_sil(){
	$id=(int)($_POST['id']??0);$back=yr_page_url('listelerim');
	if(!is_user_logged_in()||!isset($_POST['yr_n'])||!wp_verify_nonce($_POST['yr_n'],'yr_liste_sil'.$id)){wp_safe_redirect($back);exit;}
	$p=get_post($id);if($p&&$p->post_type==='liste'&&(int)$p->post_author===get_current_user_id())wp_delete_post($id,true);
	wp_safe_redirect(add_query_arg('yr_l','silindi',$back).'#liste');exit;
}
function yr_list_items($p){$o=[];foreach(preg_split('/\R/u',wp_strip_all_tags($p->post_content)) as $l){$l=trim($l);if($l!=='')$o[]=$l;}return $o;}

/* ================= Cloudflare Turnstile ================= */
add_action('customize_register',function($c){
	$c->add_section('yr_ek',['title'=>'Yazarock Ek Özellikler','priority'=>31]);
	foreach(['hafta'=>'Haftanın Albümü','anket'=>'Anket'] as $k=>$l){
		$c->add_setting('yr_s_'.$k,['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
		$c->add_control('yr_s_'.$k,['label'=>$l.' kutusunu ana sayfada göster','section'=>'yr_ek','type'=>'checkbox']);
	}
	$c->add_setting('yr_ts_site',['default'=>'','sanitize_callback'=>'sanitize_text_field']);
	$c->add_control('yr_ts_site',['label'=>'Turnstile site anahtarı','description'=>'Cloudflare panelinden ücretsiz alınır. İkisini birlikte doldur; boş bırakırsan bot koruması kapalı olur.','section'=>'yr_ek','type'=>'text']);
	$c->add_setting('yr_ts_secret',['default'=>'','sanitize_callback'=>'sanitize_text_field']);
	$c->add_control('yr_ts_secret',['label'=>'Turnstile gizli anahtarı','section'=>'yr_ek','type'=>'password','input_attrs'=>['autocomplete'=>'new-password']]);
});
function yr_ts_widget(){
	$k=get_theme_mod('yr_ts_site','');if(!$k||!get_theme_mod('yr_ts_secret',''))return;
	global $yr_ts_used;$yr_ts_used=true;echo '<div class="cf-turnstile" data-sitekey="'.esc_attr($k).'" data-theme="dark" data-language="tr"></div>';
}
add_action('wp_footer',function(){global $yr_ts_used;if($yr_ts_used)echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';});
function yr_ts_ok(){
	$s=get_theme_mod('yr_ts_secret','');if(!$s||!get_theme_mod('yr_ts_site',''))return true;
	$t=sanitize_text_field(wp_unslash($_POST['cf-turnstile-response']??''));if($t==='')return false;
	$r=wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify',['timeout'=>8,'body'=>['secret'=>$s,'response'=>$t]]);
	if(is_wp_error($r))return false;$d=json_decode(wp_remote_retrieve_body($r),true);return !empty($d['success']);
}
add_filter('comment_form_submit_field',function($f){if(is_user_logged_in())return $f;ob_start();yr_ts_widget();return ob_get_clean().$f;});
add_action('pre_comment_on_post',function(){if(!is_user_logged_in()&&!yr_ts_ok())wp_die('Güvenlik doğrulaması başarısız. Geri dönüp tekrar dene.','Güvenlik',['back_link'=>true]);});

/* ================= PWA ================= */
function yr_icon($f){return get_template_directory_uri().'/icons/'.$f;}
add_action('wp_head',function(){
	echo '<link rel="manifest" href="'.esc_url(home_url('/manifest.webmanifest')).'"><meta name="theme-color" content="#0d0809"><meta name="mobile-web-app-capable" content="yes"><link rel="apple-touch-icon" href="'.esc_url(yr_icon('icon-192.png')).'">';
	if(!has_site_icon())echo '<link rel="icon" type="image/png" href="'.esc_url(yr_icon('icon-192.png')).'">';
},3);
add_action('wp_footer',function(){echo '<script>window.YR_SW='.wp_json_encode(home_url('/yr-sw.js')).';</script>';},1);
add_action('template_redirect',function(){
	$p=wp_parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);$base=untrailingslashit((string)wp_parse_url(home_url('/'),PHP_URL_PATH));
	if($p===$base.'/manifest.webmanifest'){
		nocache_headers();header('Content-Type: application/manifest+json; charset=utf-8');
		echo wp_json_encode(['name'=>'Yazarock','short_name'=>'Yazarock','description'=>'Rock ve metal müzik haberleri','lang'=>'tr','start_url'=>$base.'/','scope'=>$base.'/','display'=>'standalone','background_color'=>'#0d0809','theme_color'=>'#0d0809','icons'=>[['src'=>yr_icon('icon-192.png'),'sizes'=>'192x192','type'=>'image/png'],['src'=>yr_icon('icon-512.png'),'sizes'=>'512x512','type'=>'image/png'],['src'=>yr_icon('icon-maskable-512.png'),'sizes'=>'512x512','type'=>'image/png','purpose'=>'maskable']]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
	}
	if($p===$base.'/yr-sw.js'){
		header('Content-Type: application/javascript; charset=utf-8');header('Service-Worker-Allowed: '.$base.'/');header('Cache-Control: no-cache');
		ob_start();
		echo <<<'JS'
const V='yr-__VER__';
const OFF='<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Çevrimdışı</title><body style="background:#0d0809;color:#ece6e6;font-family:sans-serif;text-align:center;padding:20vh 20px"><h1>Bağlantı yok</h1><p>İnternet bağlantını kontrol edip tekrar dene.</p><button onclick="location.reload()">Yenile</button>';
self.addEventListener('install',function(){self.skipWaiting()});
self.addEventListener('activate',function(e){e.waitUntil(caches.keys().then(function(k){return Promise.all(k.filter(function(x){return x!==V}).map(function(x){return caches.delete(x)}))}).then(function(){return self.clients.claim()}))});
self.addEventListener('fetch',function(e){
 var r=e.request;if(r.method!=='GET')return;var u=new URL(r.url);if(u.origin!==location.origin)return;
 if(/wp-admin|wp-login|wp-json|preview=/.test(u.pathname+u.search))return;
 if(r.mode==='navigate'){e.respondWith(fetch(r).catch(function(){return new Response(OFF,{headers:{'Content-Type':'text/html; charset=utf-8'}})}));return}
 if(/\.(css|js)$/.test(u.pathname)){e.respondWith(fetch(r).then(function(x){if(x.ok){var y=x.clone();caches.open(V).then(function(c){c.put(r,y)})}return x}).catch(function(){return caches.match(r)}));return}
 if(/\.(png|jpg|jpeg|webp|svg|woff2?)$/.test(u.pathname)){e.respondWith(caches.open(V).then(function(c){return c.match(r).then(function(h){var n=fetch(r).then(function(x){if(x.ok)c.put(r,x.clone());return x}).catch(function(){return h});return h||n})}))}
});
JS;
		echo str_replace('__VER__',YR_VER,ob_get_clean());
		exit;
	}
},0);

/* ================= Tek seferlik geçiş (tema güncellenince panele ilk girişte) ================= */
add_action('admin_init',function(){
	if(get_option('yr_mig_v3')||!current_user_can('manage_options'))return;
	update_option('yr_mig_v3',1);
	/* Diskografi: eski içerikteki albüm listesini alana taşı */
	foreach(get_posts(['post_type'=>'grup','post_status'=>'any','numberposts'=>-1]) as $g){
		if(get_post_meta($g->ID,'_yr_diskografi',true))continue;
		if(preg_match('#<h2>Önemli Albümler</h2>\s*<ul>(.*?)</ul>#us',$g->post_content,$m)){
			preg_match_all('#<li>(.*?)</li>#us',$m[1],$li);$lines=array_map(function($x){return trim(html_entity_decode(wp_strip_all_tags($x),ENT_QUOTES,'UTF-8'));},$li[1]);
			update_post_meta($g->ID,'_yr_diskografi',implode("\n",array_filter($lines)));
			wp_update_post(['ID'=>$g->ID,'post_content'=>wp_slash(trim(str_replace($m[0],'',$g->post_content)))]);
		}
	}
	/* Sayfalar */
	$pg=[['Üye Ol','uye-ol'],['Listelerim','listelerim'],['Okuyucu Listeleri','listeler']];
	foreach($pg as $x)yr_page($x[0],$x[1]);
	/* İlk anket */
	if(!get_posts(['post_type'=>'anket','post_status'=>'any','numberposts'=>1,'fields'=>'ids'])){
		wp_insert_post(['post_type'=>'anket','post_status'=>'publish','post_title'=>'En iyi Türkçe rock albümü hangisi?','meta_input'=>['_yr_secenekler'=>"Duman – Belki Alışman Lazım\nMor ve Ötesi – Dünya Yalan Söylüyor\nmaNga – Şehr-i Hüzün\nPentagram – Anatolia\nMoğollar – Anadolu Pop",'_yr_aktif'=>'1']]);
	}
	/* Menü: Listeler */
	$m=wp_get_nav_menu_object('Ana Menü');
	if($m){$has=false;foreach((array)wp_get_nav_menu_items($m->term_id) as $it)if($it->title==='Listeler')$has=true;
		if(!$has)wp_update_nav_menu_item($m->term_id,0,['menu-item-title'=>'Listeler','menu-item-url'=>yr_page_url('listeler'),'menu-item-type'=>'custom','menu-item-status'=>'publish']);}
	/* Gizlilik metinleri: üyelik, anket, harici içerik ve Turnstile bilgisi eklenir */
	$k=get_page_by_path('kvkk-aydinlatma-metni',OBJECT,'page');
	if($k&&strpos($k->post_content,'Üyelik ve Okuyucu Listeleri')===false)wp_update_post(['ID'=>$k->ID,'post_content'=>wp_slash($k->post_content.'<h2>Üyelik ve Okuyucu Listeleri</h2><p>Üye olursanız ad (veya takma ad), e-posta adresi ve oluşturduğunuz listeler işlenir. Listeleriniz editör onayından sonra adınızla birlikte yayımlanır. Üyeliğinizin ve listelerinizin silinmesini info@yazarock.com adresinden isteyebilirsiniz.</p><h2>Anketler</h2><p>Ankete oy verdiğinizde, aynı kişinin tekrar oy vermesini engellemek için tarayıcınızda bir çerez saklanır. Oy verileriniz kimliğinizle ilişkilendirilmez.</p>')]);
	$gz=get_page_by_path('gizlilik-politikasi',OBJECT,'page');
	if($gz&&strpos($gz->post_content,'Harici İçerikler')===false)wp_update_post(['ID'=>$gz->ID,'post_content'=>wp_slash($gz->post_content.'<h2>Harici İçerikler</h2><p>Spotify, YouTube ve Google Haritalar içerikleri siz "yükle" düğmesine basana kadar yüklenmez. Yüklediğinizde ilgili hizmet çerez ve ziyaretçi verisi işleyebilir.</p><h2>Bot Koruması</h2><p>Formlarda, istenmeyen otomatik gönderimleri engellemek için Cloudflare Turnstile kullanılabilir. Etkinse, form sayfasında Cloudflare ile IP adresiniz ve tarayıcı bilgileriniz paylaşılır.</p>')]);
	flush_rewrite_rules();
});

/* ================= v1.2 geçişi: yeni içerik (seed2.json), vitrin ve çerez notu ================= */
add_action('admin_init',function(){
	if(get_option('yr_mig_v4')||!current_user_can('manage_options'))return;
	$f=get_template_directory().'/seed2.json';if(!is_readable($f))return;
	$d=json_decode(file_get_contents($f),true);if(!is_array($d))return;
	update_option('yr_mig_v4',1);
	$uid=get_current_user_id();
	foreach(['Haber','Metal Rehberi'] as $t)if(!term_exists($t,'category'))wp_insert_term($t,'category');
	foreach(['Rock','Metal'] as $t)if(!term_exists($t,'grup_tur'))wp_insert_term($t,'grup_tur');
	foreach(($d['yazilar']??[]) as $p){
		if(get_page_by_path($p['slug'],OBJECT,'post'))continue;
		$c=get_term_by('slug',sanitize_title($p['kategori']),'category');
		wp_insert_post(wp_slash(['post_type'=>'post','post_status'=>'publish','post_title'=>$p['baslik'],'post_name'=>$p['slug'],'post_excerpt'=>$p['ozet'],'post_content'=>$p['icerik'],'post_author'=>$uid,'post_date'=>$p['tarih'],'post_category'=>$c?[$c->term_id]:[]]));
	}
	foreach(($d['gruplar']??[]) as $g){
		if(get_page_by_path($g['slug'],OBJECT,'grup'))continue;
		$id=wp_insert_post(wp_slash(['post_type'=>'grup','post_status'=>'publish','post_title'=>$g['ad'],'post_name'=>$g['slug'],'post_excerpt'=>$g['ozet'],'post_content'=>$g['icerik'],'post_author'=>$uid,'meta_input'=>['_yr_bolge'=>$g['bolge'],'_yr_stil'=>$g['stil'],'_yr_kurulus'=>$g['kurulus'],'_yr_link'=>'','_yr_diskografi'=>$g['diskografi']]]));
		if($id&&!is_wp_error($id))wp_set_object_terms($id,[$g['tur']],'grup_tur');
	}
	/* Mevcut ve önceden eklenmiş gruplar için kısa albüm listesi yoksa dokunma. Ana sayfa vitrini artık yazılar arasında döner. */
	$st=(array)get_option('sticky_posts');$ar=get_page_by_path('anadolu-rockun-dogusu',OBJECT,'post');
	if($ar)update_option('sticky_posts',array_values(array_diff($st,[$ar->ID])));
	$cp=get_page_by_path('cerez-politikasi',OBJECT,'page');
	if($cp&&strpos($cp->post_content,'yr_ab')===false)wp_update_post(['ID'=>$cp->ID,'post_content'=>wp_slash($cp->post_content.'<h2>Bülten Abonelik Çerezi</h2><p>Bültene abone olduğunda tarayıcında <code>yr_ab</code> adlı işlevsel bir çerez saklanır (1 yıl). Bu çerez yalnızca abone olduğun için bülten kutusunu tekrar göstermemek amacıyla kullanılır; kimliğini belirleyen bir bilgi içermez.</p>')]);
});

/* Yazarock Instagram hesabı (Özelleştir → Site Ayarları → Sosyal medya ile değiştirilebilir) */
function yr_instagram(){foreach(array_filter(array_map('trim',explode("\n",(string)get_theme_mod('yr_social',''))))as $s)if(strpos($s,'instagram.com')!==false)return $s;return 'https://www.instagram.com/yazarockk/';}
function yr_instagram_kutu(){$u=yr_instagram();$h='@'.trim((string)wp_parse_url($u,PHP_URL_PATH),'/');
	echo '<a class="ig-kutu" href="'.esc_url($u).'" rel="noopener" target="_blank">'.yr_ikon('ig').'<span><b>Yazarock Instagram\'da</b> Yeni röportajlar, keşifler ve konserler: <u>'.esc_html($h).'</u></span></a>';}
