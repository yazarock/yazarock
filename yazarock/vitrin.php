<?php
if(!defined('ABSPATH'))exit;
/*
 * Vitrin: "bilinmeyen grupların sesi" ana sayfası, Haftanın Keşfi, röportaj şablonu, Hakkımızda
 */

/* ================= Haftanın Keşfi ================= */
function yr_kesif_grup(){
	$q=get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>1,'meta_key'=>'_yr_kesif','orderby'=>'meta_value_num','order'=>'DESC']);
	if($q)return $q[0];
	$q=get_posts(['post_type'=>'grup','post_status'=>'publish','numberposts'=>1,'meta_key'=>'_yr_gb_kaynak','orderby'=>'date','order'=>'DESC']);
	return $q?$q[0]:null;
}
add_action('add_meta_boxes_grup',function(){
	add_meta_box('yr_kesif','Ana Sayfa','yr_kesif_kutu','grup','side','high');
});
function yr_kesif_kutu($p){
	wp_nonce_field('yr_kesif','yr_kn');$on=(bool)get_post_meta($p->ID,'_yr_kesif',true);
	echo '<label><input type="checkbox" name="yr_kesif" value="1"'.checked($on,true,false).'> Ana sayfada <b>Haftanın Keşfi</b> olarak göster</label><p class="description">En son işaretlenen grup gösterilir.</p>';
}
add_action('save_post_grup',function($id){
	if(!isset($_POST['yr_kn'])||!wp_verify_nonce($_POST['yr_kn'],'yr_kesif')||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id))return;
	if(!empty($_POST['yr_kesif'])){if(!get_post_meta($id,'_yr_kesif',true))update_post_meta($id,'_yr_kesif',time());}else delete_post_meta($id,'_yr_kesif');
});
add_filter('manage_grup_posts_columns',function($c){$c['yr_kesif']='Keşif';return $c;});
add_action('manage_grup_posts_custom_column',function($c,$id){if($c==='yr_kesif'&&get_post_meta($id,'_yr_kesif',true))echo '★';},10,2);

/* Grup kartı (büyük: Haftanın Keşfi, küçük: röportaj altındaki kutu) */
function yr_grup_kart($id,$buyuk=false){
	$p=get_post($id);if(!$p||$p->post_type!=='grup'||$p->post_status!=='publish')return;
	$url=get_permalink($p);$alt=yr_join(yr_meta('stil',$id),yr_meta('kurulus',$id));
	echo '<div class="gkart'.($buyuk?' buyuk':'').'"><a class="gk-img" href="'.esc_url($url).'" tabindex="-1" aria-hidden="true">'.yr_cover($id,$buyuk?'large':'medium_large').'</a><div class="gk-in">';
	if($buyuk){if(yr_t('kesif_pill')!=='')echo '<span class="pill">'.esc_html(yr_t('kesif_pill')).'</span>';}else echo '<span class="gk-k">Röportaj yapılan grup</span>';
	echo '<h3><a href="'.esc_url($url).'">'.esc_html(get_the_title($p)).'</a></h3>';
	if($alt)echo '<p class="gk-alt">'.esc_html($alt).'</p>';
	$ex=has_excerpt($p)?get_the_excerpt($p):wp_trim_words(wp_strip_all_tags($p->post_content),$buyuk?40:24);
	if($ex)echo '<p>'.esc_html($ex).'</p>';
	if($buyuk){ob_start();yr_embeds($id);$e=ob_get_clean();if($e)echo '<div class="gk-emb">'.$e.'</div>';}
	echo '<div class="btnrow"><a class="btn'.($buyuk?'':' alt').'" href="'.esc_url($url).'">'.esc_html(yr_t('grup_btn')).'</a>';
	$l=yr_meta('link',$id);if($l)echo '<a class="btn alt" href="'.esc_url($l).'" target="_blank" rel="noopener">'.esc_html(yr_t('dinle_btn')).'</a>';
	echo '</div></div></div>';
}

