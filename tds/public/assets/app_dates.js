(function(){
  function attachAutoPicker(root){
    const inputs = (root||document).querySelectorAll('input[type="date"]');
    inputs.forEach(el=>{
      if(el.dataset.autopicker==='1') return;
      const open = ()=>{ if(typeof el.showPicker==='function'){ try{ el.showPicker(); }catch(e){} } };
      el.addEventListener('focus', open);
      el.addEventListener('pointerdown', (e)=>{ // open immediately on click/touch
        // Only left click/touch
        if(e.pointerType==='mouse' && e.button!==0) return;
        setTimeout(open, 0);
      }, { capture:true });
      el.dataset.autopicker = '1';
    });
  }
  document.addEventListener('DOMContentLoaded', ()=>attachAutoPicker(document));
  // Expose in case forms are dynamically injected
  window.attachAutoPicker = attachAutoPicker;
})();