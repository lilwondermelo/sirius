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

function loadAndRenderSuppliers(container) {
    const listContainer = container || $('#suppliers_list_container');
    smartAjaxCall({
        classFile: 'app.class',
        class: 'App',
        method: 'getSuppliers',
        data: {}
    })
    .then(suppliers => {
        listContainer.html(renderSuppliersTable(suppliers));
    })
    .catch(error => {
        console.error("Ошибка при загрузке поставщиков:", error);
        listContainer.html('<p>Не удалось загрузить список поставщиков.</p>');
    });
}

function attachSupplierFormSubmitListener() {
    // Use a delegated event listener on a static parent
    $('.admin-panel-tab-content[data-tab-content="suppliers"]').on('submit', '#supplier_form', function(e) {
        e.preventDefault();

        const form = $(this);
        const listContainer = form.closest('.admin-panel-tab-content').find('#suppliers_list_container');

        const formData = {
            name: form.find('#supplier_name').val(),
            inn: form.find('#supplier_inn').val(),
            email: form.find('#supplier_email').val()
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
            form[0].reset(); // Clear the form
            loadAndRenderSuppliers(listContainer); // Refresh the list in the correct container
        })
        .catch(error => {
            console.error("Ошибка при создании поставщика:", error);
            const errorMessage = error.descr || error.error || 'Неизвестная ошибка.';
            alert('Ошибка: ' + errorMessage);
        });
    });
}


function showSuppliersTab() {
    const container = $('.admin-panel-tab-content[data-tab-content="suppliers"]');
    container.html(renderSupplierForm());
    attachSupplierFormSubmitListener();
    loadAndRenderSuppliers(container.find('#suppliers_list_container'));
}