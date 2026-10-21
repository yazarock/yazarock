<?php if(!defined('ABSPATH'))exit; /* Template Name: Giriş ve Kullanıcı Paneli */ get_header();

/* ================= Giriş yapılmamış ================= */
if(!is_user_logged_in()):?>
<?php yr_ph('Giriş Yap','Yazarlar, editörler ve üyeler için.');?>
<div class="lgrid">
<div class="box"><h2>Hesabına gir</h2><?php if(($_GET['login']??'')==='failed')echo '<div class="notice err" role="alert">Kullanıcı adı veya şifre hatalı.</div>';
wp_login_form(['redirect'=>wp_validate_redirect(esc_url_raw(wp_unslash($_GET['redirect_to']??'')),yr_panel_url()),'label_username'=>'Kullanıcı adı veya e-posta','label_password'=>'Şifre','label_remember'=>'Beni hatırla','label_log_in'=>'Giriş Yap']);?>
<p class="note"><a href="<?php echo esc_url(wp_lostpassword_url(yr_panel_url()));?>">Şifremi unuttum</a></p></div>
<div class="box join"><?php $jb=yr_parca()['bloklar'];$jh='';$jl=null;$jk=[];foreach($jb as $x){if($jh===''&&$x['blockName']==='core/heading'){$jh=trim(wp_strip_all_tags(render_block($x)));continue;}if(!$jl&&$x['blockName']==='core/list'){$jl=$x;continue;}$jk[]=$x;}
if($jh!=='')echo '<h2>'.esc_html($jh).'</h2>';
if($jl){$ik=['list','pen','heart','doc','eye','up'];echo '<ul class="perk">';foreach(yr_liste_maddeleri($jl) as $i=>$m)echo '<li>'.yr_ikon($ik[$i%count($ik)]).'<span>'.($m[0]!==''?'<b>'.esc_html($m[0]).'</b> ':'').wp_kses_post($m[1]).'</span></li>';echo '</ul>';}
$x=yr_bloklar_html($jk);if(yr_dolu($x))echo '<div class="jm">'.$x.'</div>';?>
<div class="btnrow"><a class="btn" href="<?php echo esc_url(yr_page_url('uye-ol'));?>">Ücretsiz Üye Ol</a></div></div>
</div>
<?php get_footer();return;endif;

/* ================= Panel ================= */
$u=wp_get_current_user();$uid=(int)$u->ID;$yazar=yr_is_writer($u);$yonetim=current_user_can('edit_others_posts');
$B=['genel'=>['Genel Bakış','user']];
if($yazar)$B['yazilar']=['Yazılarım','doc'];
$B['begeniler']=['Beğendiklerim','heart'];$B['listeler']=['Listelerim','list'];$B['profil']=['Profil ve Ayarlar','pen'];
$b=sanitize_key($_GET['bolum']??'genel');if(!isset($B[$b]))$b='genel';
$ST=['publish'=>'Yayında','pending'=>'İncelemede','draft'=>'Taslak','future'=>'Zamanlandı','private'=>'Özel'];
$likes=array_keys(array_filter(yr_tepkilerim($uid),function($v){return (int)$v===1;}));
$bio=trim((string)$u->description);
?>
<section class="phead">
<div class="phead-bg" aria-hidden="true"></div>
<div class="phead-in">
<?php echo get_avatar($uid,112,'','',['class'=>'pav']);?>
<div class="pmeta"><span class="pill"><?php echo esc_html(yr_rol_adi($u));?></span>
<h1><?php echo esc_html($u->display_name);?></h1>
<p class="psub"><?php echo yr_ikon('clock');?>Yazarock'ta <?php echo esc_html(date_i18n('F Y',strtotime($u->user_registered)));?> tarihinden beri<?php echo yr_sosyal($uid)?'<span class="psoc">'.yr_sosyal($uid).'</span>':'';?></p>
<?php if($bio)echo '<p class="pbio">'.esc_html($bio).'</p>';elseif($b!=='profil')echo '<p class="pbio mute">Henüz kendini tanıtmadın. <a href="'.esc_url(yr_panel_url('profil')).'">Kısa bir biyografi ekle →</a></p>';?>
</div>
<div class="pact">
<?php if($yazar):?><a class="btn" href="<?php echo esc_url(admin_url('post-new.php'));?>"><?php echo yr_ikon('plus');?>Yeni Yazı</a><a class="btn alt" href="<?php echo esc_url(get_author_posts_url($uid));?>">Yazar Sayfam</a><?php else:?><a class="btn" href="<?php echo esc_url(yr_page_url('listelerim'));?>"><?php echo yr_ikon('plus');?>Liste Oluştur</a><?php endif;?>
<a class="btn alt" href="<?php echo esc_url(wp_logout_url(home_url('/')));?>"><?php echo yr_ikon('out');?>Çıkış</a>
</div></div>
</section>

