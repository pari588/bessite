// Utility to format money
function fmt(n){ return '₹ ' + Number(n).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }

async function postJSON(url, data){
  const fd = new FormData();
  Object.entries(data).forEach(([k,v])=> fd.append(k, v));
  const res = await fetch(url, { method:'POST', body: fd });
  return res.json();
}

// ------------- Invoices -------------
async function refreshInvoices(){
  const res = await fetch('/tds/api/list_invoices.php'); const data = await res.json();
  if(!data.ok) return;
  const tbody = document.getElementById('invoice-tbody'); if(!tbody) return;
  tbody.innerHTML = '';
  data.rows.forEach(r=>{
    const tr = document.createElement('tr');
    tr.dataset.id = r.id;
    tr.dataset.invoice = JSON.stringify(r);
    tr.innerHTML = `
      <td>${r.invoice_date}</td>
      <td>${r.vname}</td>
      <td class="mono">${r.invoice_no}</td>
      <td>${r.section_code}</td>
      <td>${fmt(r.base_amount)}</td>
      <td>${fmt(r.total_tds)}</td>
      <td>${r.fy}/${r.quarter}</td>
      <td>
        <md-text-button onclick="openInvEdit(this)"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">edit</span> Edit</md-text-button>
        <md-text-button onclick="deleteInv(${r.id})"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">delete</span> Delete</md-text-button>
      </td>`;
    tbody.appendChild(tr);
  });
}

async function addInvoice(e){
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  const resp = await postJSON('/tds/api/add_invoice.php', data);
  if(resp.ok){ form.reset(); await refreshInvoices(); }
  else alert('Add failed: '+(resp.msg||''));
}

async function updateInvoice(e){
  e.preventDefault();
  const data = Object.fromEntries(new FormData(document.getElementById('invEditForm')).entries());
  const resp = await postJSON('/tds/api/update_invoice.php', data);
  if(resp.ok){ document.getElementById('modal').style.display='none'; await refreshInvoices(); }
  else alert('Update failed: '+(resp.msg||''));
}

async function deleteInv(id){
  if(!confirm('Delete this invoice?')) return;
  const resp = await postJSON('/tds/api/delete_invoice.php', {id});
  if(resp.ok){ await refreshInvoices(); } else alert('Delete failed: '+(resp.msg||''));
}

window.openInvEdit = function(btn){
  const tr = btn.closest('tr'); const d = JSON.parse(tr.dataset.invoice);
  inv_id.value = d.id; inv_no.value = d.invoice_no; inv_date.value = d.invoice_date; inv_amt.value = d.base_amount;
  inv_sec.value = d.section_code; inv_rate.value = d.tds_rate;
  // vendor name (read-only display)
  const v = document.getElementById('inv_vendor'); if(v){ v.value = d.vname || ''; }
  document.getElementById('modal').style.display='flex';
};

window.closeModal = function(){ document.getElementById('modal').style.display='none'; }

// ------------- Challans -------------
async function refreshChallans(){
  const res = await fetch('/tds/api/list_challans.php'); const data = await res.json();
  if(!data.ok) return;
  const tbody = document.getElementById('challan-tbody'); if(!tbody) return;
  tbody.innerHTML = '';
  data.rows.forEach(r=>{
    const tr = document.createElement('tr');
    tr.dataset.id = r.id;
    tr.dataset.challan = JSON.stringify(r);
    tr.innerHTML = `
      <td>${r.bsr_code}</td>
      <td>${r.challan_date}</td>
      <td class="mono">${r.challan_serial_no}</td>
      <td>${fmt(r.amount_tds)}</td>
      <td>${r.fy}/${r.quarter}</td>
      <td>
        <md-text-button onclick="openChEdit(this)"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">edit</span> Edit</md-text-button>
        <md-text-button onclick="deleteCh(${r.id})"><span class="material-symbols-rounded" style="font-size:18px;vertical-align:-3px">delete</span> Delete</md-text-button>
      </td>`;
    tbody.appendChild(tr);
  });
}

async function addChallan(e){
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target).entries());
  const resp = await postJSON('/tds/api/add_challan.php', data);
  if(resp.ok){ e.target.reset(); await refreshChallans(); }
  else alert('Add failed: '+(resp.msg||''));
}

async function updateChallan(e){
  e.preventDefault();
  const data = Object.fromEntries(new FormData(document.getElementById('chEditForm')).entries());
  const resp = await postJSON('/tds/api/update_challan.php', data);
  if(resp.ok){ document.getElementById('cmodal').style.display='none'; await refreshChallans(); }
  else alert('Update failed: '+(resp.msg||''));
}

async function deleteCh(id){
  if(!confirm('Delete this challan?')) return;
  const resp = await postJSON('/tds/api/delete_challan.php', {id});
  if(resp.ok){ await refreshChallans(); } else alert('Delete failed: '+(resp.msg||''));
}

window.openChEdit = function(btn){
  const tr = btn.closest('tr'); const d = JSON.parse(tr.dataset.challan);
  ch_id.value=d.id; ch_bsr.value=d.bsr_code; ch_date.value=d.challan_date; ch_serial.value=d.challan_serial_no; ch_amt.value=d.amount_tds;
  document.getElementById('cmodal').style.display='flex';
};
window.closeCModal = function(){ document.getElementById('cmodal').style.display='none'; }

// ------------- Bind on load -------------
document.addEventListener('DOMContentLoaded', ()=>{
  document.getElementById('singleInvForm')?.addEventListener('submit', addInvoice);
  document.getElementById('invEditForm')?.addEventListener('submit', updateInvoice);
  document.getElementById('manChForm')?.addEventListener('submit', addChallan);
  document.getElementById('chEditForm')?.addEventListener('submit', updateChallan);
  // initial loads
  refreshInvoices?.(); refreshChallans?.();
});
