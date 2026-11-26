import DataTable from 'datatables.net-bs4';
import 'datatables.net-bs4/css/dataTables.bootstrap4.css';

const table = document.getElementById('orders-table');
const error = document.getElementById('orders-table-error');
const pagination = document.getElementById('orders-fallback-pagination');

if (table) {
    const text = DataTable.render.text();
    const columns = [
        'no_reservasi', 'tgl_reservasi', 'guest_name', 'guest_email', 'cek_in',
        'adult', 'total', 'guest_paid', 'payment_type', 'status', 'book_status',
    ];

    const actionColumn = {
        data: 'id',
        orderable: false,
        searchable: false,
        render: (id, type) => {
            if (type !== 'display') return '';
            const orderId = Number(id);
            if (!Number.isSafeInteger(orderId) || orderId < 1 || String(orderId) !== String(id)) {
                throw new Error('Invalid order ID received.');
            }

            const container = document.createElement('div');
            const link = document.createElement('a');
            link.href = table.dataset.detailUrl.replace('999999999', String(orderId));
            link.className = 'btn btn-sm btn-info';
            link.textContent = 'Detail';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = table.dataset.deleteUrl.replace('999999999', String(orderId));
            form.addEventListener('submit', (event) => {
                if (!window.confirm('Permanently delete this order and its room details? Stock and payments will not be changed.')) {
                    event.preventDefault();
                }
            });
            for (const [name, value] of [
                ['_token', document.querySelector('meta[name="csrf-token"]').content],
                ['_method', 'DELETE'],
            ]) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }
            const button = document.createElement('button');
            button.type = 'submit';
            button.className = 'btn btn-sm btn-danger';
            button.textContent = 'Delete';
            form.appendChild(button);
            container.append(link, form);
            return container;
        },
    };

    table.querySelector('tbody').replaceChildren();
    new DataTable(table, {
        processing: true,
        serverSide: true,
        pageLength: 20,
        lengthMenu: [10, 20, 50, 100],
        order: [],
        ajax: {
            url: table.dataset.source,
            dataSrc: (response) => {
                error.hidden = true;
                return response.data;
            },
            error: (xhr, status, message) => {
                error.hidden = false;
                console.error('Unable to load orders:', status, message);
            },
        },
        columns: [...columns.map((column) => ({ data: column, defaultContent: '', render: text })), actionColumn],
    });
    pagination.hidden = true;
}
