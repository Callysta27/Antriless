function bukaModal(id) {
  document.getElementById(id).style.display = 'flex';
}

function tutupModal(id) {
  document.getElementById(id).style.display = 'none';
}

function bukaModalEdit(id, nama, harga, stok, kategori, status) {
    document.getElementById('edit_id').value       = id;
    document.getElementById('edit_nama').value     = nama;
    document.getElementById('edit_harga').value    = harga;
    document.getElementById('edit_stok').value     = stok;
    document.getElementById('edit_kategori').value = kategori;
    document.getElementById('edit_status').value = status;
    document.getElementById('previewEdit').style.display = 'none';
    document.getElementById('placeholderEdit').style.display = '';
    document.getElementById('fotoEdit').value = '';

    bukaModal('modalEdit');
}