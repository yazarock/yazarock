<?php
if(!defined('ABSPATH'))exit;
/*
 * Sayfa metinleri: özel şablonlu sayfalardaki yazılar "Sayfayı düzenle" ekranından gelir.
 * Kural: sayfadaki ilk başlık = sayfa başlığı, hemen ardından gelen paragraf = alt başlık; geri kalan bloklar sayfaya göre yerleşir.
 */

/* Sayfa içeriğini parçalara ayır */
function yr_parca($pid=0){
	static $C=[];
	$pid=$pid?(int)$pid:(int)get_queried_object_id();
	if(isset($C[$pid]))return $C[$pid];
	$o=['baslik'=>'','giris'=>'','ust'=>'','bloklar'=>[]];
	$c=$pid?(string)get_post_field('post_content',$pid):'';
	if(trim($c)===''){/* sayfa boşsa varsayılan yazılar */
		$d=$pid?yr_sayfa_varsayilanlari():[];$sl=$pid?(string)get_post_field('post_name',$pid):'';
		if($pid&&(int)get_option('page_for_posts')===$pid)$sl='haberler';
		if(!isset($d[$sl]))return $C[$pid]=$o;
		$c=$d[$sl];
	}
	$h=false;$p=false;
	foreach(parse_blocks($c) as $b){
		if(empty($b['blockName'])){if(trim(wp_strip_all_tags((string)$b['innerHTML']))==='')continue;}
		if(!$h&&$b['blockName']==='core/paragraph'&&$o['ust']===''){$o['ust']=yr_ic_html(render_block($b));continue;}/* başlıktan önceki kısa paragraf = küçük etiket */
		if(!$h&&$b['blockName']==='core/heading'){$t=trim(wp_strip_all_tags(render_block($b)));if($t!==''){$o['baslik']=$t;$h=true;continue;}}
		if($h&&!$p&&$b['blockName']==='core/paragraph'){$o['giris']=yr_ic_html(render_block($b));$p=true;continue;}
		$o['bloklar'][]=$b;
	}
	return $C[$pid]=$o;
}
/* <p ...>...</p> kabuğunu at, satır içi biçimi (bağlantı, kalın) koru */
function yr_ic_html($html){return trim(preg_replace('#^\s*<p[^>]*>|</p>\s*$#i','',(string)$html));}
function yr_bloklar_html($bloklar){$s='';foreach((array)$bloklar as $b)$s.=render_block($b);return $s;}
function yr_dolu($html){return trim(wp_strip_all_tags((string)$html,true))!==''||stripos((string)$html,'<img')!==false||stripos((string)$html,'<iframe')!==false;}

/* Sayfa başlığı (ph): içerikten ya da varsayılandan */
function yr_ph($baslik,$alt_html,$pid=0){
	$p=yr_parca($pid);
	echo '<div class="ph"><h1>'.esc_html($p['baslik']!==''?$p['baslik']:$baslik).'</h1>';
	$a=$p['giris']!==''?$p['giris']:$alt_html;
	if($a!=='')echo '<p>'.wp_kses_post($a).'</p>';
	echo '</div>';
}
/* Başlık ve alt başlık dışındaki bloklar, kutu içinde */
function yr_sayfa_ek($pid=0,$sinif='box entry',$stil='margin-bottom:22px'){
	$h=yr_bloklar_html(yr_parca($pid)['bloklar']);
	if(yr_dolu($h))echo '<div class="'.esc_attr($sinif).'" style="'.esc_attr($stil).'"><div class="content">'.$h.'</div></div>';
}
/* Liste bloğundan maddeler: "Başlık – açıklama" → [başlık, açıklama] */
function yr_liste_maddeleri($b){
	$out=[];
	$parcalar=!empty($b['innerBlocks'])?array_map('render_block',$b['innerBlocks']):[];
	if(!$parcalar&&preg_match_all('#<li[^>]*>(.*?)</li>#is',(string)$b['innerHTML'],$m))$parcalar=$m[1];
	foreach($parcalar as $li){
		$li=trim(preg_replace('#^\s*<li[^>]*>|</li>\s*$#i','',$li));
		if(preg_match('#^\s*<strong>(.*?)</strong>\s*[–—:-]?\s*(.*)$#isu',$li,$m))$out[]=[trim(wp_strip_all_tags($m[1])),trim($m[2])];
		elseif(preg_match('#^(.*?)\s+[–—]\s+(.*)$#su',wp_strip_all_tags($li),$m)||preg_match('#^([^:]{2,60}):\s*(.*)$#su',wp_strip_all_tags($li),$m))$out[]=[trim($m[1]),esc_html(trim($m[2]))];
		else $out[]=['',$li];
	}
	return $out;
}

