// KPI Dashboard JavaScript
// ไฟล์นี้จัดการการแสดงผล KPI Dashboard

let statusChart, departmentChart, trendChart, statusPercentChart;

// เรียกใช้เมื่อโหลดหน้าเสร็จ
$(document).ready(function() {
    // Set default date range (current month)
    setDateRange('month');
    
    // Load KPI data
    loadKPIData();
});

// ตั้งค่าช่วงวันที่
function setDateRange(range) {
    const today = new Date();
    let dateFrom = new Date();
    let dateTo = new Date();
    
    switch(range) {
        case 'today':
            dateFrom = today;
            break;
        case 'week':
            dateFrom.setDate(today.getDate() - 7);
            break;
        case 'month':
            dateFrom.setDate(1); // First day of current month
            break;
        case 'lastMonth':
            dateFrom.setMonth(today.getMonth() - 1);
            dateFrom.setDate(1);
            dateTo.setMonth(today.getMonth());
            dateTo.setDate(0); // Last day of previous month
            break;
        case 'year':
            dateFrom.setMonth(0);
            dateFrom.setDate(1);
            break;
    }
    
    // Format dates as YYYY-MM-DD
    $('#dateFrom').val(formatDate(dateFrom));
    $('#dateTo').val(formatDate(dateTo));
    
    // Auto load data
    loadKPIData();
}

// Format date to YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// โหลดข้อมูล KPI
function loadKPIData() {
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();
    
    if (!dateFrom || !dateTo) {
        alert('กรุณาเลือกช่วงวันที่');
        return;
    }
    
    // Show loading
    $('#loadingOverlay').fadeIn();
    
    $.ajax({
        url: '../api/kpi_data.php',
        method: 'GET',
        data: {
            date_from: dateFrom,
            date_to: dateTo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateKPICards(response.data);
                updateCharts(response.data);
                updateTables(response.data);
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            alert('ไม่สามารถโหลดข้อมูลได้: ' + error);
        },
        complete: function() {
            // Hide loading
            $('#loadingOverlay').fadeOut();
        }
    });
}

