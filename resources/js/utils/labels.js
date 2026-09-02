export function paymentLabel(method) {
    return {
        cash: 'Tunai',
        qris: 'QRIS',
        transfer: 'Transfer',
        card: 'Kartu',
        ewallet: 'E-Wallet',
        other: 'Lainnya',
        points: 'Poin',
    }[method] ?? method ?? '-';
}

export function paymentMethodsLabel(sale) {
    const rows = sale?.payments?.length
        ? sale.payments
        : (sale?.payment ? [sale.payment] : []);

    if (!rows.length) {
        return '-';
    }

    return [...new Set(rows.map((row) => paymentLabel(row.method)))].join(' + ');
}

export function primaryPaymentMethod(sale) {
    const rows = sale?.payments?.length
        ? sale.payments
        : (sale?.payment ? [sale.payment] : []);

    return rows[0]?.method ?? null;
}

export function roleLabel(role) {
    return {
        owner: 'Owner',
        administrator: 'Administrator',
        manager: 'Manager',
        supervisor: 'Supervisor',
        cashier: 'Kasir',
    }[role] ?? role ?? '-';
}

export function shiftStatusLabel(status) {
    return {
        open: 'Berjalan',
        closed: 'Selesai',
    }[status] ?? status ?? '-';
}

export function movementTypeLabel(type) {
    return {
        initial: 'Stok awal',
        sale: 'Penjualan',
        adjustment: 'Penyesuaian',
        purchase: 'Pembelian',
        refund: 'Refund',
        return: 'Retur',
        opname: 'Opname',
        transfer_out: 'Transfer keluar',
        transfer_in: 'Transfer masuk',
    }[type] ?? type ?? '-';
}

export function saleStatusLabel(status) {
    return {
        completed: 'Selesai',
        partially_refunded: 'Refund sebagian',
        refunded: 'Refund',
        voided: 'Void',
        held: 'Hold',
    }[status] ?? status ?? '-';
}

export function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(String(value).replace(' ', 'T'));

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}
