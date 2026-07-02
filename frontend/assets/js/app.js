const PRODUCTS_API_URL = 'http://localhost:8080/api/products';
const CATEGORIES_API_URL = 'http://localhost:8080/api/categories';
const SUPPLIERS_API_URL = 'http://localhost:8080/api/suppliers';

let currentProducts = [];
let editingProductId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    loadCategories();
    loadSuppliers();

    const addProductForm = document.getElementById('addProductForm');
    addProductForm.addEventListener('submit', handleProductFormSubmit);

    const cancelEditBtn = document.getElementById('cancelEditBtn');
    cancelEditBtn.addEventListener('click', resetProductForm);
});

async function loadProducts() {
    try {
        const response = await fetch(PRODUCTS_API_URL);

        if (!response.ok) {
            throw new Error('Failed to fetch products');
        }

        const data = await response.json();
        currentProducts = data.products;

        renderStats(currentProducts);
        renderProductsTable(currentProducts);

    } catch (error) {
        showError('Could not load products from API.');
        console.error(error);
    }
}

async function loadCategories() {
    try {
        const response = await fetch(CATEGORIES_API_URL);
        const data = await response.json();

        const categorySelect = document.getElementById('category_id');
        categorySelect.innerHTML = '<option value="">Select category</option>';

        data.categories.forEach(category => {
            categorySelect.innerHTML += `
                <option value="${category.id}">${category.name}</option>
            `;
        });

    } catch (error) {
        showError('Could not load categories.');
        console.error(error);
    }
}

async function loadSuppliers() {
    try {
        const response = await fetch(SUPPLIERS_API_URL);
        const data = await response.json();

        const supplierSelect = document.getElementById('supplier_id');
        supplierSelect.innerHTML = '<option value="">Select supplier</option>';

        data.suppliers.forEach(supplier => {
            supplierSelect.innerHTML += `
                <option value="${supplier.id}">${supplier.name}</option>
            `;
        });

    } catch (error) {
        showError('Could not load suppliers.');
        console.error(error);
    }
}

async function handleProductFormSubmit(event) {
    event.preventDefault();

    const product = {
        name: document.getElementById('name').value.trim(),
        sku: document.getElementById('sku').value.trim(),
        description: document.getElementById('description').value.trim(),
        category_id: document.getElementById('category_id').value,
        supplier_id: document.getElementById('supplier_id').value,
        quantity: document.getElementById('quantity').value,
        min_stock: document.getElementById('min_stock').value,
        price: document.getElementById('price').value
    };

    try {
        const isEditing = editingProductId !== null;

        const url = isEditing
            ? `${PRODUCTS_API_URL}/${editingProductId}`
            : PRODUCTS_API_URL;

        const method = isEditing ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(product)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to save product');
        }

        showSuccess(isEditing ? 'Product updated successfully.' : 'Product added successfully.');

        resetProductForm();
        loadProducts();

    } catch (error) {
        showError(error.message);
        console.error(error);
    }
}

function editProduct(productId) {
    const product = currentProducts.find(item => Number(item.id) === Number(productId));

    if (!product) {
        showError('Product not found.');
        return;
    }

    editingProductId = product.id;

    document.getElementById('name').value = product.name;
    document.getElementById('sku').value = product.sku;
    document.getElementById('description').value = product.description ?? '';
    document.getElementById('category_id').value = product.category_id ?? '';
    document.getElementById('supplier_id').value = product.supplier_id ?? '';
    document.getElementById('quantity').value = product.quantity;
    document.getElementById('min_stock').value = product.min_stock;
    document.getElementById('price').value = product.price;

    document.getElementById('formTitle').textContent = 'Edit Product';
    document.getElementById('submitProductBtn').textContent = 'Update Product';
    document.getElementById('cancelEditBtn').classList.remove('d-none');

    document.getElementById('addProductForm').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

function resetProductForm() {
    editingProductId = null;

    document.getElementById('addProductForm').reset();
    document.getElementById('min_stock').value = 5;

    document.getElementById('formTitle').textContent = 'Add New Product';
    document.getElementById('submitProductBtn').textContent = 'Add Product';
    document.getElementById('cancelEditBtn').classList.add('d-none');
}

async function deleteProduct(productId) {
    const confirmed = confirm('Are you sure you want to delete this product?');

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch(`${PRODUCTS_API_URL}/${productId}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to delete product');
        }

        showSuccess('Product deleted successfully.');
        loadProducts();

    } catch (error) {
        showError(error.message);
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

    if (products.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center">No products found.</td>
            </tr>
        `;
        return;
    }

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
                <td>
                    <button 
                        class="btn btn-sm btn-outline-primary me-1"
                        onclick="editProduct(${product.id})"
                    >
                        Edit
                    </button>

                    <button 
                        class="btn btn-sm btn-outline-danger"
                        onclick="deleteProduct(${product.id})"
                    >
                        Delete
                    </button>
                </td>
            </tr>
        `;

        tableBody.innerHTML += row;
    });
}

function showSuccess(message) {
    const alertBox = document.getElementById('alertBox');

    alertBox.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}

function showError(message) {
    const alertBox = document.getElementById('alertBox');

    alertBox.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}