/* Blok listesini türüne göre ayır: ilk liste, ara başlık, ayrıntılar (SSS), alıntı ve kalan */
function yr_bolumle($bloklar){
	$o=['liste'=>null,'ara'=>'','sss'=>[],'alinti'=>'','once'=>[],'sonra'=>[]];
	foreach((array)$bloklar as $b){
		$n=$b['blockName'];
		if($n==='core/list'&&!$o['liste']){$o['liste']=$b;continue;}
		if($n==='core/heading'&&$o['ara']===''&&$o['liste']){$o['ara']=trim(wp_strip_all_tags(render_block($b)));continue;}
		if($n==='core/details'){$o['sss'][]=render_block($b);continue;}
		if($n==='core/quote'&&$o['alinti']===''){$o['alinti']=trim(preg_replace('#^\s*<blockquote[^>]*>|</blockquote>\s*$#i','',render_block($b)));continue;}
		if($o['liste'])$o['sonra'][]=$b;else $o['once'][]=$b;
	}
	return $o;
}

/* ================= Arşiv sayfaları (Gruplar, Etkinlikler): Özelleştir ================= */
add_action('customize_register',function($c){
	$c->add_section('yr_basliklar',['title'=>'Sayfa Başlıkları','priority'=>26,'description'=>'Gruplar ve Etkinlikler listelerinin başlığı ve açıklaması. Diğer sayfaların yazılarını o sayfayı açıp "Sayfayı düzenle" ile değiştirebilirsin.']);
	foreach([['yr_t_grup_b','Gruplar başlığı','Gruplar','text'],['yr_t_grup_a','Gruplar açıklaması',"Türkiye'nin bağımsız rock ve metal grupları ve türün ustaları.",'textarea'],['yr_t_etk_b','Etkinlikler başlığı','Etkinlikler','text'],['yr_t_etk_a','Etkinlikler açıklaması','Konser ve festival takvimi.','textarea']] as $x){
		$c->add_setting($x[0],['default'=>$x[2],'sanitize_callback'=>$x[3]==='textarea'?'sanitize_textarea_field':'sanitize_text_field']);
		$c->add_control($x[0],['label'=>$x[1],'section'=>'yr_basliklar','type'=>$x[3]]);
	}
});

