(()=>{
 const shell=document.querySelector('.exam-shell'); if(!shell)return;
 const deadline=parseInt(shell.dataset.deadline,10)*1000;
 const slides=[...document.querySelectorAll('.question-slide')];
 const nums=[...document.querySelectorAll('.num-btn')];
 let current=0;
 let sent=false;
 let terminated=false;
 let recordingViolation=false;
 let lastViolationAt=0;
 let deferredViolation=null;
 const pendingSaves=new Set();

 const show=i=>{
   if(i<0||i>=slides.length)return;
   slides.forEach(x=>x.classList.remove('active'));
   nums.forEach(x=>x.classList.remove('current'));
   slides[i].classList.add('active');
   nums[i]?.classList.add('current');
   current=i;
   window.scrollTo({top:0,behavior:'smooth'});
 };
 nums.forEach((b,i)=>b.addEventListener('click',()=>show(i)));
 document.querySelectorAll('.next').forEach(b=>b.addEventListener('click',()=>show(current+1)));
 document.querySelectorAll('.prev').forEach(b=>b.addEventListener('click',()=>show(current-1)));
 show(0);

 const csrf=document.querySelector('meta[name="csrf-token"]').content;
 const attempt=shell.dataset.attempt;
 const violationUrl=shell.dataset.violationUrl;
 const loginUrl=shell.dataset.loginUrl;
 const counter=document.getElementById('violationCounter');

 document.querySelectorAll('.answer-option input').forEach(input=>input.addEventListener('change',()=>{
   const q=input.dataset.question;
   const status=document.getElementById('save-'+q);
   status.textContent='Menyimpan...';
   status.classList.remove('error');

   const savePromise=fetch(`/student/attempts/${attempt}/answer`,{
     method:'POST',
     headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
     body:JSON.stringify({question_id:parseInt(q),option_id:parseInt(input.value)})
   }).then(async res=>{
     if(!res.ok) throw new Error('save failed');
     status.textContent='Tersimpan';
     const slide=input.closest('.question-slide');
     const idx=parseInt(slide.dataset.index);
     nums[idx].classList.add('answered');
   }).catch(()=>{
     status.textContent='Gagal menyimpan';
     status.classList.add('error');
     throw new Error('save failed');
   }).finally(()=>pendingSaves.delete(savePromise));

   pendingSaves.add(savePromise);
 }));

 const submit=async()=>{
   if(sent||terminated)return;
   sent=true;
   const confirmBtn=document.getElementById('confirmFinish');
   if(confirmBtn){ confirmBtn.disabled=true; confirmBtn.textContent='Menyimpan jawaban...'; }
   try {
     if(pendingSaves.size) await Promise.allSettled([...pendingSaves]);
   } finally {
     document.getElementById('submitExam').submit();
   }
 };

 const timer=document.getElementById('timer');
 const tick=()=>{
   const left=Math.max(0,Math.floor((deadline-Date.now())/1000));
   const h=String(Math.floor(left/3600)).padStart(2,'0');
   const m=String(Math.floor((left%3600)/60)).padStart(2,'0');
   const s=String(left%60).padStart(2,'0');
   timer.textContent=`${h}:${m}:${s}`;
   if(left<=300)timer.classList.add('danger-text');
   if(left<=0)submit();
 };
 tick();
 setInterval(tick,1000);

 const modal=document.getElementById('confirmModal');
 document.getElementById('finishBtn')?.addEventListener('click',()=>modal.classList.add('show'));
 document.getElementById('cancelFinish')?.addEventListener('click',()=>modal.classList.remove('show'));
 document.getElementById('confirmFinish')?.addEventListener('click',submit);

 const violationModal=document.getElementById('violationModal');
 const violationTitle=document.getElementById('violationTitle');
 const violationMessage=document.getElementById('violationMessage');
 const violationProgress=document.getElementById('violationProgress');
 const closeViolation=document.getElementById('closeViolation');

 const renderViolation=data=>{
   const count=Math.min(3,parseInt(data.count||0,10));
   if(counter) counter.textContent=`${count}/3`;
   if(!violationModal)return;

   violationTitle.textContent=data.terminated?'Ujian Dihentikan Otomatis':`Peringatan ${count} dari 3`;
   violationMessage.textContent=data.message||'Anda terdeteksi meninggalkan tab ujian.';
   violationProgress.style.width=`${(count/3)*100}%`;
   violationProgress.classList.toggle('danger',count>=3);

   if(data.terminated){
     terminated=true;
     closeViolation.disabled=true;
     closeViolation.textContent='Mengakhiri sesi...';
   }
   violationModal.classList.add('show');
 };

 const handleViolationResponse=data=>{
   if(data.terminated){
     renderViolation(data);
     setTimeout(()=>{ window.location.href=data.redirect||loginUrl; },1400);
     return;
   }
   if(document.hidden){
     deferredViolation=data;
   }else{
     renderViolation(data);
   }
 };

 const recordViolation=async()=>{
   if(sent||terminated||recordingViolation)return;
   const now=Date.now();
   if(now-lastViolationAt<1500)return;
   lastViolationAt=now;
   recordingViolation=true;

   try{
     const response=await fetch(violationUrl,{
       method:'POST',
       headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
       body:JSON.stringify({event_type:'visibility_hidden'}),
       keepalive:true
     });
     const data=await response.json().catch(()=>({}));

     if(response.status===401||response.status===419){
       terminated=true;
       window.location.href=loginUrl;
       return;
     }
     if(data.redirect&&data.submitted){
       sent=true;
       window.location.href=data.redirect;
       return;
     }
     if(response.ok) handleViolationResponse(data);
   }catch(e){
     // Kegagalan jaringan tidak membuat hitungan lokal palsu. Server tetap menjadi sumber hitungan.
   }finally{
     recordingViolation=false;
   }
 };

 document.addEventListener('visibilitychange',()=>{
   if(document.hidden){
     recordViolation();
   }else if(deferredViolation){
     const data=deferredViolation;
     deferredViolation=null;
     renderViolation(data);
   }
 });

 closeViolation?.addEventListener('click',()=>{
   if(!terminated) violationModal.classList.remove('show');
 });
})();
