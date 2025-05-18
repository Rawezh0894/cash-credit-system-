// Set Chart.js defaults for RTL support
Chart.defaults.font.family = 'Tajawal, sans-serif';
Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--bs-body-color');

// Common colors for charts
const chartColors = [
    '#4CAF50',  // cash
    '#F44336',  // credit
    '#2196F3',  // advance
    '#FF9800',  // payment
    '#9C27B0'   // collection
];

// Transaction Types Chart
const typesCtx = document.getElementById('transactionTypesChart').getContext('2d');
const typesChart = new Chart(typesCtx, {
    type: 'doughnut',
    data: {
        labels: window.transaction_types,
        datasets: [{
            data: window.transaction_amounts,
            backgroundColor: chartColors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                rtl: true
            }
        }
    }
});

// Transaction Counts Chart
const countsCtx = document.getElementById('transactionCountsChart').getContext('2d');
const countsChart = new Chart(countsCtx, {
    type: 'bar',
    data: {
        labels: window.transaction_types,
        datasets: [{
            label: 'ژمارەی مامەڵەکان',
            data: window.transaction_counts,
            backgroundColor: chartColors,
            borderColor: chartColors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Daily Transactions Chart
const dailyCtx = document.getElementById('dailyTransactionsChart').getContext('2d');
const dailyChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: window.dates,
        datasets: [
            {
                label: 'نەقد',
                data: window.cash_amounts,
                borderColor: chartColors[0],
                backgroundColor: chartColors[0] + '33',
                fill: true,
                tension: 0.4
            },
            {
                label: 'قەرز',
                data: window.credit_amounts,
                borderColor: chartColors[1],
                backgroundColor: chartColors[1] + '33',
                fill: true,
                tension: 0.4
            },
            {
                label: 'پێشەکی',
                data: window.advance_amounts,
                borderColor: chartColors[2],
                backgroundColor: chartColors[2] + '33',
                fill: true,
                tension: 0.4
            },
            {
                label: 'قەرز دانەوە',
                data: window.payment_amounts,
                borderColor: chartColors[3],
                backgroundColor: chartColors[3] + '33',
                fill: true,
                tension: 0.4
            },
            {
                label: 'قەرز وەرگرتنەوە',
                data: window.collection_amounts,
                borderColor: chartColors[4],
                backgroundColor: chartColors[4] + '33',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                position: 'top',
                rtl: true
            }
        }
    }
});

// --- Report Filter AJAX ---
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('report-filter-form');
    const reportRange = document.getElementById('report_range');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    // Helper: update all report data
    function updateReportData(data) {
        // Update stat cards
        document.querySelectorAll('.stat-card')[0].querySelector('h3').textContent = numberWithCommas(data.total_they_owe);
        document.querySelectorAll('.stat-card')[1].querySelector('h3').textContent = numberWithCommas(data.total_we_owe);
        document.querySelectorAll('.stat-card')[2].querySelector('h3').textContent = numberWithCommas(data.total_they_advance);
        document.querySelectorAll('.stat-card')[3].querySelector('h3').textContent = numberWithCommas(data.total_we_advance);

        // Update account summaries
        // Customer
        document.querySelector('.summary-table td:nth-child(2)').textContent = numberWithCommas(data.account_balances.customer.total_customer_owed || 0);
        document.querySelectorAll('.summary-table')[0].querySelectorAll('td')[3].textContent = numberWithCommas(data.account_balances.customer.total_customer_advance || 0);
        // Supplier
        document.querySelectorAll('.summary-table')[1].querySelectorAll('td')[1].textContent = numberWithCommas(data.account_balances.supplier.total_supplier_owed || 0);
        document.querySelectorAll('.summary-table')[1].querySelectorAll('td')[3].textContent = numberWithCommas(data.account_balances.supplier.total_supplier_advance || 0);
        // Mixed
        document.querySelectorAll('.summary-table')[2].querySelectorAll('td')[1].textContent = numberWithCommas(data.account_balances.mixed.total_mixed_they_owe || 0);
        document.querySelectorAll('.summary-table')[2].querySelectorAll('td')[3].textContent = numberWithCommas(data.account_balances.mixed.total_mixed_we_owe || 0);
        document.querySelectorAll('.summary-table')[2].querySelectorAll('td')[5].textContent = numberWithCommas(data.account_balances.mixed.total_mixed_they_advance || 0);
        document.querySelectorAll('.summary-table')[2].querySelectorAll('td')[7].textContent = numberWithCommas(data.account_balances.mixed.total_mixed_we_advance || 0);

        // Update charts
        typesChart.data.labels = data.transaction_types;
        typesChart.data.datasets[0].data = data.transaction_amounts;
        typesChart.update();

        countsChart.data.labels = data.transaction_types;
        countsChart.data.datasets[0].data = data.transaction_counts;
        countsChart.update();

        dailyChart.data.labels = data.dates;
        dailyChart.data.datasets[0].data = data.cash_amounts;
        dailyChart.data.datasets[1].data = data.credit_amounts;
        dailyChart.data.datasets[2].data = data.advance_amounts;
        dailyChart.data.datasets[3].data = data.payment_amounts;
        dailyChart.data.datasets[4].data = data.collection_amounts;
        dailyChart.update();

        // Update overdue/upcoming tables
        function renderTableRows(tbodySelector, rows) {
            const tbody = document.querySelector(tbodySelector);
            if (!tbody) return;
            tbody.innerHTML = '';
            rows.forEach(credit => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${credit.account_name}</td>
                    <td>${credit.account_type}</td>
                    <td><span class="${credit.due_date_class || ''}">${credit.due_date}</span></td>
                    <td>${numberWithCommas(credit.amount)} د.ع</td>
                    <td><div class="action-buttons"><a href="transactions.php?id=${credit.id}" class="btn btn-sm btn-outline-primary" title="بینین"><i class="bi bi-eye"></i></a></div></td>
                `;
                tbody.appendChild(tr);
            });
        }
        renderTableRows('.their-debts-overdue-body', data.overdue_their_debts);
        renderTableRows('.our-debts-overdue-body', data.overdue_our_debts);
        renderTableRows('.their-debts-upcoming-body', data.upcoming_their_debts);
        renderTableRows('.our-debts-upcoming-body', data.upcoming_our_debts);

        // Re-init pagination
        if (typeof initTablePagination === 'function') {
            initTablePagination('.their-debts-overdue-body', '#their-debts-overdue-pagination');
            initTablePagination('.our-debts-overdue-body', '#our-debts-overdue-pagination');
            initTablePagination('.their-debts-upcoming-body', '#their-debts-upcoming-pagination');
            initTablePagination('.our-debts-upcoming-body', '#our-debts-upcoming-pagination');
        }
    }

    // Helper: format numbers
    function numberWithCommas(x) {
        if (x == null) return '0';
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Helper: get filter values
    function getFilterValues() {
        return {
            report_range: reportRange.value,
            start_date: startDate.value,
            end_date: endDate.value
        };
    }

    // Fetch report data via AJAX
    function fetchReportData() {
        const params = getFilterValues();
        // Show loading state if needed
        // ...
        fetch('../process/reports/reports.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(params)
        })
        .then(res => res.json())
        .then(data => {
            updateReportData(data);
        })
        .catch(err => {
            // Optionally show error
            console.error('Report fetch error:', err);
        });
    }

    // Listen for filter changes
    reportRange.addEventListener('change', function(e) {
        e.preventDefault();
        // Clear date fields so PHP can auto-calculate
        startDate.value = '';
        endDate.value = '';
        fetchReportData();
    });
    [startDate, endDate].forEach(el => {
        el.addEventListener('change', function(e) {
            e.preventDefault();
            fetchReportData();
        });
    });

    // Prevent form submit reload
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
    });

    // Optionally: fetch on page load
    // fetchReportData();

    const resetBtn = document.getElementById('reset-filters-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            reportRange.value = 'daily';
            startDate.value = '';
            endDate.value = '';
            fetchReportData();
        });
    }
});
