<?php if(!defined('ABSPATH'))exit; get_header();
$P=yr_parca();$B=yr_bolumle($P['bloklar']);?>
<section class="gt-hero">
<div class="gt-in">
<?php if($P['ust']!=='')echo '<span class="pill">'.wp_kses_post($P['ust']).'</span>';?>
<h1><?php echo esc_html($P['baslik']!==''?$P['baslik']:'Grubunu Tanıt');?></h1>
<?php if($P['giris']!=='')echo '<p class="gt-lead">'.wp_kses_post($P['giris']).'</p>';
if($B['liste']){echo '<ol class="gt-steps">';$i=0;foreach(yr_liste_maddeleri($B['liste']) as $m){$i++;echo '<li><b>'.$i.'</b><span>'.($m[0]!==''?'<strong>'.esc_html($m[0]).'</strong>':'').wp_kses_post($m[1]).'</span></li>';}echo '</ol>';}?>
</div></section>
<?php $ek=yr_bloklar_html(array_merge($B['once'],$B['sonra']));if(yr_dolu($ek))echo '<div class="box entry" style="margin:22px 0"><div class="content">'.$ek.'</div></div>';?>
<div class="gt-grid">
<div class="box" id="basvuru">
<?php yr_flash('yr_g',['tamam'=>['Başvurun bize ulaştı, teşekkürler! E-posta adresine bir onay mesajı gönderdik. Uygun bulduğumuzda bu adresten sana yazacağız.','ok'],'gecersiz'=>['Lütfen zorunlu alanları kontrol et: grup adı, şehir, tür, en az 80 karakterlik hikâye, en az bir dinleme bağlantısı, ad, e-posta ve iki onay kutusu.','err'],'tekrar'=>['Bu grup için zaten değerlendirmede olan bir başvuru var. Yeni bir şey eklemek istersen info@yazarock.com adresine yazabilirsin.','err'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'limit'=>['Bugün bu bağlantıdan çok fazla başvuru yapıldı; yarın tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenileyip tekrar dene.','err'],'hata'=>['Bir sorun oluştu, tekrar dene.','err']]);
if(($_GET['yr_g']??'')==='tamam'):?>
<div class="gt-ok"><?php echo yr_ikon('heart');?><h2>Sahnede görüşmek üzere!</h2><p>Bu arada sitede tanıttığımız diğer gruplara göz atabilirsin.</p><div class="btnrow" style="justify-content:center"><a class="btn" href="<?php echo esc_url(get_post_type_archive_link('grup'));?>">Gruplar</a><a class="btn alt" href="<?php echo esc_url(remove_query_arg('yr_g'));?>">Başka bir grup için başvur</a></div></div>
<?php else:?>
<form class="fg pform gtf" id="gtform" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_grup_basvuru"><?php wp_nonce_field('yr_gb','yr_n');?><input class="hp" tabindex="-1" autocomplete="off" name="website">
<fieldset><legend><span>1</span>Grup</legend>
<div class="g2"><label>Grup adı *<input name="grup" required minlength="2" maxlength="80"></label>
<label>Şehir *<input name="sehir" list="gt-sehir" required minlength="2" maxlength="40" placeholder="ör. İzmir"></label></div>
<datalist id="gt-sehir"><?php foreach(yr_gb_sehirler() as $s)echo '<option value="'.esc_attr($s).'">';?></datalist>
<div class="g2"><label>Tür *<input name="tur" required minlength="2" maxlength="80" placeholder="ör. Stoner rock, Thrash metal"></label>
<label>Kuruluş yılı<input name="yil" inputmode="numeric" pattern="(19[6-9][0-9]|20[0-9][0-9])" maxlength="4" placeholder="ör. 2019"></label></div>
<label>Üyeler<textarea name="uyeler" rows="3" maxlength="600" placeholder="Her satıra bir kişi: Ad – enstrüman"></textarea></label>
<label>Grubun hikâyesi * <small>(<span id="gtcnt">0</span>/2000, en az 80)</small><textarea name="hikaye" id="gthk" rows="6" required minlength="80" maxlength="2000" placeholder="Nasıl bir araya geldiniz, neyi anlatıyorsunuz, sizi etkileyen gruplar kimler, şimdiye kadar neler yaptınız?"></textarea></label>
</fieldset>
<fieldset><legend><span>2</span>Müzik</legend>
<p class="note">En az bir bağlantı zorunlu. Kendi bestelerinizin yer aldığı kayıtları paylaşın.</p>
<div class="g2"><?php foreach(yr_gb_linkler() as $k=>$l)echo '<label>'.esc_html($l).'<input type="url" name="l_'.esc_attr($k).'" placeholder="https://"></label>';?></div>
<div class="g2"><label>Son çıkış (albüm, EP ya da tekli)<input name="son" maxlength="120" placeholder="ör. Karanlık Şehir EP (2025)"></label>
<label>Yaklaşan konser<input name="konser" maxlength="200" placeholder="ör. 14 Kasım, İzmir, Mekân adı"></label></div>
<label>Grup fotoğrafı <small>(isteğe bağlı · JPG, PNG, WEBP · en fazla 5 MB)</small><input type="file" name="foto" id="gtfoto" accept="image/jpeg,image/png,image/webp"></label>
<p class="note">Bizden ne istersiniz?</p>
<div class="gt-chips"><?php foreach(yr_gb_istekler() as $k=>$l)echo '<label class="chip"><input type="checkbox" name="istek[]" value="'.esc_attr($k).'"'.($k==='tanitim'?' checked':'').'><span>'.esc_html($l).'</span></label>';?></div>
</fieldset>
<fieldset><legend><span>3</span>İletişim</legend>
<div class="g2"><label>Adın soyadın *<input name="ad" required minlength="2" maxlength="60" autocomplete="name"></label>
<label>Gruptaki rolün<input name="rol" maxlength="40" placeholder="ör. Vokal, menajer"></label></div>
<div class="g2"><label>E-posta *<input name="email" type="email" required autocomplete="email"></label>
<label>Telefon <small>(isteğe bağlı)</small><input name="tel" type="tel" maxlength="25" autocomplete="tel"></label></div>
<p class="note">İletişim bilgilerin yayımlanmaz; yalnızca sana ulaşmak için kullanılır.</p>
<label class="chk"><input type="checkbox" name="insan" value="1" required> <span>Müziğimizdeki vokal, enstrüman ve besteler insan emeğiyle yapılmıştır; yapay zekâ ile üretilmiş müzik içermez.</span></label>
<label class="chk"><input type="checkbox" name="hak" value="1" required> <span>Paylaştığım müziğin ve fotoğrafın haklarına sahip olduğumu (ya da grubun iznini aldığımı) ve bunların Yazarock'ta grubun tanıtımı amacıyla kullanılabileceğini kabul ediyorum.</span></label>
<label class="chk"><input type="checkbox" name="kvkk" value="1" required> <span><a href="<?php echo esc_url(yr_page_url('kvkk-aydinlatma-metni'));?>" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum; bilgilerimin başvurunun değerlendirilmesi ve benimle iletişim kurulması için işlenmesini kabul ediyorum.</span></label>
</fieldset>
<?php yr_ts_widget();?>
<button class="btn gt-send" type="submit"><?php echo yr_ikon('plus');?>Başvuruyu Gönder</button>
</form>
<?php endif;?>
</div>
<aside class="gt-side">
<?php if($B['sss']){echo '<div class="box">'.($B['ara']!==''?'<h2>'.esc_html($B['ara']).'</h2>':'');foreach($B['sss'] as $k=>$d)echo $k?$d:preg_replace('#<details#','<details open',$d,1);echo '</div>';}
echo '<div class="box"><h2>Röportaj ister misin?</h2><p style="margin:0 0 12px;color:#d6cdcd">Garajdan Sahneye serisinde 10 soruyu grup olarak yanıtla, röportajın Yazarock\'ta yayımlansın.</p><a class="btn" href="'.esc_url(yr_page_url('garajdan-sahneye')).'">Soruları yanıtla</a></div>';
if($B['alinti']!=='')echo '<div class="box gt-q">'.yr_ikon('cmt').'<div>'.wp_kses_post($B['alinti']).'</div></div>';?>
</aside>
</div>
<?php get_footer();