/* ================= Röportaj ================= */
function yr_roportaj_kat(){$t=get_term_by('slug','roportaj','category');if(!$t)$t=get_term_by('name','Röportaj','category');return $t?(int)$t->term_id:0;}
function yr_roportaj_mi($id=0){$id=$id?$id:get_the_ID();$k=yr_roportaj_kat();return ($k&&has_category($k,$id))||(bool)get_post_meta($id,'_yr_roportaj_grup',true);}
add_action('add_meta_boxes_post',function(){add_meta_box('yr_rop','Röportaj','yr_rop_kutu','post','side','default');});
function yr_rop_kutu($p){
	wp_nonce_field('yr_rop','yr_rn');$v=(int)get_post_meta($p->ID,'_yr_roportaj_grup',true);
	$gs=get_posts(['post_type'=>'grup','post_status'=>['publish','draft'],'numberposts'=>500,'orderby'=>'title','order'=>'ASC']);
	echo '<p><label for="yr_rop_g">Röportaj yapılan grup</label><select name="yr_roportaj_grup" id="yr_rop_g" style="width:100%"><option value="">— Seç —</option>';
	foreach($gs as $g)echo '<option value="'.(int)$g->ID.'"'.selected($v,$g->ID,false).'>'.esc_html($g->post_title).($g->post_status==='draft'?' (taslak)':'').'</option>';
	echo '</select></p><p class="description">Seçersen yazının altında grubun kartı ve dinleme bağlantıları görünür, grup sayfasında da bu röportaj listelenir. Kategoriyi "Röportaj" yapmayı unutma. Soru-cevap düzeni için: + → Desenler → Yazarock → Röportaj.</p>';
}
add_action('save_post_post',function($id){
	if(!isset($_POST['yr_rn'])||!wp_verify_nonce($_POST['yr_rn'],'yr_rop')||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('edit_post',$id))return;
	$g=(int)($_POST['yr_roportaj_grup']??0);
	if($g&&get_post_type($g)==='grup')update_post_meta($id,'_yr_roportaj_grup',$g);else delete_post_meta($id,'_yr_roportaj_grup');
});
add_action('init',function(){
	if(!function_exists('register_block_pattern'))return;
	$q=function($s,$c){return '<!-- wp:paragraph {"className":"soru"} --><p class="soru"><strong>'.$s.'</strong></p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"cevap"} --><p class="cevap">'.$c.'</p><!-- /wp:paragraph -->';};
	register_block_pattern('yazarock/roportaj',['title'=>'Röportaj (soru-cevap)','categories'=>['yazarock'],'description'=>'Giriş, soru-cevap düzeni ve kapanış.','content'=>
		'<!-- wp:paragraph {"className":"rop-giris"} --><p class="rop-giris">Giriş: Grubu iki üç cümleyle tanıt. Nereden çıktılar, ne çalıyorlar, neden şimdi konuşuyoruz? Son çıkışlarının adını ve röportajın nasıl yapıldığını (yüz yüze, yazılı, prova sonrası) belirt.</p><!-- /wp:paragraph -->'
		.$q('Grup nasıl bir araya geldi? İsim nereden geliyor?','Cevap…')
		.$q('Müziğinizi hiç dinlememiş birine nasıl anlatırsınız?','Cevap…')
		.$q('Son çıkışınızın hikâyesi ne? Kayıt süreci nasıl geçti?','Cevap…')
		.$q('Yaşadığınız şehrin sahnesinde bağımsız bir grup olmak nasıl bir şey?','Cevap…')
		.$q('Sizi en çok kimler ve neler etkiledi?','Cevap…')
		.$q('Sırada ne var? Okurlarımız sizi nerede izleyebilir?','Cevap…')
		.'<!-- wp:paragraph {"className":"rop-kapanis"} --><p class="rop-kapanis">Kapanış: Grubu nereden dinleyebileceğini ve yaklaşan konserlerini yaz. (Sağdaki "Röportaj" kutusundan grubu seçersen dinleme bağlantıları yazının altına otomatik eklenir.)</p><!-- /wp:paragraph -->']);
});

/* ================= Özelleştir: yeni ana sayfa bölümleri ================= */
add_action('customize_register',function($c){
	foreach(['kesif'=>'Haftanın Keşfi','roportaj'=>'Röportajlar','sahne'=>'Sahneden (Keşif, yeni çıkış, konser duyurusu)','trgrup'=>"Türkiye'den Gruplar"] as $k=>$l){
		$c->add_setting('yr_s_'.$k,['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
		$c->add_control('yr_s_'.$k,['label'=>$l.' bölümünü göster','section'=>'yr','type'=>'checkbox']);
	}
},20);
function yr_m_metin_varsayilan(){return "Garajlarda, prova stüdyolarında ve küçük sahnelerde bambaşka bir Türkiye çalıyor. Yazarock, adını henüz duymadığın rock ve metal gruplarının hikâyelerini, röportajlarını ve konserlerini sana getiriyor.";}

/* ================= v1.8 geçişi: Hakkımızda sayfası ================= */
add_action('admin_init',function(){
	if(get_option('yr_mig_v7')||!current_user_can('manage_options'))return;
	update_option('yr_mig_v7',1);
	if(!get_page_by_path('hakkimizda')){
		$gt=esc_url(yr_page_url('grubunu-tanit'));$yo=esc_url(yr_page_url('uye-ol'));
		wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Hakkımızda','post_name'=>'hakkimizda','post_content'=>wp_slash(
			'<!-- wp:paragraph {"className":"rop-giris"} --><p class="rop-giris">Yazarock, Türkiye\'de rock ve metal yapan bilinmeyen, tanınmayan grupların sesi olmak için kuruldu.</p><!-- /wp:paragraph -->'
			.'<!-- wp:heading --><h2 class="wp-block-heading">Neden varız?</h2><!-- /wp:heading -->'
			.'<!-- wp:paragraph --><p>Bu ülkede her hafta yeni bir şarkı kaydediliyor, yeni bir grup ilk konserine çıkıyor. Çoğu, büyük medyada kendine yer bulamıyor. Oysa sahnenin geleceği tam da bu garajlarda, prova stüdyolarında ve küçük mekânlarda yazılıyor. Biz o hikâyeleri dinlemek, kaydetmek ve okurlarla buluşturmak istiyoruz.</p><!-- /wp:paragraph -->'
			.'<!-- wp:heading --><h2 class="wp-block-heading">Ne yapıyoruz?</h2><!-- /wp:heading -->'
			.'<!-- wp:list --><ul class="wp-block-list"><li>Bağımsız gruplarla röportajlar yapıyoruz.</li><li>Yeni albüm, EP ve teklileri tanıtıyoruz.</li><li>Küçük sahnelerdeki konserleri duyuruyor, konser kesitlerini paylaşıyoruz.</li><li>Türkiye\'nin rock ve metal sahnesinden haberleri derliyoruz.</li></ul><!-- /wp:list -->'
			.'<!-- wp:heading --><h2 class="wp-block-heading">Grubun mu var?</h2><!-- /wp:heading -->'
			.'<!-- wp:paragraph --><p>Yazarock\'ta yer almak tamamen ücretsizdir; gruplardan hiçbir ücret talep etmeyiz. Müziğini dinlememiz için <a href="'.$gt.'">Grubunu Tanıt</a> sayfasından başvurabilirsin.</p><!-- /wp:paragraph -->'
			.'<!-- wp:heading --><h2 class="wp-block-heading">Okur musun?</h2><!-- /wp:heading -->'
			.'<!-- wp:paragraph --><p><a href="'.$yo.'">Ücretsiz üye ol</a>, beğendiğin yazıları biriktir, kendi listelerini oluştur. Yazmak istersen üye olduktan sonra yazarlık başvurusu yapabilirsin.</p><!-- /wp:paragraph -->'
			.'<!-- wp:paragraph --><p>Bize her zaman info@yazarock.com adresinden ulaşabilirsin.</p><!-- /wp:paragraph -->')]);
	}
	if(!term_exists('Röportaj','category'))wp_insert_term('Röportaj','category',['slug'=>'roportaj']);
});

