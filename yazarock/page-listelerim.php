<?php if(!defined('ABSPATH'))exit; get_header();?>
<div class="ph"><h1>Listelerim</h1><p>"Benim 10 favori albümüm" gibi kendi listeni oluştur. Listeler editör onayından sonra yayımlanır.</p></div>
<div class="box" id="liste"><?php if(!is_user_logged_in()):?>
<p>Liste oluşturmak için giriş yapmalısın.</p><div class="btnrow"><a class="btn" href="<?php echo esc_url(yr_page_url('giris'));?>">Giriş Yap</a><a class="btn alt" href="<?php echo esc_url(yr_page_url('uye-ol'));?>">Üye Ol</a></div>
<?php else:yr_flash('yr_l',['tamam'=>['Listen alındı; onaylandıktan sonra yayımlanacak.','ok'],'silindi'=>['Liste silindi.','ok'],'gecersiz'=>['Başlık en az 3 karakter, liste en az 3 madde olmalı.','err'],'limit'=>['En fazla 10 liste oluşturabilirsin.','err'],'bekle'=>['Biraz bekleyip tekrar dene.','err'],'guvenlik'=>['Güvenlik doğrulaması başarısız; sayfayı yenile.','err'],'onay'=>['Liste oluşturmak için önce e-posta adresini onaylamalısın. Onay bağlantısını panelinden tekrar isteyebilirsin.','err']]);?>
<h2>Yeni Liste</h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="yr_liste"><?php wp_nonce_field('yr_liste','yr_n');?>
<label>Liste başlığı<input name="baslik" required minlength="3" maxlength="80" placeholder="Benim 10 favori albümüm"></label>
<?php for($i=1;$i<=10;$i++)echo '<label>'.$i.'. madde'.($i<=3?' *':'').'<input name="m[]" maxlength="100"'.($i<=3?' required':'').'></label>';?>
<p class="note">En az 3 madde gerekli. Listen adınla birlikte yayımlanır. Nefret söylemi, reklam ve bağlantı içeren listeler yayımlanmaz.</p>
<?php yr_ts_widget();?><button class="btn" type="submit">Listeyi Gönder</button></form>
<h2 style="margin-top:24px">Gönderdiklerim</h2><ul class="plist">
<?php $L=get_posts(['post_type'=>'liste','post_status'=>['publish','pending'],'author'=>get_current_user_id(),'numberposts'=>20]);
foreach($L as $p):?><li><span><?php echo $p->post_status==='publish'?'<a href="'.esc_url(get_permalink($p)).'">'.esc_html($p->post_title).'</a>':esc_html($p->post_title);?> <span class="pill"><?php echo $p->post_status==='publish'?'Yayında':'Onay bekliyor';?></span></span>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" onsubmit="return confirm('Liste silinsin mi?')"><input type="hidden" name="action" value="yr_liste_sil"><input type="hidden" name="id" value="<?php echo (int)$p->ID;?>"><?php wp_nonce_field('yr_liste_sil'.$p->ID,'yr_n');?><button class="btn alt" type="submit">Sil</button></form></li>
<?php endforeach;if(!$L)echo '<li>Henüz listen yok.</li>';?></ul>
<p><a class="btn alt" href="<?php echo esc_url(wp_logout_url(home_url('/')));?>">Çıkış</a></p>
<?php endif;?></div>
<?php get_footer();