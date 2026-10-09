document.addEventListener('DOMContentLoaded',()=>{
 const menu=document.querySelector('.mobile-menu');const nav=document.querySelector('.navigation');
 menu?.addEventListener('click',()=>{let visible=nav.classList.toggle('open');menu.setAttribute('aria-expanded',String(visible));menu.textContent=visible?'×':'☰';});
 const input=document.querySelector('#season-search');if(input){const items=[...document.querySelectorAll('[data-search]')];const empty=document.querySelector('.archive-no-results');input.addEventListener('input',()=>{const term=input.value.toLocaleLowerCase('cs').trim();let shown=0;items.forEach(el=>{let ok=el.dataset.search.includes(term);el.hidden=!ok;if(ok)shown++;});if(empty)empty.hidden=shown!==0;});}
 // Upgrade historically authored tables without altering their text or results.
 document.querySelectorAll('.archive-reader, .history-prose').forEach(reader=>{
   reader.querySelectorAll('table').forEach(table=>{
     const outer=document.createElement('div');outer.className='historical-table-scroll';table.parentNode.insertBefore(outer,table);outer.appendChild(table);
   });
   const toc=reader.previousElementSibling;
   if(toc?.classList.contains('archive-toc')){
     const heads=[...reader.querySelectorAll('h2,h3')].filter(h=>h.textContent.trim().length>2).slice(0,40);
     if(heads.length>1){const title=document.createElement('strong');title.textContent='V TOMTO ROČNÍKU';toc.append(title);
       heads.forEach((head,i)=>{head.id='kapitola-'+i;const a=document.createElement('a');a.href='#'+head.id;a.textContent=head.textContent.trim();toc.append(a);});toc.hidden=false;}
   }
 });
 const toggles=document.querySelectorAll('[data-confirm]');toggles.forEach(el=>el.addEventListener('click',event=>{if(!confirm(el.dataset.confirm||'Opravdu pokračovat?'))event.preventDefault();}));
});