<?php yr_onay_banner();yr_flash('yr_p',['hosgeldin'=>['Hoş geldin! Hesabın açıldı. E-posta adresine bir onay bağlantısı gönderdik.','ok'],'onaylandi'=>['E-posta adresin onaylandı, teşekkürler!','ok'],'onay_gitti'=>['Onay e-postasını tekrar gönderdik; gelen kutunu ve spam klasörünü kontrol et.','ok'],'onay_bekle'=>['Çok fazla onay e-postası istendi; bir saat sonra tekrar dene.','err']]);?>
<nav class="ptabs" aria-label="Panel bölümleri"><?php foreach($B as $k=>$x)echo '<a href="'.esc_url(yr_panel_url($k==='genel'?'':$k)).'"'.($k===$b?' class="on" aria-current="page"':'').'>'.yr_ikon($x[1]).esc_html($x[0]).'</a>';?></nav>

<?php if($b==='genel'):
	if($yazar){$s=yr_yazar_ist($uid);
		$T=[['Yayında',yr_durum_sayi($uid,'publish'),'doc'],['İncelemede',yr_durum_sayi($uid,'pending'),'clock'],['Taslak',yr_durum_sayi($uid,'draft'),'pen'],['Okunma',$s['okunma'],'eye'],['Beğeni',$s['begeni'],'up'],['Yorum',$s['yorum'],'cmt']];
	}else{
		$T=[['Beğendiklerim',count($likes),'heart'],['Listelerim',count(get_posts(['post_type'=>'liste','author'=>$uid,'post_status'=>['publish','pending'],'numberposts'=>50,'fields'=>'ids'])),'list'],['Yorumlarım',(int)get_comments(['user_id'=>$uid,'status'=>'approve','count'=>true]),'cmt']];
	}?>
<div class="pst"><?php foreach($T as $x)echo '<div>'.yr_ikon($x[2]).'<b>'.esc_html(yr_sayi($x[1])).'</b><span>'.esc_html($x[0]).'</span></div>';?></div>

<?php if($yonetim):
	$pend=(int)(wp_count_posts('post')->pending??0);
	$q=new WP_Query(['post_type'=>'post','post_status'=>'draft','meta_key'=>'_yr_derleme_id','posts_per_page'=>1,'fields'=>'ids']);$der=(int)$q->found_posts;
	$lst=(int)(wp_count_posts('liste')->pending??0);$cm=(int)wp_count_comments()->moderated;
	$bq=new WP_Query(['post_type'=>'basvuru','post_status'=>'any','meta_key'=>'_durum','meta_value'=>'yeni','posts_per_page'=>1,'fields'=>'ids']);$bas=(int)$bq->found_posts;
	$gq=new WP_Query(['post_type'=>'grup_basvuru','post_status'=>'any','meta_key'=>'_gb_durum','meta_value'=>'yeni','posts_per_page'=>1,'fields'=>'ids']);$gbn=(int)$gq->found_posts;
	$Y=[['Yeni grup başvuruları',$gbn,admin_url('edit.php?post_type=grup_basvuru&gb_durum=yeni')],['Onay bekleyen yazılar',$pend,admin_url('edit.php?post_status=pending&post_type=post')],['Sabah derlemesi taslakları',$der,admin_url('edit.php?post_status=draft&yr_derleme=1')],['Onay bekleyen yorumlar',$cm,admin_url('edit-comments.php?comment_status=moderated')],['Onay bekleyen listeler',$lst,admin_url('edit.php?post_status=pending&post_type=liste')],['Yeni yazar başvuruları',$bas,admin_url('edit.php?post_type=basvuru')]];?>