/* ================= Ana sayfa girişi = Ana Sayfa sayfasının ilk başlığı ve paragrafı ================= */
/* "Sayfayı düzenle" ile açılan Ana Sayfa'daki ilk başlık girişin büyük başlığı, ilk paragraf metnidir; geri kalan bloklar girişin altında kutu olarak görünür. */
function yr_giris_icerik(){
	$out=['baslik'=>get_theme_mod('yr_m_baslik','Bilinmeyen grupların sesi'),'metin'=>get_theme_mod('yr_m_metin',yr_m_metin_varsayilan()),'kalan'=>''];
	$fid=(int)get_option('page_on_front');$c=$fid?(string)get_post_field('post_content',$fid):'';if(trim($c)==='')return $out;
	$bloklar=parse_blocks($c);$h=false;$p=false;$kalan=[];
	foreach($bloklar as $b){
		if(empty($b['blockName'])&&trim(wp_strip_all_tags((string)$b['innerHTML']))==='')continue;
		if(!$h&&$b['blockName']==='core/paragraph'&&!isset($out['ust'])){$out['ust']=trim(wp_strip_all_tags(render_block($b)));continue;}/* başlıktan önceki paragraf = küçük etiket */
		if(!$h&&$b['blockName']==='core/heading'){$t=trim(wp_strip_all_tags(render_block($b)));if($t!==''){$out['baslik']=$t;$h=true;continue;}}
		if($h&&!$p&&$b['blockName']==='core/paragraph'){$t=trim(wp_strip_all_tags(render_block($b)));if($t!==''){$out['metin']=$t;$p=true;continue;}}
		$kalan[]=$b;
	}
	foreach($kalan as $b)$out['kalan'].=render_block($b);
	return $out;
}
/* Ana Sayfa boşsa girişi içine yaz: düzenleyince yazıyı doğrudan görsün */
add_action('admin_init',function(){
	if(get_option('yr_mig_v8')||!current_user_can('manage_options'))return;
	update_option('yr_mig_v8',1);
	$fid=(int)get_option('page_on_front');if(!$fid)return;
	if(trim((string)get_post_field('post_content',$fid))!=='')return;
	$b=esc_html(get_theme_mod('yr_m_baslik','Bilinmeyen grupların sesi'));$m=esc_html(get_theme_mod('yr_m_metin',yr_m_metin_varsayilan()));
	wp_update_post(['ID'=>$fid,'post_content'=>wp_slash('<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">'.$b.'</h1><!-- /wp:heading --><!-- wp:paragraph --><p>'.$m.'</p><!-- /wp:paragraph -->')]);
});
/* İçerik kaydedilince sunucu önbelleğini temizle: değişiklik hemen görünsün */
add_action('save_post',function($id,$p){
	if(wp_is_post_revision($id)||wp_is_post_autosave($id)||!in_array($p->post_status,['publish','trash'],true))return;
	if(!in_array($p->post_type,['post','page','grup','etkinlik','liste'],true))return;
	if(function_exists('yr_onbellek_temizle'))yr_onbellek_temizle();
},20,2);
