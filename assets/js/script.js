// Document Ready
$(document).ready(function() {
    // Toggle sidebar on mobile
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').toggleClass('show');
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });

    // Product search functionality
    $('#productSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.product-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Category filter
    $('.category-filter').on('click', function() {
        var categoryId = $(this).data('category');
        
        if (categoryId === 'all') {
            $('.product-item').show();
        } else {
            $('.product-item').hide();
            $('.product-item[data-category="' + categoryId + '"]').show();
        }
        
        $('.category-filter').removeClass('active');
        $(this).addClass('active');
    });

    // Add product to cart (POS page)
    $(document).on('click', '.add-to-cart', function() {
        var productId = $(this).data('id');
        var productName = $(this).data('name');
        var productPrice = $(this).data('price');
        
        addToCart(productId, productName, productPrice, 1);
        updateCartSummary();
    });

    // Remove item from cart
    $(document).on('click', '.remove-from-cart', function() {
        var productId = $(this).data('id');
        removeFromCart(productId);
        updateCartSummary();
    });

    // Update quantity in cart
    $(document).on('change', '.cart-quantity', function() {
        var productId = $(this).data('id');
        var quantity = parseInt($(this).val());
        
        if (quantity <= 0) {
            removeFromCart(productId);
        } else {
            updateCartQuantity(productId, quantity);
        }
        
        updateCartSummary();
    });

    // Clear cart
    $('#clearCart').on('click', function() {
        if (confirm('Are you sure you want to clear the cart?')) {
            clearCart();
            updateCartSummary();
        }
    });

    // Apply discount
    $('#applyDiscount').on('click', function() {
        var discount = parseFloat($('#discountAmount').val()) || 0;
        $('#cartDiscount').text(discount.toFixed(2));
        updateCartSummary();
    });

    // Calculate change
    $('#paidAmount').on('keyup', function() {
        var paidAmount = parseFloat($(this).val()) || 0;
        var totalAmount = parseFloat($('#cartTotal').text());
        var change = paidAmount - totalAmount;
        
        $('#changeAmount').text(change >= 0 ? change.toFixed(2) : '0.00');
    });

    // Process payment
    $('#processPayment').on('click', function() {
        var paidAmount = parseFloat($('#paidAmount').val()) || 0;
        var totalAmount = parseFloat($('#cartTotal').text());
        
        if (paidAmount < totalAmount) {
            alert('Paid amount must be greater than or equal to the total amount.');
            return;
        }
        
        // Submit form
        $('#paymentForm').submit();
    });

    // Delete confirmation
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // Barcode scanner simulation
    $('#barcodeInput').on('keyup', function(e) {
        if (e.keyCode === 13) { // Enter key
            var barcode = $(this).val();
            if (barcode) {
                searchProductByBarcode(barcode);
                $(this).val('');
            }
        }
    });

    // Initialize date range picker if exists
    if ($.fn.daterangepicker) {
        $('.date-range-picker').daterangepicker({
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    }
});

// Cart Functions
function addToCart(id, name, price, quantity) {
    var cart = getCart();
    
    // Check if product already in cart
    var found = false;
    for (var i = 0; i < cart.length; i++) {
        if (cart[i].id == id) {
            cart[i].quantity += quantity;
            found = true;
            break;
        }
    }
    
    // If not found, add new item
    if (!found) {
        cart.push({
            id: id,
            name: name,
            price: price,
            quantity: quantity
        });
    }
    
    // Save cart
    saveCart(cart);
    
    // Update cart UI
    renderCart();
}

function removeFromCart(id) {
    var cart = getCart();
    
    // Remove item with matching id
    cart = cart.filter(function(item) {
        return item.id != id;
    });
    
    // Save cart
    saveCart(cart);
    
    // Update cart UI
    renderCart();
}

function updateCartQuantity(id, quantity) {
    var cart = getCart();
    
    // Update quantity for matching id
    for (var i = 0; i < cart.length; i++) {
        if (cart[i].id == id) {
            cart[i].quantity = quantity;
            break;
        }
    }
    
    // Save cart
    saveCart(cart);
    
    // Update cart UI
    renderCart();
}

function clearCart() {
    saveCart([]);
    renderCart();
}

function getCart() {
    var cart = localStorage.getItem('pos_cart');
    return cart ? JSON.parse(cart) : [];
}

function saveCart(cart) {
    localStorage.setItem('pos_cart', JSON.stringify(cart));
}

function renderCart() {
    var cart = getCart();
    var cartItems = $('#cartItems');
    cartItems.empty();
    
    if (cart.length === 0) {
        cartItems.append('<div class="text-center py-4">Cart is empty</div>');
        return;
    }
    
    for (var i = 0; i < cart.length; i++) {
        var item = cart[i];
        var subtotal = item.price * item.quantity;
        
        var html = `
            <div class="cart-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="cart-item-name">${item.name}</h6>
                        <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)}</div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="input-group input-group-sm me-2" style="width: 80px;">
                            <input type="number" class="form-control cart-quantity" value="${item.quantity}" min="1" data-id="${item.id}">
                        </div>
                        <button class="btn btn-sm btn-danger remove-from-cart" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div>Subtotal:</div>
                    <div>$${subtotal.toFixed(2)}</div>
                </div>
            </div>
        `;
        
        cartItems.append(html);
    }
    
    updateCartSummary();
}

function updateCartSummary() {
    var cart = getCart();
    var subtotal = 0;
    var itemCount = 0;
    
    for (var i = 0; i < cart.length; i++) {
        subtotal += cart[i].price * cart[i].quantity;
        itemCount += cart[i].quantity;
    }
    
    var discount = parseFloat($('#cartDiscount').text()) || 0;
    var tax = parseFloat($('#cartTax').text()) || 0;
    var total = subtotal - discount + tax;
    
    $('#cartSubtotal').text(subtotal.toFixed(2));
    $('#cartTotal').text(total.toFixed(2));
    $('#cartItemCount').text(itemCount);
    
    // Update hidden fields for form submission
    $('#inputSubtotal').val(subtotal.toFixed(2));
    $('#inputDiscount').val(discount.toFixed(2));
    $('#inputTax').val(tax.toFixed(2));
    $('#inputTotal').val(total.toFixed(2));
    
    // Update cart items JSON for form submission
    $('#inputCartItems').val(JSON.stringify(cart));
    
    // Calculate change
    var paidAmount = parseFloat($('#paidAmount').val()) || 0;
    var change = paidAmount - total;
    $('#changeAmount').text(change >= 0 ? change.toFixed(2) : '0.00');
    $('#inputChange').val(change >= 0 ? change.toFixed(2) : '0.00');
}

function searchProductByBarcode(barcode) {
    $.ajax({
        url: 'ajax/get_product.php',
        type: 'GET',
        data: { barcode: barcode },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                addToCart(response.product.id, response.product.name, response.product.price, 1);
                updateCartSummary();
            } else {
                alert('Product not found!');
            }
        },
        error: function() {
            alert('Error searching for product');
        }
    });
} 