<div class="box ybox"><h2><?php echo yr_ikon('shield');?>Yönetim</h2><div class="yq"><?php foreach($Y as $x)echo '<a href="'.esc_url($x[2]).'"'.($x[1]?' class="has"':'').'><b>'.(int)$x[1].'</b><span>'.esc_html($x[0]).'</span></a>';?></div></div>
<?php endif;?>

<div class="pgrid">
<?php if($yazar):
	$top=get_posts(['post_type'=>'post','author'=>$uid,'post_status'=>'publish','numberposts'=>5,'meta_key'=>'_yr_okunma','orderby'=>'meta_value_num','order'=>'DESC']);
	if(!$top)$top=get_posts(['post_type'=>'post','author'=>$uid,'post_status'=>'publish','numberposts'=>5]);
	$mx=1;foreach($top as $p)$mx=max($mx,yr_okunma($p->ID));?>
<div class="box"><h2>En Çok Okunan Yazıların</h2><?php if($top):?><ol class="tlist"><?php foreach($top as $p){$o=yr_okunma($p->ID);$c=yr_tepki_sayi($p->ID);
	echo '<li><a href="'.esc_url(get_permalink($p)).'">'.esc_html(get_the_title($p)).'</a><span class="tbar"><i style="width:'.(int)round($o*100/$mx).'%"></i></span><small>'.yr_ikon('eye').esc_html(yr_sayi($o)).' '.yr_ikon('up').esc_html(yr_sayi($c['l'])).' '.yr_ikon('down').esc_html(yr_sayi($c['d'])).'</small></li>';}?></ol>
<?php else:?><p class="empty-s">Henüz yayımlanmış yazın yok. İlk yazın onaylandığında istatistikleri burada göreceksin.</p><p><a class="btn" href="<?php echo esc_url(admin_url('post-new.php'));?>">İlk yazını yaz</a></p><?php endif;?></div>
<div class="box"><h2>Yazılarına Gelen Yorumlar</h2><?php $cms=get_comments(['post_author'=>$uid,'post_type'=>['post','grup','etkinlik'],'status'=>'approve','number'=>5]);
	if($cms){echo '<ul class="clist">';foreach($cms as $cmt)echo '<li>'.get_avatar($cmt,36).'<div><b>'.esc_html($cmt->comment_author).'</b> <a href="'.esc_url(get_comment_link($cmt)).'">'.esc_html(get_the_title($cmt->comment_post_ID)).'</a> hakkında<p>'.esc_html(wp_trim_words(wp_strip_all_tags($cmt->comment_content),18)).'</p><small>'.esc_html(human_time_diff(strtotime($cmt->comment_date_gmt.' UTC'))).' önce</small></div></li>';echo '</ul>';}
	else echo '<p class="empty-s">Yazılarına henüz yorum gelmedi.</p>';?></div>
<?php else:?>
<div class="box"><h2>Son Beğendiklerin</h2><?php if($likes){$lq=new WP_Query(['post_type'=>'post','post__in'=>array_slice(array_reverse($likes),0,3),'orderby'=>'post__in','posts_per_page'=>3,'ignore_sticky_posts'=>1,'no_found_rows'=>true]);echo '<div class="news">';while($lq->have_posts()){$lq->the_post();yr_card();}echo '</div>';wp_reset_postdata();echo '<p style="margin:12px 0 0"><a class="more" href="'.esc_url(yr_panel_url('begeniler')).'">Tümünü gör →</a></p>';}
	else echo '<p class="empty-s">Henüz bir yazıyı beğenmedin. Yazıların altındaki '.yr_ikon('up').' düğmesiyle beğendiklerin burada birikir.</p><p><a class="btn alt" href="'.esc_url(yr_haber_url()).'">Haberlere göz at</a></p>';?></div>
