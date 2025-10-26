// WP POS v1.9.0 - Receipts Management Module
// Handles receipt display, formatting, and printing

class ReceiptsManager {
    constructor(state, uiHelpers) {
        this.state = state;
        this.ui = uiHelpers;
        
        // Setup receipt modal event listeners
        this.setupReceiptModal();
    }
    
    /**
     * Setup receipt modal event listeners
     */
    setupReceiptModal() {
        const printBtn = document.getElementById('print-receipt-btn');
        const closeBtn = document.getElementById('close-receipt-btn');
        
        if (printBtn) {
            printBtn.addEventListener('click', () => this.printReceipt());
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeReceiptModal());
        }
    }

    /**
     * Display receipt in modal
     * @param {Object} data - Receipt data including order details
     */
    showReceipt(data) {
        const container = document.getElementById('receipt-content');
        let itemsHTML = '';
        
        (data.items || []).forEach((item, index) => {
            itemsHTML += `
                <div class="grid grid-cols-12 gap-2 py-1 border-t border-dashed border-gray-400">
                    <div class="col-span-1">${index + 1}</div>
                    <div class="col-span-6">
                        ${item.name}<br>
                        <span class="text-xs text-gray-500">SKU: ${item.sku || 'N/A'}</span>
                    </div>
                    <div class="col-span-2 text-center">${item.quantity}</div>
                    <div class="col-span-3 text-right">$${parseFloat(item.total).toFixed(2)}</div>
                </div>
            `;
        });

        // Fallback: calculate subtotal if missing or not a number
        let subtotal = parseFloat(data.subtotal);
        if (isNaN(subtotal)) {
            subtotal = 0;
            (data.items || []).forEach(item => {
                subtotal += (parseFloat(item.total) || 0);
            });
        }

        let totalsHTML = `
            <div class="flex justify-between">
                <p>Subtotal:</p>
                <p>$${subtotal.toFixed(2)}</p>
            </div>
        `;
        
        // Show fee if present (check both possible data formats)
        if (data.fee && data.fee.amount) {
            const feeAmount = parseFloat(
                data.fee.amountType === 'percentage' 
                    ? (data.subtotal * (parseFloat(data.fee.amount) / 100)) 
                    : data.fee.amount
            );
            totalsHTML += `
                <div class="flex justify-between text-black">
                    <p>${data.fee.label || ((data.fee.amountType === 'percentage' ? data.fee.amount + '%' : '$' + parseFloat(data.fee.amount).toFixed(2)) + ' Fee')}:</p>
                    <p>+$${feeAmount.toFixed(2)}</p>
                </div>
            `;
        } else if (data.fee_discount && data.fee_discount.type === 'fee' && data.fee_discount.amount) {
            const feeAmount = parseFloat(
                data.fee_discount.amountType === 'percentage' 
                    ? (data.subtotal * (parseFloat(data.fee_discount.amount) / 100)) 
                    : data.fee_discount.amount
            );
            totalsHTML += `
                <div class="flex justify-between text-black">
                    <p>${data.fee_discount.label || ((data.fee_discount.amountType === 'percentage' ? data.fee_discount.amount + '%' : '$' + parseFloat(data.fee_discount.amount).toFixed(2)) + ' Fee')}:</p>
                    <p>+$${feeAmount.toFixed(2)}</p>
                </div>
            `;
        }
        
        // Show discount if present (check both possible data formats)
        if (data.discount && data.discount.amount) {
            const discountAmount = parseFloat(
                data.discount.amountType === 'percentage' 
                    ? (data.subtotal * (parseFloat(data.discount.amount) / 100)) 
                    : data.discount.amount
            );
            totalsHTML += `
                <div class="flex justify-between text-black">
                    <p>${data.discount.label || ((data.discount.amountType === 'percentage' ? data.discount.amount + '%' : '$' + parseFloat(data.discount.amount).toFixed(2)) + ' Discount')}:</p>
                    <p>-$${Math.abs(discountAmount).toFixed(2)}</p>
                </div>
            `;
        } else if (data.fee_discount && data.fee_discount.type === 'discount' && data.fee_discount.amount) {
            const discountAmount = parseFloat(
                data.fee_discount.amountType === 'percentage' 
                    ? (data.subtotal * (parseFloat(data.fee_discount.amount) / 100)) 
                    : data.fee_discount.amount
            );
            totalsHTML += `
                <div class="flex justify-between text-black">
                    <p>${data.fee_discount.label || ((data.fee_discount.amountType === 'percentage' ? data.fee_discount.amount + '%' : '$' + parseFloat(data.fee_discount.amount).toFixed(2)) + ' Discount')}:</p>
                    <p>-$${Math.abs(discountAmount).toFixed(2)}</p>
                </div>
            `;
        }
        
        totalsHTML += `
            <div class="flex justify-between font-bold text-lg border-t border-solid border-gray-400 mt-1 pt-1">
                <p>Total:</p>
                <p>$${parseFloat(data.total).toFixed(2)}</p>
            </div>
        `;
        
        // Payment methods and change
        let paymentHTML = '';
        let totalPaid = 0;
        let paymentBreakdown = { cash: 0, card: 0, credit: 0, other: 0 };
        
        if (data.split_payments && Array.isArray(data.split_payments) && data.split_payments.length > 1) {
            paymentHTML = `<div class='flex flex-col gap-1 mt-2'><p class='font-semibold'>Payment Methods:</p>`;
            data.split_payments.forEach(sp => {
                let method = sp.method === 'Other' ? 'Other' : sp.method;
                let amount = parseFloat(sp.amount) || 0;
                totalPaid += amount;
                
                // Track payment breakdown
                const methodLower = method.toLowerCase();
                if (methodLower === 'cash') {
                    paymentBreakdown.cash += amount;
                } else if (methodLower === 'card') {
                    paymentBreakdown.card += amount;
                } else if (methodLower.includes('return') || methodLower.includes('refund') || methodLower.includes('credit')) {
                    paymentBreakdown.credit += amount;
                } else {
                    paymentBreakdown.other += amount;
                }
                
                paymentHTML += `
                    <div class='flex justify-between'>
                        <span>${method}</span>
                        <span>$${amount.toFixed(2)}</span>
                    </div>
                `;
            });
            paymentHTML += '</div>';
            
            // Count how many payment types were actually used
            const paymentTypesUsed = (paymentBreakdown.cash > 0 ? 1 : 0) +
                                     (paymentBreakdown.card > 0 ? 1 : 0) +
                                     (paymentBreakdown.credit > 0 ? 1 : 0) +
                                     (paymentBreakdown.other > 0 ? 1 : 0);
            
            // Only show payment breakdown summary if there are genuinely multiple payment types
            if (paymentTypesUsed > 1) {
                paymentHTML += `
                    <div class='mt-2 pt-2 border-t border-dashed border-gray-400'>
                        <p class='font-semibold text-xs mb-1'>Payment Breakdown:</p>
                        ${paymentBreakdown.cash > 0 ? `<div class='flex justify-between text-xs'><span>Cash:</span><span>$${paymentBreakdown.cash.toFixed(2)}</span></div>` : ''}
                        ${paymentBreakdown.card > 0 ? `<div class='flex justify-between text-xs'><span>Card:</span><span>$${paymentBreakdown.card.toFixed(2)}</span></div>` : ''}
                        ${paymentBreakdown.credit > 0 ? `<div class='flex justify-between text-xs'><span>Return/Refund Credit:</span><span>$${paymentBreakdown.credit.toFixed(2)}</span></div>` : ''}
                        ${paymentBreakdown.other > 0 ? `<div class='flex justify-between text-xs'><span>Other:</span><span>$${paymentBreakdown.other.toFixed(2)}</span></div>` : ''}
                    </div>
                `;
            }
        } else {
            let method = data.payment_method === 'Other' ? 'Other' : data.payment_method;
            totalPaid = parseFloat(data.amount_paid || data.total) || 0;
            paymentHTML = `
                <div class="flex justify-between mt-2">
                    <p>Payment Method:</p>
                    <p>${method}</p>
                </div>
            `;
        }
        
        // Calculate and show change if payment was more than total
        const orderTotal = parseFloat(data.total) || 0;
        const change = totalPaid - orderTotal;
        if (change > 0) {
            paymentHTML += `
                <div class="flex justify-between mt-1 font-semibold">
                    <p>Change:</p>
                    <p>$${change.toFixed(2)}</p>
                </div>
            `;
        }
        
        // Get settings and handle API response wrapping
        let settings = this.state.getState('settings') || {};
        if (settings.data) {
            settings = settings.data;
        }
        
        // Ensure logo URL is absolute
        const logoUrl = settings.logo_url ?
            (settings.logo_url.startsWith('http')
                ? settings.logo_url
                : window.location.origin + settings.logo_url) :
            '';
        
        container.innerHTML = `
            <div class="text-center space-y-1 mb-4">
                ${logoUrl ? `<img src="${logoUrl}" alt="Logo" class="w-24 h-auto mx-auto" onerror="this.style.display='none';">` : ''}
                <p class="font-bold text-lg">${settings.name || 'Your Store'}</p>
                <p>${settings.email || ''}</p>
                <p>Phone: ${settings.phone || ''}</p>
                <p>${settings.address || ''}</p>
            </div>
            <div class="space-y-1 border-t border-dashed border-gray-400 pt-2">
                <p>Order No: #${data.order_number}</p>
                <p>Date: ${this.ui.formatDateTime(data.date_created || data.date)}</p>
                ${data.customer_name ? `<p>Customer: ${data.customer_name}</p>` : ''}
                ${data.cashier_name ? `<p>Cashier: ${data.cashier_name}</p>` : ''}
            </div>
            <div class="mt-2">
                <div class="grid grid-cols-12 gap-2 font-bold py-1">
                    <div class="col-span-1">#</div>
                    <div class="col-span-6">Item</div>
                    <div class="col-span-2 text-center">Qty</div>
                    <div class="col-span-3 text-right">Total</div>
                </div>
                ${itemsHTML}
            </div>
            <div class="mt-2 pt-2 border-t border-dashed border-gray-400 space-y-1">
                ${totalsHTML}
                ${paymentHTML}
            </div>
            <div class="text-center mt-4 pt-2 border-t border-dashed border-gray-400 space-y-1">
                <p>${settings.footer_message_1 || ''}</p>
                <p class="text-xs">${settings.footer_message_2 || ''}</p>
            </div>
        `;

        document.getElementById('receipt-modal').classList.remove('hidden');
    }

    /**
     * Close receipt modal
     */
    closeReceiptModal() {
        document.getElementById('receipt-modal').classList.add('hidden');
    }

    /**
     * Show refund/exchange receipt
     * @param {Object} data - Refund receipt data from API
     */
    showRefundReceipt(data) {
        const modal = document.getElementById('receipt-modal');
        const content = document.getElementById('receipt-content');
        
        if (!modal || !content) return;
        
        // Get store name from state or use default
        const storeName = this.state.getState('settings.receipt.store_name') || 'Store';
        const isExchange = data.transaction_type === 'EXCHANGE';
        
        // Build receipt HTML
        let html = `
            <div style="font-family: monospace; text-align: center;">
                <h2 style="margin: 0; font-size: 1.5em;">${storeName}</h2>
                <p style="margin: 5px 0; font-size: 1.2em; font-weight: bold; color: ${isExchange ? '#f59e0b' : '#ef4444'};">
                    ${data.transaction_type} RECEIPT
                </p>
                <p style="margin: 5px 0;">Original Order: #${data.original_order_number}</p>
                <p style="margin: 5px 0;">${isExchange ? 'Exchange' : 'Refund'} ID: #${data.refund_id}</p>
                <p style="margin: 5px 0;">${new Date(data.date_created).toLocaleString()}</p>
                ${data.customer_name ? `<p style="margin: 5px 0;">Customer: ${data.customer_name}</p>` : ''}
                <hr style="border: 1px dashed #000; margin: 10px 0;">
                
                <h3 style="text-align: left; margin: 10px 0; font-weight: bold;">RETURNED ITEMS:</h3>
        `;
        
        // Add returned items
        data.returned_items.forEach(item => {
            html += `
                <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                    <span>${item.quantity}x ${item.name}</span>
                    <span style="color: #ef4444;">-$${Math.abs(item.total).toFixed(2)}</span>
                </div>
                ${item.sku ? `<div style="font-size: 0.85em; color: #666; margin-left: 20px;">SKU: ${item.sku}</div>` : ''}
            `;
        });
        
        html += `
                <hr style="border: 1px dashed #000; margin: 10px 0;">
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1em;">
                    <span>REFUND CREDIT:</span>
                    <span style="color: #10b981;">$${data.refund_amount.toFixed(2)}</span>
                </div>
        `;
        
        // Add new items if exchange
        if (isExchange && data.new_items && data.new_items.length > 0) {
            html += `
                <hr style="border: 1px dashed #000; margin: 10px 0;">
                <h3 style="text-align: left; margin: 10px 0; font-weight: bold;">NEW ITEMS:</h3>
            `;
            
            data.new_items.forEach(item => {
                html += `
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>${item.quantity}x ${item.name}</span>
                        <span style="color: #10b981;">$${item.total.toFixed(2)}</span>
                    </div>
                    ${item.sku ? `<div style="font-size: 0.85em; color: #666; margin-left: 20px;">SKU: ${item.sku}</div>` : ''}
                `;
            });
            
            html += `
                <hr style="border: 1px dashed #000; margin: 10px 0;">
                <div style="display: flex; justify-content: space-between; font-weight: bold;">
                    <span>New Order Total:</span>
                    <span>$${data.exchange_total.toFixed(2)}</span>
                </div>
                <div style="font-size: 0.9em; margin: 5px 0;">
                    New Order: #${data.exchange_order_number}
                </div>
            `;
        }
        
        // Net amount (refund due or payment due)
        html += `
                <hr style="border: 2px solid #000; margin: 10px 0;">
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.3em;">
                    <span>${data.net_amount < 0 ? 'REFUND DUE:' : 'PAYMENT DUE:'}</span>
                    <span style="color: ${data.net_amount < 0 ? '#10b981' : '#ef4444'};">
                        $${Math.abs(data.net_amount).toFixed(2)}
                    </span>
                </div>
                <hr style="border: 2px solid #000; margin: 10px 0;">
                
                <p style="margin: 10px 0;">Payment Method: ${data.payment_method}</p>
                <p style="margin: 20px 0; font-size: 0.9em;">Thank you for your business!</p>
            </div>
        `;
        
        content.innerHTML = html;
        modal.classList.remove('hidden');
    }

    /**
     * Print receipt in new window
     */
    printReceipt() {
        const content = document.getElementById('receipt-content').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=400');
        
        printWindow.document.write('<html><head><title>Print Receipt</title>');
        printWindow.document.write(`
            <style>
                body {
                    font-family: monospace;
                    font-size: 12px;
                    margin: 20px;
                }
                .grid {
                    display: grid;
                }
                .grid-cols-12 {
                    grid-template-columns: repeat(12, minmax(0, 1fr));
                }
                .col-span-1 { grid-column: span 1 / span 1; }
                .col-span-2 { grid-column: span 2 / span 2; }
                .col-span-3 { grid-column: span 3 / span 3; }
                .col-span-6 { grid-column: span 6 / span 6; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .font-bold { font-weight: 700; }
                .text-lg { font-size: 1.125rem; }
                .mb-4 { margin-bottom: 1rem; }
                .pt-2 { padding-top: 0.5rem; }
                .mt-1 { margin-top: 0.25rem; }
                .mt-2 { margin-top: 0.5rem; }
                .mt-4 { margin-top: 1rem; }
                .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
                .space-y-1 > *:not([hidden]) ~ *:not([hidden]) {
                    --tw-space-y-reverse: 0;
                    margin-top: calc(0.25rem * calc(1 - var(--tw-space-y-reverse)));
                    margin-bottom: calc(0.25rem * var(--tw-space-y-reverse));
                }
                .border-t { border-top-width: 1px; }
                .border-dashed { border-style: dashed; }
                .border-solid { border-style: solid; }
                .border-gray-400 { border-color: #9ca3af; }
                img {
                    max-width: 150px;
                    margin: 0 auto;
                    display: block;
                    height: auto;
                }
                .text-black { color: #000; }
            </style>
        `);
        printWindow.document.write('</head><body>');
        printWindow.document.write(content);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }
}

// Export as singleton
window.ReceiptsManager = ReceiptsManager;