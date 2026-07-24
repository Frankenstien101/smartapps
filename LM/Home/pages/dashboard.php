<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Load Checking Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <style>
    body { background-color: #f8f9fa; font-size: 0.95rem; }
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.2s; margin-top: 10px; margin-bottom: 10px; }
    .card:hover { transform: translateY(-4px); }
    .card-header { border-radius: 12px 12px 0 0; font-weight: 600; }
    .status-ok    { background-color: #d4edda; color: #155724; }
    .status-load  { background-color: #f8d7da; color: #721c24; font-weight: bold; }
    .low-balance  { color: #e67e22; font-weight: bold; }
    .chart-container { position: relative; height: 280px; width: 100%; }
    .small-pie-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin-top: 15px; }
    .small-pie-item { text-align: center; width: 140px; }
    .small-pie { height: 100px; width: 100%; margin: 0 auto; }
    .small-pie-label { font-size: 0.8rem; margin-top: 4px; }
    .unsubmitted-list { max-height: 300px; overflow-y: auto; font-size: 0.87rem; }
    .date-range { max-width: 420px; }
    .date-input { width: 48%; }
    @media (max-width: 768px) {
      .chart-container { height: 240px; }
      .small-pie { height: 90px; width: 90px; }
      .small-pie-container { gap: 10px; }
      .date-range { max-width: 100%; }
      .date-input { width: 100%; margin-bottom: 0.5rem; }
    }
  </style>
</head>
<body>

<div class="container-fluid py-4" style="overflow-y: auto; height: 88vh; max-height: 90vh;">
  <h2 class="mb-2 fw-bold text-dark">Load Checking Dashboard</h2>

  <!-- Summary Cards -->
  <div class="row g-4 mb-2">
    <div class="col-6 col-md-3"><div class="card bg-primary text-white h-100"><div class="card-body text-center"><h6>Total Devices</h6><h3 id="totalDevices">—</h3></div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-danger text-white h-100"><div class="card-body text-center"><h6>Needs Load</h6><h3 id="needsLoad">—</h3></div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-warning text-dark h-100"><div class="card-body text-center"><h6>Low Balance <small>(<5GB)</small></h6><h3 id="lowBalance">—</h3></div></div></div>
    <div class="col-6 col-md-3"><div class="card bg-success text-white h-100"><div class="card-body text-center"><h6>Submitted Today</h6><h3 id="submittedToday">—</h3></div></div></div>
  </div>

  <!-- Charts Row 1 -->
  <div class="row g-4 mb-1">
    <div class="col-lg-4"><div class="card"><div class="card-header bg-light text-dark">Load Status</div><div class="card-body"><div class="chart-container"><canvas id="pieLoadStatus"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header bg-light text-dark">Devices per Site</div><div class="card-body"><div class="chart-container"><canvas id="barSites"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-header bg-light text-dark">Data Balance Buckets</div><div class="card-body"><div class="chart-container"><canvas id="barBalance"></canvas></div></div></div></div>
  </div>

  <!-- Submission Compliance -->
  <h4 class="mb-1 mt-3 fw-semibold text-primary">Submission Compliance</h4>

  <!-- Date Range Filter -->
  <div class="mb-3">
    <div class="input-group date-range mx-auto">
      <input type="date" class="form-control date-input" id="dateFrom" placeholder="From">
      <input type="date" class="form-control date-input ms-2" id="dateTo" placeholder="To">
      <button class="btn btn-outline-primary ms-2" id="applyDateRange">Apply</button>
    </div>
  </div>

  <div class="row g-4 mb-3">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header bg-light text-dark">Submission Rate per Site</div>
        <div class="card-body"><div class="chart-container"><canvas id="barSubmissionRate"></canvas></div></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header bg-light text-dark">Overall Submission Rate</div>
        <div class="card-body">
          <div class="chart-container"><canvas id="doughnutSubmission"></canvas></div>
          <div class="mt-3 text-center">
            <h5 id="overallRateText" class="fw-bold">—%</h5>
            <small class="text-muted">devices submitted in selected period</small>
          </div>
          <div class="mt-4">
            <h6 class="text-center small fw-semibold mb-2">Submission per Site</h6>
            <div class="small-pie-container" id="smallPiesContainer"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Not Submitted List -->
  <div class="card mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0" id="listTitle">⚠️ Devices NOT Submitted</h5>
        <small id="listSubtitle">Select date range to view</small>
      </div>
    </div>
    <div class="card-body">
      <div class="unsubmitted-list" id="notSubmittedList">
        <p class="text-center text-muted py-4">Select date range and click Apply...</p>
      </div>
      <div class="mt-3 text-end">
        <button class="btn btn-sm btn-warning" id="downloadExcel">
          Download Unsubmitted List
        </button>
      </div>
    </div>
  </div>

  <!-- Devices Overview -->
  <h4 class="mb-1 fw-semibold">Devices Overview</h4>
  <div class="row g-3" id="devicesGrid"></div>

  <!-- Messages -->
  <div id="table-error" class="alert alert-danger mt-3 d-none"></div>
  <div id="table-success" class="alert alert-success mt-3 d-none"></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Global chart instances (we will destroy them before recreating)
let chartInstances = {
  pieLoadStatus: null,
  barSites: null,
  barBalance: null,
  barSubmissionRate: null,
  doughnutSubmission: null,
  // small pies will be handled separately
};

let devices = [];
let submittedToday = [];

// Default to today
let dateFrom = new Date().toISOString().split('T')[0];
let dateTo   = new Date().toISOString().split('T')[0];

document.getElementById('dateFrom').value = dateFrom;
document.getElementById('dateTo').value   = dateTo;

function isForLoad(lastLoadDate, balance) {
  if (Number(balance) < 5) return true;
  if (!lastLoadDate) return true;
  const last = new Date(lastLoadDate);
  if (isNaN(last)) return true;
  const diffMonths = (new Date().getFullYear() - last.getFullYear()) * 12 +
                     (new Date().getMonth() - last.getMonth());
  return diffMonths >= 6;
}

async function loadAllData() {
  const companyId = "<?php echo $_SESSION['Company_ID'] ?? ''; ?>";

  try {
    const [devRes, subRes] = await Promise.all([
      fetch(`/LM/datafetcher/loadcheckingdata.php?action=loaddevice&company=${encodeURIComponent(companyId)}`),
      fetch(`/LM/datafetcher/loadcheckingdata.php?action=today_submissions&company=${encodeURIComponent(companyId)}&datefrom=${dateFrom}&dateto=${dateTo}`)
    ]);

    devices = await devRes.json() || [];
    submittedToday = await subRes.json() || [];

    renderAll();
  } catch (err) {
    console.error('Load error:', err);
    document.getElementById('table-error').textContent = 'Failed to load data.';
    document.getElementById('table-error').classList.remove('d-none');
  }
}

function destroyChart(chartId) {
  if (chartInstances[chartId]) {
    chartInstances[chartId].destroy();
    chartInstances[chartId] = null;
  }
}

function renderAll() {
  renderSummary();
  renderCharts();
  renderSubmissionCharts();
  renderNotSubmittedList();
  renderDeviceCards();
}
function renderSummary() {
  const total = devices.length;
  const needsLoad = devices.filter(d => d.LOAD_STATUS === 'FOR LOAD').length;
  const lowBal = devices.filter(d => Number(d.BALANCE) < 5).length;

  document.getElementById('totalDevices').textContent = total;
  document.getElementById('needsLoad').textContent = needsLoad;
  document.getElementById('lowBalance').textContent = lowBal;
}

function renderCharts() {
  // Destroy old charts first
  destroyChart('pieLoadStatus');
  destroyChart('barSites');
  destroyChart('barBalance');

  const statusCount = {
    OK: devices.filter(d => d.LOAD_STATUS === 'OK').length,
    'FOR LOAD': devices.filter(d => d.LOAD_STATUS === 'FOR LOAD').length
  };

  chartInstances.pieLoadStatus = new Chart(document.getElementById('pieLoadStatus'), {
    type: 'pie',
    data: { 
      labels: ['OK', 'FOR LOAD'], 
      datasets: [{ 
        data: [statusCount.OK, statusCount['FOR LOAD']], 
        backgroundColor: ['#28a745', '#dc3545'] 
      }] 
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false, 
      plugins: { 
        legend: { position: 'bottom' } 
      }
    }
  });

  const siteCount = {};
  devices.forEach(d => siteCount[d.SITE_ID || 'Unknown'] = (siteCount[d.SITE_ID || 'Unknown'] || 0) + 1);
  const sorted = Object.entries(siteCount).sort((a,b)=>b[1]-a[1]).slice(0,8);

  chartInstances.barSites = new Chart(document.getElementById('barSites'), {
    type: 'bar',
    data: { 
      labels: sorted.map(([s])=>s), 
      datasets: [{ 
        label: 'Devices', 
        data: sorted.map(([_,c])=>c), 
        backgroundColor: '#0d6efd', 
        borderRadius: 6 
      }] 
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false, 
      scales: { y: { beginAtZero: true } } 
    }
  });

  const buckets = { '<1':0, '1-5':0, '6-10':0, '11-20':0, '21+':0 };
  devices.forEach(d => {
    const b = Number(d.BALANCE) || 0;
    if (b < 1) buckets['<1']++;
    else if (b < 5) buckets['1-5']++;
    else if (b < 10) buckets['6-10']++;
    else if (b < 20) buckets['11-20']++;
    else buckets['21+']++;
  });

  chartInstances.barBalance = new Chart(document.getElementById('barBalance'), {
    type: 'bar',
    data: { 
      labels: Object.keys(buckets), 
      datasets: [{ 
        label: 'Devices', 
        data: Object.values(buckets), 
        backgroundColor: ['#dc3545', '#e67e22', '#ffc107', '#28a745', '#0d6efd'], 
        borderRadius: 6 
      }] 
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false, 
      scales: { y: { beginAtZero: true } } 
    }
  });
}

// Helper function removed since we're using LOAD_STATUS directly

function renderSubmissionCharts() {
  // Destroy old charts
  destroyChart('barSubmissionRate');
  destroyChart('doughnutSubmission');

  const submittedSet = new Set(submittedToday.filter(s => s.IS_SUBMIT === 'Yes').map(s => s.NUMBER));
  const siteSubmission = {};

  devices.forEach(d => {
    const site = d.SITE_ID || 'Unknown';
    if (!siteSubmission[site]) siteSubmission[site] = { total: 0, submitted: 0 };
    siteSubmission[site].total++;
    if (submittedSet.has(d.NUMBER)) siteSubmission[site].submitted++;
  });

  const sortedSites = Object.entries(siteSubmission)
    .map(([site, data]) => ({ site, rate: data.total > 0 ? (data.submitted / data.total) * 100 : 0, submitted: data.submitted, total: data.total }))
    .sort((a,b) => b.rate - a.rate)
    .slice(0, 10);

  chartInstances.barSubmissionRate = new Chart(document.getElementById('barSubmissionRate'), {
    type: 'bar',
    data: {
      labels: sortedSites.map(s => s.site),
      datasets: [{
        label: 'Submission Rate (%)',
        data: sortedSites.map(s => s.rate.toFixed(1)),
        backgroundColor: sortedSites.map(s => s.rate >= 90 ? '#28a745' : s.rate >= 70 ? '#ffc107' : '#dc3545'),
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, max: 100 } },
      plugins: { tooltip: { callbacks: { afterLabel: ctx => ` ${ctx.raw}% (${sortedSites[ctx.dataIndex].submitted}/${sortedSites[ctx.dataIndex].total})` } } }
    }
  });

  const totalSubmitted = submittedSet.size;
  const overallRate = devices.length > 0 ? (totalSubmitted / devices.length) * 100 : 0;

  document.getElementById('overallRateText').textContent = overallRate.toFixed(1) + '%';

  chartInstances.doughnutSubmission = new Chart(document.getElementById('doughnutSubmission'), {
    type: 'doughnut',
    data: {
      labels: ['Submitted Today', 'Not Submitted'],
      datasets: [{
        data: [totalSubmitted, devices.length - totalSubmitted],
        backgroundColor: ['#28a745', '#dc3545'],
        borderWidth: 2
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  // Small pies per site
  const container = document.getElementById('smallPiesContainer');
  container.innerHTML = '';

  sortedSites.slice(0, 8).forEach(siteData => {
    const pieDiv = document.createElement('div');
    pieDiv.className = 'small-pie-item';
    const canvasId = `pie-${siteData.site.replace(/\s+/g, '')}`;
    pieDiv.innerHTML = `
      <div class="small-pie">
        <canvas id="${canvasId}"></canvas>
      </div>
      <div class="small-pie-label text-truncate">${siteData.site}</div>
    `;
    container.appendChild(pieDiv);

    new Chart(document.getElementById(canvasId), {
      type: 'pie',
      data: {
        labels: ['Submitted', 'Not Submitted'],
        datasets: [{
          data: [siteData.submitted, siteData.total - siteData.submitted],
          backgroundColor: ['#28a745', '#fa6c7a'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.label}: ${ctx.raw} (${((ctx.raw / siteData.total) * 100).toFixed(1)}%)`
            }
          }
        },
        cutout: '50%'
      }
    });
  });
}

function renderNotSubmittedList() {
  const submittedNumbers = new Set(submittedToday.filter(s => s.IS_SUBMIT === 'Yes').map(s => s.NUMBER));
  const notSubmitted = devices.filter(d => !submittedNumbers.has(d.NUMBER));

  const titleEl = document.getElementById('listTitle');
  const subtitleEl = document.getElementById('listSubtitle');

  const fromDate = new Date(dateFrom);
  const toDate   = new Date(dateTo);
  const isSingleDay = dateFrom === dateTo;

  if (notSubmitted.length === 0) {
    titleEl.innerHTML = 'All Devices Submitted';
    subtitleEl.textContent = isSingleDay 
      ? `All submitted on ${fromDate.toLocaleDateString('en-PH')}` 
      : `All submitted from ${fromDate.toLocaleDateString('en-PH')} to ${toDate.toLocaleDateString('en-PH')}`;
  } else {
    titleEl.innerHTML = 'Devices NOT Submitted';
    subtitleEl.textContent = isSingleDay 
      ? `${notSubmitted.length} not submitted on ${fromDate.toLocaleDateString('en-PH')}` 
      : `${notSubmitted.length} not submitted from ${fromDate.toLocaleDateString('en-PH')} to ${toDate.toLocaleDateString('en-PH')}`;
  }

  const list = document.getElementById('notSubmittedList');

  if (notSubmitted.length === 0) {
    list.innerHTML = '<p class="text-success text-center fw-bold py-4">All devices were submitted in this period!</p>';
    return;
  }

  const bySite = {};
  notSubmitted.forEach(d => {
    const site = d.SITE_ID || 'Unknown';
    if (!bySite[site]) bySite[site] = [];
    bySite[site].push(`${d.PERSON_USING || '—'} (${d.NUMBER || 'No Number'})`);
  });

  list.innerHTML = Object.entries(bySite)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([site, items]) => `
      <div class="mb-3">
        <strong class="text-danger">${site}</strong> 
        <small class="text-muted">(${items.length} not submitted)</small>
        <div class="mt-1">
          ${items.map(i => `<span class="badge bg-light text-dark border me-1 mb-1">${i}</span>`).join('')}
        </div>
      </div>
    `).join('');
}

function renderDeviceCards() {
  const grid = document.getElementById('devicesGrid');
  if (!grid) return;

  grid.innerHTML = '';

  if (devices.length === 0) {
    grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">No devices found.</div>';
    return;
  }

  devices.forEach((item) => {
    const forLoad = isForLoad(item.LAST_LOAD_HISTORY, item.BALANCE);
    const statusClass = forLoad ? 'status-load' : 'status-ok';
    const statusText  = forLoad ? 'FOR LOAD' : 'OK';
    const balColor    = Number(item.BALANCE) < 5 ? 'low-balance' : '';

    let nextLoadText = '—';
    let nextLoadClass = '';
    if (item.LOAD_TERMS && item.LAST_LOAD_HISTORY) {
      const terms = Number(item.LOAD_TERMS);
      if (!isNaN(terms) && terms > 0) {
        try {
          const lastDate = new Date(item.LAST_LOAD_HISTORY.trim());
          if (!isNaN(lastDate)) {
            const nextDate = new Date(lastDate);
            nextDate.setMonth(nextDate.getMonth() + terms);
            nextLoadText = nextDate.toLocaleDateString('en-PH', { day: '2-digit', month: 'short', year: 'numeric' });
            const daysUntil = Math.ceil((nextDate - new Date()) / (86400000));
            if (daysUntil <= 0) nextLoadClass = 'text-danger fw-bold';
            else if (daysUntil <= 14) nextLoadClass = 'text-warning';
          }
        } catch (e) {}
      }
    }

    const card = document.createElement('div');
    card.className = 'col-12 col-md-6 col-lg-4 col-xl-3';
    card.innerHTML = `
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <h6 class="mb-0">${item.NUMBER || '—'}</h6>
              <small class="text-muted">${item.SITE_ID || ''} • ${item.DEPARTMENT || ''}</small>
            </div>
            <span class="badge ${statusClass} px-2 py-1">${statusText}</span>
          </div>
          <hr class="my-2">
          <p class="mb-1"><strong>Balance:</strong> 
            <span class="${balColor}">${Number(item.BALANCE || 0).toFixed(2)} GB</span>
          </p>
          <p class="mb-1"><strong>User:</strong> ${item.PERSON_USING || '—'}</p>
          <p class="mb-1"><small>Last Load:</small> ${item.LAST_LOAD_HISTORY || 'Never'}</p>
          <p class="mb-1"><small>Next Load (${item.LOAD_TERMS || '?'} mo):</small> 
            <span class="${nextLoadClass}">${nextLoadText}</span>
          </p>
        </div>
      </div>
    `;
    grid.appendChild(card);
  });
}

// Download Excel
document.getElementById('downloadExcel')?.addEventListener('click', () => {
  const submittedNumbers = new Set(submittedToday.filter(s => s.IS_SUBMIT === 'Yes').map(s => s.NUMBER));
  const notSubmitted = devices.filter(d => !submittedNumbers.has(d.NUMBER));

  if (notSubmitted.length === 0) {
    alert('No unsubmitted devices in selected range.');
    return;
  }

  const data = notSubmitted.map(d => ({
    Site: d.SITE_ID || 'Unknown',
    Department: d.DEPARTMENT || '',
    User: d.PERSON_USING || '—',
    Number: d.NUMBER || 'No Number',
    Balance: Number(d.BALANCE || 0).toFixed(2) + ' GB',
    Last_Load: d.LAST_LOAD_HISTORY || 'Never',
    Status: isForLoad(d.LAST_LOAD_HISTORY, d.BALANCE) ? 'FOR LOAD' : 'OK'
  }));

  const ws = XLSX.utils.json_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Unsubmitted");

  const fromStr = dateFrom.replace(/-/g, '');
  const toStr   = dateTo.replace(/-/g, '');
  const fileName = `Unsubmitted_${fromStr}_to_${toStr}.xlsx`;

  XLSX.writeFile(wb, fileName);
});

// Apply date range
document.getElementById('applyDateRange')?.addEventListener('click', () => {
  dateFrom = document.getElementById('dateFrom').value;
  dateTo   = document.getElementById('dateTo').value;

  if (!dateFrom || !dateTo) {
    alert('Please select both From and To dates.');
    return;
  }

  if (dateFrom > dateTo) {
    alert('From date cannot be later than To date.');
    return;
  }

  loadAllData();
});

// Initial load with today's data
document.addEventListener('DOMContentLoaded', () => {
  loadAllData();
});
</script>

</body>
</html>