<div class="box"><h2>Yazar Olmak İster misin?</h2><p>Rock ve metal üzerine yazıyorsan, yazılarını Yazarock okurlarıyla buluşturabilirsin. Başvurunu değerlendirip sana dönüş yapıyoruz.</p><p><a class="btn" href="<?php echo esc_url(yr_page_url('yazar-ol'));?>">Yazar Başvurusu Yap</a></p></div>
<?php endif;?>
</div>

<?php elseif($b==='yazilar'&&$yazar):
	$D=['tum'=>'Tümü','publish'=>'Yayında','pending'=>'İncelemede','draft'=>'Taslak'];$d=sanitize_key($_GET['durum']??'tum');if(!isset($D[$d]))$d='tum';
	$pg=max(1,(int)($_GET['sayfa']??1));
	$q=new WP_Query(['post_type'=>'post','author'=>$uid,'post_status'=>$d==='tum'?['publish','pending','draft','future','private']:$d,'posts_per_page'=>15,'paged'=>$pg,'orderby'=>'modified','order'=>'DESC']);?>
<div class="box"><div class="bh"><h2>Yazılarım</h2><a class="btn" href="<?php echo esc_url(admin_url('post-new.php'));?>"><?php echo yr_ikon('plus');?>Yeni Yazı</a></div>
<div class="tabs"><?php foreach($D as $k=>$l)echo '<a href="'.esc_url(yr_panel_url('yazilar',$k==='tum'?[]:['durum'=>$k])).'"'.($k===$d?' class="on"':'').'>'.esc_html($l).($k!=='tum'?' ('.(int)yr_durum_sayi($uid,$k).')':'').'</a>';?></div>
<?php if($q->have_posts()):?>
<div class="ptable" role="table" aria-label="Yazılarım"><div class="pr-h" role="row"><span role="columnheader">Başlık</span><span role="columnheader">Durum</span><span role="columnheader"><?php echo yr_ikon('eye');?><i class="screen-reader-text">Okunma</i></span><span role="columnheader"><?php echo yr_ikon('up');?><i class="screen-reader-text">Beğeni</i></span><span role="columnheader"><?php echo yr_ikon('down');?><i class="screen-reader-text">Beğenmeme</i></span><span role="columnheader"><?php echo yr_ikon('cmt');?><i class="screen-reader-text">Yorum</i></span><span role="columnheader"></span></div>
<?php while($q->have_posts()){$q->the_post();$p=get_post();$c=yr_tepki_sayi($p->ID);$st=$p->post_status;
	$link=$st==='publish'?get_permalink($p):get_preview_post_link($p);
	echo '<div class="pr-r" role="row"><span role="cell" class="pt"><a href="'.esc_url($link).'">'.esc_html(get_the_title()?get_the_title():'(Başlıksız)').'</a><small>'.esc_html(($st==='publish'?'Yayın: ':'Son düzenleme: ').get_the_modified_date('j F Y')).'</small></span>'
		.'<span role="cell"><span class="pill st-'.esc_attr($st).'">'.esc_html($ST[$st]??$st).'</span></span>'
		.'<span role="cell" data-l="Okunma">'.esc_html(yr_sayi(yr_okunma($p->ID))).'</span><span role="cell" data-l="Beğeni">'.esc_html(yr_sayi($c['l'])).'</span><span role="cell" data-l="Beğenmeme">'.esc_html(yr_sayi($c['d'])).'</span><span role="cell" data-l="Yorum">'.(int)get_comments_number($p).'</span>'
		.'<span role="cell" class="pa">'.(current_user_can('edit_post',$p->ID)?'<a href="'.esc_url(get_edit_post_link($p->ID)).'">Düzenle</a>':'').'<a href="'.esc_url($link).'">'.($st==='publish'?'Görüntüle':'Önizle').'</a></span></div>';}
