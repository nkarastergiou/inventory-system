const PRODUCTS_API_URL = 'http://localhost:8080/api/products';
const CATEGORIES_API_URL = 'http://localhost:8080/api/categories';
const SUPPLIERS_API_URL = 'http://localhost:8080/api/suppliers';
const STOCK_MOVEMENTS_API_URL = 'http://localhost:8080/api/stock-movements';

let currentProducts = [];
let editingProductId = null;

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    loadCategories();
    loadSuppliers();
    loadStockMovements();
    setupSidebarNavigation();

    const addProductForm = document.getElementById('addProductForm');
    addProductForm.addEventListener('submit', handleProductFormSubmit);

    const cancelEditBtn = document.getElementById('cancelEditBtn');
    cancelEditBtn.addEventListener('click', resetProductForm);
    
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', handleSearchProducts);
});

function setupSidebarNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            sidebarLinks.forEach(item => {
                item.parentElement.classList.remove('active');
            });

            link.parentElement.classList.add('active');
        });
    });
}

async function loadProducts() {
    try {
        const response = await fetch(PRODUCTS_API_URL);

        if (!response.ok) {
            throw new Error('Failed to fetch products');
        }

        const data = await response.json();
        currentProducts = data.products;

        renderStats(currentProducts);
        renderLowStockAlerts(currentProducts);
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

function handleSearchProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();

    const filteredProducts = currentProducts.filter(product => {
        const name = String(product.name ?? '').toLowerCase();
        const sku = String(product.sku ?? '').toLowerCase();
        const category = String(product.category_name ?? '').toLowerCase();
        const supplier = String(product.supplier_name ?? '').toLowerCase();

        if (searchTerm.length <= 2) {
            return (
                name.includes(searchTerm) ||
                sku.includes(searchTerm)
            );
        }

        return (
            name.includes(searchTerm) ||
            sku.includes(searchTerm) ||
            category.includes(searchTerm) ||
            supplier.includes(searchTerm)
        );
    });

    renderProductsTable(filteredProducts);
}

async function createStockMovement(productId, movementType) {
    const product = currentProducts.find(item => Number(item.id) === Number(productId));

    if (!product) {
        showError('Product not found.');
        return;
    }

    const quantityInput = prompt(
        movementType === 'in'
            ? `Enter quantity to add for ${product.name}:`
            : `Enter quantity to remove from ${product.name}:`
    );

    if (quantityInput === null) {
        return;
    }

    const quantity = Number(quantityInput);

    if (!Number.isInteger(quantity) || quantity <= 0) {
        showError('Quantity must be a positive whole number.');
        return;
    }

    const note = prompt('Add a note for this stock movement:', '');

    const movement = {
        product_id: productId,
        movement_type: movementType,
        quantity: quantity,
        note: note ?? ''
    };

    try {
        const response = await fetch(STOCK_MOVEMENTS_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(movement)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to create stock movement');
        }

        showSuccess(
            movementType === 'in'
                ? `Stock increased successfully. New quantity: ${data.new_quantity}`
                : `Stock decreased successfully. New quantity: ${data.new_quantity}`
        );

        loadProducts();
        loadStockMovements();

    } catch (error) {
        showError(error.message);
        console.error(error);
    }
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
        loadStockMovements();

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

    const totalStockItems = products.reduce((sum, product) => {
        return sum + Number(product.quantity);
    }, 0);

    document.getElementById('totalProducts').textContent = totalProducts;
    document.getElementById('lowStockProducts').textContent = lowStockProducts;
    document.getElementById('totalStockItems').textContent = totalStockItems;
}

function renderLowStockAlerts(products) {
    const lowStockProducts = products.filter(product => {
        return Number(product.quantity) <= Number(product.min_stock);
    });

    const alertsList = document.getElementById('lowStockAlertsList');
    const lowStockBadge = document.getElementById('lowStockBadge');

    lowStockBadge.textContent = `${lowStockProducts.length} alert${lowStockProducts.length === 1 ? '' : 's'}`;

    if (lowStockProducts.length === 0) {
        alertsList.innerHTML = `
            <div class="empty-alert-state">
                <strong>All products are sufficiently stocked.</strong>
                <p>No products are currently below their minimum stock level.</p>
            </div>
        `;
        return;
    }

    alertsList.innerHTML = '';

    lowStockProducts.forEach(product => {
        const alertItem = `
            <div class="low-stock-item">
                <div>
                    <h6>${product.name}</h6>
                    <p>SKU: ${product.sku} | Category: ${product.category_name ?? '-'}</p>
                </div>

                <div class="low-stock-numbers">
                    <span>Current: <strong>${product.quantity}</strong></span>
                    <span>Minimum: <strong>${product.min_stock}</strong></span>
                </div>
            </div>
        `;

        alertsList.innerHTML += alertItem;
    });
}

function renderProductsTable(products) {
    const tableBody = document.getElementById('productsTableBody');
    const productsCountText = document.getElementById('productsCountText');

    productsCountText.textContent = `Showing ${products.length} product${products.length === 1 ? '' : 's'}`;

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
                <td class="actions-cell">
    <div class="action-buttons">
        <button 
            class="btn btn-action btn-stock-in"
            onclick="createStockMovement(${product.id}, 'in')"
        >
            Stock In
        </button>

        <button 
            class="btn btn-action btn-stock-out"
            onclick="createStockMovement(${product.id}, 'out')"
        >
            Stock Out
        </button>

        <button 
            class="btn btn-action btn-edit"
            onclick="editProduct(${product.id})"
        >
            Edit
        </button>

        <button 
            class="btn btn-action btn-delete"
            onclick="deleteProduct(${product.id})"
        >
            Delete
        </button>
    </div>
</td>
            </tr>
        `;

        tableBody.innerHTML += row;
    });
}

async function loadStockMovements() {
    try {
        const response = await fetch(STOCK_MOVEMENTS_API_URL);

        if (!response.ok) {
            throw new Error('Failed to fetch stock movements');
        }

        const data = await response.json();
        renderStockMovementsTable(data.movements);

    } catch (error) {
        showError('Could not load stock movements.');
        console.error(error);
    }
}

function renderStockMovementsTable(movements) {
    const tableBody = document.getElementById('movementsTableBody');
    
    document.getElementById('totalMovements').textContent = movements.length;


    tableBody.innerHTML = '';

    if (movements.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">No stock movements found.</td>
            </tr>
        `;
        return;
    }

    movements.forEach(movement => {
        const typeBadge = movement.movement_type === 'in'
            ? '<span class="badge bg-success">IN</span>'
            : '<span class="badge bg-warning text-dark">OUT</span>';

        const row = `
            <tr>
                <td>${movement.created_at}</td>
                <td>${movement.product_name}</td>
                <td>${movement.sku}</td>
                <td>${typeBadge}</td>
                <td>${movement.quantity}</td>
                <td>${movement.note ?? '-'}</td>
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