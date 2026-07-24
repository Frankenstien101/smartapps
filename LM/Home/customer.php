<?php
session_start();
if (!isset($_SESSION['Name_of_user']) || empty($_SESSION['Name_of_user'])) {
    header("Location: /PaOrder/Home/verify.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" type="image/x-icon" href="Services/img/orderkoico.ico">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Ko</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    body { background-color: #f8f9fa; padding-top: 100px; }
    .navbar { background-color: #343a40; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .page-section { display: none; min-height: 70vh; }
    .page-section.active { display: block; }
    .logo-img { height: 60px; width: auto; }
    .card:hover { cursor: pointer; transform: scale(1.01); transition: 0.2s; }

    #loadingOverlay { z-index: 9999; transition: opacity 0.5s ease-out; }
    #loadingOverlay.hidden { opacity: 0; pointer-events: none; }

    /* Cart Sidebar */
    #cartSidebar {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100vh;
        background: white;
        box-shadow: -5px 0 15px rgba(0,0,0,0.2);
        transition: right 0.4s ease;
        z-index: 1050;
        display: flex;
        flex-direction: column;
    }
    #cartSidebar.open { right: 0; }
    #cartOverlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
        display: none;
    }
    #cartOverlay.open { display: block; }
    .cart-header {
        background: #343a40;
        color: white;
        padding: 1rem;
    }
    .cart-body { flex: 1; overflow-y: auto; padding: 1rem; }
    .cart-footer {
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    .cart-item {
        border-bottom: 1px solid #eee;
        padding: 0.75rem 0;
    }

    /* Custom Leaflet marker using Font Awesome */
    .custom-div-icon .marker-pin{
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        font-size: 16px;
    }
    .custom-div-icon i{ line-height: 1; }
</style>
</head>
<body>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 bg-light bg-opacity-75 d-flex align-items-center justify-content-center">
    <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 4rem; height: 4rem;"></div>
        <h4 class="text-muted">Loading your orders...</h4>
    </div>
</div>

<!-- Cart Overlay & Sidebar -->
<div id="cartOverlay" onclick="toggleCart()"></div>
<div id="cartSidebar">
    <div class="cart-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Your Cart (<span id="cartItemCount">0</span>)</h5>
        <button class="btn-close btn-close-white" onclick="toggleCart()"></button>
    </div>
    <div class="cart-body" id="cartItems">
        <p class="text-center text-muted my-5">No items in cart yet.</p>
    </div>
    <div class="cart-footer">
        <div class="d-flex justify-content-between mb-3">
            <strong>Grand Total:</strong>
            <strong id="cartGrandTotal">₱0.00</strong>
        </div>
        <button class="btn btn-outline-danger w-100 mb-2" onclick="clearCart()">
            <i class="fas fa-trash me-2"></i>Clear Cart
        </button>
        <button class="btn btn-success w-100" onclick="confirmOrder()" id="confirmBtn" disabled>
            <i class="fas fa-check me-2"></i>Confirm Order
        </button>
    </div>
</div>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#" onclick="showPage('home', event)">
            <img src="\PaOrder\Home\img\paordernew.png" alt="PaOrder Logo" class="logo-img me-3">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="#" onclick="showPage('home', event)">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showPage('myorders', event)">MY ORDERS</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="showPage('booking', event)">BOOK ORDER</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-2x me-2"></i>
                        <span><?php echo $_SESSION['Name_of_user']; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="verify.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Sections -->
<div class="container my-5">

    <!-- Home Page -->
    <div id="home" class="page-section active text-center">
        <h1 class="display-4">Welcome to Order Ko Tracker</h1>
        <p class="lead">Track your deliveries with ease.</p>
        <hr class="my-4">
        <p>Click on <strong>My Orders</strong> to view current delivery status or <strong>Book Order</strong> to place a new order.</p>
    </div>

    <!-- My Orders Page -->
    <div id="myorders" class="page-section">
        <h2 class="mb-4 text-center">My Orders</h2>
        <ul class="nav nav-tabs justify-content-center mb-4" id="orderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">Pending</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fordelivery-tab" data-bs-toggle="tab" data-bs-target="#fordelivery" type="button">For Delivery</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button">Completed</button>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="pending">
                <div id="pendingOrders" class="mb-3"></div>
            </div>
            <div class="tab-pane fade" id="fordelivery">
                <div id="forDeliveryOrders" class="mb-3"></div>
            </div>
            <div class="tab-pane fade" id="completed">
                <div id="completedOrders" class="mb-3"></div>
            </div>
        </div>
    </div>

    <!-- Booking Page -->
    <div id="booking" class="page-section">
        <h2 class="mb-4">Book Order - Product List</h2>

        <!-- Search Bar + Show Cart Button -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Search Product</label>
                        <input type="text" class="form-control" id="productSearch" placeholder="Type SKU, barcode, or description..." autofocus>
                    </div>
             
                   
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Available Products</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table table-hover mb-0" id="productsTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Price</th>
                                <th width="120" class="text-center">Qty to Purchase</th>
                                <th width="100" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-3 text-muted">Loading products...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light text-muted small">
                Enter quantity and click "Add" to include the product in your booking.
            </div>
        </div>
         <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-warning w-100 position-relative" onclick="toggleCart()">
                            <i class="fas fa-shopping-cart me-2"></i>Show Cart
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartBadge" style="display:none;">0</span>
                        </button>
                    </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalOrderTitle">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalOrderBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// === CONFIGURATION ===
const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
const loadingOverlay = document.getElementById('loadingOverlay');
let currentMap = null;
let currentRouteLayer = null;
let currentRiderMarker = null;
let riderPollInterval = null;
// Icon placeholders (will be initialized when map is created)
let warehouseIcon = null;
let storeIcon = null;
let riderIcon = null;

function startRiderPolling(orderNo) {
    if (!orderNo) return;
    // clear existing
    if (riderPollInterval) clearInterval(riderPollInterval);

    const fetchAndUpdate = () => {
        fetch(`/PaOrder/datafetcher/customers_data.php?action=getDeliveryDetails&order_no=${encodeURIComponent(orderNo)}&_=${Date.now()}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success || !res.data || !res.data[0]) return;
                const d = res.data[0];
                if (d.rider_lat && d.rider_lng && !isNaN(parseFloat(d.rider_lat)) && !isNaN(parseFloat(d.rider_lng))) {
                    const lat = parseFloat(d.rider_lat);
                    const lng = parseFloat(d.rider_lng);
                    const latlng = [lat, lng];
                    console.log('Rider poll response:', d);
                    if (currentRiderMarker) {
                        console.log('Updating existing rider marker to', lat, lng);
                        currentRiderMarker.setLatLng(latlng);
                        try { currentRiderMarker.setIcon(riderIcon); } catch(e) { /* ignore */ }
                    } else if (currentMap) {
                        console.log('Creating new rider marker at', lat, lng);
                        currentRiderMarker = L.marker(latlng, { icon: riderIcon }).addTo(currentMap).bindPopup('Delivery Agent');
                    }
                }
            })
            .catch(err => console.error('Rider poll error:', err));
    };

    // initial fetch immediately
    fetchAndUpdate();
    riderPollInterval = setInterval(fetchAndUpdate, 5000);
}

function stopRiderPolling() {
    if (riderPollInterval) {
        clearInterval(riderPollInterval);
        riderPollInterval = null;
    }
}

// CART SYSTEM
let cart = [];

function toggleCart() {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
    if (sidebar.classList.contains('open')) renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const itemCountEl = document.getElementById('cartItemCount');
    const badge = document.getElementById('cartBadge');
    const confirmBtn = document.getElementById('confirmBtn');

    if (cart.length === 0) {
        container.innerHTML = '<p class="text-center text-muted my-5">No items in cart yet.</p>';
        grandTotalEl.textContent = '₱0.00';
        itemCountEl.textContent = '0';
        badge.style.display = 'none';
        confirmBtn.disabled = true;
        return;
    }

    let grandTotal = 0;
    container.innerHTML = '';
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.qty;
        grandTotal += itemTotal;

        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong>${escapeHtml(item.description)}</strong><br>
                    <small class="text-muted">₱${item.price.toLocaleString('en-PH', {minimumFractionDigits: 2})} × ${item.qty}</small>
                </div>
                <strong>₱${itemTotal.toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong>
            </div>
            <div class="d-flex align-items-center">
                <input type="number" class="form-control form-control-sm me-2" style="width:80px;" value="${item.qty}" min="1"
                       onchange="updateCartQty(${index}, this.value)">
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
    });

    grandTotalEl.textContent = '₱' + grandTotal.toLocaleString('en-PH', {minimumFractionDigits: 2});
    itemCountEl.textContent = cart.length;
    badge.textContent = cart.length;
    badge.style.display = 'block';
    confirmBtn.disabled = false;
}

function updateCartQty(index, newQty) {
    const qty = parseInt(newQty) || 1;
    if (qty > 0) {
        cart[index].qty = qty;
        renderCart();
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    if (confirm('Clear all items from cart?')) {
        cart = [];
        renderCart();
    }
}

async function confirmOrder() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    if (!confirm(`Confirm booking?\n\n${cart.length} item(s)\nTotal: ₱${total.toLocaleString('en-PH', {minimumFractionDigits: 2})}\n\nThis will submit the order to the system.`)) {
        return;
    }

    // Disable button and show loading
    const confirmBtn = document.getElementById('confirmBtn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

 const orderData = {
    store_id: <?= json_encode($_SESSION['store_id'] ?? '') ?>,
    principal: <?= json_encode($_SESSION['principal'] ?? '') ?>,
    user_name: <?= json_encode($_SESSION['Name_of_user'] ?? '') ?>,
    items: cart.map(item => ({
        sku: item.sku,
        barcode: item.barcode,
        description: item.description,
        price: item.price,
        qty: item.qty
    })),
    total_amount: total
};
    try {
        const response = await fetch('/PaOrder/datafetcher/customers_data.php?action=submitBooking', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });

        const result = await response.json();

        if (result.success) {
            alert(`Order successfully submitted!\n\n${result.order_no ? 'Order No: ' + result.order_no : 'Order saved.'}`);
            cart = [];
            renderCart();
            toggleCart(); 
        } else {
            alert('Failed to submit order:\n' + (result.error || 'Unknown error. Please try again.'));
        }
    } catch (err) {
        console.error('Network error:', err);
        alert('Connection failed. Please check your internet and try again.');
    } finally {

        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    }
}

// Load products
async function loadProducts(search = '') {
    const tbody = document.getElementById('productsTableBody');
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 text-muted">Loading products...</p></td></tr>`;

    try {
        const params = new URLSearchParams();
        if (search) params.append('search', search);

        const response = await fetch(`/PaOrder/datafetcher/customers_data.php?action=getProductList&${params.toString()}`);
        const result = await response.json();

        if (!result.success || !result.data || result.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-muted">No products found.</td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        result.data.forEach(product => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(product.DESCRIPTION || 'No name')}</td>
                <td class="text-end fw-bold">₱${Number(product.SELLING_PRICE || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm text-center" value="1" min="1" style="width:90px;" id="qty-${product.SKU}">
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-success" 
                           onclick="addToCart(
                               '${escapeHtml(product.SKU)}', 
                               '${escapeHtml(product.DESCRIPTION)}', 
                               ${product.SELLING_PRICE || 0}, 
                               document.getElementById('qty-${product.SKU}').value,
                               '${escapeHtml(product.PRD_BARCODE || product.IT_BARCODE || '')}'  // <-- pass barcode here
                           )">
                       <i class="fas fa-plus"></i> Add
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Failed to load products.</td></tr>`;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function addToCart(sku, description, price, qtyStr, barcode) {  // <-- added barcode parameter
    const qty = parseInt(qtyStr) || 1;
    if (qty < 1) {
        alert('Please enter a valid quantity');
        return;
    }

    const existingIndex = cart.findIndex(item => item.sku === sku);
    if (existingIndex > -1) {
        cart[existingIndex].qty += qty;
    } else {
        cart.push({ 
            sku, 
            description, 
            price, 
            qty,
            barcode: barcode || ''  // <-- store barcode here
        });
    }

    renderCart();
    alert(`Added ${qty} × ${description} to cart!`);
}

// Live search
let searchTimeout;
document.getElementById('productSearch')?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadProducts(this.value.trim()), 400);
});

// Page navigation
function showPage(id, e) {
    if (e) e.preventDefault();
    document.querySelectorAll('.page-section').forEach(p => p.classList.remove('active'));
    document.getElementById(id).classList.add('active');

    if (id === 'myorders') {
        loadingOverlay.classList.remove('hidden');
        Promise.all([loadPendingOrders(), loadForDeliveryOrders(), loadCompletedOrders()])
            .then(() => setTimeout(() => loadingOverlay.classList.add('hidden'), 300))
            .catch(() => loadingOverlay.classList.add('hidden'));
    } else if (id === 'booking') {
        loadProducts();
    } else {
        loadingOverlay.classList.add('hidden');
    }
}

/* =========================
   MY ORDERS LOADERS
========================= */

function loadPendingOrders(){
    return fetchOrders(
        'viewmyorders',
        document.getElementById('pendingOrders'),
        createPendingCard
    );
}

function loadForDeliveryOrders(){
    return fetchOrders(
        'viewForDeliveryOrders',
        document.getElementById('forDeliveryOrders'),
        createForDeliveryCard
    );
}

function loadCompletedOrders(){
    return fetchOrders(
        'viewCompletedOrders',
        document.getElementById('completedOrders'),
        createCompletedCard
    );
}

function fetchOrders(action, container, cardBuilder){
    const company = "<?= $_SESSION['principal']; ?>";
    const store   = "<?= $_SESSION['store_id']; ?>";

    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading...</p></div>';

    return fetch(`/PaOrder/datafetcher/customers_data.php?action=${action}&companyid=${company}&storeid=${store}`)
    .then(res => res.json())
    .then(res => {
        container.innerHTML = '';
        if(!res.success || res.data.length === 0){
            container.innerHTML = '<p class="text-center text-muted py-5">No orders found.</p>';
            return;
        }
        res.data.forEach(o => container.appendChild(cardBuilder(o)));
    })
    .catch(err => {
        console.error(err);
        container.innerHTML = '<p class="text-center text-danger py-5">Failed to load orders.</p>';
    });
}

function createPendingCard(o){
    return createCard(o, getStatusText(o.STATUS), getHeaderColor(o.STATUS),
        () => showOrderModalPending(o.order_no));
}

function createForDeliveryCard(o){
    return createCard(o, 'For Delivery', 'bg-primary',
        () => showOrderModalForDelivery(o.order_no));
}

function createCompletedCard(o){
    return createCard(o, 'Completed', 'bg-success',
        () => showOrderModalCompleted(o.order_no));
}

function createCard(o, statusText, headerClass, onClick){
    const card = document.createElement('div');
    card.className = 'card mb-3 shadow-sm';
    card.style.cursor = 'pointer';
    card.innerHTML = `
        <div class="card-header ${headerClass} text-white">
            <strong>${o.order_no}</strong>
            <span class="badge bg-dark float-end">${statusText}</span>
        </div>
        <div class="card-body">
            <p class="mb-1"><strong>Date:</strong> ${o.order_date || 'N/A'}</p>
            <p class="mb-1"><strong>Total:</strong> ₱${Number(o.TOTAL_AMOUNT || 0).toLocaleString('en-PH',{minimumFractionDigits:2})}</p>
            <p class="mb-0"><strong>Deliver By:</strong> ${o.DATE_TO_DELIVER || 'N/A'}</p>
        </div>
    `;
    card.onclick = onClick;
    return card;
}

function getStatusText(s){
    if (s === '1') return 'Prepared';
    if (s === '2') return 'Ready';
    return 'Pending';
}

function getHeaderColor(s){
    if (s === '1') return 'bg-warning text-dark';
    if (s === '2') return 'bg-info text-dark';
    return 'bg-secondary';
}

/* =========================
   MODAL CORE
========================= */

function loadModal(title, actionQuery, renderer) {
    document.getElementById('modalOrderTitle').innerText = title;

    document.getElementById('modalOrderBody').innerHTML = `
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Loading order details...</p>
        </div>
    `;

    orderModal.show();

    fetch(`/PaOrder/datafetcher/customers_data.php?action=${actionQuery}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data || res.data.length === 0) {
                document.getElementById('modalOrderBody').innerHTML = `
                    <div class="text-center py-5">
                        <p class="text-danger">No details available for this order.</p>
                    </div>`;
                return;
            }

            const html = typeof renderer === 'function' ? renderer(res.data) : renderer(res.data);
            document.getElementById('modalOrderBody').innerHTML = html;

            if (actionQuery.includes('getDeliveryDetails') && document.getElementById('deliveryMap')) {
                setTimeout(() => initDeliveryMap(res.data, actionQuery), 0);
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            document.getElementById('modalOrderBody').innerHTML = `
                <div class="text-center py-5">
                    <p class="text-danger">Failed to load order details. Please try again.</p>
                </div>`;
        });
}

function showOrderModalPending(orderNo){
    loadModal(`${orderNo}`, `getOrderDetails&order_no=${orderNo}`, buildItemsTableOrder);
}

function showOrderModalForDelivery(orderNo){
    loadModal(`${orderNo}`, `getDeliveryDetails&order_no=${orderNo}`, (data) => buildDeliveryView(data, orderNo));
}

function showOrderModalCompleted(orderNo){
    loadModal(`${orderNo}`, `getCompletedOrderDetails&order_no=${orderNo}`, (data) => buildCompletedView(data, orderNo));
}

/* =========================
   ITEMS TABLE
========================= */

function buildItemsTableOrder(items){
    if (!items || items.length === 0) {
        return '<p class="text-muted mt-4">No items found for this order.</p>';
    }

    const rows = items.map(i => `
        <tr>
            <td>${i.PRD_SKU_NAME || 'Unknown Item'}</td>
            <td class="text-center">${i.QTY_PIECE || 0}</td>
            <td class="text-end">₱${Number(i.ORDER_VALUE || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');

    return `
        <h5 class="mt-5 mb-3">Purchased Items</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function buildItemsTableForDelivery(items){
    if (!items || items.length === 0) {
        return '<p class="text-muted mt-4">No items found for this order.</p>';
    }

    const rows = items.map(i => `
        <tr>
            <td>${i.DESCRIPTION || 'Unknown Item'}</td>
            <td class="text-center">${i.ITEM_QTY_IT || 0}</td>
            <td class="text-end">₱${Number(i.SALES_AMOUNT || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');

    return `
        <h5 class="mt-5 mb-3">Purchased Items</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

/* =========================
   MODAL VIEWS
========================= */

function buildDeliveryView(deliveryData, orderNo){
    const d = deliveryData[0];

    const mapHtml = `<div id="deliveryMap" style="width:100%; height:420px; margin-top:20px; border:2px solid #0078d4; border-radius:8px;"></div>`;
    const itemsPlaceholder = `<div id="itemsTableContainer"><p class="text-center text-muted mt-4">Loading purchased items...</p></div>`;

    fetch(`/PaOrder/datafetcher/customers_data.php?action=getOrderItems&order_no=${orderNo}`)
        .then(r => r.json())
        .then(res => {
            let itemsHtml = '';
            if (res.success && res.data && Array.isArray(res.data) && res.data.length > 0) {
                itemsHtml = buildItemsTableForDelivery(res.data);
            } else {
                itemsHtml = '<p class="text-muted mt-4">No items found for this order.</p>';
            }
            const container = document.getElementById('itemsTableContainer');
            if (container) container.outerHTML = itemsHtml;
        })
        .catch(() => {
            const container = document.getElementById('itemsTableContainer');
            if (container) container.outerHTML = '<p class="text-danger mt-4">Failed to load items.</p>';
        });

    return `
        <p><strong>Agent:</strong> ${d.MAIN_AGENT || d.RIDER_NAME || 'Not assigned yet'}</p>
        <p class="text-muted small">Planned route from warehouse to your store and live Delivery Agent location.</p>
        ${mapHtml}
        ${itemsPlaceholder}
    `;
}

function buildCompletedView(completedData, orderNo){
    const d = completedData[0];

    const podHtml = d.POD_IMAGE 
        ? `<img src="${d.POD_IMAGE}" class="img-fluid rounded mt-2 mb-4" alt="Proof of Delivery">`
        : '<p class="text-muted mt-3 mb-4">No image available</p>';

    const itemsPlaceholder = `<div id="completedItemsTableContainer"><p class="text-center text-muted mt-4">Loading purchased items...</p></div>`;

    fetch(`/PaOrder/datafetcher/customers_data.php?action=getOrderItems&order_no=${orderNo}`)
        .then(r => r.json())
        .then(res => {
            let itemsHtml = '';
            if (res.success && res.data && Array.isArray(res.data) && res.data.length > 0) {
                itemsHtml = buildItemsTableForDelivery(res.data);
            } else {
                itemsHtml = '<p class="text-muted mt-4">No items found for this order.</p>';
            }
            const container = document.getElementById('completedItemsTableContainer');
            if (container) container.outerHTML = itemsHtml;
        })
        .catch(() => {
            const container = document.getElementById('completedItemsTableContainer');
            if (container) container.outerHTML = '<p class="text-danger mt-4">Failed to load items.</p>';
        });

    return `
        <p><strong>Delivered On:</strong> ${d.DATE_TO_DELIVER || 'N/A'}</p>
        <p><strong>Agent:</strong> ${d.AGENT_ID || 'N/A'}</p>
        ${itemsPlaceholder}
        <h5 class="mt-4 mb-3">Proof of Delivery</h5>
        ${podHtml}
    `;
}

/* =========================
   AZURE MAP INITIALIZATION
========================= */

function initDeliveryMap(data, actionQuery = '') {
    if (currentMap) {
        try { currentMap.remove(); } catch (e) { console.warn(e); }
        currentMap = null;
    }
    if (currentRouteLayer) {
        try { currentRouteLayer.remove(); } catch (e) { console.warn(e); }
        currentRouteLayer = null;
    }

    const d = data[0];

    // Leaflet expects [lat, lng]
    const warehousePos = [parseFloat(d.warehouse_lat), parseFloat(d.warehouse_lng)];
    const storePos = [parseFloat(d.STORE_LAT), parseFloat(d.STORE_LONG)];

    let riderPos = null;
    let hasRider = false;
    if (d.rider_lat && d.rider_lng && !isNaN(parseFloat(d.rider_lat)) && !isNaN(parseFloat(d.rider_lng))) {
        riderPos = [parseFloat(d.rider_lat), parseFloat(d.rider_lng)];
        hasRider = true;
    }

    const center = hasRider ? riderPos : warehousePos;

    const map = L.map('deliveryMap').setView(center, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    currentMap = map;
    // define custom icons using Font Awesome
    warehouseIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="marker-pin" style="background:#6c757d;"><i class="fas fa-warehouse"></i></div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -30]
    });
    storeIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="marker-pin" style="background:#28a745;"><i class="fas fa-store"></i></div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -30]
    });
    riderIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<div class="marker-pin" style="background:#0078d4;"><i class="fas fa-user"></i></div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -30]
    });

    const whMarker = L.marker(warehousePos, { icon: warehouseIcon }).addTo(map).bindPopup('Warehouse');
    const storeMarker = L.marker(storePos, { icon: storeIcon }).addTo(map).bindPopup('Store');
    // set or replace global rider marker
    if (currentRiderMarker) {
        try { currentRiderMarker.remove(); } catch (e) { console.warn(e); }
        currentRiderMarker = null;
    }
    if (hasRider) {
        currentRiderMarker = L.marker(riderPos, { icon: riderIcon }).addTo(map).bindPopup('Delivery Agent');
    }

    // Fit bounds to points
    const points = [warehousePos, storePos];
    if (hasRider) points.push(riderPos);
    map.fitBounds(points, { padding: [80, 80] });

    // Request a driving route from OSRM public API and draw it
    try {
        const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${warehousePos[1]},${warehousePos[0]};${storePos[1]},${storePos[0]}?overview=full&geometries=geojson`;
        fetch(osrmUrl)
            .then(r => r.json())
            .then(routeData => {
                if (routeData && routeData.routes && routeData.routes.length > 0) {
                    const geometry = routeData.routes[0].geometry;
                    currentRouteLayer = L.geoJSON(geometry, {
                        style: { color: '#0078d4', weight: 6, opacity: 0.9 }
                    }).addTo(map);
                    try { map.fitBounds(currentRouteLayer.getBounds(), { padding: [80, 80] }); } catch (e) {}
                }
            })
            .catch(err => console.error('Routing error:', err));
    } catch (e) {
        console.error('Routing request failed', e);
    }

    // Extract order_no from actionQuery if present and start polling for rider updates
    let orderNo = null;
    if (actionQuery) {
        const m = actionQuery.match(/order_no=([^&]+)/);
        if (m) orderNo = decodeURIComponent(m[1]);
    }
    if (!orderNo && d.order_no) orderNo = d.order_no;
    if (!orderNo && d.ORDER_NO) orderNo = d.ORDER_NO;
    if (orderNo) startRiderPolling(orderNo);
}

// Clean up map on modal close
document.querySelectorAll('#orderModal .btn-close, #orderModal .btn-secondary').forEach(btn => {
    btn.addEventListener('click', () => {
        stopRiderPolling();
        if (currentRiderMarker) {
            try { currentRiderMarker.remove(); } catch (e) { console.warn(e); }
            currentRiderMarker = null;
        }
        if (currentRouteLayer) {
            try { currentRouteLayer.remove(); } catch (e) { console.warn(e); }
            currentRouteLayer = null;
        }
        if (currentMap) {
            try { currentMap.remove(); } catch (e) { console.warn(e); }
            currentMap = null;
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    loadingOverlay.classList.add('hidden');
});
</script>

</body>
</html>