wp_reset_postdata();?></div>
<?php echo '<div class="pnav">'.paginate_links(['base'=>esc_url_raw(add_query_arg('sayfa','%#%')),'format'=>'','current'=>$pg,'total'=>$q->max_num_pages,'prev_text'=>'←','next_text'=>'→']).'</div>';?>
<?php else:?><p class="empty-s">Bu bölümde yazı yok.</p><?php endif;?>
<p class="note">Yazıların yayına alınmadan önce editör tarafından incelenir. Yayımlanan bir yazında değişiklik istiyorsan editöre yaz.</p></div>

<?php elseif($b==='begeniler'):
	$pg=max(1,(int)($_GET['sayfa']??1));?>
<div class="box"><h2>Beğendiklerim</h2>
<?php if($likes){$lq=new WP_Query(['post_type'=>'post','post_status'=>'publish','post__in'=>array_reverse($likes),'orderby'=>'post__in','posts_per_page'=>12,'paged'=>$pg,'ignore_sticky_posts'=>1]);
	echo '<div class="news">';while($lq->have_posts()){$lq->the_post();yr_card();}echo '</div>';
	echo '<div class="pnav">'.paginate_links(['base'=>esc_url_raw(add_query_arg('sayfa','%#%')),'format'=>'','current'=>$pg,'total'=>$lq->max_num_pages,'prev_text'=>'←','next_text'=>'→']).'</div>';wp_reset_postdata();}
else echo '<p class="empty-s">Henüz bir yazıyı beğenmedin. Yazıların sağ altındaki '.yr_ikon('up').' düğmesine bastığında burada görünür.</p>';?></div>

<?php elseif($b==='listeler'):
	$L=get_posts(['post_type'=>'liste','post_status'=>['publish','pending'],'author'=>$uid,'numberposts'=>20]);?>
<div class="box"><div class="bh"><h2>Listelerim</h2><a class="btn" href="<?php echo esc_url(yr_page_url('listelerim'));?>"><?php echo yr_ikon('plus');?>Yeni Liste</a></div>
<?php if($L){echo '<div class="bands">';foreach($L as $p){$it=yr_list_items($p);
	echo '<a class="band" href="'.esc_url($p->post_status==='publish'?get_permalink($p):yr_page_url('listelerim')).'"><div class="bb"><span class="pill'.($p->post_status==='publish'?'':' st-pending').'">'.($p->post_status==='publish'?'Yayında':'Onay bekliyor').'</span><h3>'.esc_html($p->post_title).'</h3><em>'.count($it).' madde</em><p>'.esc_html(implode(' · ',array_slice($it,0,3))).'…</p></div></a>';}
	echo '</div><p class="note" style="margin-top:12px">Listeleri silmek için <a href="'.esc_url(yr_page_url('listelerim')).'">Listelerim</a> sayfasını kullan.</p>';}
else echo '<p class="empty-s">Henüz listen yok. "Benim 10 favori albümüm" gibi bir liste oluştur; editör onayından sonra adınla yayımlanır.</p>';?></div>

<?php elseif($b==='profil'):
	yr_flash('yr_p',['tamam'=>['Profilin güncellendi.','ok'],'sifre_ok'=>['Şifren değiştirildi.','ok'],'ad'=>['Görünen ad 2-40 karakter olmalı.','err'],'foto'=>['Fotoğraf yüklenemedi. JPG, PNG veya WEBP, en fazla 2 MB olmalı.','err'],'eski'=>['Mevcut şifren hatalı.','err'],'yeni'=>['Yeni şifre en az 8 karakter olmalı, tekrarıyla aynı ve eskisinden farklı olmalı.','err'],'bekle'=>['Çok fazla deneme yaptın; 15 dakika sonra tekrar dene.','err'],'sil_hata'=>['Hesap silinemedi: onay kutusunu işaretleyip şifreni doğru girmelisin.','err'],'yetki'=>['Yazar ve yönetici hesapları buradan silinemez; info@yazarock.com adresine yaz.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenileyip tekrar dene.','err'],'hata'=>['Bir sorun oluştu, tekrar dene.','err']]);?>
