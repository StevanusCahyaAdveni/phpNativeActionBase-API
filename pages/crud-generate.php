<form action="actions/?hal=crud-generate" method="POST" id="crudForm">
    <div class="card shadow-sm p-2 mb-1">
        <div class="row g-1">
            <div class="col-6">
                <label for="" class="label">Direktori & File</label>
                <input type="text" name="direktori" id="direktori" placeholder="Direktori & files" class="form-control form-control-sm mb-0" required>
                <p style="line-height: 10px; font-size: 10px;"><i>Gunakan "/" untuk direktori (contoh: users/user-management)</i></p>
            </div>
            <div class="col-6">
                <label for="" class="label">Nama Table DB</label>
                <input type="text" name="nama_table" id="nama_table" placeholder="Nama Table DB" class="form-control form-control-sm mb-0" required>
                <p style="line-height: normal; font-size: 10px;"><i>Gunakan '_' untuk spasi (contoh: users)</i></p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm p-2 mb-1">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="label mb-0">Struktur Kolom Database</label>
            <button type="button" class="btn btn-primary btn-sm" onclick="addColumn()">
                <i class="bi bi-plus-circle"></i> Tambah Kolom
            </button>
        </div>
        <div id="columnsContainer">
            <!-- Kolom akan ditambahkan di sini -->
        </div>
    </div>

    <div class="card shadow-sm p-2">
        <label class="label mb-2">Opsi Tambahan</label>
        <div class="row g-1">
            <!-- <div class="col-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="has_file_upload" id="has_file_upload">
                    <label class="form-check-label" for="has_file_upload" style="font-size: 12px;">
                        <i class="bi bi-image"></i> File Upload
                    </label>
                </div>
            </div>
            <div class="col-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="has_password" id="has_password">
                    <label class="form-check-label" for="has_password" style="font-size: 12px;">
                        <i class="bi bi-key"></i> Password
                    </label>
                </div>
            </div> -->
            <div class="col-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="has_timestamps" id="has_timestamps" checked>
                    <label class="form-check-label" for="has_timestamps" style="font-size: 12px;">
                        <i class="bi bi-clock"></i> Timestamps
                    </label>
                </div>
            </div>
            <div class="col-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="has_api" id="has_api">
                    <label class="form-check-label" for="has_api" style="font-size: 12px;">
                        <i class="bi bi-braces"></i> REST API
                    </label>
                </div>
            </div>
        </div>
        <div class="mt-3 text-end">
            <button type="button" class="btn btn-secondary btn-sm me-1" onclick="resetForm()">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-gear"></i> Generate CRUD
            </button>
        </div>
    </div>

    <input type="hidden" name="columns" id="columns_json" value="[]">
</form>

<script>
    let columnCount = 0;
    let columns = [];

    // Add initial column
    addColumn();

    function addColumn() {
        columnCount++;
        const container = document.getElementById('columnsContainer');
        const columnDiv = document.createElement('div');
        columnDiv.className = 'row g-1 mb-2 align-items-end';
        columnDiv.id = 'column_' + columnCount;
        columnDiv.innerHTML = `
        <div class="col-4">
            <label class="form-label" style="font-size: 11px;">Nama Kolom</label>
            <input type="text" class="form-control form-control-sm column-name" placeholder="username" required>
        </div>
        <div class="col-3">
            <label class="form-label" style="font-size: 11px;">Label</label>
            <input type="text" class="form-control form-control-sm column-label" placeholder="Username" required>
        </div>
        <div class="col-3">
            <label class="form-label" style="font-size: 11px;">Tipe Data</label>
            <select class="form-select form-select-sm column-type">
                <option value="varchar">VARCHAR(255)</option>
                <option value="int">INT</option>
                <option value="text">TEXT</option>
                <option value="date">DATE</option>
                <option value="datetime">DATETIME</option>
            </select>
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeColumn(${columnCount})">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
        container.appendChild(columnDiv);
    }

    function removeColumn(id) {
        const element = document.getElementById('column_' + id);
        if (element) {
            element.remove();
        }
    }

    function updateColumnsJSON() {
        columns = [];
        const columnDivs = document.querySelectorAll('#columnsContainer .row');
        columnDivs.forEach(div => {
            const name = div.querySelector('.column-name').value;
            const label = div.querySelector('.column-label').value;
            const type = div.querySelector('.column-type').value;
            if (name && label) {
                columns.push({
                    name,
                    label,
                    type
                });
            }
        });
        document.getElementById('columns_json').value = JSON.stringify(columns);
    }

    function resetForm() {
        if (confirm('Yakin ingin reset form?')) {
            document.getElementById('crudForm').reset();
            document.getElementById('columnsContainer').innerHTML = '';
            columnCount = 0;
            columns = [];
            addColumn();
        }
    }

    // Update columns JSON before submit
    document.getElementById('crudForm').addEventListener('submit', function(e) {
        updateColumnsJSON();
        if (columns.length === 0) {
            e.preventDefault();
            alert('Tambahkan minimal 1 kolom!');
            return false;
        }
    });

    // Auto-fill dari direktori
    document.getElementById('direktori').addEventListener('input', function(e) {
        const direktori = e.target.value;
        const tableNameInput = document.getElementById('nama_table');
        if (!tableNameInput.value && direktori) {
            // Ambil nama terakhir dari path
            const parts = direktori.split('/');
            const lastPart = parts[parts.length - 1];
            // Remove hyphens untuk table name
            tableNameInput.value = lastPart.replace(/-/g, '_');
        }
    });
</script>

<style>
    .form-check-input:checked {
        background-color: #435ebe;
        border-color: #435ebe;
    }

    #columnsContainer .row {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        border-left: 3px solid #435ebe;
    }

    #columnsContainer .row:hover {
        background: #e9ecef;
    }
</style>