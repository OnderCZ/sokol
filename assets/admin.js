document.addEventListener('DOMContentLoaded',()=>{
 const sidebar=document.querySelector('.admin-sidebar');
 document.querySelector('.admin-nav-toggle')?.addEventListener('click',()=>sidebar?.classList.toggle('open'));
 document.querySelectorAll('.rich-wrap').forEach(wrap=>{
   const surface=wrap.querySelector('.rich-surface');const source=wrap.querySelector('textarea[data-rich-source]');
   wrap.querySelectorAll('[data-cmd]').forEach(btn=>btn.addEventListener('click',()=>{surface.focus();document.execCommand(btn.dataset.cmd,false,btn.dataset.value||null);}));
   wrap.querySelector('[data-link]')?.addEventListener('click',()=>{let url=prompt('Adresa odkazu (https://...)');if(url && /^https?:\/\//i.test(url)){surface.focus();document.execCommand('createLink',false,url);}});
   surface.closest('form')?.addEventListener('submit',()=>{source.value=surface.innerHTML;});
 });
 document.querySelectorAll('.copy-url').forEach(btn=>btn.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(btn.dataset.url);btn.textContent='Zkopírováno ✓';}catch(e){prompt('Zkopírujte adresu:',btn.dataset.url);}}));
});