<div class="pgrid pf">
<div class="box" id="profil"><h2>Profil Bilgileri</h2>
<form class="fg pform" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_profil"><?php wp_nonce_field('yr_profil','yr_n');?>
<div class="avup"><?php echo get_avatar($uid,96,'','',['class'=>'pav','id'=>'avprev']);?><div><label>Profil fotoğrafı<input type="file" name="foto" id="avfile" accept="image/jpeg,image/png,image/webp"></label><small class="note">JPG, PNG veya WEBP · en fazla 2 MB · kare fotoğraf en iyi sonucu verir.</small>
<?php if(get_user_meta($uid,'yr_foto',true))echo '<label class="chk"><input type="checkbox" name="foto_sil" value="1"> <span>Fotoğrafımı kaldır</span></label>';?></div></div>
<label>Görünen ad<input name="ad" value="<?php echo esc_attr($u->display_name);?>" required minlength="2" maxlength="40" autocomplete="nickname"></label>
<label>Kısa biyografi <small>(<span id="biocnt"><?php echo (int)mb_strlen($bio);?></span>/400)</small><textarea name="bio" id="bio" rows="4" maxlength="400" placeholder="<?php echo esc_attr($yazar?'Kimsin, hangi türleri seversin, ne yazarsın? Yazar sayfanda ve yazılarının altında görünür.':'Kendinden kısaca bahset.');?>"><?php echo esc_textarea($bio);?></textarea></label>
<label>X (Twitter) adresi<input type="url" name="yr_twitter" value="<?php echo esc_attr(get_user_meta($uid,'yr_twitter',true));?>" placeholder="https://x.com/kullaniciadi"></label>
<label>Instagram adresi<input type="url" name="yr_instagram" value="<?php echo esc_attr(get_user_meta($uid,'yr_instagram',true));?>" placeholder="https://instagram.com/kullaniciadi"></label>
<label>Web sitesi<input type="url" name="yr_web" value="<?php echo esc_attr(get_user_meta($uid,'yr_web',true));?>" placeholder="https://"></label>
<p class="note">E-posta adresin: <b><?php echo esc_html($u->user_email);?></b> (başkalarına gösterilmez)</p>
<button class="btn" type="submit">Kaydet</button></form></div>
<div>
<div class="box" id="sifre"><h2>Şifre Değiştir</h2>
<form class="fg pform" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_sifre"><?php wp_nonce_field('yr_sifre','yr_n');?>
<input type="text" name="username" value="<?php echo esc_attr($u->user_login);?>" autocomplete="username" hidden>
<label>Mevcut şifre<input type="password" name="eski" required autocomplete="current-password"></label>
<label>Yeni şifre<input type="password" name="yeni" required minlength="8" autocomplete="new-password"></label>
<label>Yeni şifre (tekrar)<input type="password" name="yeni2" required minlength="8" autocomplete="new-password"></label>
<button class="btn" type="submit">Şifreyi Değiştir</button></form></div>
<?php if(yr_is_member()&&count((array)$u->roles)===1):?>
<div class="box danger" id="hesap"><h2>Hesabı Sil</h2><p class="note">Hesabın, listelerin ve profil fotoğrafın kalıcı olarak silinir. Bu işlem geri alınamaz.</p>
<form class="fg pform" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" onsubmit="return confirm('Hesabın kalıcı olarak silinecek. Emin misin?')"><input type="hidden" name="action" value="yr_hesap_sil"><?php wp_nonce_field('yr_hesap_sil','yr_n');?>
<label>Şifren<input type="password" name="sifre" required autocomplete="current-password"></label>
<label class="chk"><input type="checkbox" name="onay" value="1" required> <span>Hesabımın kalıcı olarak silineceğini anlıyorum.</span></label>
<button class="btn" type="submit">Hesabımı Sil</button></form></div>
<?php endif;?>
</div></div>
<?php endif;?>
<?php get_footer();