// อัปเดต KPI Cards
function updateKPICards(data) {
    const summary = data.summary;
    
    $('#totalRepairs').text(summary.total_repairs || 0);
    $('#pendingRepairs').text(summary.pending_count || 0);
    $('#inProgressRepairs').text(summary.in_progress_count || 0);
    $('#waitingPartsRepairs').text(summary.waiting_parts_count || 0);
    $('#completedRepairs').text(summary.completed_count || 0);
    
    // Average repair time
    const avgHours = parseFloat(summary.avg_repair_hours) || 0;
    $('#avgRepairTime').text(avgHours.toFixed(1));
    
    // Total cost
    const totalCost = parseFloat(data.cost_stats.total_cost) || 0;
    $('#totalCost').text(totalCost.toLocaleString('th-TH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }));
}

// อัปเดตกราฟทั้งหมด
function updateCharts(data) {
    updateStatusChart(data.status_stats);
    updateStatusPercentChart(data.summary);
    updateDepartmentChart(data.department_stats);
    updateTrendChart(data.daily_trend);
}

// กราฟวงกลมแสดงสถานะ
function updateStatusChart(statusData) {
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    // Destroy existing chart
    if (statusChart) {
        statusChart.destroy();
    }
    
    // Prepare data
    const labels = [];
    const counts = [];
    const colors = [];
    
    // สถานะ: 10=รออนุมัติ, 11=ไม่อนุมัติ, 20=รอดำเนินการ, 30=รออะไหล่, 40=ซ่อมเสร็จสิ้น
    const statusColors = {
        '10': '#ffc107',
        '11': '#dc3545',
        '20': '#17a2b8',
        '30': '#ff9800',
        '40': '#28a745'
    };
    
    const statusLabels = {
        '10': 'รออนุมัติ',
        '11': 'ไม่อนุมัติ',
        '20': 'รอดำเนินการ',
        '30': 'รออะไหล่',
        '40': 'ซ่อมเสร็จสิ้น'
    };
    
    statusData.forEach(item => {
        labels.push(statusLabels[item.status] || item.status);
        counts.push(parseInt(item.count));
        colors.push(statusColors[item.status] || '#6c757d');
    });
    
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14,
                            family: 'Sarabun'
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// กราฟวงกลมแสดงเปอร์เซ็นต์สถานะงานซ่อม (กำลังซ่อม, รออะไหล่, เสร็จสิ้น)
function updateStatusPercentChart(summary) {
    const ctx = document.getElementById('statusPercentChart').getContext('2d');
    
    // Destroy existing chart
    if (statusPercentChart) {
        statusPercentChart.destroy();
    }
    
    const inProgress = parseInt(summary.in_progress_count) || 0;
    const waitingParts = parseInt(summary.waiting_parts_count) || 0;
    const completed = parseInt(summary.completed_count) || 0;
    
    const total = inProgress + waitingParts + completed;
    
    // Calculate percentages
    const inProgressPercent = total > 0 ? ((inProgress / total) * 100).toFixed(1) : 0;
    const waitingPartsPercent = total > 0 ? ((waitingParts / total) * 100).toFixed(1) : 0;
    const completedPercent = total > 0 ? ((completed / total) * 100).toFixed(1) : 0;
    
    statusPercentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                `กำลังซ่อม (${inProgressPercent}%)`,
                `รออะไหล่ (${waitingPartsPercent}%)`,
                `เสร็จสิ้น (${completedPercent}%)`
            ],
            datasets: [{
                data: [inProgress, waitingParts, completed],
                backgroundColor: [
                    'rgba(23, 162, 184, 0.8)',  // สีฟ้า - กำลังซ่อม
                    'rgba(255, 152, 0, 0.8)',   // สีส้ม - รออะไหล่
                    'rgba(40, 167, 69, 0.8)'    // สีเขียว - เสร็จสิ้น
                ],
                borderColor: [
                    'rgba(23, 162, 184, 1)',
                    'rgba(255, 152, 0, 1)',
                    'rgba(40, 167, 69, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14,
                            family: 'Sarabun'
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return `${label}: ${value} รายการ`;
                        }
                    }
                }
            }
        }
    });
}

// กราฟแท่งแสดงสถิติตามแผนก
function updateDepartmentChart(deptData) {
    const ctx = document.getElementById('departmentChart').getContext('2d');
    
    // Destroy existing chart
    if (departmentChart) {
        departmentChart.destroy();
    }
    
    // Prepare data
    const labels = deptData.map(item => item.department || 'ไม่ระบุ');
    const repairCounts = deptData.map(item => parseInt(item.repair_count));
    const completedCounts = deptData.map(item => parseInt(item.completed_count));
    
    departmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'แจ้งซ่อมทั้งหมด',
                    data: repairCounts,
                    backgroundColor: 'rgba(102, 126, 234, 0.7)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2
                },
                {
                    label: 'เสร็จสิ้น',
                    data: completedCounts,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            family: 'Sarabun'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            family: 'Sarabun'
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            family: 'Sarabun'
                        }
                    }
                }
            }
        }
    });
}

// กราฟเส้นแสดงแนวโน้มรายวัน
function updateTrendChart(trendData) {
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    // Destroy existing chart
    if (trendChart) {
        trendChart.destroy();
    }
    
    // Prepare data
    const labels = trendData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('th-TH', { day: '2-digit', month: 'short' });
    });
    
    const repairCounts = trendData.map(item => parseInt(item.repair_count));
    const completedCounts = trendData.map(item => parseInt(item.completed_count));
    const inProgressCounts = trendData.map(item => parseInt(item.in_progress_count));
    const waitingPartsCounts = trendData.map(item => parseInt(item.waiting_parts_count || 0));
    const pendingCounts = trendData.map(item => parseInt(item.pending_count));
    const rejectedCounts = trendData.map(item => parseInt(item.rejected_count || 0));
    
    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'แจ้งซ่อมทั้งหมด',
                    data: repairCounts,
                    borderColor: 'rgba(102, 126, 234, 1)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                },
                {
                    label: 'เสร็จสิ้น',
                    data: completedCounts,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                },
                {
                    label: 'กำลังซ่อม',
                    data: inProgressCounts,
                    borderColor: 'rgba(23, 162, 184, 1)',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                },
                {
                    label: 'รออนุมัติ',
                    data: pendingCounts,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                },
                {
                    label: 'รออะไหล่',
                    data: waitingPartsCounts,
                    borderColor: 'rgba(255, 152, 0, 1)',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            family: 'Sarabun'
                        },
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            family: 'Sarabun'
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            family: 'Sarabun'
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
}

