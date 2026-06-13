/**
 * SPA Engine (No-Load System)
 * Handles AJAX navigation and form submissions.
 */

$(document).ready(function() {
    
    // Konfigurasi default DataTables (agar sesuai preferensi)
    $.extend(true, $.fn.dataTable.defaults, {
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
        },
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
    });

    /**
     * Re-initialize script / plugin saat berganti halaman
     */
    function initPagePlugins() {
        // Init DataTables pada tabel dengan class .datatable
        if ($('.datatable').length) {
            $('.datatable').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable();
                }
            });
        }
    }

    // Jalankan init pertama kali halaman dimuat (jika ada plugin)
    initPagePlugins();

    /**
     * Helper: Menampilkan Bootstrap Toast
     */
    window.showToast = function(message, type = 'success') {
        const toastEl = document.getElementById('spaToast');
        const toastMsg = document.getElementById('spaToastMessage');
        const toast = new bootstrap.Toast(toastEl);
        
        toastMsg.textContent = message;
        
        // Ubah warna berdasarkan tipe
        if (type === 'success') {
            toastEl.classList.remove('bg-danger');
            toastEl.classList.add('bg-success');
        } else {
            toastEl.classList.remove('bg-success');
            toastEl.classList.add('bg-danger');
        }
        
        toast.show();
    };

    /**
     * 1. Intercept Navigation (Pjax)
     * Targetkan link sidebar atau link biasa yang mengarah ke ?hal=
     */
    $(document).on('click', 'a', function(e) {
        let href = $(this).attr('href');
        let target = $(this).attr('target');
        
        // Jika link tidak valid, ada target blank, atau mengarah ke ID eksternal
        if (!href || href === '#' || href.startsWith('javascript') || target === '_blank') return;
        
        // Jika link download atau logout, biarkan browser menangani
        if (href.includes('logout') || href.includes('delete') || $(this).hasClass('no-spa')) return;
        
        // Hanya tangkap link internal dengan '?hal=' atau link navigasi sidebar
        if (href.startsWith('?hal=') || $(this).hasClass('sidebar-link')) {
            e.preventDefault();
            
            // Tampilkan loading spinner (opsional, bisa diganti dengan animasi bar CSS)
            $('.page-content').css('opacity', '0.5');

            $.ajax({
                url: href,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && typeof response.html !== 'undefined') {
                        // Update konten HTML
                        $('.page-content').html(response.html).css('opacity', '1');
                        
                        // Update Title
                        document.title = response.title + " - App";
                        $('.page-heading h3').text(response.title);
                        
                        // Push history URL agar tombol back browser bekerja
                        window.history.pushState({"html": response.html, "title": response.title}, "", href);
                        
                        // Re-inisialisasi plugin (DataTables, dsb) di halaman baru
                        initPagePlugins();
                    } else {
                        // Jika bukan format JSON yang diharapkan, fallback load biasa
                        window.location.href = href;
                    }
                },
                error: function() {
                    // Fallback
                    window.location.href = href;
                }
            });
        }
    });

    /**
     * Handle Browser Back/Forward Button
     */
    window.onpopstate = function(e) {
        if (e.state && e.state.html) {
            $('.page-content').html(e.state.html);
            document.title = e.state.title;
            $('.page-heading h3').text(e.state.title);
            initPagePlugins();
        } else {
            window.location.reload();
        }
    };

    /**
     * Simpan referensi tombol submit yang diklik agar namanya ikut terkirim via AJAX
     */
    $(document).on('click', 'form.ajax-form button[type="submit"], form.ajax-form input[type="submit"]', function() {
        let btn = $(this);
        let form = btn.closest('form');
        form.data('clicked-btn-name', btn.attr('name'));
        form.data('clicked-btn-value', btn.val() || '1');
    });

    /**
     * 2. Intercept Form Submit (CRUD Ajax Form)
     * Targetkan form dengan class .ajax-form
     */
    $(document).on('submit', 'form.ajax-form', function(e) {
        e.preventDefault();
        
        let form = $(this);
        let url = form.attr('action');
        let formData = new FormData(this);
        
        // Tambahkan data tombol submit yang diklik ke dalam FormData
        let clickedBtnName = form.data('clicked-btn-name');
        if (clickedBtnName) {
            formData.append(clickedBtnName, form.data('clicked-btn-value'));
        }
        
        // Disable tombol submit & tambah tulisan loading
        let btnSubmit = form.find('button[type="submit"]:focus');
        if (btnSubmit.length === 0) {
            btnSubmit = form.find('button[type="submit"]').last();
        }
        let originalText = btnSubmit.html();
        btnSubmit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Kembalikan tombol submit
                btnSubmit.prop('disabled', false).html(originalText);
                
                // Tutup semua modal bootstrap yang sedang terbuka
                $('.modal').modal('hide');
                
                // Tampilkan Toast
                if (response && response.message) {
                    showToast(response.message, response.type);
                } else {
                    showToast('Operasi berhasil!', 'success');
                }
                
                // Jika ada instruksi redirect dari backend (seperti setelah login/register atau CRUD)
                if (response && response.redirect) {
                    // Bersihkan "../" atau awalan relative dari URL redirect
                    let targetUrl = response.redirect.replace(/^(\.\.\/)+/, '').replace(/^(\.\/)+/, '');
                    
                    // Cek apakah target redirect SAMA dengan halaman yang sedang kita buka (contoh: ?hal=users_user-management)
                    let currentSearch = window.location.search || window.location.search.replace('&', '?');
                    
                    // Jika beda (misal dari login.php ke index.php), lakukan redirect sungguhan
                    if (targetUrl !== currentSearch && targetUrl !== currentSearch.replace('?', '')) {
                        setTimeout(function() {
                            // Jika pindahnya antar modul SPA (pakai ?hal=), picu Pjax saja agar tidak reload full
                            if (targetUrl.startsWith('?hal=')) {
                                $('<a href="'+targetUrl+'" class="d-none no-spa-simulate"></a>').appendTo('body').trigger('click');
                            } else {
                                window.location.href = targetUrl;
                            }
                        }, 1000); // Jeda 1 detik agar toast terbaca
                        return;
                    }
                    // Jika sama (CRUD kembali ke halamannya sendiri), ABAIKAN dan biarkan Pjax merefresh tabel di bawah ini
                }
                
                // Refresh halaman konten yang sekarang (via Pjax) agar tabel terupdate
                let currentUrl = window.location.search; // get "?hal=..."
                if(currentUrl) {
                    $('.page-content').css('opacity', '0.5');
                    $.ajax({
                        url: currentUrl,
                        type: 'GET',
                        dataType: 'json',
                        success: function(res) {
                            if (res && typeof res.html !== 'undefined') {
                                $('.page-content').html(res.html).css('opacity', '1');
                                initPagePlugins();
                            }
                        }
                    });
                } else {
                    window.location.reload();
                }
            },
            error: function(xhr) {
                btnSubmit.prop('disabled', false).html(originalText);
                showToast('Terjadi kesalahan server.', 'error');
            }
        });
    });

    /**
     * 3. Intercept Delete Button (Konfirmasi dengan SweetAlert2)
     * Targetkan <a> tag dengan class .delete-btn atau yang punya onclick confirm
     */
    $(document).on('click', 'a.btn-danger[onclick*="confirm"], a.delete-btn', function(e) {
        e.preventDefault();
        let href = $(this).attr('href');
        
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tembak URL delete menggunakan Ajax POST/GET
                // Karena delete menggunakan $_GET['delete'], kita kirim GET
                $.ajax({
                    url: href,
                    type: 'GET',
                    success: function(response) {
                        if (response && response.message) {
                            showToast(response.message, response.type);
                        } else {
                            showToast('Data dihapus.', 'success');
                        }
                        
                        // Menangani redirect jika backend memintanya saat delete
                        if (response && response.redirect) {
                            let targetUrl = response.redirect.replace(/^(\.\.\/)+/, '').replace(/^(\.\/)+/, '');
                            let currentSearch = window.location.search || window.location.search.replace('&', '?');
                            
                            if (targetUrl !== currentSearch && targetUrl !== currentSearch.replace('?', '')) {
                                setTimeout(function() {
                                    if (targetUrl.startsWith('?hal=')) {
                                        $('<a href="'+targetUrl+'" class="d-none"></a>').appendTo('body').trigger('click');
                                    } else {
                                        window.location.href = targetUrl;
                                    }
                                }, 1000);
                                return;
                            }
                        }
                        
                        // Refresh halaman
                        let currentUrl = window.location.search;
                        if(currentUrl) {
                            $('.page-content').css('opacity', '0.5');
                            $.ajax({
                                url: currentUrl,
                                type: 'GET',
                                dataType: 'json',
                                success: function(res) {
                                    if (res && typeof res.html !== 'undefined') {
                                        $('.page-content').html(res.html).css('opacity', '1');
                                        initPagePlugins();
                                    }
                                }
                            });
                        }
                    },
                    error: function() {
                        showToast('Gagal menghapus data.', 'error');
                    }
                });
            }
        });
    });

});
