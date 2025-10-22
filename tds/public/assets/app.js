window.toggleNav = function(){ document.body.classList.toggle('nav-open'); }
window.closeNav = function(){ document.body.classList.remove('nav-open'); }

window.showCalendar = function(id){
  const el = document.getElementById(id);
  if(el && el.showPicker){ try{ el.showPicker(); }catch(e){ el.focus(); } }
  else if(el){ el.focus(); }
}
