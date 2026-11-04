(()=>{
 const url=window.CBTLecturerMonitorUrl;
 if(!url)return;
 let busy=false;
 let firstRun=true;

 const container=()=>{
   let el=document.getElementById('lecturerToastContainer');
   if(!el){
     el=document.createElement('div');
     el.id='lecturerToastContainer';
     el.className='lecturer-toast-container';
     document.body.appendChild(el);
   }
   return el;
 };

 const showToast=event=>{
   const wrap=container();
   const toast=document.createElement('div');
   const isLogout=event.event_type==='logout';
   toast.className='lecturer-toast '+(event.terminated&&!isLogout?'critical':isLogout?'warning':'warning');
   const title=isLogout?'Logout saat ujian terdeteksi':event.terminated?'Ujian dihentikan otomatis':'Pelanggaran tab terdeteksi';
   const detail=isLogout
     ? `${event.student} logout saat ujian ${event.exam} - Ujian ke-${event.attempt_number}. Tercatat logout ke-${event.count} pada pukul ${event.occurred_at}.`
     : event.terminated
     ? `${event.student} mencapai pelanggaran 3/3 pada ${event.exam} - Ujian ke-${event.attempt_number}. Ujian otomatis selesai dan akun mahasiswa telah logout.`
     : `${event.student} meninggalkan tab pada ${event.exam} - Ujian ke-${event.attempt_number} (${event.count}/3) pukul ${event.occurred_at}.`;

   toast.innerHTML=`
     <div class="lecturer-toast-icon">!</div>
     <div class="lecturer-toast-body">
       <strong>${escapeHtml(title)}</strong>
       <p>${escapeHtml(detail)}</p>
       <a href="${event.results_url}">Lihat hasil & riwayat</a>
     </div>
     <button type="button" class="lecturer-toast-close" aria-label="Tutup">×</button>`;
   toast.querySelector('.lecturer-toast-close').addEventListener('click',()=>toast.remove());
   wrap.appendChild(toast);
   setTimeout(()=>toast.remove(),event.terminated&&!isLogout?14000:9000);
 };

 const escapeHtml=value=>String(value??'').replace(/[&<>'"]/g,c=>({
   '&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'
 }[c]));

 const poll=async()=>{
   if(busy||document.hidden)return;
   busy=true;
   try{
     const res=await fetch(url,{headers:{'Accept':'application/json'},cache:'no-store'});
     if(!res.ok)return;
     const data=await res.json();
     (data.events||[]).forEach(showToast);
   }catch(e){
   }finally{
     busy=false;
     firstRun=false;
   }
 };

 poll();
 setInterval(poll,4000);
 document.addEventListener('visibilitychange',()=>{ if(!document.hidden)poll(); });
})();
