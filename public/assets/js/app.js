(function(){
  const qs = new URLSearchParams(location.search);
  const utms = ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'];
  const maxFiles = 5;
  const maxEach = 15 * 1024 * 1024;
  const maxTotal = 50 * 1024 * 1024;
  const allowed = ['jpg','jpeg','png','webp','pdf','doc','docx','xls','xlsx'];

  document.querySelectorAll('[data-nav-toggle]').forEach(btn=>btn.addEventListener('click',()=>document.querySelector('[data-nav]')?.classList.toggle('is-open')));
  document.querySelectorAll('[data-submenu-toggle]').forEach(btn=>btn.addEventListener('click',(e)=>{
    e.preventDefault();
    btn.closest('.nav-item')?.classList.toggle('is-open');
  }));

  document.querySelectorAll('[data-calculator]').forEach(form=>{
    const typeInput = form.querySelector('[data-lead-type]');
    const syncProperty = form.querySelector('[data-sync-property]');
    const syncRepair = form.querySelector('[data-sync-repair]');
    const syncArea = form.querySelector('[data-sync-area]');
    const applyMode = ()=>{
      const checked = form.querySelector('[name="calc_direction"]:checked');
      const mode = checked?.value || typeInput?.value || 'apartment';
      if(typeInput) typeInput.value = mode;
      form.dataset.calcMode = mode;
      form.querySelectorAll('[data-mode]').forEach(block=>{
        const on = block.getAttribute('data-mode') === mode;
        block.hidden = !on;
        block.querySelectorAll('input,select,textarea').forEach(el=>{
          el.disabled = !on;
        });
      });
      if(mode === 'apartment'){
        syncProperty && (syncProperty.value = form.querySelector('[name="apt_property_type"]:checked')?.value || '');
        syncRepair && (syncRepair.value = form.querySelector('[name="apt_repair_type"]:checked')?.value || '');
        syncArea && (syncArea.value = form.querySelector('[name="apt_area"]')?.value || '');
      } else if(mode === 'house'){
        syncProperty && (syncProperty.value = form.querySelector('[name="house_property_type"]:checked')?.value || '');
        syncRepair && (syncRepair.value = form.querySelector('[name="house_repair_type"]:checked')?.value || '');
        syncArea && (syncArea.value = form.querySelector('[name="house_area"]')?.value || '');
      } else {
        syncProperty && (syncProperty.value = form.querySelector('[name="com_property_type"]:checked')?.value || '');
        syncRepair && (syncRepair.value = form.querySelector('[name="premise_condition"]:checked')?.value || '');
        syncArea && (syncArea.value = form.querySelector('[name="com_area"]')?.value || '');
      }
    };
    form.querySelectorAll('input,select,textarea').forEach(el=>el.addEventListener('change', applyMode));
    form.addEventListener('input', applyMode);
    applyMode();
  });

  document.querySelectorAll('[data-file-attach]').forEach(wrap=>{
    const input = wrap.querySelector('[data-file-input]');
    const list = wrap.querySelector('[data-file-list]');
    const error = wrap.querySelector('[data-file-error]');
    if(!input) return;
    const showError = (text)=>{
      if(!error) return;
      error.hidden = !text;
      error.textContent = text || '';
    };
    const render = ()=>{
      const files = Array.from(input.files || []);
      if(!list) return;
      list.innerHTML = '';
      list.hidden = files.length === 0;
      files.forEach((file, index)=>{
        const li = document.createElement('li');
        li.innerHTML = '<span>' + file.name + ' · ' + Math.round(file.size/1024) + ' КБ</span>';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'file-remove';
        btn.textContent = 'Удалить';
        btn.addEventListener('click', ()=>{
          const dt = new DataTransfer();
          files.forEach((item, i)=>{ if(i !== index) dt.items.add(item); });
          input.files = dt.files;
          render();
        });
        li.appendChild(btn);
        list.appendChild(li);
      });
    };
    input.addEventListener('change', ()=>{
      const files = Array.from(input.files || []);
      if(files.length > maxFiles){
        showError('Можно прикрепить не больше ' + maxFiles + ' файлов.');
        input.value = '';
        render();
        return;
      }
      let total = 0;
      for(const file of files){
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if(!allowed.includes(ext)){
          showError('Формат ' + file.name + ' не поддерживается.');
          input.value = '';
          render();
          return;
        }
        if(file.size > maxEach){
          showError(file.name + ' больше 15 МБ.');
          input.value = '';
          render();
          return;
        }
        total += file.size;
      }
      if(total > maxTotal){
        showError('Общий размер файлов превышает 50 МБ.');
        input.value = '';
        render();
        return;
      }
      showError('');
      render();
    });
  });

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
        form.querySelectorAll('[data-file-list]').forEach(list=>{list.innerHTML=''; list.hidden=true;});
        form.querySelectorAll('[data-calculator]').forEach(()=>{});
        form.dispatchEvent(new Event('change'));
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
      try{const r=await fetch('/lead/estimate',{method:'POST',body:fd}); const j=await r.json(); out.textContent=j.estimate||'Индивидуальный расчёт';}catch(e){}
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