// อัปเดตตาราง
function updateTables(data) {
    updateFrequentMachinesTable(data.frequent_machines);
    updateTechnicianTable(data.technician_stats);
    updateExpensiveMachinesTable(data.expensive_machines);
    updateBranchTable(data.branch_stats);
}

// ตารางเครื่องจักรที่มีปัญหาบ่อย
function updateFrequentMachinesTable(machines) {
    const tbody = $('#frequentMachinesTable tbody');
    tbody.empty();
    
    if (machines.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center text-muted">ไม่มีข้อมูล</td></tr>');
        return;
    }
    
    machines.forEach((machine, index) => {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${machine.machine_number || '-'}</strong></td>
                <td class="text-center"><span class="badge badge-warning">${machine.repair_count}</span></td>
                <td class="text-center"><span class="badge badge-success">${machine.completed_count}</span></td>
            </tr>
        `;
        tbody.append(row);
    });
}

// ตารางช่างที่ทำงานมากที่สุด
function updateTechnicianTable(technicians) {
    const tbody = $('#technicianTable tbody');
    tbody.empty();
    
    if (technicians.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center text-muted">ไม่มีข้อมูล</td></tr>');
        return;
    }
    
    technicians.forEach((tech, index) => {
        const avgHours = parseFloat(tech.avg_hours) || 0;
        const totalHours = parseFloat(tech.total_hours) || 0;
        
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${tech.technician}</strong></td>
                <td class="text-center"><span class="badge badge-info">${tech.job_count}</span></td>
                <td class="text-center">${totalHours.toFixed(1)} ชม.</td>
            </tr>
        `;
        tbody.append(row);
    });
}

// ตารางเครื่องจักรที่มีค่าใช้จ่ายสูงสุด
function updateExpensiveMachinesTable(machines) {
    const tbody = $('#expensiveMachinesTable tbody');
    tbody.empty();
    
    if (machines.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center text-muted">ไม่มีข้อมูล</td></tr>');
        return;
    }
    
    machines.forEach((machine, index) => {
        const totalCost = parseFloat(machine.total_cost) || 0;
        const avgCost = parseFloat(machine.avg_cost) || 0;
        
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${machine.machine_code || '-'}</strong></td>
                <td>${machine.machine_name || '-'}</td>
                <td class="text-center"><span class="badge badge-info">${machine.repair_count}</span></td>
                <td class="text-right"><strong class="text-danger">${totalCost.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></td>
                <td class="text-right">${avgCost.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

// ตารางสถิติตามสาขา
function updateBranchTable(branches) {
    const tbody = $('#branchTable tbody');
    tbody.empty();
    
    if (branches.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td></tr>');
        return;
    }
    
    branches.forEach((branch, index) => {
        const repairCount = parseInt(branch.repair_count);
        const completedCount = parseInt(branch.completed_count);
        const successRate = repairCount > 0 ? (completedCount / repairCount * 100).toFixed(1) : 0;
        
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${branch.branch || 'ไม่ระบุ'}</strong></td>
                <td class="text-center"><span class="badge badge-primary">${repairCount}</span></td>
                <td class="text-center"><span class="badge badge-success">${completedCount}</span></td>
                <td class="text-center"><strong>${successRate}%</strong></td>
            </tr>
        `;
        tbody.append(row);
    });
}