/* ================= v1.9 geçişi: boş sayfalara mevcut yazıları yaz ================= */
function yr_b_h($t,$l=1){return '<!-- wp:heading {"level":'.$l.'} --><h'.$l.' class="wp-block-heading">'.$t.'</h'.$l.'><!-- /wp:heading -->';}
function yr_b_p($t){return '<!-- wp:paragraph --><p>'.$t.'</p><!-- /wp:paragraph -->';}
function yr_b_list($items,$ordered=false){
	$tag=$ordered?'ol':'ul';$s='<!-- wp:list'.($ordered?' {"ordered":true}':'').' --><'.$tag.' class="wp-block-list">';
	foreach($items as $i)$s.='<!-- wp:list-item --><li>'.$i.'</li><!-- /wp:list-item -->';
	return $s.'</'.$tag.'><!-- /wp:list -->';
}
function yr_b_details($s,$t){return '<!-- wp:details --><details class="wp-block-details"><summary>'.$s.'</summary><!-- wp:paragraph {"placeholder":"Cevap"} --><p>'.$t.'</p><!-- /wp:paragraph --></details><!-- /wp:details -->';}
function yr_b_quote($t){return '<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>'.$t.'</p><!-- /wp:paragraph --></blockquote><!-- /wp:quote -->';}
function yr_b_btn($t,$u){return '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="'.esc_url($u).'">'.$t.'</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';}
function yr_sayfa_varsayilanlari(){
	return [
		'grubunu-tanit'=>yr_b_p("Ücretsiz · Türkiye'den bağımsız gruplar").yr_b_h('Grubunu Tanıt').yr_b_p("Yazarock, Türkiye'de rock ve metal yapan bilinmeyen, tanınmayan grupların sesi olmak için var. Garajda, prova stüdyosunda, küçük sahnelerde müzik üretiyorsan seni dinlemek, seninle konuşmak ve okurlarımıza tanıtmak istiyoruz.")
			.yr_b_list(['<strong>Formu doldur</strong> – Grubunu, müziğini ve hikâyeni anlat.','<strong>Dinliyoruz</strong> – Gönderilen her başvuruyu dinliyoruz.','<strong>Sahne senin</strong> – Röportaj, inceleme ya da tanıtım için sana yazıyoruz.'],true)
			.yr_b_h('Sık Sorulanlar',2)
			.yr_b_details('Ücretli mi?',"Hayır. Yazarock'ta yer almak tamamen ücretsizdir; gruplardan hiçbir ücret talep etmeyiz.")
			.yr_b_details('Kimler başvurabilir?',"Türkiye'de rock, metal ve alt türlerinde kendi bestelerini yapan bağımsız gruplar ve solo sanatçılar. Daha önce hiç yayın yapmamış olmanız sorun değil; bir demo kaydı yeterli.")
			.yr_b_details('Ne zaman dönüş yapılır?','Her başvuruyu dinliyoruz. Röportaj, inceleme ya da tanıtım için uygun bulduğumuzda e-postayla sana yazıyoruz.')
			.yr_b_details('Neye dikkat ediyorsunuz?',"Özgün müzik, grubun hikâyesi ve sahnedeki emek. Kayıt kalitesinin mükemmel olması gerekmez.")
			.yr_b_details('Bilgilerimi nasıl sildiririm?','info@yazarock.com adresine yazman yeterli; başvurun ve fotoğrafın silinir.')
			.yr_b_quote('"Bilinmeyen grupların sesi olmak" için yola çıktık. Sahne büyüdükçe herkes kazanır.'),
		'giris'=>yr_b_h('Giriş Yap').yr_b_p('Yazarlar, editörler ve üyeler için.').yr_b_h("Yazarock'a katıl",2)
			.yr_b_list(['<strong>Kendi listeni yap.</strong> "En iyi 10 albümüm" gibi listeler oluştur, adınla yayımlansın.','<strong>Yazar ol.</strong> Üye olduktan sonra yazarlık başvurusu yapıp yazılarını okurlarla buluşturabilirsin.']),
		'uye-ol'=>yr_b_h('Üye Ol').yr_b_p('Ücretsiz üye ol; beğendiğin yazıları biriktir, kendi listelerini oluştur.')
			.yr_b_h('Zaten üye misin?',2).yr_b_p('Kullanıcı adın ya da e-postan ve şifrenle giriş yap.').yr_b_btn('Giriş Yap',yr_page_url('giris'))
			.yr_b_h('Yazmak mı istiyorsun?',2).yr_b_p('Rock ve metal üzerine yazıyorsan önce üye ol; ardından panelinden yazarlık başvurusu yapabilirsin.'),
		'yazar-ol'=>yr_b_h('Yazar Ol').yr_b_p('Rock ve metal hakkında yazmak mı istiyorsun? Kendini tanıt, ilk yazı fikrini paylaş.'),
		'yazarlar'=>yr_b_h('Yazarlar').yr_b_p("Yazarock'ta rock ve metal üzerine yazanlar."),
		'listeler'=>yr_b_h('Okuyucu Listeleri').yr_b_p('Okuyucularımızın hazırladığı favori listeleri. <a href="'.esc_url(yr_page_url('listelerim')).'">Sen de liste oluştur</a>.'),
		'listelerim'=>yr_b_h('Listelerim').yr_b_p('"Benim 10 favori albümüm" gibi kendi listeni oluştur. Listeler editör onayından sonra yayımlanır.'),
		'haberler'=>yr_b_h('Haberler').yr_b_p('Türkiye ve dünyadan rock ve metal gündemi, incelemeler ve rehberler.'),
	];
}
add_action('admin_init',function(){
	if(get_option('yr_mig_v9')||!current_user_can('manage_options'))return;
	update_option('yr_mig_v9',1);
	foreach(yr_sayfa_varsayilanlari() as $slug=>$icerik){
		$p=get_page_by_path($slug);if(!$p)continue;
		if(trim((string)$p->post_content)!=='')continue;/* dolu sayfalara dokunma */
		wp_update_post(['ID'=>$p->ID,'post_content'=>wp_slash($icerik)]);
	}
	/* Ana sayfa: başlığın üstüne küçük etiket paragrafı (sadece tema yazdıysa, başlıkla başlıyorsa) */
	$fid=(int)get_option('page_on_front');$fc=$fid?(string)get_post_field('post_content',$fid):'';
	if($fid&&strpos(ltrim($fc),'<!-- wp:heading')===0)wp_update_post(['ID'=>$fid,'post_content'=>wp_slash(yr_b_p('Türkiye · Rock &amp; Metal · Underground').$fc)]);
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
});
