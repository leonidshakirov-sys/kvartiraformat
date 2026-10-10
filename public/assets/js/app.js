(function(){
  const qs = new URLSearchParams(location.search);
  const utms = ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'];
  document.querySelectorAll('[data-nav-toggle]').forEach(btn=>btn.addEventListener('click',()=>document.querySelector('[data-nav]')?.classList.toggle('is-open')));
  document.querySelectorAll('form[data-lead-form]').forEach(form=>{
    utms.forEach(k=>form.querySelectorAll(`[name="${k}"]`).forEach(i=>i.value=qs.get(k)||''));
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const result = form.querySelector('[data-form-result]');
      if(result) result.textContent='Отправляем заявку...';
      const btn = form.querySelector('button[type="submit"]');
      if(btn) btn.disabled=true;
      try{
        const res = await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'fetch'}});
        const json = await res.json();
        if(!json.ok){throw new Error((json.errors||['Ошибка отправки']).join(' '));}
        if(result) result.textContent='Заявка сохранена. Мы свяжемся с вами для уточнения расчёта.';
        form.reset();
        goal('lead_submit_success');
      }catch(err){ if(result) result.textContent=err.message; }
      finally{ if(btn) btn.disabled=false; }
    });
  });
  document.querySelectorAll('[data-calculator]').forEach(form=>{
    const out=form.querySelector('[data-estimate]');
    const update=async()=>{
      if(!out) return;
      const fd=new FormData(form);
      try{const r=await fetch('/lead/estimate',{method:'POST',body:fd}); const j=await r.json(); out.textContent=j.estimate||'Получите индивидуальный расчёт стоимости ремонта';}catch(e){}
    };
    form.addEventListener('change',()=>{goal('calculator_start'); update();});
    form.addEventListener('input',()=>update());
  });
  document.querySelectorAll('[data-before-after] input').forEach(input=>input.addEventListener('input',e=>{const wrap=e.target.closest('[data-before-after]'); if(wrap) wrap.style.gridTemplateColumns=e.target.value+'% 1fr';}));
  document.querySelectorAll('[data-goal]').forEach(el=>el.addEventListener('click',()=>goal(el.dataset.goal)));
  function goal(name){
    if(!name) return;
    if(window.ym && window.KF_METRIKA_ID){ try{ window.ym(window.KF_METRIKA_ID,'reachGoal',name); }catch(e){} }
    window.dispatchEvent(new CustomEvent('kf_goal',{detail:{name}}));
  }
})();
