(function(){
document.querySelectorAll('[data-filter]').forEach(function(box){
var st={},items=[].slice.call(box.querySelectorAll('.fi')),q='',empty=box.querySelector('.empty');
function run(){var n=0;items.forEach(function(el){var ok=Object.keys(st).every(function(k){return st[k]==='all'||el.dataset[k]===st[k]})&&(!q||el.textContent.toLowerCase().indexOf(q)>-1);el.hidden=!ok;if(ok)n++});if(empty)empty.hidden=n>0}
box.querySelectorAll('.tabs[data-k]').forEach(function(g){g.addEventListener('click',function(e){var b=e.target.closest('button');if(!b)return;g.querySelectorAll('button').forEach(function(x){x.setAttribute('aria-pressed',x===b)});st[g.dataset.k]=b.dataset.v;run()})});
box.querySelectorAll('select[data-k]').forEach(function(s){s.addEventListener('change',function(){st[s.dataset.k]=s.value;run()})});
var i=box.querySelector('[data-search]');if(i)i.addEventListener('input',function(){q=i.value.toLowerCase();run()});
});
var kq=document.getElementById('kq'),kn=document.getElementById('knext');
if(kq&&kn){var Q=JSON.parse(kq.dataset.q),i=Math.floor(Date.now()/864e5);kn.onclick=function(){i++;kq.textContent='\u201c'+Q[i%Q.length]+'\u201d'}}
var r=document.querySelector('.rp i');
if(r)addEventListener('scroll',function(){var d=document.documentElement;r.style.width=(d.scrollTop/((d.scrollHeight-d.clientHeight)||1)*100)+'%'},{passive:true});
if(document.cookie.indexOf('yr_ab=1')>-1)document.querySelectorAll('.nl-box').forEach(function(x){x.remove()});
var ck=document.getElementById('ckb'),ok=document.getElementById('ckok');if(ck&&document.cookie.indexOf('yr_ck=')<0)ck.hidden=false;if(ok)ok.onclick=function(){document.cookie='yr_ck=1;max-age=31536000;path=/;SameSite=Lax'+(location.protocol==='https:'?';Secure':'');ck.hidden=true};

document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('.emb button');if(!b)return;var w=b.parentNode,f=document.createElement('iframe');f.src=w.getAttribute('data-src');f.loading='lazy';f.title=w.getAttribute('data-t')||'Harici içerik';f.allow='autoplay; encrypted-media; fullscreen; picture-in-picture';f.referrerPolicy='strict-origin-when-cross-origin';f.setAttribute('allowfullscreen','');var h=w.getAttribute('data-h');if(h)f.style.height=h+'px';w.innerHTML='';w.className+=' on';w.appendChild(f)});
document.addEventListener('click',function(e){var i=e.target;if(!i.matches||!i.matches('.content .wp-block-gallery img,.content .wp-block-image img'))return;var l=document.createElement('div');l.className='lb';var m=document.createElement('img');m.src=i.currentSrc||i.src;m.alt=i.alt||'';l.appendChild(m);var c=function(){l.remove();document.removeEventListener('keydown',k)},k=function(ev){if(ev.key==='Escape')c()};l.onclick=c;document.addEventListener('keydown',k);document.body.appendChild(l)});
if('serviceWorker' in navigator&&window.YR_SW&&location.protocol==='https:')navigator.serviceWorker.register(window.YR_SW).catch(function(){});

(function(){var h=document.querySelector('[data-hero]');if(!h)return;var S=h.querySelectorAll('.sl'),D=h.querySelectorAll('.hd'),n=S.length;if(n<2)return;
var i=0,t=null,paused=false,hover=false,rm=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches,pp=h.querySelector('.hp-pp');
function go(k){i=(k+n)%n;S.forEach(function(s,j){var on=j===i;s.classList.toggle('on',on);s.setAttribute('aria-hidden',on?'false':'true');var a=s.querySelector('a');if(a)a.tabIndex=on?0:-1});D.forEach(function(d,j){d.classList.toggle('on',j===i)})}
function stop(){if(t){clearInterval(t);t=null}}
function start(){stop();if(!paused&&!hover)t=setInterval(function(){go(i+1)},5000)}
h.querySelector('.hp-prev').onclick=function(){go(i-1);start()};
h.querySelector('.hp-next').onclick=function(){go(i+1);start()};
D.forEach(function(d,j){d.onclick=function(){go(j);start()}});
if(pp)pp.onclick=function(){paused=!paused;pp.textContent=paused?'▶':'❚❚';pp.setAttribute('aria-label',paused?'Otomatik geçişi başlat':'Otomatik geçişi durdur');start()};
h.addEventListener('mouseenter',function(){hover=true;stop()});h.addEventListener('mouseleave',function(){hover=false;start()});
h.addEventListener('focusin',function(){hover=true;stop()});h.addEventListener('focusout',function(){hover=false;start()});
document.addEventListener('visibilitychange',function(){document.hidden?stop():start()});
if(rm){paused=true;if(pp){pp.textContent='▶';pp.setAttribute('aria-label','Otomatik geçişi başlat')}}else start();
})();
})();
