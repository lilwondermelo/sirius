function renderSupplierForm() {
    return `
        <div class="edit-form-container">
            <h2>Новый поставщик</h2>
            <form id="supplier_form">
                <div class="form-group">
                    <label for="supplier_name" class="form-label">Название:</label>
                    <input type="text" id="supplier_name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="supplier_inn" class="form-label">ИНН:</label>
                    <input type="text" id="supplier_inn" name="inn" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="supplier_email" class="form-label">Email:</label>
                    <input type="email" id="supplier_email" name="email" class="form-input">
                </div>
                <div class="form-actions">
                    <button type="submit">Сохранить</button>
                </div>
            </form>
        </div>
        <div id="suppliers_list_container" class="edit-form-container">
            <!-- Supplier list will be loaded here -->
        </div>
    `;
}

function renderSuppliersTable(suppliers) {
    if (!suppliers || suppliers.length === 0) {
        return '<h3>Поставщиков пока нет.</h3>';
    }

    let table = '<h2>Список поставщиков</h2><table class="suppliers-table">';
    table += '<thead><tr class="table-header"><th>ID</th><th>Название</th><th>ИНН</th><th>Email</th></tr></thead><tbody>';

    suppliers.forEach(s => {
        table += `<tr>
            <td>${s.id}</td>
            <td>${s.name}</td>
            <td>${s.inn}</td>
            <td>${s.email || '—'}</td>
        </tr>`;
    });

    table += '</tbody></table>';
    return table;
}

function loadAndRenderSuppliers() {
    smartAjaxCall({
        classFile: 'app.class',
        class: 'App',
        method: 'getSuppliers',
        data: {}
    })
    .then(suppliers => {
        $('#suppliers_list_container').html(renderSuppliersTable(suppliers));
    })
    .catch(error => {
        console.error("Ошибка при загрузке поставщиков:", error);
        $('#suppliers_list_container').html('<p>Не удалось загрузить список поставщиков.</p>');
    });
}

function attachSupplierFormSubmitListener() {
    $('#supplier_form').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            name: $('#supplier_name').val(),
            inn: $('#supplier_inn').val(),
            email: $('#supplier_email').val()
        };

        if (!formData.name || !formData.inn) {
            alert('Пожалуйста, заполните название и ИНН.');
            return;
        }

        smartAjaxCall({
            classFile: 'app.class',
            class: 'App',
            method: 'createSupplier',
            data: formData
        })
        .then(response => {
            alert('Поставщик успешно создан! ID: ' + response);
            $('#supplier_form')[0].reset(); // Clear the form
            loadAndRenderSuppliers(); // Refresh the list
        })
        .catch(error => {
            console.error("Ошибка при создании поставщика:", error);
            const errorMessage = error.descr || error.error || 'Неизвестная ошибка.';
            alert('Ошибка: ' + errorMessage);
        });
    });
}


$(document).ready(function() {
    // Open supplier form in the main area
    $('#add_supplier_btn').on('click', function() {
        $('#main_table').html(renderSupplierForm());
        attachSupplierFormSubmitListener();
        loadAndRenderSuppliers();
    });
});