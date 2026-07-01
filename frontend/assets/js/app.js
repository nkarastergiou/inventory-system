const API_URL = 'http://localhost:8080/api/products';

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});

async function loadProducts() {
    try {
        const response = await fetch(API_URL);

        if (!response.ok) {
            throw new Error('Failed to fetch products');
        }

        const data = await response.json();
        const products = data.products;

        renderStats(products);
        renderProductsTable(products);

    } catch (error) {
        showError('Could not load products from API.');
        console.error(error);
    }
}

function renderStats(products) {
    const totalProducts = products.length;

    const lowStockProducts = products.filter(product => {
        return Number(product.quantity) <= Number(product.min_stock);
    }).length;

    document.getElementById('totalProducts').textContent = totalProducts;
    document.getElementById('lowStockProducts').textContent = lowStockProducts;
}

function renderProductsTable(products) {
    const tableBody = document.getElementById('productsTableBody');

    tableBody.innerHTML = '';

    products.forEach(product => {
        const isLowStock = Number(product.quantity) <= Number(product.min_stock);

        const statusBadge = isLowStock
            ? '<span class="badge bg-danger">Low Stock</span>'
            : '<span class="badge bg-success">In Stock</span>';

        const row = `
            <tr>
                <td>${product.id}</td>
                <td>${product.name}</td>
                <td>${product.sku}</td>
                <td>${product.category_name ?? '-'}</td>
                <td>${product.supplier_name ?? '-'}</td>
                <td>${product.quantity}</td>
                <td>${product.min_stock}</td>
                <td>€${product.price}</td>
                <td>${statusBadge}</td>
            </tr>
        `;

        tableBody.innerHTML += row;
    });
}

function showError(message) {
    const alertBox = document.getElementById('alertBox');

    alertBox.innerHTML = `
        <div class="alert alert-danger">
            ${message}
        </div>
    `;
}