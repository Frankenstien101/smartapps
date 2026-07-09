  const App = {
    state: {
      currentView: 'dashboard',
      settings: null,
      globalSite: '',
      workstationFilters: {
        query: '',
        branch: '',
        office: '',
        status: '',
        active: ''
      },
      assetFilters: {
        query: '',
        category: '',
        office: '',
        status: '',
        branch: ''
      },  
      employeeFilters: {
        query: '',
        branch: '',
        office: '',
        active: ''
      },
      pmFilters: {
        query: '',
        branch: '',
        cycle: ''
      },
      reportFilters: {
        type: 'inventory',
        query: '',
        branch: '',
        office: '',
        category: '',
        status: ''
      },
      assetColumnVisibility: {
        assetCode: true,
        category: true,
        brand: true,
        model: true,
        serial: true,
        mainAsset: true,
        user: true,
        branch: true,
        office: true,
        status: true
      },
      workstationData: null,
      workstationPage: 1,
      workstationPageSize: 10,
      workstationSort: {
        key: 'mainAssetNumber',
        direction: 'asc'
      },
      dashboardData: null,
      assetRegistry: null,
      assetSidebarExpanded: false,
      reportSidebarExpanded: false,
      employeeData: null,
      employeeFormMode: 'create',
      activeWorkstationDetail: null,
      activeAssetDetail: null,
      operationData: null,
      preventiveMaintenanceData: null,
      activePmItem: null,
      adminUserData: null,
      auditLogData: null,
      assetFormMode: 'create',
      wsComponents: ['systemUnit', 'monitor', 'mouse', 'keyboard', 'ups', 'printer']
    },

    init() {
      this.injectDeferredViews();
      this.bindGlobalEvents();
      this.initResizableDrawer();
      this.loadInitialData();
    },

    injectDeferredViews() {
      const appContent = document.getElementById('app-content');
      ['dashboardViewTemplate', 'assetsViewTemplate', 'employeesViewTemplate', 'reportsViewTemplate', 'operationsViewTemplate', 'adminViewTemplate'].forEach(id => {
        const tpl = document.getElementById(id);
        if (tpl) appContent.appendChild(tpl.content.cloneNode(true));
      });
    },

    bindGlobalEvents() {
      document.addEventListener('click', (event) => {
        // Settings chip selection toggle
        const clickableChip = event.target.closest('.settings-chip');
        if (clickableChip) {
          if (clickableChip.classList.contains('muted-label')) return;
          const group = clickableChip.dataset.settingsGroup;
          const value = clickableChip.dataset.settingsValue;
          if (group && value) {
            const parentCard = clickableChip.closest('.admin-list-card');
            if (parentCard) {
              const isSelected = clickableChip.classList.contains('is-selected');
              // Deselect all chips in this card
              parentCard.querySelectorAll('.settings-chip').forEach(c => c.classList.remove('is-selected'));
              
              const editBtn = parentCard.querySelector('.edit-settings-btn');
              const deleteBtn = parentCard.querySelector('.delete-settings-btn');
              
              if (isSelected) {
                editBtn.disabled = true;
                editBtn.style.opacity = '0.5';
                editBtn.style.cursor = 'not-allowed';
                editBtn.removeAttribute('data-settings-value');
                
                deleteBtn.disabled = true;
                deleteBtn.style.opacity = '0.5';
                deleteBtn.style.cursor = 'not-allowed';
                deleteBtn.removeAttribute('data-settings-value');
              } else {
                clickableChip.classList.add('is-selected');
                
                editBtn.disabled = false;
                editBtn.style.opacity = '1';
                editBtn.style.cursor = 'pointer';
                editBtn.setAttribute('data-settings-value', value);
                
                deleteBtn.disabled = false;
                deleteBtn.style.opacity = '1';
                deleteBtn.style.cursor = 'pointer';
                deleteBtn.setAttribute('data-settings-value', value);
              }
            }
          }
          return;
        }

        // Settings Management click actions
        const settingsAddBtn = event.target.closest('[data-settings-action="add"]');
        if (settingsAddBtn) {
          const group = settingsAddBtn.dataset.settingsGroup;
          const label = group === 'categories' ? 'Category' : this.titleCase(group);
          this.openSettingsModal({
            title: `Add New ${label}`,
            subtitle: `Create a new entry in the ${label} configuration.`,
            group: group,
            action: 'add',
            onSubmit: (payload) => {
              this.setBusy(true);
              this.server('apiAddSetting', { group, ...payload })
                .then((newSettings) => {
                  this.state.settings = newSettings;
                  this.renderSettingsAdmin();
                  this.closeSettingsModal();
                })
                .catch(this.handleError)
                .finally(() => this.setBusy(false));
            }
          });
          return;
        }

        const settingsEditBtn = event.target.closest('[data-settings-action="edit"]');
        if (settingsEditBtn) {
          const group = settingsEditBtn.dataset.settingsGroup;
          const oldValue = settingsEditBtn.dataset.settingsValue;
          const label = group === 'categories' ? 'Category' : this.titleCase(group);
          this.openSettingsModal({
            title: `Edit ${label}`,
            subtitle: `Modify the configuration value for ${oldValue}.`,
            group: group,
            action: 'edit',
            oldValue: oldValue,
            onSubmit: (payload) => {
              this.setBusy(true);
              this.server('apiUpdateSetting', { group, ...payload })
                .then((newSettings) => {
                  this.state.settings = newSettings;
                  this.renderSettingsAdmin();
                  this.closeSettingsModal();
                })
                .catch(this.handleError)
                .finally(() => this.setBusy(false));
            }
          });
          return;
        }

        const settingsDeleteBtn = event.target.closest('[data-settings-action="delete"]');
        if (settingsDeleteBtn) {
          const group = settingsDeleteBtn.dataset.settingsGroup;
          const value = settingsDeleteBtn.dataset.settingsValue;
          const label = group === 'categories' ? 'Category' : this.titleCase(group);
          if (confirm(`Are you sure you want to delete "${value}" from ${label}?`)) {
            this.setBusy(true);
            const payload = group === 'categories' ? { category: value } : { value };
            this.server('apiDeleteSetting', { group, ...payload })
              .then((newSettings) => {
                this.state.settings = newSettings;
                this.renderSettingsAdmin();
              })
              .catch(this.handleError)
              .finally(() => this.setBusy(false));
          }
          return;
        }

        const assetCategoryBtn = event.target.closest('[data-asset-sidebar-category]');
        if (assetCategoryBtn) {
          this.setAssetCategoryFromSidebar(assetCategoryBtn.dataset.assetSidebarCategory || '');
          return;
        }

        const reportTypeBtn = event.target.closest('[data-report-sidebar-type]');
        if (reportTypeBtn) {
          this.setReportTypeFromSidebar(reportTypeBtn.dataset.reportSidebarType || 'inventory');
          return;
        }

        const navItem = event.target.closest('.nav-item');
        if (navItem) {
          if (navItem.dataset.view === 'assets') {
            const isSameAssetsView = this.state.currentView === 'assets';
            const hadAssetRegistry = !!this.state.assetRegistry;
            const categoryChanged = this.state.assetFilters.category !== '';
            this.state.assetSidebarExpanded = !(isSameAssetsView && this.state.assetSidebarExpanded);
            this.state.assetFilters.category = '';
            this.setValue('assetCategoryFilter', '');
            this.updateAssetSidebarActiveCategory();
            this.switchView('assets');
            if (hadAssetRegistry && (isSameAssetsView || categoryChanged)) this.loadAssets();
            return;
          }
          if (navItem.dataset.view === 'reports') {
            const isSameReportsView = this.state.currentView === 'reports';
            this.state.reportSidebarExpanded = !(isSameReportsView && this.state.reportSidebarExpanded);
            this.switchView('reports');
            return;
          }
          this.switchView(navItem.dataset.view);
          return;
        }

        const viewTarget = event.target.closest('[data-view-target]');
        if (viewTarget) {
          this.switchView(viewTarget.dataset.viewTarget);
          return;
        }

        if (event.target.closest('[data-action="close-drawer"]')) {
          this.closeDrawer();
          return;
        }

        if (event.target.closest('[data-action="expand-global-search"]')) {
          this.expandGlobalSearch();
          return;
        }

        if (event.target.closest('[data-action="toggle-global-site"]')) {
          this.toggleGlobalSiteMenu();
          return;
        }

        const siteOption = event.target.closest('[data-site-value]');
        if (siteOption) {
          this.setGlobalSite(siteOption.dataset.siteValue || '');
          this.closeGlobalSiteMenu();
          return;
        }

        if (event.target.closest('[data-action="toggle-notifications"]')) {
          this.toggleNotificationPanel();
          return;
        }

        if (!event.target.closest('.topbar-site-filter')) {
          this.closeGlobalSiteMenu();
        }

        if (!event.target.closest('.notification-wrap')) {
          this.closeNotificationPanel();
        }

        if (!event.target.closest('.global-search')) {
          this.collapseGlobalSearch(false);
        }

        if (event.target.closest('[data-action="open-add-asset"]')) {
          this.openAddAssetModal();
          return;
        }

        if (event.target.closest('[data-action="close-add-asset"]')) {
          this.closeAddAssetModal();
          return;
        }

        if (event.target.closest('[data-action="open-add-workstation"]')) {
          this.openAddWorkstationModal();
          return;
        }

        if (event.target.closest('[data-action="transfer-workstation"]')) {
          this.transferActiveWorkstation();
          return;
        }

        const resignBtn = event.target.closest('[data-action="mark-employee-resigned"]');
        if (resignBtn) {
          this.markEmployeeResigned(resignBtn.dataset.employeeId);
          return;
        }

        if (event.target.closest('[data-action="reset-employee-form"]')) {
          this.resetEmployeeForm();
          return;
        }

        const employeeEditBtn = event.target.closest('[data-action="edit-employee"]');
        if (employeeEditBtn) {
          this.editEmployeeFromTable(employeeEditBtn.dataset.employeeId);
          return;
        }

        const employeeToggle = event.target.closest('[data-action="toggle-employee-active"]');
        if (employeeToggle) {
          this.toggleEmployeeActive(employeeToggle.dataset.employeeId, employeeToggle.dataset.nextActive);
          return;
        }

        if (event.target.closest('[data-action="refresh-dashboard"]')) {
          this.loadDashboard();
          return;
        }

        if (event.target.closest('[data-action="refresh-operation"]')) {
          this.loadOperationView(this.state.currentView);
          return;
        }

        if (event.target.closest('[data-action="scan-qr"]')) {
          this.openQrScanner();
          return;
        }

        const pmRow = event.target.closest('[data-pm-main]');
        if (pmRow) {
          this.openPmChecklist(pmRow.dataset.pmMain);
          return;
        }

        const reportTab = event.target.closest('[data-report-type]');
        if (reportTab) {
          this.state.reportFilters.type = reportTab.dataset.reportType || 'inventory';
          this.loadReports();
          return;
        }

        const dashboardReport = event.target.closest('[data-dashboard-report]');
        if (dashboardReport) {
          this.openDashboardReport(dashboardReport.dataset.dashboardReport || 'inventory');
          return;
        }

        const investmentToggle = event.target.closest('[data-invest-toggle]');
        if (investmentToggle) {
          this.toggleInvestmentColumns(investmentToggle.dataset.investToggle);
          return;
        }

        if (event.target.closest('[data-action="export-report"]')) {
          this.exportCurrentReport();
          return;
        }

        if (event.target.closest('[data-action="print-report"]')) {
          this.printCurrentReport();
          return;
        }

        if (event.target.closest('[data-action="print-visible-tags"]')) {
          this.printVisibleAssetTags();
          return;
        }

        if (event.target.closest('[data-action="print-selected-tags"]')) {
          this.printSelectedAssetTags();
          return;
        }

        const workstationPageBtn = event.target.closest('[data-workstation-page]');
        if (workstationPageBtn) {
          this.state.workstationPage = Math.max(1, Number(workstationPageBtn.dataset.workstationPage || 1));
          this.renderWorkstations(this.state.workstationData);
          return;
        }

        const workstationSortBtn = event.target.closest('[data-ws-sort]');
        if (workstationSortBtn) {
          const key = workstationSortBtn.dataset.wsSort;
          const current = this.state.workstationSort || {};
          this.state.workstationSort = {
            key,
            direction: current.key === key && current.direction === 'asc' ? 'desc' : 'asc'
          };
          this.state.workstationPage = 1;
          this.renderWorkstations(this.state.workstationData);
          return;
        }

        if (event.target.closest('[data-action="close-add-workstation"]')) {
          this.closeAddWorkstationModal();
          return;
        }

        if (event.target.closest('[data-action="close-qr-scanner"]')) {
          this.closeQrScanner();
          return;
        }

        const assetLink = event.target.closest('[data-asset-code]');
        if (assetLink) {
          this.openAssetDetails(assetLink.dataset.assetCode);
          return;
        }

        const employeeLink = event.target.closest('[data-employee-id]');
        if (employeeLink) {
          this.openEmployeeAssets(employeeLink.dataset.employeeId);
          return;
        }

        const wsLink = event.target.closest('a[data-main-asset-number], button[data-main-asset-number], [data-workstation-row]');
        if (wsLink) {
          this.openWorkstationDetails(wsLink.dataset.mainAssetNumber);
          return;
        }

        const tabBtn = event.target.closest('.tab-btn');
        if (tabBtn && document.getElementById('assetTabNav')) {
          this.switchAssetTab(tabBtn.dataset.tab);
          return;
        }

      });

      document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
          event.preventDefault();
          this.expandGlobalSearch();
          return;
        }

        if (event.key === 'Escape' && event.target && event.target.id === 'globalSearch') {
          event.preventDefault();
          this.collapseGlobalSearch(true);
          return;
        }

        if (event.key !== 'Enter' && event.key !== ' ') return;
        const viewTarget = event.target.closest('[data-view-target]');
        const dashboardReport = event.target.closest('[data-dashboard-report]');
        if (!viewTarget && !dashboardReport) return;
        event.preventDefault();
        if (dashboardReport) {
          this.openDashboardReport(dashboardReport.dataset.dashboardReport || 'inventory');
        } else {
          this.switchView(viewTarget.dataset.viewTarget);
        }
      });

      document.addEventListener('submit', (event) => {
        if (event.target && event.target.id === 'addAssetForm') {
          event.preventDefault();
          this.submitAddAssetForm();
          return;
        }

        if (event.target && event.target.id === 'addWorkstationForm') {
          event.preventDefault();
          this.submitAddWorkstationForm();
          return;
        }

        if (event.target && event.target.id === 'employeeForm') {
          event.preventDefault();
          this.submitEmployeeForm();
          return;
        }

        if (event.target && event.target.id === 'operationTransferForm') {
          event.preventDefault();
          this.submitOperationTransfer();
          return;
        }

        if (event.target && event.target.id === 'operationCheckinForm') {
          event.preventDefault();
          this.submitOperationCheckin();
          return;
        }

        if (event.target && event.target.id === 'operationMaintenanceForm') {
          event.preventDefault();
          this.submitOperationMaintenance();
          return;
        }

        if (event.target && event.target.id === 'preventiveMaintenanceForm') {
          event.preventDefault();
          this.submitPreventiveMaintenance();
        }
      });

      document.addEventListener('change', (event) => {
        if (event.target && event.target.id === 'assetSelectAll') {
          document.querySelectorAll('.asset-row-select').forEach(cb => cb.checked = event.target.checked);
        }
      });

      this.bindFilters();
      this.bindAddAssetFormEvents();
      this.bindAddWorkstationFormEvents();
    },

    expandGlobalSearch() {
      const shell = document.getElementById('globalSearchShell');
      const input = document.getElementById('globalSearch');
      if (!shell || !input) return;
      shell.classList.add('is-open');
      window.setTimeout(() => input.focus(), 40);
    },

    collapseGlobalSearch(force) {
      const shell = document.getElementById('globalSearchShell');
      const input = document.getElementById('globalSearch');
      if (!shell || !input) return;
      if (!force && input.value.trim()) return;
      shell.classList.remove('is-open');
      if (force) input.value = '';
      input.blur();
    },

    initResizableDrawer() {
      const handle = document.getElementById('drawerResizeHandle');
      const drawer = document.getElementById('detailsDrawer');
      if (!handle || !drawer) return;

      let dragging = false;
      const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

      handle.addEventListener('mousedown', (event) => {
        dragging = true;
        document.body.classList.add('drawer-resizing');
        event.preventDefault();
      });

      window.addEventListener('mousemove', (event) => {
        if (!dragging) return;
        const width = clamp(window.innerWidth - event.clientX, 380, Math.min(980, window.innerWidth - 80));
        drawer.style.setProperty('--drawer-width', `${width}px`);
      });

      window.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        document.body.classList.remove('drawer-resizing');
      });
    },

    bindFilters() {
      const bindInput = (id, handler) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', handler);
      };

      const bindChange = (id, handler) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', handler);
      };

      bindChange('globalSiteFilter', () => {
        this.setGlobalSite(document.getElementById('globalSiteFilter').value || '');
      });

      bindInput('workstationSearch', () => {
        this.state.workstationFilters.query = document.getElementById('workstationSearch').value || '';
        this.state.workstationPage = 1;
        this.loadWorkstations();
      });

      bindChange('officeFilter', () => {
        this.state.workstationFilters.office = document.getElementById('officeFilter').value || '';
        this.state.workstationPage = 1;
        this.loadWorkstations();
      });

      bindChange('branchFilter', () => {
        this.setGlobalSite(document.getElementById('branchFilter').value || '');
      });

      bindChange('statusFilter', () => {
        this.state.workstationFilters.status = document.getElementById('statusFilter').value || '';
        this.state.workstationPage = 1;
        this.loadWorkstations();
      });

      bindChange('extraFilter', () => {
        const value = document.getElementById('extraFilter').value || '';
        this.state.workstationFilters.active = value === 'active' ? 'Yes' : '';
        this.state.workstationPage = 1;
        this.loadWorkstations();
      });

      bindChange('workstationPageSize', () => {
        const value = document.getElementById('workstationPageSize').value || '10';
        this.state.workstationPageSize = value === 'all' ? 0 : Number(value || 10);
        this.state.workstationPage = 1;
        this.renderWorkstations(this.state.workstationData);
      });

      bindInput('assetSearch', () => {
        this.state.assetFilters.query = document.getElementById('assetSearch').value || '';
        this.loadAssets();
      });

      bindChange('assetCategoryFilter', () => {
        this.state.assetFilters.category = document.getElementById('assetCategoryFilter').value || '';
        this.applyAssetColumnVisibility();
        this.loadAssets();
      });

      bindChange('assetOfficeFilter', () => {
        this.state.assetFilters.office = document.getElementById('assetOfficeFilter').value || '';
        this.loadAssets();
      });

      bindChange('assetStatusFilter', () => {
        this.state.assetFilters.status = document.getElementById('assetStatusFilter').value || '';
        this.loadAssets();
      });

      bindChange('assetBranchFilter', () => {
        this.setGlobalSite(document.getElementById('assetBranchFilter').value || '');
      });

      bindInput('employeeSearch', () => {
        this.state.employeeFilters.query = document.getElementById('employeeSearch').value || '';
        this.loadEmployees();
      });

      bindChange('employeeActiveFilter', () => {
        this.state.employeeFilters.active = document.getElementById('employeeActiveFilter').value || '';
        this.loadEmployees();
      });

      bindInput('reportSearch', () => {
        this.state.reportFilters.query = document.getElementById('reportSearch').value || '';
        this.loadReports();
      });

      bindChange('reportTypeSelect', () => {
        this.state.reportFilters.type = document.getElementById('reportTypeSelect').value || 'inventory';
        this.loadReports();
      });

      bindChange('reportSiteFilter', () => {
        this.state.reportFilters.branch = document.getElementById('reportSiteFilter').value || '';
        this.loadReports();
      });

      bindChange('reportOfficeFilter', () => {
        this.state.reportFilters.office = document.getElementById('reportOfficeFilter').value || '';
        this.loadReports();
      });

      bindChange('reportCategoryFilter', () => {
        this.state.reportFilters.category = document.getElementById('reportCategoryFilter').value || '';
        this.loadReports();
      });

      bindChange('reportStatusFilter', () => {
        this.state.reportFilters.status = document.getElementById('reportStatusFilter').value || '';
        this.loadReports();
      });

      bindInput('pmSearch', () => {
        this.state.pmFilters.query = document.getElementById('pmSearch').value || '';
        this.loadPreventiveMaintenance();
      });

      bindChange('pmCycle', () => {
        this.state.pmFilters.cycle = document.getElementById('pmCycle').value || '';
        this.loadPreventiveMaintenance();
      });

      bindInput('adminUserSearch', () => this.loadAdminUsers());
      bindChange('adminUserStatus', () => this.loadAdminUsers());
      bindInput('auditSearch', () => this.renderAuditLog());
      bindChange('auditActionFilter', () => this.renderAuditLog());

      document.querySelectorAll('[data-asset-column-toggle]').forEach(input => {
        input.addEventListener('change', () => {
          this.state.assetColumnVisibility[input.dataset.assetColumnToggle] = input.checked;
          this.applyAssetColumnVisibility();
        });
      });
    },

    bindAddAssetFormEvents() {
      const categoryEl = document.getElementById('addAssetCategory');
      const purchaseDateEl = document.getElementById('addAssetPurchaseDate');
      const purchasePriceEl = document.getElementById('addAssetPurchasePrice');
      const currentUserEl = document.getElementById('addAssetCurrentUser');
      const lookupBtn = document.getElementById('lookupAssetUserBtn');
      const clearLookupBtn = document.getElementById('clearAssetLookupBtn');
      const importBtn = document.getElementById('addAssetImportBtn');
      const importFileEl = document.getElementById('addAssetImportFile');

      if (categoryEl) {
        categoryEl.addEventListener('change', () => {
          this.updateAddAssetDynamicFields();
          this.applyExpectedLifeFromSettings();
          this.calculateAddAssetDepreciation();
          this.previewNextAssetCode();
        });
      }

      if (purchaseDateEl) {
        purchaseDateEl.addEventListener('input', () => {
          this.calculateAddAssetDepreciation();
        });
      }

      if (purchasePriceEl) {
        purchasePriceEl.addEventListener('input', () => {
          this.calculateAddAssetDepreciation();
        });
      }

      if (lookupBtn) {
        lookupBtn.addEventListener('click', () => {
          this.lookupCurrentUserAssets();
        });
      }

      if (clearLookupBtn) {
        clearLookupBtn.addEventListener('click', () => {
          this.clearAssetLookupResult();
        });
      }

      if (currentUserEl) {
        currentUserEl.addEventListener('blur', () => {
          if (currentUserEl.value.trim()) {
            this.lookupCurrentUserAssets(true);
          }
        });
      }

      if (importFileEl) {
        importFileEl.addEventListener('change', () => {
          this.setAddAssetImportStatus(importFileEl.files && importFileEl.files[0]
            ? `Selected ${importFileEl.files[0].name}`
            : 'No file selected.');
        });
      }

      if (importBtn) {
        importBtn.addEventListener('click', () => {
          this.importAssetsFromSpreadsheet();
        });
      }
    },

    bindAddWorkstationFormEvents() {
      const branchEl = document.getElementById('addWsBranch');
      const userEl = document.getElementById('addWsUser');
      const lookupBtn = document.getElementById('lookupWsEmployeeBtn');
      if (branchEl) {
        branchEl.addEventListener('change', () => {
          this.previewNextWorkstationCode();
        });
      }

      if (lookupBtn) {
        lookupBtn.addEventListener('click', () => this.lookupWorkstationEmployee(false));
      }

      if (userEl) {
        userEl.addEventListener('blur', () => {
          if (userEl.value.trim()) this.lookupWorkstationEmployee(true);
        });
      }

      this.state.wsComponents.forEach((key) => {
        const modeEl = document.getElementById(this.wsModeId(key));
        if (modeEl) {
          modeEl.addEventListener('change', () => {
            this.updateWorkstationComponentVisibility(key);
          });
        }
      });
    },

    loadInitialData() {
      this.server('getInitialAppData')
        .then((res) => {
          this.state.settings = res.settings;
          this.populateFilters();
          this.syncGlobalSiteControls();
          this.populateAddAssetForm();
          this.populateAddWorkstationForm();
          this.populateEmployeeForm();
          if (res.dashboard) {
            this.state.dashboardData = res.dashboard;
            this.renderDashboard(res.dashboard);
          }
          this.switchView('dashboard');
          const assetFromUrl = new URLSearchParams(window.location.search).get('asset');
          if (assetFromUrl) {
            this.switchView('assets');
            this.openAssetDetails(assetFromUrl);
          }
        })
        .catch(this.handleError);
    },

    populateFilters() {
      const settings = this.state.settings || {};
      const sites = this.cleanSiteOptions(settings.sites || []);
      this.fillSelect('globalSiteFilter', sites, 'All Sites');
      this.populateGlobalSiteMenu(sites);
      this.fillSelect('branchFilter', sites, 'All Sites');
      this.fillSelect('officeFilter', settings.offices || [], 'All Offices');
      this.fillSelect('statusFilter', settings.deploymentStatuses || [], 'All Status');

      this.fillSelect('assetCategoryFilter', settings.assetCategories || [], 'All Categories');
      this.fillSelect('assetOfficeFilter', settings.offices || [], 'All Offices');
      this.fillSelect('assetStatusFilter', settings.assetStatuses || [], 'All Status');
      this.fillSelect('assetBranchFilter', sites, 'All Sites');
      this.fillSelect('reportSiteFilter', sites, 'All Sites');
      this.fillSelect('reportOfficeFilter', settings.offices || [], 'All Offices');
      this.fillSelect('reportCategoryFilter', settings.assetCategories || [], 'All Categories');
      this.fillSelect('reportStatusFilter', settings.assetStatuses || [], 'All Status');
      this.updateReportFilterControls();
      this.renderReportSidebarTypes();
    },

    populateEmployeeForm() {
      const settings = this.state.settings || {};
      const branches = (settings.sites || []).length
        ? settings.sites.filter(v => String(v).toUpperCase() !== 'ALL SITES')
        : ['DVO', 'KOR', 'CDO', 'BXU', 'OZA', 'ZAM'];

      this.fillSelect('employeeBranch', branches, 'Select Branch');
      this.fillSelect('employeeOffice', settings.offices || [], 'Select Office');
    },

    populateAddAssetForm() {
      const settings = this.state.settings || {};
      const categories = (settings.assetCategories || []).length
        ? settings.assetCategories
        : ['System Unit', 'Monitor', 'Mouse', 'Keyboard', 'UPS', 'Printer', 'Laptop'];

      const branches = (settings.sites || []).length
        ? settings.sites.filter(v => String(v).toUpperCase() !== 'ALL SITES')
        : ['DVO', 'KOR', 'CDO', 'BXU', 'OZA', 'ZAM'];

      const offices = settings.offices || [];
      const assetStatuses = (settings.assetStatuses || []).length
        ? settings.assetStatuses
        : ['Ready to Deploy', 'Deployed', 'In Repair', 'Disposed'];

      this.fillSelect('addAssetCategory', categories, 'Select Category');
      this.fillSelect('addAssetBranch', branches, 'Select Branch');
      this.fillSelect('addAssetOffice', offices, 'Select Office');
      this.fillSelect('addAssetStatus', assetStatuses, 'Select Status');
      if (this.state.globalSite) this.setValue('addAssetBranch', this.state.globalSite);

      this.applyExpectedLifeFromSettings();
      this.updateAddAssetDynamicFields();
      this.calculateAddAssetDepreciation();
      this.previewNextAssetCode();
    },

    populateAddWorkstationForm() {
      const settings = this.state.settings || {};
      const branches = (settings.sites || []).length
        ? settings.sites.filter(v => String(v).toUpperCase() !== 'ALL SITES')
        : ['DVO', 'KOR', 'CDO', 'BXU', 'OZA', 'ZAM'];

      const offices = settings.offices || [];
      const deploymentStatuses = (settings.deploymentStatuses || []).length
        ? settings.deploymentStatuses
        : ['Deployed', 'Ready to Deploy', 'Pulled Out', 'Archived'];

      this.fillSelect('addWsBranch', branches, 'Select Site');
      this.fillSelect('addWsOffice', offices, 'Select Office');
      this.fillSelect('addWsDeploymentStatus', deploymentStatuses, 'Select Status');
      if (this.state.globalSite) this.setValue('addWsBranch', this.state.globalSite);

      this.state.wsComponents.forEach((key) => this.updateWorkstationComponentVisibility(key));
      this.previewNextWorkstationCode();
    },

    fillSelect(id, values, firstLabel) {
      const el = document.getElementById(id);
      if (!el) return;

      const currentValue = el.value || '';
      el.innerHTML = '';

      const first = document.createElement('option');
      first.value = '';
      first.textContent = firstLabel;
      el.appendChild(first);

      (values || []).forEach(value => {
        const text = String(value || '').trim();
        if (!text) return;

        const opt = document.createElement('option');
        opt.value = text;
        opt.textContent = text;
        if (text === currentValue) opt.selected = true;
        el.appendChild(opt);
      });
    },

    populateGlobalSiteMenu(sites) {
      const menu = document.getElementById('globalSiteMenu');
      if (!menu) return;
      const values = ['', ...(sites || [])];
      menu.innerHTML = values.map((site) => {
        const label = site || 'All Sites';
        return `
          <button class="site-select-option" type="button" role="option" data-site-value="${this.escapeHtml(site)}">
            <span>${this.escapeHtml(label)}</span>
          </button>
        `;
      }).join('');
      this.syncGlobalSiteControls();
    },

    toggleGlobalSiteMenu() {
      const menu = document.getElementById('globalSiteMenu');
      const button = document.querySelector('[data-action="toggle-global-site"]');
      if (!menu || !button) return;
      const isOpen = !menu.classList.contains('hidden');
      menu.classList.toggle('hidden', isOpen);
      button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      button.classList.toggle('is-open', !isOpen);
    },

    closeGlobalSiteMenu() {
      const menu = document.getElementById('globalSiteMenu');
      const button = document.querySelector('[data-action="toggle-global-site"]');
      if (menu) menu.classList.add('hidden');
      if (button) {
        button.setAttribute('aria-expanded', 'false');
        button.classList.remove('is-open');
      }
    },

    cleanSiteOptions(values) {
      return (values || [])
        .map(value => String(value || '').trim())
        .filter(value => value && value.toUpperCase() !== 'ALL SITES');
    },

    setGlobalSite(site) {
      this.state.globalSite = site || '';
      this.state.workstationFilters.branch = site || '';
      this.state.assetFilters.branch = site || '';
      this.state.employeeFilters.branch = site || '';
      this.state.workstationPage = 1;
      this.syncGlobalSiteControls();
      this.state.dashboardData = null;
      this.state.workstationData = null;
      this.state.assetRegistry = null;
      this.state.employeeData = null;
      this.state.operationData = null;
      this.state.adminUserData = null;
      this.state.auditLogData = null;
      this.state.currentReport = null;
      this.state.preventiveMaintenanceData = null;

      if (this.state.currentView === 'dashboard') this.loadDashboard();
      if (this.state.currentView === 'workstations') this.loadWorkstations();
      if (this.state.currentView === 'assets') this.loadAssets();
      if (this.state.currentView === 'employees') this.loadEmployees();
      if (this.state.currentView === 'reports') this.loadReports();
      if (this.state.currentView === 'maintenance') {
        this.state.activePmItem = null;
        this.loadPreventiveMaintenance();
      }
      if (['deploy', 'transfer', 'checkin'].includes(this.state.currentView)) this.loadOperationView(this.state.currentView);
      if (this.state.currentView === 'manage-users') this.loadAdminUsers();
      if (this.state.currentView === 'audit-log') this.loadAuditLog();
    },

    syncGlobalSiteControls() {
      const value = this.state.globalSite || '';
      ['globalSiteFilter', 'branchFilter', 'assetBranchFilter'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.value = value;
      });
      const topLabel = document.getElementById('globalSiteLabel');
      if (topLabel) topLabel.textContent = value || 'All Sites';
      document.querySelectorAll('[data-site-value]').forEach((button) => {
        const selected = (button.dataset.siteValue || '') === value;
        button.classList.toggle('is-selected', selected);
        button.setAttribute('aria-selected', selected ? 'true' : 'false');
      });
      const localBranchFilters = ['branchFilter', 'assetBranchFilter'];
      localBranchFilters.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.disabled = !!value;
        const box = el.closest('.filter-box');
        if (box) box.classList.toggle('site-filter-locked', !!value);
      });
      const label = document.getElementById('notificationScope');
      if (label) label.textContent = value || 'All Sites';
      if (!this.state.reportFilters.branch) {
        const reportSite = document.getElementById('reportSiteFilter');
        if (reportSite) reportSite.value = value;
      }
    },

    effectiveWorkstationFilters() {
      return { ...this.state.workstationFilters, branch: this.state.globalSite || this.state.workstationFilters.branch || '' };
    },

    effectiveAssetFilters() {
      return { ...this.state.assetFilters, branch: this.state.globalSite || this.state.assetFilters.branch || '' };
    },

    effectiveEmployeeFilters() {
      return { ...this.state.employeeFilters, branch: this.state.globalSite || this.state.employeeFilters.branch || '' };
    },

    switchView(viewKey) {
      this.state.currentView = viewKey;

      document.querySelectorAll('.nav-item').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === viewKey);
      });
      const assetWrap = document.getElementById('assetNavWrap');
      if (assetWrap) assetWrap.classList.toggle('is-open', viewKey === 'assets' && this.state.assetSidebarExpanded);
      const reportWrap = document.getElementById('reportNavWrap');
      if (reportWrap) reportWrap.classList.toggle('is-open', viewKey === 'reports' && this.state.reportSidebarExpanded);

      document.querySelectorAll('.view').forEach(view => {
        view.classList.remove('active');
      });

      const target = document.getElementById('view-' + viewKey);
      if (target) {
        target.classList.add('active');
        window.requestAnimationFrame(() => {
          target.classList.add('view-enter');
          window.setTimeout(() => target.classList.remove('view-enter'), 150);
        });
      } else {
        this.showPlaceholderView(viewKey);
      }

      const titles = {
        dashboard: ['IT Asset Command Center', 'Real-time overview of inventory, deployments, maintenance, and asset movement.'],
        workstations: ['Workstation Inventory - One-Liner View', 'Overview of all workstation deployments across BSPI offices.'],
        assets: ['Asset Registry', 'Unified listing of all assets from all category tables.'],
        employees: ['Employee Module', 'Manage employee records used by workstation and asset assignments.'],
        reports: ['Reports Center', 'Generate official inventory, assignment, maintenance, and deployment reports.'],
        deploy: ['Deploy Operations', 'Assign available assets to employees and create workstation deployments.'],
        transfer: ['Transfer Operations', 'Move assets or complete workstations to another employee.'],
        checkin: ['Check-In Operations', 'Return assets, clear assignments, and set the next status.'],
        maintenance: ['Maintenance Operations', 'Mark assets for service and track open repair work.'],
        settings: ['Settings', 'Review configured sites, offices, categories, statuses, and counters.'],
        'manage-users': ['Manage Users', 'Review user records synced from employees and assignments.'],
        'audit-log': ['Audit Log', 'Trace asset, workstation, transfer, check-in, and maintenance activity.']
      };

      this.setText('pageTitle', (titles[viewKey] || [this.titleCase(viewKey), 'Module ready for expansion.'])[0]);
      this.setText('pageSubtitle', (titles[viewKey] || [this.titleCase(viewKey), 'Module ready for expansion.'])[1]);

      if (viewKey === 'dashboard' && !this.state.dashboardData) this.loadDashboard();
      if (viewKey === 'workstations' && !this.state.workstationData) this.loadWorkstations();
      if (viewKey === 'assets' && !this.state.assetRegistry) this.loadAssets();
      if (viewKey === 'employees' && !this.state.employeeData) this.loadEmployees();
      if (viewKey === 'reports' && !this.state.currentReport) this.loadReports();
      if (['deploy', 'transfer', 'checkin'].includes(viewKey)) {
        if (!this.state.operationData) {
          this.loadOperationView(viewKey);
        } else {
          this.renderOperationLists(viewKey, (this.state.operationData.assets && this.state.operationData.assets.items) || []);
          if (['transfer', 'checkin'].includes(viewKey)) {
            this.server('apiGetDashboardData', { branch: this.state.globalSite || '' })
              .then((data) => this.renderOperationHistory(viewKey, data.recentDeploymentActivity || []))
              .catch(() => {});
          }
        }
      }
      if (viewKey === 'maintenance' && !this.state.preventiveMaintenanceData) this.loadPreventiveMaintenance();
      if (viewKey === 'settings') this.renderSettingsAdmin();
      if (viewKey === 'manage-users' && !this.state.adminUserData) this.loadAdminUsers();
      if (viewKey === 'audit-log' && !this.state.auditLogData) this.loadAuditLog();
    },

    showPlaceholderView(viewKey) {
      let view = document.getElementById('view-' + viewKey);
      if (!view) {
        view = document.createElement('section');
        view.id = 'view-' + viewKey;
        view.className = 'view active';
        view.innerHTML = `
          <div class="section-card">
            <div class="empty-wrap">
              <div class="empty-card">
                <div style="font-size:44px;margin-bottom:10px;">⌁</div>
                <div style="font-weight:800;margin-bottom:6px;">${this.titleCase(viewKey)} module</div>
                <div class="muted">This page structure is reserved for the next build phase.</div>
              </div>
            </div>
          </div>
        `;
        document.getElementById('app-content').appendChild(view);
      }
      view.classList.add('active');
      window.requestAnimationFrame(() => {
        view.classList.add('view-enter');
        window.setTimeout(() => view.classList.remove('view-enter'), 150);
      });
    },

    loadWorkstations() {
      const tbody = document.getElementById('workstationTableBody');
      const meta = document.getElementById('workstationTableMeta');
      if (tbody) tbody.innerHTML = `<tr><td colspan="11">${this.loadingMarkup()}</td></tr>`;
      if (meta) meta.textContent = 'Loading workstation records...';
      this.setBusy(true);

      this.server('apiGetWorkstationList', this.effectiveWorkstationFilters())
        .then((res) => {
          this.state.workstationData = res;
          this.renderWorkstations(res);
          this.renderWorkstationKpis(res.kpis);
          this.renderRecentActivityFromWorkstations(res.items);
          this.renderComponentSummary(res.items);
        })
        .catch((error) => {
          if (tbody) tbody.innerHTML = `<tr><td colspan="11">${this.emptyMarkup('Load failed', error.message || 'Unable to load workstation records.')}</td></tr>`;
        })
        .finally(() => this.setBusy(false));
    },

    renderWorkstations(data) {
      const tbody = document.getElementById('workstationTableBody');
      const meta = document.getElementById('workstationTableMeta');
      const pill = document.getElementById('deployCountPill');
      const pageSizeSelect = document.getElementById('workstationPageSize');
      const prevBtn = document.getElementById('workstationPrevPage');
      const nextBtn = document.getElementById('workstationNextPage');
      const title = document.getElementById('workstationTableTitle');
      if (title) {
        const branch = this.state.globalSite || this.state.workstationFilters.branch || '';
        title.textContent = branch ? `${branch} Workstations` : 'All Workstations';
      }

      const items = this.sortedWorkstationItems((data && data.items) || []);
      if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="11">${this.emptyMarkup('No workstations yet', 'Create a workstation to populate this table.')}</td></tr>`;
        meta.textContent = 'Showing 0 workstation records';
        pill.textContent = '0 Deployed';
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) nextBtn.disabled = true;
        this.updateWorkstationSortHeads();
        return;
      }

      const pageSize = Number(this.state.workstationPageSize || 0);
      const total = items.length;
      const totalPages = pageSize ? Math.max(1, Math.ceil(total / pageSize)) : 1;
      const page = pageSize ? Math.min(Math.max(1, Number(this.state.workstationPage || 1)), totalPages) : 1;
      this.state.workstationPage = page;
      const startIndex = pageSize ? (page - 1) * pageSize : 0;
      const visibleItems = pageSize ? items.slice(startIndex, startIndex + pageSize) : items;
      if (pageSizeSelect) pageSizeSelect.value = pageSize ? String(pageSize) : 'all';

      tbody.innerHTML = visibleItems.map((row, idx) => `
        <tr class="clickable-row" data-workstation-row data-main-asset-number="${this.escapeHtml(row.mainAssetNumber)}" style="--row-index:${idx}">
          <td>${row.rowNumber}</td>
          <td><a class="table-link" data-main-asset-number="${this.escapeHtml(row.mainAssetNumber)}">${this.escapeHtml(row.mainAssetNumber)}</a></td>
          <td>${this.escapeHtml(row.user || '—')}</td>
          <td>${this.escapeHtml(row.office || '—')}</td>
          <td>${this.assetChip(row.systemUnitCode)}</td>
          <td>${this.assetChip(row.monitorCode)}</td>
          <td>${this.assetChip(row.mouseCode)}</td>
          <td>${this.assetChip(row.keyboardCode)}</td>
          <td>${this.assetChip(row.upsCode)}</td>
          <td>${this.assetChip(row.printerCode)}</td>
          <td>${this.badge(row.deploymentStatus, row.badgeTone)}</td>
        </tr>
      `).join('');

      meta.textContent = `Showing 1 – ${items.length} of ${items.length} workstations`;
      const from = startIndex + 1;
      const to = startIndex + visibleItems.length;
      meta.textContent = `Showing ${from} - ${to} of ${total} workstations${pageSize ? ` (page ${page} of ${totalPages})` : ''}`;
      pill.textContent = `${data.kpis.deployed || 0} Deployed`;
      if (prevBtn) {
        prevBtn.disabled = page <= 1;
        prevBtn.dataset.workstationPage = String(Math.max(1, page - 1));
      }
      if (nextBtn) {
        nextBtn.disabled = page >= totalPages;
        nextBtn.dataset.workstationPage = String(Math.min(totalPages, page + 1));
      }
      this.updateWorkstationSortHeads();
    },

    sortedWorkstationItems(items) {
      const sort = this.state.workstationSort || { key: 'mainAssetNumber', direction: 'asc' };
      const key = sort.key || 'mainAssetNumber';
      const direction = sort.direction === 'desc' ? -1 : 1;
      return [...items].sort((a, b) => {
        const av = a[key];
        const bv = b[key];
        const an = Number(av);
        const bn = Number(bv);
        if (Number.isFinite(an) && Number.isFinite(bn) && String(av).trim() !== '' && String(bv).trim() !== '') {
          return (an - bn) * direction;
        }
        return String(av || '').localeCompare(String(bv || ''), undefined, { numeric: true, sensitivity: 'base' }) * direction;
      });
    },

    updateWorkstationSortHeads() {
      const sort = this.state.workstationSort || {};
      document.querySelectorAll('[data-ws-sort]').forEach((button) => {
        const active = button.dataset.wsSort === sort.key;
        button.classList.toggle('active', active);
        button.dataset.direction = active ? sort.direction : '';
      });
    },

    renderWorkstationKpis(kpis) {
      const el = document.getElementById('workstationKpis');
      if (!el) return;

      const cards = [
        ['Total Workstations', kpis.totalWorkstations || 0, 'All Branches', 'soft-blue', 'workstation-icon'],
        ['Ready to Deploy', kpis.readyToDeploy || 0, 'Available', 'soft-green', 'available-icon'],
        ['Deployed', kpis.deployed || 0, 'In Use', 'soft-purple', 'assigned-icon'],
        ['In Repair', kpis.inRepair || 0, 'For Service', 'soft-orange', 'maintenance-icon'],
        ['All Sites', kpis.allSites || 0, 'Coverage', 'soft-gray', 'dashboard-icon']
      ];

      el.innerHTML = cards.map((card, idx) => `
        <div class="kpi-card ${card[3]}" style="--row-index:${idx}">
          <div>
            <div class="kpi-meta">${card[0]}</div>
            <div class="kpi-value">${card[1]}</div>
            <div class="kpi-sub">${card[2]}</div>
          </div>
          <div class="kpi-icon">${this.iconImg(card[4], 'kpi-img')}</div>
        </div>
      `).join('');
    },

    renderRecentActivityFromWorkstations(items) {
      const el = document.getElementById('recentActivityList');
      if (!el) return;

      const recent = (items || []).slice(0, 6);
      if (!recent.length) {
        el.innerHTML = this.emptyMarkup('No recent activity', 'Deployment activity will appear here once records exist.');
        return;
      }

      el.innerHTML = recent.map((item, idx) => `
        <div class="feed-item" style="--row-index:${idx}">
          <div class="feed-icon">${this.iconImg('activity-icon', 'feed-img')}</div>
          <div>
            <div class="feed-title">
              <a class="table-link" data-main-asset-number="${this.escapeHtml(item.mainAssetNumber)}">${this.escapeHtml(item.mainAssetNumber)}</a>
              ${item.user ? ' - ' + this.escapeHtml(item.user) : ''}
            </div>
            <div class="feed-meta">
              ${this.escapeHtml(item.dateDeployed || 'No deployment date')} · ${this.escapeHtml(item.office || 'No office')} · ${this.escapeHtml(item.deploymentStatus || 'No status')}
            </div>
          </div>
        </div>
      `).join('');
    },

    renderComponentSummary(items) {
      const counts = {
        'System Units': 0,
        'Monitors': 0,
        'Mice': 0,
        'Keyboards': 0,
        'UPS': 0,
        'Printers': 0
      };

      (items || []).forEach(item => {
        if (item.systemUnitCode) counts['System Units']++;
        if (item.monitorCode) counts['Monitors']++;
        if (item.mouseCode) counts['Mice']++;
        if (item.keyboardCode) counts['Keyboards']++;
        if (item.upsCode) counts['UPS']++;
        if (item.printerCode) counts['Printers']++;
      });

      const total = Object.values(counts).reduce((a, b) => a + b, 0);
      const legend = document.getElementById('componentLegend');
      const totalValue = document.getElementById('componentTotalValue');

      if (totalValue) totalValue.textContent = total;
      if (!legend) return;

      const colors = ['#6d93ff', '#7fd1d4', '#ffbe46', '#f38c8c', '#b390ff', '#8ecfa7', '#4f7dff'];
      legend.innerHTML = Object.keys(counts).map((label, idx) => `
        <div class="legend-row">
          <span class="legend-dot" style="background:${colors[idx % colors.length]}"></span>
          <span>${label}</span>
          <strong>${counts[label]}</strong>
        </div>
      `).join('');
    },

    loadDashboard() {
      const view = document.getElementById('view-dashboard');
      if (!view) return;
      this.setBusy(true);

      this.server('apiGetDashboardData', { branch: this.state.globalSite || '' })
        .then((res) => {
          this.state.dashboardData = res;
          this.renderDashboard(res);
        })
        .catch(this.handleError)
        .finally(() => this.setBusy(false));
    },

    renderDashboard(data) {
      const kpis = data.kpis || {};
      const statusCounts = data.statusCounts || {};
      const totalAssets = Number(kpis.totalAssets || 0);
      const totalDeployed = Number(kpis.totalDeployed || statusCounts.assigned || 0);
      const ready = Number(kpis.readyToDeploy || statusCounts.available || 0);
      const repair = Number(kpis.inRepair || statusCounts.maintenance || 0);
      const disposed = Number(kpis.disposed || statusCounts.retired || 0);
      const unclassified = Number(kpis.unclassified || statusCounts.unclassified || 0);
      const available = Math.max(ready, 0);
      const assigned = totalDeployed;
      this.updateNotificationPanel({ repair, unclassified, disposed, available });

      const updated = document.getElementById('dashboardLastUpdated');
      if (updated) {
        const now = new Date();
        updated.textContent = `Last updated: ${now.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })} ${now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
      }

      const kpiWrap = document.getElementById('dashboardKpis');
      if (kpiWrap) {
        const cards = [
          { label: 'Total Inventory', value: totalAssets, note: 'All registered assets', icon: 'dashboard-total-inventory', tone: 'blue', trend: '↗ This month', report: 'investment' },
          { label: 'Total Asset Value', value: this.money(kpis.totalValue || 0), note: 'Acquisition price', icon: 'inventory-icon', tone: 'blue', trend: '', report: 'investment' },
          { label: 'Assigned Devices', value: assigned, note: 'In use by employees', icon: 'dashboard-assigned-devices', tone: 'green', trend: '↗ This month' },
          { label: 'Available Devices', value: available, note: 'Ready to deploy', icon: 'dashboard-available-devices', tone: 'violet', trend: '↓ This month' },
          { label: 'Under Maintenance', value: repair, note: 'Currently in repair', icon: 'dashboard-under-maintenance', tone: 'amber', trend: '↗ This month' },
          { label: 'Retired Assets', value: disposed, note: 'Retired / disposed', icon: 'dashboard-retired-assets', tone: 'red', trend: '' }
        ];

        kpiWrap.innerHTML = cards.map(card => {
          const isLong = String(card.value).length > 8;
          return `
            <div class="command-kpi-card ${card.tone}${card.report ? ' is-clickable' : ''}" ${card.report ? `data-dashboard-report="${card.report}" role="button" tabindex="0" title="Open ${card.label} report"` : ''}>
              <div class="kpi-symbol">${this.iconImg(card.icon, 'kpi-symbol-img')}</div>
              <div>
                <div class="kpi-label">${card.label}</div>
                <div class="kpi-number${isLong ? ' long-value' : ''}">${card.value}</div>
                <div class="kpi-note">${card.note}</div>
                ${card.trend ? `<div class="kpi-trend">${card.trend}</div>` : ''}
              </div>
            </div>
          `;
        }).join('');
      }

      const deploymentRows = data.recentDeploymentActivity || [];
      const repairRows = data.recentRepairActivity || [];
      const statusRows = [
        ['Assigned', assigned, '#2f6bff'],
        ['Available', available, '#22a957'],
        ['Maintenance', repair, '#f59e0b'],
        ['Retired', disposed, '#ef4444'],
        ['Unclassified', unclassified, '#94a3b8']
      ];
      const statusTotal = Math.max(totalAssets, assigned + available + repair + disposed + unclassified, 1);

      const statusCard = document.getElementById('assetStatusCard');
      if (statusCard) {
        statusCard.innerHTML = `
          <div class="command-card-head"><h3>Assets by Status</h3><a data-dashboard-report="status-summary">View full report ›</a></div>
          <div class="status-layout">
            <div class="donut-mini" style="--assigned:${(assigned/statusTotal)*100};--available:${(available/statusTotal)*100};--maintenance:${(repair/statusTotal)*100};--retired:${(disposed/statusTotal)*100};--unclassified:${(unclassified/statusTotal)*100};">
              <strong>${totalAssets}</strong><span>Total</span>
            </div>
            <div class="status-legend">
              ${statusRows.map(row => `
                <div class="status-legend-row">
                  <span class="dot" style="background:${row[2]}"></span>
                  <span>${row[0]}</span>
                  <strong>${row[1]}</strong>
                </div>
              `).join('')}
            </div>
          </div>
        `;
      }

      const siteCard = document.getElementById('assetSiteCard');
      if (siteCard) {
        const sites = (data.assetsBySite || []).slice().sort((a, b) => Number(b.value || 0) - Number(a.value || 0)).slice(0, 5);
        const maxSite = Math.max(...sites.map(row => Number(row.value || 0)), 1);
        siteCard.innerHTML = `
          <div class="command-card-head"><h3>Assets by Site</h3><a data-dashboard-report="inventory">View full report ›</a></div>
          <div class="site-bars compact-site-bars">
            ${(sites.length ? sites : [{label:'No Site', value:0}]).map(row => {
              const value = Number(row.value || 0);
              return `
                <div class="site-bar-row compact-site-row">
                  <div class="site-bar"><i style="height:${Math.max(8, (value / maxSite) * 100)}%"></i></div>
                  <strong>${value}</strong>
                  <span>${this.escapeHtml(row.label || 'No Site')}</span>
                </div>
              `;
            }).join('')}
          </div>
        `;
      }

      const categoryCard = document.getElementById('assetCategoryCard');
      if (categoryCard) {
        const categories = (data.assetsByCategory || []).slice().sort((a, b) => Number(b.value || 0) - Number(a.value || 0)).slice(0, 6);
        categoryCard.innerHTML = `
          <div class="command-card-head"><h3>Assets by Category</h3><a data-dashboard-report="category-summary">View full report ›</a></div>
          <table class="mini-table dashboard-category-table">
            <thead><tr><th>Category</th><th>Total</th><th>Assigned</th><th>Available</th><th>Maintenance</th><th>Retired</th></tr></thead>
            <tbody>
              ${(categories.length ? categories : [{label:'No category', value:0, assigned:0, available:0, maintenance:0, retired:0}]).map(row => `
                <tr>
                  <td><span class="category-cell">${this.categoryIconImg(row.label, 'mini-category-img')}${this.escapeHtml(row.label || 'Uncategorized')}</span></td>
                  <td><strong>${Number(row.value || 0)}</strong></td>
                  <td>${Number(row.assigned || 0)}</td>
                  <td>${Number(row.available || 0)}</td>
                  <td>${Number(row.maintenance || 0)}</td>
                  <td>${Number(row.retired || 0)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
      }

      const attentionCard = document.getElementById('attentionCard');
      if (attentionCard) {
        const missingSerial = unclassified;
        const alerts = [
          ['alert-icon', repair, 'Open repairs', 'Maintenance tickets to review'],
          ['warranty-icon', disposed, 'Retired assets', 'For audit or disposal record'],
          ['missing-icon', missingSerial, 'Unclassified assets', 'Check missing status data']
        ];
        attentionCard.innerHTML = `
          <div class="command-card-head"><h3>Needs Attention</h3><a data-dashboard-report="attention">View all alerts ›</a></div>
          <div class="attention-list">
            ${alerts.map(a => `<div class="attention-row"><span>${this.iconImg(a[0], 'attention-img')}</span><strong>${a[1]}</strong><b>${a[2]}</b><em>${a[3]}</em><i>›</i></div>`).join('')}
          </div>
        `;
      }

      const recentCard = document.getElementById('recentActivityCard');
      if (recentCard) {
        const rows = deploymentRows.slice(0, 5);
        recentCard.innerHTML = `
          <div class="command-card-head"><h3>Recent Activity</h3><a data-view-target="audit-log">View all</a></div>
          <div class="activity-feed">
            ${(rows.length ? rows : [{action:'No activity yet', transactionDate:'', assetCode:'—', toUser:'No records found'}]).map(row => `
              <div class="activity-row">
                <span class="activity-icon">${this.iconImg('activity-icon', 'activity-img')}</span>
                <div><strong>${this.escapeHtml(row.assetCode || row.action || 'Activity')}</strong><small>${this.escapeHtml(row.action || 'Deployment activity')} ${row.toUser ? 'to ' + this.escapeHtml(row.toUser) : ''}</small></div>
                <time>${this.escapeHtml(row.transactionDate || '')}</time>
              </div>
            `).join('')}
          </div>
        `;
      }

      const ticketCard = document.getElementById('maintenanceTicketCard');
      if (ticketCard) {
        const rows = repairRows.slice(0, 3);
        ticketCard.innerHTML = `
          <div class="command-card-head"><h3>Open Maintenance Tickets</h3><a data-view-target="maintenance">View all</a></div>
          <div class="ticket-list">
            ${(rows.length ? rows : [{assetCode:'No repairs yet', issue:'No repair records found.', repairStatus:'Low'}]).map((row, idx) => `
              <div class="ticket-row">
                <span class="ticket-icon">${this.iconImg('maintenance-icon', 'ticket-img')}</span><div><strong>${this.escapeHtml(row.assetCode || 'Asset')}</strong><small>${this.escapeHtml(row.issue || 'No issue details')}</small></div><b class="priority p${idx}">${idx === 0 ? 'High' : idx === 1 ? 'Medium' : 'Low'}</b>
              </div>
            `).join('')}
          </div>
        `;
      }
    },

    toggleNotificationPanel() {
      const panel = document.getElementById('notificationPanel');
      const button = document.querySelector('[data-action="toggle-notifications"]');
      if (!panel) return;
      const isOpen = !panel.classList.contains('hidden');
      panel.classList.toggle('hidden', isOpen);
      if (button) button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      if (!isOpen) this.updateNotificationPanel();
    },

    closeNotificationPanel() {
      const panel = document.getElementById('notificationPanel');
      const button = document.querySelector('[data-action="toggle-notifications"]');
      if (panel) panel.classList.add('hidden');
      if (button) button.setAttribute('aria-expanded', 'false');
    },

    updateNotificationPanel(counts) {
      const data = counts || this.notificationCountsFromDashboard();
      const items = [
        { count: Number(data.repair || 0), title: 'Open repairs', note: 'Maintenance items need review.', report: 'maintenance' },
        { count: Number(data.unclassified || 0), title: 'Unclassified assets', note: 'Missing or unclear asset status.', report: 'attention' },
        { count: Number(data.disposed || 0), title: 'Retired assets', note: 'Ready for audit or disposal record.', report: 'retired' }
      ].filter(item => item.count > 0);

      const actionableCount = items.reduce((sum, item) => sum + item.count, 0);
      const badge = document.getElementById('notificationCount');
      if (badge) {
        badge.textContent = String(actionableCount);
        badge.classList.toggle('hidden', actionableCount === 0);
      }

      const scope = document.getElementById('notificationScope');
      if (scope) scope.textContent = this.state.globalSite || 'All Sites';

      const list = document.getElementById('notificationList');
      if (!list) return;
      if (!items.length) {
        list.innerHTML = '<div class="notification-empty">No alerts to review.</div>';
        return;
      }
      list.innerHTML = items.map(item => `
        <button class="notification-item" type="button" data-dashboard-report="${item.report}">
          <strong>${item.count}</strong>
          <span><b>${this.escapeHtml(item.title)}</b><small>${this.escapeHtml(item.note)}</small></span>
        </button>
      `).join('');
    },

    notificationCountsFromDashboard() {
      const data = this.state.dashboardData || {};
      const kpis = data.kpis || {};
      const statusCounts = data.statusCounts || {};
      return {
        repair: Number(kpis.inRepair || statusCounts.maintenance || 0),
        unclassified: Number(kpis.unclassified || statusCounts.unclassified || 0),
        disposed: Number(kpis.disposed || statusCounts.retired || 0),
        available: Number(kpis.readyToDeploy || statusCounts.available || 0)
      };
    },

    loadAssets() {
      const body = document.getElementById('assetRegistryBody');
      const meta = document.getElementById('assetRegistryMeta');
      if (body) body.innerHTML = `<tr><td colspan="11">${this.loadingMarkup()}</td></tr>`;
      if (meta) meta.textContent = 'Loading assets...';
      this.renderAssetSidebarCategoryPlaceholders();
      this.setBusy(true);

      this.server('apiGetUnifiedAssetRegistry', this.effectiveAssetFilters())
        .then((res) => {
          this.state.assetRegistry = res;
          this.renderAssets(res);
          if (!this.state.assetFilters.category) {
            this.updateAssetCategoryFilterCounts((res && res.items) || []);
          } else {
            this.updateAssetSidebarActiveCategory();
          }
        })
        .catch((error) => {
          if (body) body.innerHTML = `<tr><td colspan="11">${this.emptyMarkup('Load failed', error.message || 'Unable to load assets.')}</td></tr>`;
        })
        .finally(() => this.setBusy(false));
    },

    assetCategoryOptions() {
      const settings = this.state.settings || {};
      const categoryOrder = ['System Unit', 'Monitor', 'Mouse', 'Keyboard', 'UPS', 'Printer', 'Laptop'];
      const configured = settings.assetCategories || [];
      const categories = [];
      const addCategory = (category) => {
        const label = String(category || '').trim();
        if (!label || categories.some(item => item.toLowerCase() === label.toLowerCase())) return;
        categories.push(label);
      };
      categoryOrder.forEach(addCategory);
      configured.forEach(addCategory);
      return categories;
    },

    renderAssetSidebarCategoryPlaceholders() {
      this.renderAssetSidebarCategories(this.assetCategoryOptions(), {}, null, false);
    },

    updateAssetCategoryFilterCounts(items) {
      const select = document.getElementById('assetCategoryFilter');
      const current = (select && select.value) || this.state.assetFilters.category || '';
      const categories = this.assetCategoryOptions();
      const addCategory = (category) => {
        const label = String(category || '').trim();
        if (!label || categories.some(item => item.toLowerCase() === label.toLowerCase())) return;
        categories.push(label);
      };

      const counts = {};
      (items || []).forEach(item => {
        const category = item.assetCategory || 'Uncategorized';
        addCategory(category);
        counts[category] = (counts[category] || 0) + 1;
      });

      const total = (items || []).length;
      if (select) {
        select.innerHTML = `<option value="">All Categories (${total})</option>` + categories.map(category => {
          const count = counts[category] || 0;
          return `<option value="${this.escapeHtml(category)}">${this.escapeHtml(category)} (${count})</option>`;
        }).join('');
        select.value = current;
      }
      this.renderAssetSidebarCategories(categories, counts, total, true);
    },

    renderAssetSidebarCategories(categories, counts, total, showCounts = true) {
      const wrap = document.getElementById('assetSidebarCategories');
      if (!wrap) return;
      const current = this.state.assetFilters.category || '';
      const rows = [
        { label: 'All Assets', value: '', count: total },
        ...(categories || []).map(category => ({
          label: category,
          value: category,
          count: counts[category] || 0
        }))
      ];
      wrap.innerHTML = rows.map(row => `
        <button class="asset-category-item ${String(row.value).toLowerCase() === String(current).toLowerCase() ? 'is-active' : ''}" type="button" data-asset-sidebar-category="${this.escapeHtml(row.value)}">
          <span>${row.value ? this.categoryIconImg(row.value, 'asset-category-mini-icon') : ''}${this.escapeHtml(row.label)}</span>
          <strong>${showCounts ? Number(row.count || 0) : ''}</strong>
        </button>
      `).join('');
    },

    updateAssetSidebarActiveCategory() {
      const current = String(this.state.assetFilters.category || '').toLowerCase();
      document.querySelectorAll('[data-asset-sidebar-category]').forEach(button => {
        button.classList.toggle('is-active', String(button.dataset.assetSidebarCategory || '').toLowerCase() === current);
      });
    },

    setAssetCategoryFromSidebar(category) {
      this.state.assetSidebarExpanded = true;
      this.state.assetFilters.category = category || '';
      this.setValue('assetCategoryFilter', this.state.assetFilters.category);
      this.updateAssetSidebarActiveCategory();
      this.applyAssetColumnVisibility();
      if (this.state.currentView !== 'assets') {
        this.switchView('assets');
      }
      this.loadAssets();
    },

    reportTypeOptions() {
      return [
        ['inventory', 'Inventory'],
        ['status-summary', 'Status Summary'],
        ['assigned', 'Assigned Assets'],
        ['available', 'Available Assets'],
        ['maintenance', 'Maintenance'],
        ['retired', 'Retired'],
        ['attention', 'Needs Attention'],
        ['investment', 'Lifecycle Forecast'],
        ['employees', 'Employees'],
        ['workstations', 'Workstations']
      ];
    },

    renderReportSidebarTypes() {
      const wrap = document.getElementById('reportSidebarTypes');
      if (!wrap) return;
      const current = this.state.reportFilters.type || 'inventory';
      wrap.innerHTML = this.reportTypeOptions().map(([value, label]) => `
        <button class="report-sidebar-item ${value === current ? 'is-active' : ''}" type="button" data-report-sidebar-type="${this.escapeHtml(value)}">
          <span>${this.escapeHtml(label)}</span>
        </button>
      `).join('');
    },

    updateReportSidebarActiveType() {
      const current = this.state.reportFilters.type || 'inventory';
      document.querySelectorAll('[data-report-sidebar-type]').forEach(button => {
        button.classList.toggle('is-active', button.dataset.reportSidebarType === current);
      });
    },

    setReportTypeFromSidebar(type) {
      this.state.reportSidebarExpanded = true;
      this.state.reportFilters.type = type || 'inventory';
      this.setValue('reportTypeSelect', this.state.reportFilters.type);
      this.updateReportSidebarActiveType();
      if (this.state.currentView !== 'reports') {
        this.switchView('reports');
        return;
      }
      this.loadReports();
    },

    renderAssets(data) {
      const body = document.getElementById('assetRegistryBody');
      const meta = document.getElementById('assetRegistryMeta');
      const items = (data && data.items) || [];
      if (!body) return;

      if (!items.length) {
        body.innerHTML = `<tr><td colspan="11">${this.emptyMarkup('No assets found', 'Add records to the asset tables to populate the registry.')}</td></tr>`;
        if (meta) meta.textContent = 'Showing 0 assets';
        this.applyAssetColumnVisibility();
        return;
      }

      body.innerHTML = items.map((row, idx) => `
        <tr data-row-asset-code="${this.escapeHtml(row.assetCode || '')}" style="--row-index:${idx}">
          <td class="select-col"><input class="asset-row-select" type="checkbox" value="${this.escapeHtml(row.assetCode || '')}" aria-label="Select ${this.escapeHtml(row.assetCode || 'asset')}"></td>
          <td data-col="assetCode"><span class="asset-code-cell"><span class="asset-row-icon">${this.categoryIconImg(row.assetCategory, 'asset-table-img')}</span><a class="table-link" data-asset-code="${this.escapeHtml(row.assetCode)}">${this.escapeHtml(row.assetCode)}</a></span></td>
          <td data-col="category">${this.escapeHtml(row.assetCategory || '—')}</td>
          <td data-col="brand">${this.escapeHtml(row.brand || '—')}</td>
          <td data-col="model">${this.escapeHtml(row.model || '—')}</td>
          <td data-col="serial">${this.escapeHtml(row.serialNumber || '—')}</td>
          <td data-col="mainAsset">${this.escapeHtml(row.assignedMainAssetNo || '—')}</td>
          <td data-col="user">${this.escapeHtml(row.currentUser || '—')}</td>
          <td data-col="branch">${this.escapeHtml(row.reportingBranch || '—')}</td>
          <td data-col="office">${this.escapeHtml(row.office || '—')}</td>
          <td data-col="status" class="status-cell">${this.badge(row.assetStatus, row.badgeTone)}</td>
        </tr>
      `).join('');

      if (meta) meta.textContent = `Showing ${items.length} asset records`;
      const selectAll = document.getElementById('assetSelectAll');
      if (selectAll) selectAll.checked = false;
      this.applyAssetColumnVisibility();
    },

    loadEmployees() {
      const body = document.getElementById('employeeTableBody');
      const meta = document.getElementById('employeeTableMeta');
      if (body) body.innerHTML = `<tr><td colspan="8">${this.loadingMarkup()}</td></tr>`;
      if (meta) meta.textContent = 'Loading employees...';
      this.setBusy(true);

      this.server('apiGetEmployeeList', this.effectiveEmployeeFilters())
        .then((res) => {
          this.state.employeeData = res;
          this.renderEmployees(res);
        })
        .catch((error) => {
          if (body) body.innerHTML = `<tr><td colspan="8">${this.emptyMarkup('Load failed', error.message || 'Unable to load employees.')}</td></tr>`;
        })
        .finally(() => this.setBusy(false));
    },

    renderEmployees(data) {
      const body = document.getElementById('employeeTableBody');
      const meta = document.getElementById('employeeTableMeta');
      const pill = document.getElementById('employeeCountPill');
      const items = (data && data.items) || [];
      if (!body) return;

      if (!items.length) {
        body.innerHTML = `<tr><td colspan="8">${this.emptyMarkup('No employees found', 'Add an employee to populate this table.')}</td></tr>`;
        if (meta) meta.textContent = 'Showing 0 employees';
        if (pill) pill.textContent = '0 Employees';
        return;
      }

      body.innerHTML = items.map((row, idx) => `
        <tr style="--row-index:${idx}">
          <td><strong>${this.escapeHtml(row.employeeId || '')}</strong></td>
          <td><a class="table-link" data-employee-id="${this.escapeHtml(row.employeeId || '')}">${this.escapeHtml(row.employeeName || '')}</a></td>
          <td>${this.escapeHtml(row.reportingBranch || '-')}</td>
          <td>${this.escapeHtml(row.office || '-')}</td>
          <td>${this.employeeActiveToggle(row)}</td>
          <td>${this.escapeHtml(row.idSource || '-')}</td>
          <td>${this.escapeHtml(row.updatedAt || '-')}</td>
          <td><button class="inline-btn compact" type="button" data-action="edit-employee" data-employee-id="${this.escapeHtml(row.employeeId || '')}">Edit</button></td>
        </tr>
      `).join('');

      if (meta) meta.textContent = `Showing ${items.length} employee${items.length === 1 ? '' : 's'}`;
      if (pill) pill.textContent = `${items.length} Employee${items.length === 1 ? '' : 's'}`;
    },

    employeeActiveToggle(row) {
      const status = String(row.active || 'Yes');
      const isResigned = status.toLowerCase() === 'resigned';
      const isActive = !isResigned && status.toLowerCase() !== 'no';
      const next = isActive ? 'No' : 'Yes';
      return `
        <button class="employee-switch ${isActive ? 'is-active' : 'is-inactive'} ${isResigned ? 'is-resigned' : ''}" type="button"
          data-action="toggle-employee-active"
          data-employee-id="${this.escapeHtml(row.employeeId || '')}"
          data-next-active="${next}"
          aria-label="Set ${this.escapeHtml(row.employeeName || 'employee')} ${next === 'Yes' ? 'active' : 'inactive'}">
          <span></span><b>${isResigned ? 'Resigned' : (isActive ? 'Active' : 'Inactive')}</b>
        </button>
      `;
    },

    collectEmployeePayload() {
      return {
        employeeId: this.valueOf('employeeId'),
        employeeName: this.valueOf('employeeName'),
        reportingBranch: this.valueOf('employeeBranch'),
        office: this.valueOf('employeeOffice'),
        active: this.state.employeeFormMode === 'edit'
          ? (this.state.editingEmployeeActive || 'Yes')
          : 'Yes'
      };
    },

    submitEmployeeForm() {
      const payload = this.collectEmployeePayload();
      const status = document.getElementById('employeeFormStatus');
      if (status) status.textContent = 'Saving employee...';
      this.setEmployeeSubmitting(true);

      const method = this.state.employeeFormMode === 'edit' ? 'apiUpdateEmployee' : 'apiCreateEmployee';
      this.server(method, payload)
        .then((res) => {
          if (status) status.textContent = `Saved ${res.employeeId || 'employee'} to Employees.`;
          this.resetEmployeeForm();
          this.state.employeeData = null;
          this.loadEmployees();
        })
        .catch((error) => {
          if (status) status.textContent = error.message || 'Unable to save employee.';
        })
        .finally(() => this.setEmployeeSubmitting(false));
    },

    resetEmployeeForm() {
      const form = document.getElementById('employeeForm');
      if (form) form.reset();
      this.state.employeeFormMode = 'create';
      this.state.editingEmployeeActive = 'Yes';
      const id = document.getElementById('employeeId');
      if (id) id.readOnly = false;
      const status = document.getElementById('employeeFormStatus');
      if (status) status.textContent = '';
      const btn = document.getElementById('employeeSubmitBtn');
      if (btn) btn.textContent = 'Save Employee';
    },

    editEmployeeFromTable(employeeId) {
      const row = ((this.state.employeeData && this.state.employeeData.items) || [])
        .find(item => String(item.employeeId || '') === String(employeeId || ''));
      if (!row) return;
      this.state.employeeFormMode = 'edit';
      this.state.editingEmployeeActive = row.active || 'Yes';
      this.setValue('employeeId', row.employeeId);
      this.setValue('employeeName', row.employeeName);
      this.setValue('employeeBranch', row.reportingBranch);
      this.setValue('employeeOffice', row.office);
      const id = document.getElementById('employeeId');
      if (id) id.readOnly = true;
      const status = document.getElementById('employeeFormStatus');
      if (status) status.textContent = `Editing ${row.employeeId}. Active status is changed from the table toggle.`;
      const btn = document.getElementById('employeeSubmitBtn');
      if (btn) btn.textContent = 'Update Employee';
      document.getElementById('employeeForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    toggleEmployeeActive(employeeId, nextActive) {
      const row = ((this.state.employeeData && this.state.employeeData.items) || [])
        .find(item => String(item.employeeId || '') === String(employeeId || ''));
      if (!row) return;
      const next = nextActive === 'No' ? 'No' : 'Yes';
      if (!confirm(`Are you sure you want to mark ${row.employeeName || row.employeeId} as ${next === 'Yes' ? 'active' : 'inactive'}?`)) return;
      this.server('apiUpdateEmployee', {
        employeeId: row.employeeId,
        employeeName: row.employeeName,
        reportingBranch: row.reportingBranch,
        office: row.office,
        active: next
      })
        .then(() => {
          this.state.employeeData = null;
          this.loadEmployees();
        })
        .catch(this.handleError);
    },

    openEmployeeAssets(employeeId) {
      this.openDrawer(this.loadingMarkup());
      this.server('apiGetEmployeeAssets', employeeId)
        .then((res) => {
          if (!res.found) {
            this.openDrawer(this.emptyMarkup('Employee not found', res.message || 'No employee record matched.'));
            return;
          }
          const employee = res.employee || {};
          const assets = res.assets || [];
          const workstations = res.workstations || [];
          const assetList = assets.length
            ? assets.map(asset => `
              <div class="mini-card">
                <div class="mini-title"><a class="table-link" data-asset-code="${this.escapeHtml(asset.assetCode || '')}">${this.escapeHtml(asset.assetCode || 'Asset')}</a> - ${this.escapeHtml(asset.assetCategory || 'Asset')}</div>
                <div class="mini-meta">${this.escapeHtml([asset.brand, asset.model].filter(Boolean).join(' ') || 'No model')}</div>
                <div class="mini-note">Main Asset: ${this.escapeHtml(asset.assignedMainAssetNo || '-')}<br>Serial: ${this.escapeHtml(asset.serialNumber || '-')}<br>Status: ${this.escapeHtml(asset.assetStatus || '-')}</div>
              </div>
            `).join('')
            : this.emptyMarkup('No deployed assets', 'No assets are currently linked to this employee.');
          const workstationList = workstations.length
            ? workstations.map(ws => `
              <div class="mini-card">
                <div class="mini-title"><a class="table-link" data-main-asset-number="${this.escapeHtml(ws.mainAssetNumber || '')}">${this.escapeHtml(ws.mainAssetNumber || 'Workstation')}</a></div>
                <div class="mini-note">Office: ${this.escapeHtml(ws.office || '-')}<br>Status: ${this.escapeHtml(ws.deploymentStatus || '-')}</div>
              </div>
            `).join('')
            : this.emptyMarkup('No workstation records', 'No workstation records matched this employee.');
          this.openDrawer(`
            <div class="drawer-head">
              <div>
                <div class="muted" style="font-size:13px;font-weight:700;">Employee Assets</div>
                <h3 class="drawer-title">${this.escapeHtml(employee.employeeName || employee.employeeId || 'Employee')}</h3>
              </div>
              <button class="drawer-close" type="button" data-action="close-drawer">x</button>
            </div>
            <div class="drawer-scroll">
              <div class="detail-card"><div class="detail-card-body">${this.kvRows([
                ['Employee ID', employee.employeeId],
                ['Reporting Branch', employee.reportingBranch],
                ['Office', employee.office],
                ['Active', employee.active]
              ])}</div></div>
              <div class="detail-card"><div class="detail-card-head">Deployed Assets</div><div class="detail-card-body assigned-asset-list">${assetList}</div></div>
              <div class="detail-card"><div class="detail-card-head">Workstations</div><div class="detail-card-body history-list">${workstationList}</div></div>
            </div>
            <div class="drawer-foot">
              <button class="light-btn" type="button" data-action="mark-employee-resigned" data-employee-id="${this.escapeHtml(employee.employeeId || '')}">Mark Resigned</button>
            </div>
          `);
        })
        .catch((error) => {
          this.openDrawer(this.emptyMarkup('Load failed', error.message || 'Unable to load employee assets.'));
        });
    },

    markEmployeeResigned(employeeId) {
      if (!employeeId) return;
      if (!confirm('Mark this employee as resigned? Deployed assets will remain visible until you transfer or return them.')) return;
      this.server('apiGetEmployeeAssets', employeeId)
        .then((res) => {
          const employee = res.employee || {};
          return this.server('apiUpdateEmployee', {
            employeeId: employee.employeeId,
            employeeName: employee.employeeName,
            reportingBranch: employee.reportingBranch,
            office: employee.office,
            active: 'Resigned'
          });
        })
        .then(() => {
          this.state.employeeData = null;
          this.loadEmployees();
          this.openEmployeeAssets(employeeId);
        })
        .catch(this.handleError);
    },

    transferActiveWorkstation() {
      const detail = this.state.activeWorkstationDetail;
      const dep = detail && detail.deployment;
      if (!dep || !dep.mainAssetNumber) return;
      const target = prompt('Transfer to which employee? Enter exact Employee ID or Employee Name.');
      if (!target || !target.trim()) return;
      const markOldResigned = confirm('Is this transfer because the current employee resigned? Choose OK to mark the old employee as Resigned after transfer.');
      const assetCount = ((detail.assignedAssets || []).length);
      if (!confirm(`Transfer ${dep.mainAssetNumber} and ${assetCount} linked asset(s) from ${dep.user || 'current user'} to ${target.trim()}?`)) return;

      this.server('apiTransferWorkstation', {
        mainAssetNumber: dep.mainAssetNumber,
        toEmployee: target.trim(),
        markOldResigned,
        encodedBy: 'Admin',
        remarks: markOldResigned ? 'Transfer due to employee resignation.' : 'Transfer from workstation details.'
      })
        .then(() => {
          this.state.workstationData = null;
          this.state.dashboardData = null;
          this.state.assetRegistry = null;
          this.state.employeeData = null;
          this.loadWorkstations();
          if (this.state.currentView === 'dashboard') this.loadDashboard();
          if (this.state.currentView === 'assets') this.loadAssets();
          if (this.state.currentView === 'employees') this.loadEmployees();
          this.openWorkstationDetails(dep.mainAssetNumber);
        })
        .catch(this.handleError);
    },

    transferActiveAsset() {
      const detail = this.state.activeAssetDetail;
      const asset = detail && detail.asset;
      if (!asset || !asset.assetCode) return;
      const target = prompt('Transfer this asset to which employee? Enter exact Employee ID or Employee Name.');
      if (!target || !target.trim()) return;
      if (!confirm(`Transfer ${asset.assetCode} from ${asset.currentUser || 'current user'} to ${target.trim()}?`)) return;

      this.server('apiTransferAsset', {
        assetCode: asset.assetCode,
        toEmployee: target.trim(),
        encodedBy: 'Admin',
        remarks: 'Transfer from asset details.'
      })
        .then(() => {
          this.state.assetRegistry = null;
          this.state.workstationData = null;
          this.state.dashboardData = null;
          this.state.employeeData = null;
          if (this.state.currentView === 'assets') this.loadAssets();
          if (this.state.currentView === 'workstations') this.loadWorkstations();
          if (this.state.currentView === 'dashboard') this.loadDashboard();
          if (this.state.currentView === 'employees') this.loadEmployees();
          this.openAssetDetails(asset.assetCode);
        })
        .catch(this.handleError);
    },

    applyAssetColumnVisibility() {
      const visibility = this.state.assetColumnVisibility || {};
      const laptopOnly = String(this.state.assetFilters.category || '').toLowerCase() === 'laptop';
      document.querySelectorAll('[data-col]').forEach(el => {
        const key = el.dataset.col;
        el.classList.toggle('hidden-col', visibility[key] === false || (laptopOnly && key === 'mainAsset'));
      });
      const mainAssetToggle = document.querySelector('[data-asset-column-toggle="mainAsset"]');
      if (mainAssetToggle) {
        mainAssetToggle.disabled = laptopOnly;
        const label = mainAssetToggle.closest('label');
        if (label) label.classList.toggle('hidden', laptopOnly);
      }
    },

    openDashboardReport(type) {
      this.state.reportFilters.type = type || 'inventory';
      this.state.reportFilters.query = '';
      this.state.reportFilters.office = '';
      this.state.reportFilters.category = '';
      this.state.reportFilters.status = '';
      this.setValue('reportTypeSelect', this.state.reportFilters.type);
      this.state.reportSidebarExpanded = true;
      this.updateReportSidebarActiveType();
      this.switchView('reports');
    },

    loadOperationView(viewKey) {
      const activeView = viewKey || this.state.currentView;
      if (activeView === 'maintenance') {
        this.loadPreventiveMaintenance();
        return;
      }
      const assetFilters = { branch: this.state.globalSite || '' };
      this.server('apiGetUnifiedAssetRegistry', assetFilters)
        .then((assets) => {
          this.state.operationData = { assets };
          this.renderOperationLists(activeView, (assets && assets.items) || []);
        })
        .catch((error) => {
          ['deployAvailableList', 'maintenanceOpenList', 'checkinRecentList', 'transferRecentList'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = this.emptyMarkup('Load failed', error.message || 'Unable to load operation records.');
          });
        });

      if (['transfer', 'checkin'].includes(activeView)) {
        this.server('apiGetDashboardData', { branch: this.state.globalSite || '' })
          .then((data) => this.renderOperationHistory(activeView, data.recentDeploymentActivity || []))
          .catch(() => {});
      }
    },

    currentPmCycle() {
      const now = new Date();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      return `${now.getFullYear()}-${month}`;
    },

    effectivePmFilters() {
      const cycle = this.state.pmFilters.cycle || this.currentPmCycle();
      return {
        query: this.state.pmFilters.query || '',
        branch: this.state.globalSite || '',
        cycle
      };
    },

    loadPreventiveMaintenance() {
      const body = document.getElementById('pmWorkstationBody');
      const meta = document.getElementById('pmTableMeta');
      const cycleInput = document.getElementById('pmCycle');
      if (cycleInput && !cycleInput.value) cycleInput.value = this.state.pmFilters.cycle || this.currentPmCycle();
      this.state.pmFilters.cycle = (cycleInput && cycleInput.value) || this.state.pmFilters.cycle || this.currentPmCycle();
      if (body) body.innerHTML = `<tr><td colspan="6">${this.loadingMarkup()}</td></tr>`;
      if (meta) meta.textContent = 'Loading preventive maintenance records...';
      this.server('apiGetPreventiveMaintenance', this.effectivePmFilters())
        .then((res) => {
          this.state.preventiveMaintenanceData = res;
          this.renderPreventiveMaintenance(res);
        })
        .catch((error) => {
          if (body) body.innerHTML = `<tr><td colspan="6">${this.emptyMarkup('Load failed', error.message || 'Unable to load preventive maintenance records.')}</td></tr>`;
          if (meta) meta.textContent = 'Preventive maintenance load failed';
        });
    },

    renderPreventiveMaintenance(data) {
      const summary = (data && data.summary) || {};
      this.setText('pmTotalCount', summary.total || 0);
      this.setText('pmDoneCount', summary.done || 0);
      this.setText('pmRemainingCount', summary.remaining || 0);
      this.setText('pmOverdueCount', summary.overdue || 0);
      this.setText('pmCyclePill', `Cycle ${data.cycle || this.currentPmCycle()}`);
      this.renderPmSiteProgress(data.siteProgress || []);

      const body = document.getElementById('pmWorkstationBody');
      const meta = document.getElementById('pmTableMeta');
      const items = (data && data.items) || [];
      if (!body) return;
      if (!items.length) {
        body.innerHTML = `<tr><td colspan="6">${this.emptyMarkup('No workstations found', 'Adjust the site filter or create workstation deployments first.')}</td></tr>`;
        if (meta) meta.textContent = 'Showing 0 workstations';
        this.clearPmChecklist();
        return;
      }

      body.innerHTML = items.map(item => `
        <tr class="clickable-row ${item.done === 'Yes' ? 'pm-row-done' : ''}" data-pm-main="${this.escapeHtml(item.mainAssetNumber)}">
          <td><a class="table-link" data-pm-main="${this.escapeHtml(item.mainAssetNumber)}">${this.escapeHtml(item.mainAssetNumber || '—')}</a></td>
          <td>${this.escapeHtml(item.user || '—')}</td>
          <td>${this.escapeHtml(item.reportingBranch || '—')}</td>
          <td>${this.escapeHtml(item.office || '—')}</td>
          <td>${this.escapeHtml(item.completedAt || '—')}</td>
          <td>${this.badge(item.pmStatus || 'Pending', item.done === 'Yes' ? 'success' : 'warning')}</td>
        </tr>
      `).join('');
      if (meta) meta.textContent = `Showing ${items.length} workstation preventive records`;

      if (this.state.activePmItem) {
        const stillThere = items.find(item => item.mainAssetNumber === this.state.activePmItem.mainAssetNumber);
        if (stillThere) this.openPmChecklist(stillThere.mainAssetNumber);
      }
    },

    renderPmSiteProgress(sites) {
      const wrap = document.getElementById('pmSiteProgress');
      if (!wrap) return;
      if (!sites.length || this.state.globalSite || sites.length === 1) {
        wrap.innerHTML = '';
        return;
      }
      wrap.innerHTML = sites.map(site => {
        const total = Number(site.total || 0);
        const done = Number(site.done || 0);
        const pct = total ? Math.round((done / total) * 100) : 0;
        return `
          <div class="pm-site-card">
            <div><strong>${this.escapeHtml(site.site || 'No Site')}</strong><span>${done}/${total} done</span></div>
            <i><b style="width:${pct}%"></b></i>
          </div>
        `;
      }).join('');
    },

    clearPmChecklist() {
      this.state.activePmItem = null;
      this.setText('pmChecklistTitle', 'Checklist');
      this.setText('pmChecklistStatus', 'Select Workstation');
      const empty = document.getElementById('pmChecklistEmpty');
      const fields = document.getElementById('pmChecklistFields');
      if (empty) empty.classList.remove('hidden');
      if (fields) fields.classList.add('hidden');
    },

    openPmChecklist(mainAssetNumber) {
      const data = this.state.preventiveMaintenanceData || {};
      const item = ((data.items || []).find(row => row.mainAssetNumber === mainAssetNumber));
      if (!item) return;
      this.state.activePmItem = item;
      this.setText('pmChecklistTitle', item.mainAssetNumber || 'Checklist');
      this.setText('pmChecklistStatus', item.done === 'Yes' ? 'Done' : 'Pending');
      this.setValue('pmSelectedMain', item.mainAssetNumber || '');
      this.setValue('pmTechnician', item.technician || 'Admin');
      this.setValue('pmRemarks', item.remarks || '');
      const checklist = item.checklist || {};
      document.querySelectorAll('[data-pm-check]').forEach(input => {
        input.checked = !!checklist[input.dataset.pmCheck];
      });
      const doneToggle = document.getElementById('pmDoneToggle');
      if (doneToggle) doneToggle.checked = item.done === 'Yes';
      const empty = document.getElementById('pmChecklistEmpty');
      const fields = document.getElementById('pmChecklistFields');
      if (empty) empty.classList.add('hidden');
      if (fields) fields.classList.remove('hidden');
      this.setOperationStatus('pmSaveStatus', item.completedAt ? `Last completed ${item.completedAt}` : 'Ready.');
    },

    submitPreventiveMaintenance() {
      const main = this.valueOf('pmSelectedMain');
      if (!main) return;
      const checklist = {};
      document.querySelectorAll('[data-pm-check]').forEach(input => {
        checklist[input.dataset.pmCheck] = !!input.checked;
      });
      const done = document.getElementById('pmDoneToggle');
      this.setOperationStatus('pmSaveStatus', 'Saving checklist...');
      this.server('apiCompletePreventiveMaintenance', {
        mainAssetNumber: main,
        cycle: this.state.pmFilters.cycle || this.currentPmCycle(),
        branch: this.state.globalSite || '',
        checklist,
        remarks: this.valueOf('pmRemarks'),
        technician: this.valueOf('pmTechnician') || 'Admin',
        done: done && done.checked ? 'Yes' : 'No'
      })
        .then((res) => {
          this.state.preventiveMaintenanceData = res;
          this.renderPreventiveMaintenance(res);
          this.setOperationStatus('pmSaveStatus', 'Preventive checklist saved.');
          this.state.dashboardData = null;
        })
        .catch((error) => this.setOperationStatus('pmSaveStatus', error.message || 'Unable to save checklist.'));
    },

    renderOperationLists(viewKey, assets) {
      if (viewKey === 'deploy') {
        this.renderDeployAvailableList(assets);
      }
      if (viewKey === 'maintenance') {
        this.renderMaintenanceOpenList(assets);
      }
    },

    renderDeployAvailableList(assets) {
      const el = document.getElementById('deployAvailableList');
      if (!el) return;
      const rows = assets.filter(asset => this.isAvailableAsset(asset)).slice(0, 8);
      if (!rows.length) {
        el.innerHTML = this.emptyMarkup('No available assets', 'Ready-to-deploy assets will appear here.');
        return;
      }
      el.innerHTML = rows.map(asset => `
        <button class="operation-row" type="button" data-asset-code="${this.escapeHtml(asset.assetCode)}">
          <span>${this.categoryIconImg(asset.assetCategory, 'operation-row-img')}</span>
          <b>${this.escapeHtml(asset.assetCode)}</b>
          <em>${this.escapeHtml(asset.assetCategory || 'Asset')} - ${this.escapeHtml(asset.reportingBranch || 'No site')}</em>
        </button>
      `).join('');
    },

    renderMaintenanceOpenList(assets) {
      const el = document.getElementById('maintenanceOpenList');
      if (!el) return;
      const rows = assets.filter(asset => this.isMaintenanceAsset(asset)).slice(0, 8);
      if (!rows.length) {
        el.innerHTML = this.emptyMarkup('No open maintenance', 'Assets marked In Repair will appear here.');
        return;
      }
      el.innerHTML = rows.map(asset => `
        <button class="operation-row" type="button" data-asset-code="${this.escapeHtml(asset.assetCode)}">
          <span>${this.iconImg('maintenance-icon', 'operation-row-img')}</span>
          <b>${this.escapeHtml(asset.assetCode)}</b>
          <em>${this.escapeHtml(asset.remarks || asset.assetStatus || 'In Repair')}</em>
        </button>
      `).join('');
    },

    renderOperationHistory(viewKey, history) {
      const id = viewKey === 'transfer' ? 'transferRecentList' : 'checkinRecentList';
      const el = document.getElementById(id);
      if (!el) return;
      const actionText = viewKey === 'transfer' ? 'transfer' : 'check';
      const rows = (history || []).filter(row => String(row.action || '').toLowerCase().includes(actionText)).slice(0, 8);
      if (!rows.length) {
        el.innerHTML = this.emptyMarkup(viewKey === 'transfer' ? 'No recent transfers' : 'No recent check-ins', 'Completed operation history will appear here.');
        return;
      }
      el.innerHTML = rows.map(row => `
        <button class="operation-row" type="button" data-asset-code="${this.escapeHtml(row.assetCode || '')}">
          <span>${this.iconImg(viewKey === 'transfer' ? 'transfer-icon' : 'checkin-icon', 'operation-row-img')}</span>
          <b>${this.escapeHtml(row.assetCode || row.mainAssetNumber || 'Operation')}</b>
          <em>${this.escapeHtml(row.fromUser || '—')} to ${this.escapeHtml(row.toUser || '—')}</em>
        </button>
      `).join('');
    },

    setOperationStatus(id, text) {
      const el = document.getElementById(id);
      if (el) el.textContent = text || 'Ready.';
    },

    submitOperationTransfer() {
      const type = this.valueOf('transferType') || 'asset';
      const reference = this.valueOf('transferReference');
      const target = this.valueOf('transferTarget');
      const remarks = this.valueOf('transferRemarks') || 'Transfer from Operations module.';
      if (!reference || !target) return;
      this.setOperationStatus('transferStatus', 'Transferring...');
      const method = type === 'workstation' ? 'apiTransferWorkstation' : 'apiTransferAsset';
      const payload = type === 'workstation'
        ? { mainAssetNumber: reference, toEmployee: target, remarks, encodedBy: 'Admin' }
        : { assetCode: reference, toEmployee: target, remarks, encodedBy: 'Admin' };
      this.server(method, payload)
        .then(() => {
          this.afterOperationMutation();
          this.setOperationStatus('transferStatus', 'Transfer completed.');
          document.getElementById('operationTransferForm').reset();
          this.loadOperationView('transfer');
        })
        .catch((error) => this.setOperationStatus('transferStatus', error.message || 'Transfer failed.'));
    },

    submitOperationCheckin() {
      const assetCode = this.valueOf('checkinAssetCode');
      if (!assetCode) return;
      this.setOperationStatus('checkinStatusText', 'Checking in...');
      this.server('apiCheckInAsset', {
        assetCode,
        status: this.valueOf('checkinStatus') || 'Ready to Deploy',
        remarks: this.valueOf('checkinRemarks') || 'Asset checked in.',
        encodedBy: 'Admin'
      })
        .then(() => {
          this.afterOperationMutation();
          this.setOperationStatus('checkinStatusText', 'Asset checked in.');
          document.getElementById('operationCheckinForm').reset();
          this.loadOperationView('checkin');
        })
        .catch((error) => this.setOperationStatus('checkinStatusText', error.message || 'Check-in failed.'));
    },

    submitOperationMaintenance() {
      const assetCode = this.valueOf('maintenanceAssetCode');
      if (!assetCode) return;
      this.setOperationStatus('maintenanceStatusText', 'Creating maintenance record...');
      this.server('apiMarkAssetMaintenance', {
        assetCode,
        priority: this.valueOf('maintenancePriority') || 'Medium',
        issue: this.valueOf('maintenanceIssue') || 'Requires maintenance review.',
        encodedBy: 'Admin'
      })
        .then(() => {
          this.afterOperationMutation();
          this.setOperationStatus('maintenanceStatusText', 'Asset marked In Repair.');
          document.getElementById('operationMaintenanceForm').reset();
          this.loadOperationView('maintenance');
        })
        .catch((error) => this.setOperationStatus('maintenanceStatusText', error.message || 'Maintenance update failed.'));
    },

    afterOperationMutation() {
      this.state.assetRegistry = null;
      this.state.workstationData = null;
      this.state.dashboardData = null;
      this.state.operationData = null;
      if (this.state.currentView === 'assets') this.loadAssets();
      if (this.state.currentView === 'workstations') this.loadWorkstations();
      if (this.state.currentView === 'dashboard') this.loadDashboard();
    },

    renderSettingsAdmin() {
      const settings = this.state.settings || {};
      const summary = [
        ['Sites', (settings.sites || []).filter(site => String(site).toUpperCase() !== 'ALL SITES').length],
        ['Offices', (settings.offices || []).length],
        ['Categories', (settings.assetCategories || []).length],
        ['Counters', (settings.counters || []).length]
      ];
      const summaryWrap = document.getElementById('settingsSummaryGrid');
      if (summaryWrap) {
        summaryWrap.innerHTML = summary.map(([label, value]) => `
          <div class="report-summary-card">
            <span>${this.escapeHtml(label)}</span>
            <strong>${this.escapeHtml(value)}</strong>
          </div>
        `).join('');
      }

      const lists = [
        { key: 'sites', title: 'Sites', values: (settings.sites || []).filter(site => String(site).toUpperCase() !== 'ALL SITES') },
        { key: 'offices', title: 'Offices', values: settings.offices || [] },
        { key: 'categories', title: 'Asset Categories', values: settings.assetCategories || [] },
        { key: 'statuses', title: 'Asset Statuses', values: settings.assetStatuses || [] },
        { key: 'deploymentStatuses', title: 'Deployment Statuses', values: settings.deploymentStatuses || [] },
        { key: 'prefixes', title: 'Prefixes', values: settings.prefixes || [] }
      ];
      const listsWrap = document.getElementById('settingsLists');
      if (listsWrap) {
        listsWrap.innerHTML = lists.map(group => {
          return `
            <div class="section-card admin-list-card" data-settings-group="${group.key}">
              <div class="card-head">
                <h3 class="card-title">${this.escapeHtml(group.title)}</h3>
                <div class="card-actions-group" style="display: flex; gap: 6px; align-items: center;">
                  <button class="light-btn compact" type="button" data-settings-action="add" data-settings-group="${group.key}">
                    ${this.iconImg('add-icon', 'btn-icon')} Add
                  </button>
                  <button class="light-btn compact edit-settings-btn" type="button" data-settings-action="edit" data-settings-group="${group.key}" disabled style="opacity: 0.5; cursor: not-allowed;">
                    ${this.iconImg('edit-icon', 'btn-icon')} Edit
                  </button>
                  <button class="light-btn compact delete-settings-btn" type="button" data-settings-action="delete" data-settings-group="${group.key}" disabled style="opacity: 0.5; cursor: not-allowed;">
                    ${this.iconImg('delete-icon', 'btn-icon')} Delete
                  </button>
                </div>
              </div>
              <div class="admin-chip-list">
                ${(group.values && group.values.length ? group.values : ['No values configured']).map(val => {
                  if (typeof val === 'string' && val === 'No values configured') {
                    return `<span class="muted-label">${this.escapeHtml(val)}</span>`;
                  }
                  
                  const displayVal = typeof val === 'string' ? val : '';
                  const escVal = this.escapeHtml(displayVal);
                  
                  return `
                    <span class="settings-chip clickable-chip" data-settings-group="${group.key}" data-settings-value="${escVal}">
                      <b class="chip-text">${escVal}</b>
                    </span>
                  `;
                }).join('')}
              </div>
            </div>
          `;
        }).join('');
      }
    },

    openSettingsModal(options) {
      this.closeSettingsModal();
      
      const modal = document.createElement('div');
      modal.id = 'settingsManageModal';
      modal.className = 'modal-shell open';
      
      const isCategory = options.group === 'categories';
      const isEdit = options.action === 'edit';
      
      let fieldsHtml = '';
      if (isCategory) {
        let categoryName = '';
        let prefix = '';
        let eol = '5';
        
        if (isEdit && options.oldValue) {
          categoryName = options.oldValue;
          prefix = (this.state.settings && this.state.settings.categoryPrefixes && this.state.settings.categoryPrefixes[options.oldValue]) || '';
          eol = (this.state.settings && this.state.settings.defaultEolYearsByCategory && this.state.settings.defaultEolYearsByCategory[options.oldValue]) || '5';
        }
        
        fieldsHtml = `
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label" style="display:block;margin-bottom:6px;font-weight:700;font-size:13px;" for="settingsCategoryName">Category Name</label>
            <input class="form-input" style="width:100%;min-height:38px;padding:8px 12px;border:1px solid #dedede;border-radius:10px;font-size:14px;" id="settingsCategoryName" type="text" value="${this.escapeHtml(categoryName)}" required ${isEdit ? 'disabled' : ''}>
          </div>
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label" style="display:block;margin-bottom:6px;font-weight:700;font-size:13px;" for="settingsCategoryPrefix">Prefix</label>
            <input class="form-input" style="width:100%;min-height:38px;padding:8px 12px;border:1px solid #dedede;border-radius:10px;font-size:14px;" id="settingsCategoryPrefix" type="text" value="${this.escapeHtml(prefix)}" placeholder="e.g. PRJ" required>
          </div>
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label" style="display:block;margin-bottom:6px;font-weight:700;font-size:13px;" for="settingsCategoryEol">Default EOL (Years)</label>
            <input class="form-input" style="width:100%;min-height:38px;padding:8px 12px;border:1px solid #dedede;border-radius:10px;font-size:14px;" id="settingsCategoryEol" type="number" min="1" max="20" value="${this.escapeHtml(eol)}" required>
          </div>
        `;
      } else {
        const val = isEdit ? options.oldValue : '';
        fieldsHtml = `
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label" style="display:block;margin-bottom:6px;font-weight:700;font-size:13px;" for="settingsSimpleValue">Value</label>
            <input class="form-input" style="width:100%;min-height:38px;padding:8px 12px;border:1px solid #dedede;border-radius:10px;font-size:14px;" id="settingsSimpleValue" type="text" value="${this.escapeHtml(val)}" required>
          </div>
        `;
      }
      
      modal.innerHTML = `
        <div class="modal-backdrop" id="settingsModalBackdrop" style="background:rgba(15,23,42,0.4);"></div>
        <div class="modal-panel" style="max-width: 440px; width: 90%; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:1000; padding:0;">
          <div class="modal-card" style="box-shadow: 0 20px 60px rgba(0,0,0,0.15); border:1px solid #c9d3e6; border-radius:16px; background:#fff; overflow:hidden;">
            <div class="modal-head" style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start;">
              <div class="modal-title-group">
                <div class="modal-eyebrow" style="font-size:11px; font-weight:800; color:#2563eb; text-transform:uppercase; margin-bottom:4px;">Settings Management</div>
                <h3 class="modal-title" style="margin:0; font-size:16px; font-weight:800; color:#1e293b;">${this.escapeHtml(options.title)}</h3>
                <div class="modal-subtitle" style="font-size:12px; color:#64748b; margin-top:2px;">${this.escapeHtml(options.subtitle || 'Configure asset manager values.')}</div>
              </div>
              <button class="modal-close" type="button" id="settingsModalCloseBtn" style="border:none; background:transparent; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
            </div>
            <form id="settingsManageForm" class="modal-body" style="padding:20px;">
              ${fieldsHtml}
              <div class="modal-foot" style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #e2e8f0;">
                <button class="light-btn" type="button" id="settingsModalCancelBtn" style="min-height:38px; border-radius:10px; padding:0 16px; font-size:13px; font-weight:700;">Cancel</button>
                <button class="action-btn" type="submit" style="min-height:38px; border-radius:10px; padding:0 16px; font-size:13px; font-weight:700;">${isEdit ? 'Save Changes' : 'Add Value'}</button>
              </div>
            </form>
          </div>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      const form = document.getElementById('settingsManageForm');
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {};
        if (isCategory) {
          payload.category = document.getElementById('settingsCategoryName').value.trim();
          payload.prefix = document.getElementById('settingsCategoryPrefix').value.trim().toUpperCase();
          payload.eol = document.getElementById('settingsCategoryEol').value.trim();
          if (isEdit) payload.oldCategory = options.oldValue;
        } else {
          payload.value = document.getElementById('settingsSimpleValue').value.trim();
          if (isEdit) payload.oldValue = options.oldValue;
        }
        options.onSubmit(payload);
      });
      
      const close = () => this.closeSettingsModal();
      document.getElementById('settingsModalCloseBtn').onclick = close;
      document.getElementById('settingsModalCancelBtn').onclick = close;
      document.getElementById('settingsModalBackdrop').onclick = close;
    },
    
    closeSettingsModal() {
      const modal = document.getElementById('settingsManageModal');
      if (modal) modal.remove();
    },

    loadAdminUsers() {
      const body = document.getElementById('adminUserBody');
      if (body) body.innerHTML = `<tr><td colspan="7">${this.loadingMarkup()}</td></tr>`;
      const query = this.valueOf('adminUserSearch');
      const active = this.valueOf('adminUserStatus');
      this.server('apiGetEmployeeList', { query, active, branch: this.state.globalSite || '' })
        .then((res) => {
          this.state.adminUserData = res;
          this.renderAdminUsers(res);
        })
        .catch((error) => {
          if (body) body.innerHTML = `<tr><td colspan="7">${this.emptyMarkup('Load failed', error.message || 'Unable to load users.')}</td></tr>`;
        });
    },

    renderAdminUsers(data) {
      const body = document.getElementById('adminUserBody');
      const count = document.getElementById('adminUserCount');
      const items = (data && data.items) || [];
      if (count) count.textContent = `${items.length} Users`;
      if (!body) return;
      if (!items.length) {
        body.innerHTML = `<tr><td colspan="7">${this.emptyMarkup('No users found', 'Adjust filters or add employees first.')}</td></tr>`;
        return;
      }
      body.innerHTML = items.map(row => `
        <tr>
          <td><strong>${this.escapeHtml(row.employeeId || '—')}</strong></td>
          <td><a class="table-link" data-employee-id="${this.escapeHtml(row.employeeId || '')}">${this.escapeHtml(row.employeeName || '—')}</a></td>
          <td>${this.escapeHtml(row.reportingBranch || '—')}</td>
          <td>${this.escapeHtml(row.office || '—')}</td>
          <td>${this.badge(row.active || '—', row.active === 'Yes' ? 'success' : row.active === 'Resigned' ? 'danger' : 'neutral')}</td>
          <td>${this.escapeHtml(row.idSource || '—')}</td>
          <td>${this.escapeHtml(row.updatedAt || '—')}</td>
        </tr>
      `).join('');
    },

    loadAuditLog() {
      const body = document.getElementById('auditLogBody');
      if (body) body.innerHTML = `<tr><td colspan="8">${this.loadingMarkup()}</td></tr>`;
      this.server('apiGetAssetHistory', '')
        .then((rows) => {
          const scoped = this.state.globalSite
            ? (rows || []).filter(row => String(row.reportingBranch || '').toLowerCase() === this.state.globalSite.toLowerCase())
            : (rows || []);
          this.state.auditLogData = scoped;
          this.populateAuditActionFilter(scoped);
          this.renderAuditLog();
        })
        .catch((error) => {
          if (body) body.innerHTML = `<tr><td colspan="8">${this.emptyMarkup('Load failed', error.message || 'Unable to load audit log.')}</td></tr>`;
        });
    },

    populateAuditActionFilter(rows) {
      const select = document.getElementById('auditActionFilter');
      if (!select) return;
      const current = select.value || '';
      const actions = Array.from(new Set((rows || []).map(row => row.action).filter(Boolean))).sort();
      select.innerHTML = '<option value="">All Actions</option>' + actions.map(action => `<option value="${this.escapeHtml(action)}">${this.escapeHtml(action)}</option>`).join('');
      if (actions.includes(current)) select.value = current;
    },

    renderAuditLog() {
      const body = document.getElementById('auditLogBody');
      const count = document.getElementById('auditLogCount');
      const query = this.valueOf('auditSearch').toLowerCase();
      const action = this.valueOf('auditActionFilter');
      let rows = (this.state.auditLogData || []).slice();
      if (query) rows = rows.filter(row => Object.values(row).join(' ').toLowerCase().includes(query));
      if (action) rows = rows.filter(row => row.action === action);
      rows.sort((a, b) => String(b.transactionDate || '').localeCompare(String(a.transactionDate || '')));
      if (count) count.textContent = `${rows.length} Entries`;
      if (!body) return;
      if (!rows.length) {
        body.innerHTML = `<tr><td colspan="8">${this.emptyMarkup('No audit entries found', 'Activity history will appear after asset operations.')}</td></tr>`;
        return;
      }
      body.innerHTML = rows.slice(0, 300).map(row => `
        <tr>
          <td>${this.escapeHtml(row.transactionDate || '—')}</td>
          <td>${this.escapeHtml(row.action || '—')}</td>
          <td>${this.assetChip(row.assetCode)}</td>
          <td>${this.escapeHtml(row.mainAssetNumber || '—')}</td>
          <td>${this.escapeHtml(row.fromUser || '—')}</td>
          <td>${this.escapeHtml(row.toUser || '—')}</td>
          <td>${this.escapeHtml(row.statusAfter || '—')}</td>
          <td>${this.escapeHtml(row.encodedBy || '—')}</td>
        </tr>
      `).join('');
    },

    currentReportDefinition() {
      const forecastYears = Array.from({ length: 6 }, (_, index) => new Date().getFullYear() + index);
      const defs = {
        inventory: {
          title: 'Inventory Report',
          source: 'assets',
          columns: [
            ['Asset Code', 'assetCode'],
            ['Category', 'assetCategory'],
            ['Brand', 'brand'],
            ['Model', 'model'],
            ['Serial Number', 'serialNumber'],
            ['Main Asset No.', 'assignedMainAssetNo'],
            ['User', 'currentUser'],
            ['Site', 'reportingBranch'],
            ['Office', 'office'],
            ['Status', 'assetStatus']
          ]
        },
        'status-summary': {
          title: 'Asset Status Summary',
          source: 'assets',
          aggregate: 'statusSummary',
          columns: [
            ['Status', 'status'],
            ['Assets', 'count'],
            ['Share', 'share'],
            ['Meaning', 'description']
          ]
        },
        'category-summary': {
          title: 'Asset Category Summary',
          source: 'assets',
          aggregate: 'categorySummary',
          columns: [
            ['Category', 'assetCategory'],
            ['Total', 'count'],
            ['Assigned', 'assigned'],
            ['Available', 'available'],
            ['Maintenance', 'maintenance'],
            ['Retired', 'retired'],
            ['Unclassified', 'unclassified']
          ]
        },
        assigned: {
          title: 'Assigned Assets Report',
          source: 'assets',
          predicate: row => this.isAssignedAsset(row),
          columns: [
            ['Asset Code', 'assetCode'],
            ['Category', 'assetCategory'],
            ['Main Asset No.', 'assignedMainAssetNo'],
            ['User', 'currentUser'],
            ['Employee ID', 'employeeId'],
            ['Site', 'reportingBranch'],
            ['Office', 'office'],
            ['Status', 'assetStatus']
          ]
        },
        available: {
          title: 'Available Assets Report',
          source: 'assets',
          predicate: row => this.isAvailableAsset(row),
          columns: [
            ['Asset Code', 'assetCode'],
            ['Category', 'assetCategory'],
            ['Brand', 'brand'],
            ['Model', 'model'],
            ['Serial Number', 'serialNumber'],
            ['Site', 'reportingBranch'],
            ['Office', 'office'],
            ['Status', 'assetStatus']
          ]
        },
        maintenance: {
          title: 'Maintenance Report',
          source: 'assets',
          predicate: row => this.isMaintenanceAsset(row),
          columns: [
            ['Asset Code', 'assetCode'],
            ['Category', 'assetCategory'],
            ['Brand', 'brand'],
            ['Model', 'model'],
            ['Serial Number', 'serialNumber'],
            ['User', 'currentUser'],
            ['Site', 'reportingBranch'],
            ['Status', 'assetStatus'],
            ['Remarks', 'remarks']
          ]
        },
        retired: {
          title: 'Retired Assets Report',
          source: 'assets',
          predicate: row => this.isRetiredAsset(row),
          columns: [
            ['Asset Code', 'assetCode'],
            ['Category', 'assetCategory'],
            ['Brand', 'brand'],
            ['Model', 'model'],
            ['Serial Number', 'serialNumber'],
            ['Site', 'reportingBranch'],
            ['Office', 'office'],
            ['Status', 'assetStatus'],
            ['Remarks', 'remarks']
          ]
        },
        attention: {
          title: 'Needs Attention Report',
          source: 'assets',
          predicate: row => this.isMaintenanceAsset(row) || this.isRetiredAsset(row) || this.isUnclassifiedAsset(row),
          columns: [
            ['Asset Code', 'assetCode'],
            ['Category', 'assetCategory'],
            ['Brand', 'brand'],
            ['Model', 'model'],
            ['Serial Number', 'serialNumber'],
            ['User', 'currentUser'],
            ['Site', 'reportingBranch'],
            ['Office', 'office'],
            ['Status', 'assetStatus'],
            ['Remarks', 'remarks']
          ]
        },
        investment: {
          title: 'IT Lifecycle Investment Forecast',
          source: 'assets',
          aggregate: 'investment',
          columns: [
            ['Asset Type', 'assetCategory'],
            ['Count', 'count'],
            ['Average Unit Cost', 'averageUnitCost'],
            ['Total Acquisition Cost', 'totalAssetCost'],
            ['Useful Life (Months)', 'usefulLifeMonths'],
            ['Monthly Depreciation', 'monthlyDepreciation'],
            ...forecastYears.map(year => [`${year} Qty / Budget`, `forecast${year}`])
          ]
        },
        employees: {
          title: 'Employee Asset Report',
          source: 'employees',
          columns: [
            ['Employee ID', 'employeeId'],
            ['Employee Name', 'employeeName'],
            ['Site', 'reportingBranch'],
            ['Office', 'office'],
            ['Active', 'active'],
            ['ID Source', 'idSource'],
            ['Updated At', 'updatedAt']
          ]
        },
        workstations: {
          title: 'Workstation Deployment Report',
          source: 'workstations',
          columns: [
            ['Workstation ID', 'mainAssetNumber'],
            ['User', 'user'],
            ['Employee ID', 'employeeId'],
            ['Site', 'reportingBranch'],
            ['Office', 'office'],
            ['System Unit', 'systemUnitCode'],
            ['Monitor', 'monitorCode'],
            ['Mouse', 'mouseCode'],
            ['Keyboard', 'keyboardCode'],
            ['UPS', 'upsCode'],
            ['Printer', 'printerCode'],
            ['Status', 'deploymentStatus']
          ]
        }
      };
      return defs[this.state.reportFilters.type] || defs.inventory;
    },

    updateReportFilterControls() {
      const def = this.currentReportDefinition();
      const settings = this.state.settings || {};
      const category = document.getElementById('reportCategoryFilter');
      const status = document.getElementById('reportStatusFilter');
      if (category) {
        category.disabled = def.source !== 'assets';
        const box = category.closest('.filter-box');
        if (box) box.classList.toggle('is-disabled', def.source !== 'assets');
        if (def.source !== 'assets') {
          category.value = '';
          this.state.reportFilters.category = '';
        }
      }

      let values = settings.assetStatuses || [];
      let label = 'All Status';
      if (def.source === 'workstations') {
        values = settings.deploymentStatuses || ['Deployed', 'Ready to Deploy', 'Pulled Out', 'Archived'];
      } else if (def.source === 'employees') {
        values = ['Yes', 'No', 'Resigned'];
        label = 'All Employees';
      }
      const current = this.state.reportFilters.status || '';
      if (current && !values.some(value => String(value).toLowerCase() === current.toLowerCase())) {
        this.state.reportFilters.status = '';
      }
      this.fillSelect('reportStatusFilter', values, label);
      if (status) status.value = this.state.reportFilters.status || '';
    },

    effectiveReportBranch() {
      return this.state.reportFilters.branch || this.state.globalSite || '';
    },

    loadReports() {
      const head = document.getElementById('reportTableHead');
      const body = document.getElementById('reportTableBody');
      const meta = document.getElementById('reportMeta');
      const def = this.currentReportDefinition();
      this.updateReportFilterControls();
      if (body) body.innerHTML = `<tr><td colspan="${def.columns.length}">${this.loadingMarkup()}</td></tr>`;
      if (meta) meta.textContent = 'Loading report records...';
      this.setBusy(true);

      document.querySelectorAll('.report-tab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.reportType === this.state.reportFilters.type);
      });
      this.setValue('reportTypeSelect', this.state.reportFilters.type);
      this.updateReportSidebarActiveType();

      const filters = this.reportSourceFilters(def.source);
      const method = def.source === 'workstations'
        ? 'apiGetWorkstationList'
        : def.source === 'employees'
          ? 'apiGetEmployeeList'
          : 'apiGetUnifiedAssetRegistry';

      this.server(method, filters)
        .then((res) => {
          const filteredRows = this.filterReportRows((res && res.items) || [], def);
          const rows = def.aggregate === 'investment'
            ? this.buildInvestmentForecastRows(filteredRows)
            : def.aggregate === 'statusSummary'
              ? this.buildAssetStatusSummaryRows(filteredRows)
              : def.aggregate === 'categorySummary'
                ? this.buildAssetCategorySummaryRows(filteredRows)
              : filteredRows;
          this.state.currentReport = { definition: def, rows };
          if (def.aggregate === 'investment') {
            this.renderInvestmentReport(def, filteredRows, rows);
          } else if (def.aggregate === 'statusSummary') {
            this.renderStatusSummaryReport(def, rows);
          } else {
            this.renderReport(def, rows);
          }
        })
        .catch((error) => {
          if (head) head.innerHTML = '';
          if (body) body.innerHTML = `<tr><td>${this.emptyMarkup('Load failed', error.message || 'Unable to load report records.')}</td></tr>`;
          if (meta) meta.textContent = 'Report load failed';
        })
        .finally(() => this.setBusy(false));
    },

    reportSourceFilters(source) {
      const f = this.state.reportFilters || {};
      const branch = this.effectiveReportBranch();
      if (source === 'workstations') {
        return { query: f.query, branch, office: f.office, status: f.status };
      }
      if (source === 'employees') {
        return { query: f.query, branch, office: f.office, active: f.status };
      }
      return { query: f.query, branch, office: f.office, category: f.category };
    },

    filterReportRows(rows, def) {
      const f = this.state.reportFilters || {};
      const sameText = (a, b) => String(a || '').toLowerCase() === String(b || '').toLowerCase();
      return rows.filter(row => {
        if (def.predicate && !def.predicate(row)) return false;
        if (def.source === 'assets' && f.status && !sameText(row.assetStatus, f.status)) return false;
        if (def.source !== 'assets' && f.category) return false;
        return true;
      });
    },

    buildEquipmentMatrixRows(assets) {
      const categoryOrder = ['System Unit', 'Monitor', 'Mouse', 'Keyboard', 'UPS', 'Printer', 'Laptop'];
      const source = (assets || []).filter(asset => String(asset.assetCategory || '').toLowerCase() !== 'sfa');
      const categories = categoryOrder.filter(category =>
        source.some(asset => String(asset.assetCategory || '').toLowerCase() === category.toLowerCase())
      );
      const bySite = {};
      source.forEach(asset => {
        const site = asset.reportingBranch || 'Unassigned';
        const category = asset.assetCategory || 'Uncategorized';
        if (String(category).toLowerCase() === 'sfa') return;
        if (!categories.includes(category)) categories.push(category);
        if (!bySite[site]) bySite[site] = { site, counts: {}, total: 0 };
        bySite[site].counts[category] = (bySite[site].counts[category] || 0) + 1;
        bySite[site].total++;
      });
      const rows = Object.values(bySite).sort((a, b) => String(a.site).localeCompare(String(b.site)));
      const totals = {};
      categories.forEach(category => {
        totals[category] = rows.reduce((sum, row) => sum + Number(row.counts[category] || 0), 0);
      });
      return { categories, rows, totals, total: rows.reduce((sum, row) => sum + Number(row.total || 0), 0) };
    },

    buildAssetStatusSummaryRows(assets) {
      const rows = [
        { status: 'Assigned', count: 0, tone: 'primary', description: 'Assets currently assigned or deployed to a user.' },
        { status: 'Available', count: 0, tone: 'success', description: 'Assets ready for deployment.' },
        { status: 'Maintenance', count: 0, tone: 'warning', description: 'Assets in repair, maintenance, or service review.' },
        { status: 'Retired', count: 0, tone: 'danger', description: 'Assets retired, disposed, or marked for disposal audit.' },
        { status: 'Unclassified', count: 0, tone: 'neutral', description: 'Assets with missing or unclear status data.' }
      ];
      const byStatus = Object.fromEntries(rows.map(row => [row.status, row]));
      (assets || []).forEach(asset => {
        if (this.isMaintenanceAsset(asset)) byStatus.Maintenance.count++;
        else if (this.isRetiredAsset(asset)) byStatus.Retired.count++;
        else if (this.isUnclassifiedAsset(asset)) byStatus.Unclassified.count++;
        else if (this.isAssignedAsset(asset)) byStatus.Assigned.count++;
        else if (this.isAvailableAsset(asset)) byStatus.Available.count++;
        else byStatus.Unclassified.count++;
      });
      const total = rows.reduce((sum, row) => sum + row.count, 0) || 1;
      return rows.map(row => ({
        ...row,
        share: `${Math.round((row.count / total) * 100)}%`
      }));
    },

    buildAssetCategorySummaryRows(assets) {
      const groups = {};
      (assets || []).forEach(asset => {
        const category = asset.assetCategory || 'Uncategorized';
        if (!groups[category]) {
          groups[category] = {
            assetCategory: category,
            count: 0,
            assigned: 0,
            available: 0,
            maintenance: 0,
            retired: 0,
            unclassified: 0
          };
        }
        const group = groups[category];
        group.count++;
        if (this.isMaintenanceAsset(asset)) group.maintenance++;
        else if (this.isRetiredAsset(asset)) group.retired++;
        else if (this.isUnclassifiedAsset(asset)) group.unclassified++;
        else if (this.isAssignedAsset(asset)) group.assigned++;
        else if (this.isAvailableAsset(asset)) group.available++;
        else group.unclassified++;
      });
      return Object.values(groups).sort((a, b) => Number(b.count || 0) - Number(a.count || 0));
    },

    buildInvestmentForecastRows(assets) {
      const startYear = new Date().getFullYear();
      const years = Array.from({ length: 6 }, (_, index) => startYear + index);
      const groups = {};

      (assets || []).filter(asset => String(asset.assetCategory || '').toLowerCase() !== 'sfa').forEach(asset => {
        const category = asset.assetCategory || 'Uncategorized';
        if (!groups[category]) {
          groups[category] = {
            assetCategory: category,
            count: 0,
            totalAssetCost: 0,
            usefulLifeTotal: 0,
            usefulLifeCount: 0,
            forecasts: {}
          };
        }
        const group = groups[category];
        const price = Number(asset.purchasePrice || 0);
        const lifeYears = Number(asset.expectedLifeYears || 0);
        group.count++;
        group.totalAssetCost += price;
        if (lifeYears > 0) {
          group.usefulLifeTotal += lifeYears * 12;
          group.usefulLifeCount++;
        }

        let replacementYear = Number(String(asset.deviceEol || '').slice(0, 4));
        if (!replacementYear && asset.purchaseDate && lifeYears > 0) {
          replacementYear = Number(String(asset.purchaseDate).slice(0, 4)) + Math.round(lifeYears);
        }
        if (replacementYear && replacementYear < startYear) replacementYear = startYear;
        if (years.includes(replacementYear)) {
          if (!group.forecasts[replacementYear]) group.forecasts[replacementYear] = { quantity: 0, budget: 0 };
          group.forecasts[replacementYear].quantity++;
          group.forecasts[replacementYear].budget += price;
        }
      });

      return Object.values(groups).map(group => {
        const usefulLifeMonths = group.usefulLifeCount ? Math.round(group.usefulLifeTotal / group.usefulLifeCount) : 0;
        const averageUnitCost = group.count ? group.totalAssetCost / group.count : 0;
        const row = {
          assetCategory: group.assetCategory,
          count: group.count,
          averageUnitCost: this.money(averageUnitCost),
          totalAssetCost: this.money(group.totalAssetCost),
          usefulLifeMonths,
          monthlyDepreciation: this.money(usefulLifeMonths ? group.totalAssetCost / usefulLifeMonths : 0)
        };
        years.forEach(year => {
          const forecast = group.forecasts[year] || { quantity: 0, budget: 0 };
          row[`forecast${year}`] = `${forecast.quantity} / ${this.money(forecast.budget)}`;
        });
        return row;
      }).sort((a, b) => String(a.assetCategory).localeCompare(String(b.assetCategory)));
    },

    renderInvestmentReport(def, assets, forecastRows) {
      const title = document.getElementById('reportTitle');
      const pill = document.getElementById('reportCountPill');
      const head = document.getElementById('reportTableHead');
      const body = document.getElementById('reportTableBody');
      const meta = document.getElementById('reportMeta');
      const planningAssets = (assets || []).filter(asset => String(asset.assetCategory || '').toLowerCase() !== 'sfa');
      const scope = this.effectiveReportBranch() || 'All Sites';
      const years = Array.from({ length: 6 }, (_, index) => new Date().getFullYear() + index);
      const regularRows = forecastRows.filter(row => String(row.assetCategory || '').toLowerCase() !== 'laptop');
      const laptopRows = forecastRows.filter(row => String(row.assetCategory || '').toLowerCase() === 'laptop');
      const investmentRows = regularRows.length ? regularRows : forecastRows.filter(row => String(row.assetCategory || '').toLowerCase() !== 'sfa');

      if (title) title.textContent = 'IT Equipment Inventory and Lifecycle Forecast';
      if (pill) pill.textContent = `${planningAssets.length} Assets`;
      if (head) head.innerHTML = '';
      if (!body) return;

      body.innerHTML = `
        <tr class="special-report-row">
          <td>
            <div class="investment-report">
              <section class="investment-section">
                <div class="investment-title investment-title-row">
                  <span>IT LIFECYCLE INVESTMENT FORECAST</span>
                  <span class="investment-tools">
                    <button type="button" data-invest-toggle="qty">− Qty</button>
                    <button type="button" data-invest-toggle="budget">− Budget</button>
                  </span>
                </div>
                <div class="equipment-matrix-wrap compact-scroll">
                  <table class="investment-table investment-table-compact">
                    <thead>
                      <tr>
                        <th colspan="6">Item Details</th>
                        <th class="invest-group-head invest-qty-head" colspan="${years.length}">Investment by Qty</th>
                        <th class="invest-group-head invest-budget-head is-collapsed" colspan="${years.length}">Investment by Budget</th>
                      </tr>
                      <tr>
                        <th>Asset Type</th><th>Count</th><th>Unit Price</th><th>Total Asset</th><th>Useful Life<br>(Months)</th><th>Monthly<br>Depreciation</th>
                        ${years.map(year => `<th class="invest-qty-col">${year}</th>`).join('')}
                        ${years.map(year => `<th class="invest-budget-col">${year}</th>`).join('')}
                      </tr>
                    </thead>
                    <tbody>
                      ${(investmentRows.length ? investmentRows : [{assetCategory:'No asset data', count:0, averageUnitCost:'₱0.00', totalAssetCost:'₱0.00', usefulLifeMonths:0, monthlyDepreciation:'₱0.00'}]).map(row => `
                        <tr>
                          <td>${this.escapeHtml(row.assetCategory)}</td>
                          <td>${Number(row.count || 0)}</td>
                          <td>${this.escapeHtml(row.averageUnitCost || '₱0.00')}</td>
                          <td>${this.escapeHtml(row.totalAssetCost || '₱0.00')}</td>
                          <td>${Number(row.usefulLifeMonths || 0)}</td>
                          <td>${this.escapeHtml(row.monthlyDepreciation || '₱0.00')}</td>
                          ${years.map(year => `<td class="invest-qty-col">${this.escapeHtml(this.forecastPart(row[`forecast${year}`], 'qty'))}</td>`).join('')}
                          ${years.map(year => `<td class="invest-budget-col">${this.escapeHtml(this.forecastPart(row[`forecast${year}`], 'budget'))}</td>`).join('')}
                        </tr>
                      `).join('')}
                      ${laptopRows.length ? `<tr class="plan-spacer-row"><td colspan="${6 + (years.length * 2)}"></td></tr>` : ''}
                      ${laptopRows.map(row => `
                        <tr class="laptop-plan-row">
                          <td>${this.escapeHtml(row.assetCategory)}</td>
                          <td>${Number(row.count || 0)}</td>
                          <td>${this.escapeHtml(row.averageUnitCost || '₱0.00')}</td>
                          <td>${this.escapeHtml(row.totalAssetCost || '₱0.00')}</td>
                          <td>${Number(row.usefulLifeMonths || 0)}</td>
                          <td>${this.escapeHtml(row.monthlyDepreciation || '₱0.00')}</td>
                          ${years.map(year => `<td class="invest-qty-col">${this.escapeHtml(this.forecastPart(row[`forecast${year}`], 'qty'))}</td>`).join('')}
                          ${years.map(year => `<td class="invest-budget-col">${this.escapeHtml(this.forecastPart(row[`forecast${year}`], 'budget'))}</td>`).join('')}
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>
                ${laptopRows.length ? '<div class="investment-note">Laptop planning is separated because unit pricing, lifecycle, warranty, and replacement decisions are usually different from desktop workstations.</div>' : ''}
              </section>
            </div>
          </td>
        </tr>
      `;
      if (meta) meta.textContent = `${def.title} - ${scope} - Generated ${new Date().toLocaleString()}`;
      this.state.currentReport = { definition: def, rows: forecastRows };
      const summary = document.getElementById('reportSummaryGrid');
      if (summary) summary.innerHTML = '';
    },

    forecastPart(value, part) {
      const pieces = String(value || '0 / ₱0.00').split('/');
      return part === 'budget' ? (pieces[1] || '₱0.00').trim() : (pieces[0] || '0').trim();
    },

    toggleInvestmentColumns(group) {
      if (!group) return;
      const isBudget = group === 'budget';
      const selector = isBudget ? '.invest-budget-col,.invest-budget-head' : '.invest-qty-col,.invest-qty-head';
      const cells = document.querySelectorAll(selector);
      const currentlyCollapsed = Array.from(cells).some(el => el.classList.contains('is-collapsed'));
      cells.forEach(el => el.classList.toggle('is-collapsed', !currentlyCollapsed));
      const btn = document.querySelector(`[data-invest-toggle="${group}"]`);
      if (btn) btn.textContent = `${currentlyCollapsed ? '−' : '+'} ${isBudget ? 'Budget' : 'Qty'}`;
    },

    renderStatusSummaryReport(def, rows) {
      const title = document.getElementById('reportTitle');
      const pill = document.getElementById('reportCountPill');
      const head = document.getElementById('reportTableHead');
      const body = document.getElementById('reportTableBody');
      const meta = document.getElementById('reportMeta');
      const total = rows.reduce((sum, row) => sum + Number(row.count || 0), 0);
      const scope = this.effectiveReportBranch() || 'All Sites';

      if (title) title.textContent = def.title;
      if (pill) pill.textContent = `${total} Assets`;
      if (head) head.innerHTML = '';
      if (!body) return;

      body.innerHTML = `
        <tr class="special-report-row">
          <td>
            <div class="status-summary-report">
              ${rows.map(row => `
                <article class="status-summary-card tone-${this.escapeHtml(row.tone)}">
                  <div>
                    <span>${this.escapeHtml(row.status)}</span>
                    <strong>${Number(row.count || 0)}</strong>
                  </div>
                  <b>${this.escapeHtml(row.share)}</b>
                  <p>${this.escapeHtml(row.description)}</p>
                </article>
              `).join('')}
            </div>
            <table class="professional-report-table">
              <thead><tr><th>Status</th><th>Assets</th><th>Share</th><th>Meaning</th></tr></thead>
              <tbody>
                ${rows.map(row => `
                  <tr>
                    <td>${this.badge(row.status, row.tone)}</td>
                    <td>${Number(row.count || 0)}</td>
                    <td>${this.escapeHtml(row.share)}</td>
                    <td>${this.escapeHtml(row.description)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </td>
        </tr>
      `;
      if (meta) meta.textContent = `${def.title} - ${scope} - Generated ${new Date().toLocaleString()}`;
      const summary = document.getElementById('reportSummaryGrid');
      if (summary) summary.innerHTML = '';
    },

    renderReport(def, rows) {
      const title = document.getElementById('reportTitle');
      const pill = document.getElementById('reportCountPill');
      const head = document.getElementById('reportTableHead');
      const body = document.getElementById('reportTableBody');
      const meta = document.getElementById('reportMeta');
      const scope = this.effectiveReportBranch() || 'All Sites';
      if (title) title.textContent = def.title;
      if (pill) pill.textContent = `${rows.length} Records`;
      if (head) {
        head.innerHTML = `<tr>${def.columns.map(([label]) => `<th>${this.escapeHtml(label)}</th>`).join('')}</tr>`;
      }

      if (!body) return;
      if (!rows.length) {
        body.innerHTML = `<tr><td colspan="${def.columns.length}">${this.emptyMarkup('No records found', 'Adjust filters or choose another report type.')}</td></tr>`;
      } else {
        body.innerHTML = rows.map(row => `
          <tr>
            ${def.columns.map(([, key]) => `<td>${this.formatReportCell(row, key)}</td>`).join('')}
          </tr>
        `).join('');
      }
      if (meta) meta.textContent = `${def.title} - ${scope} - Generated ${new Date().toLocaleString()}`;
      this.renderReportSummary(def, rows);
    },

    renderReportSummary(def, rows) {
      const wrap = document.getElementById('reportSummaryGrid');
      if (!wrap) return;
      const total = rows.length;
      const siteCount = new Set(rows.map(row => row.reportingBranch).filter(Boolean)).size;
      const statusKey = def.source === 'workstations' ? 'deploymentStatus' : def.source === 'employees' ? 'active' : 'assetStatus';
      const statusCount = new Set(rows.map(row => row[statusKey]).filter(Boolean)).size;
      const categoryCount = def.source === 'assets'
        ? new Set(rows.map(row => row.assetCategory).filter(Boolean)).size
        : new Set(rows.map(row => row.office).filter(Boolean)).size;
      const summary = [
        ['Records', total],
        ['Sites', siteCount],
        [def.source === 'assets' ? 'Categories' : 'Offices', categoryCount],
        ['Statuses', statusCount]
      ];
      wrap.innerHTML = summary.map(([label, value]) => `
        <div class="report-summary-card">
          <span>${this.escapeHtml(label)}</span>
          <strong>${this.escapeHtml(value)}</strong>
        </div>
      `).join('');
    },

    formatReportCell(row, key) {
      const value = row[key];
      if (key === 'assetCode' && value) return `<a class="table-link" data-asset-code="${this.escapeHtml(value)}">${this.escapeHtml(value)}</a>`;
      if (key === 'mainAssetNumber' && value) return `<a class="table-link" data-main-asset-number="${this.escapeHtml(value)}">${this.escapeHtml(value)}</a>`;
      if (key === 'assetStatus' || key === 'deploymentStatus' || key === 'active') return this.badge(value || '—', row.badgeTone || 'neutral');
      return this.escapeHtml(value || '—');
    },

    isAssignedAsset(row) {
      const status = String(row.assetStatus || '').toLowerCase();
      return !!(row.currentUser || row.assignedMainAssetNo || status.includes('deployed') || status.includes('assigned'));
    },

    isAvailableAsset(row) {
      const status = String(row.assetStatus || '').toLowerCase();
      return !this.isAssignedAsset(row) && (status.includes('ready') || status.includes('available') || status === '');
    },

    isMaintenanceAsset(row) {
      const status = String(row.assetStatus || '').toLowerCase();
      return status.includes('repair') || status.includes('maintenance') || status.includes('service');
    },

    isRetiredAsset(row) {
      const status = String(row.assetStatus || '').toLowerCase();
      return status.includes('retired') || status.includes('disposed') || status.includes('dispose');
    },

    isUnclassifiedAsset(row) {
      return !String(row.assetStatus || '').trim();
    },

    exportCurrentReport() {
      const report = this.state.currentReport;
      if (!report || !report.rows || !report.rows.length) {
        alert('No report records to export.');
        return;
      }
      this.downloadCsv(report.rows, report.definition.columns, this.slugify(report.definition.title));
    },

    printCurrentReport() {
      const report = this.state.currentReport;
      if (!report || !report.definition) {
        alert('Load a report before printing.');
        return;
      }
      const def = report.definition;
      const rows = report.rows || [];
      const tableRows = rows.map(row => `
        <tr>${def.columns.map(([, key]) => `<td>${this.escapeHtml(row[key] || '—')}</td>`).join('')}</tr>
      `).join('');
      const win = window.open('', '_blank', 'width=1100,height=780');
      if (!win) {
        alert('Allow popups to print this report.');
        return;
      }
      win.document.write(`
        <!doctype html>
        <html>
          <head>
            <title>${this.escapeHtml(def.title)}</title>
            <style>
              body{font-family:Arial,sans-serif;color:#111827;margin:28px}
              h1{font-size:22px;margin:0 0 6px}
              p{margin:0 0 18px;color:#475569;font-size:13px}
              table{width:100%;border-collapse:collapse;font-size:12px}
              th,td{border:1px solid #d7deea;padding:7px;text-align:left}
              th{background:#f3f6fb}
            </style>
          </head>
          <body>
            <h1>${this.escapeHtml(def.title)}</h1>
            <p>${this.escapeHtml(this.effectiveReportBranch() || 'All Sites')} - ${new Date().toLocaleString()}</p>
            <table>
              <thead><tr>${def.columns.map(([label]) => `<th>${this.escapeHtml(label)}</th>`).join('')}</tr></thead>
              <tbody>${tableRows || `<tr><td colspan="${def.columns.length}">No records found.</td></tr>`}</tbody>
            </table>
          </body>
        </html>
      `);
      win.document.close();
      win.focus();
      win.print();
    },

    downloadCsv(rows, columns, filename) {
      const csvEscape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;
      const csv = [
        columns.map(([label]) => csvEscape(label)).join(','),
        ...rows.map(row => columns.map(([, key]) => csvEscape(row[key])).join(','))
      ].join('\r\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${filename}-${new Date().toISOString().slice(0, 10)}.csv`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    },

    slugify(text) {
      return String(text || 'report').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'report';
    },

    openQrScanner() {
      if (!('BarcodeDetector' in window) || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        this.promptQrManualEntry('Camera QR scanning is not available in this browser.');
        return;
      }

      this.closeQrScanner();
      const overlay = document.createElement('div');
      overlay.id = 'qrScannerOverlay';
      overlay.className = 'qr-scanner-overlay';
      overlay.innerHTML = `
        <div class="qr-scanner-panel">
          <div class="qr-scanner-head">
            <div>
              <strong>Scan QR</strong>
              <span>Point your phone camera at an asset tag.</span>
            </div>
            <button type="button" data-action="close-qr-scanner" aria-label="Close scanner">x</button>
          </div>
          <div class="qr-video-wrap">
            <video id="qrScannerVideo" autoplay muted playsinline></video>
            <i></i>
          </div>
          <div class="qr-scanner-foot">
            <button class="light-btn compact-btn" type="button" id="qrManualEntryBtn">Enter Code</button>
            <span id="qrScannerStatus">Starting camera...</span>
          </div>
        </div>
      `;
      document.body.appendChild(overlay);
      const manualBtn = document.getElementById('qrManualEntryBtn');
      if (manualBtn) manualBtn.onclick = () => this.promptQrManualEntry();
      this.startQrScanner();
    },

    startQrScanner() {
      const video = document.getElementById('qrScannerVideo');
      const status = document.getElementById('qrScannerStatus');
      if (!video) return;
      const detector = new BarcodeDetector({ formats: ['qr_code'] });
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then((stream) => {
          this.qrScannerStream = stream;
          video.srcObject = stream;
          if (status) status.textContent = 'Scanning...';
          const scan = () => {
            if (!this.qrScannerStream || video.readyState < 2) {
              this.qrScannerFrame = window.requestAnimationFrame(scan);
              return;
            }
            detector.detect(video)
              .then((codes) => {
                if (codes && codes[0]) {
                  this.handleQrScanValue(codes[0].rawValue || '');
                  return;
                }
                this.qrScannerFrame = window.requestAnimationFrame(scan);
              })
              .catch(() => {
                if (status) status.textContent = 'Scanner error. Try manual entry.';
              });
          };
          this.qrScannerFrame = window.requestAnimationFrame(scan);
        })
        .catch((error) => {
          this.closeQrScanner();
          this.promptQrManualEntry(error && error.message ? error.message : 'Camera permission was denied.');
        });
    },

    closeQrScanner() {
      if (this.qrScannerFrame) {
        window.cancelAnimationFrame(this.qrScannerFrame);
        this.qrScannerFrame = null;
      }
      if (this.qrScannerStream) {
        this.qrScannerStream.getTracks().forEach(track => track.stop());
        this.qrScannerStream = null;
      }
      const overlay = document.getElementById('qrScannerOverlay');
      if (overlay) overlay.remove();
    },

    promptQrManualEntry(message) {
      const code = prompt(`${message ? message + '\n\n' : ''}Enter scanned Asset Code or QR text:`);
      if (code && code.trim()) this.handleQrScanValue(code.trim());
    },

    handleQrScanValue(value) {
      const assetCode = this.extractAssetCodeFromQr(value);
      if (!assetCode) {
        const status = document.getElementById('qrScannerStatus');
        if (status) status.textContent = 'QR did not contain an asset code.';
        return;
      }
      this.closeQrScanner();
      this.switchView('assets');
      this.openAssetDetails(assetCode);
    },

    extractAssetCodeFromQr(value) {
      const text = String(value || '').trim();
      if (!text) return '';
      try {
        const url = new URL(text, window.location.origin);
        const fromUrl = url.searchParams.get('asset');
        if (fromUrl) return fromUrl.trim();
      } catch (error) {}
      const match = text.match(/(?:asset=)?([A-Z]{2,5}-?\d{3,}|BSPI[A-Z0-9-]+)/i);
      return match ? match[1].trim() : text;
    },

    openWorkstationDetails(mainAssetNumber) {
      this.openDrawer(this.loadingMarkup());

      this.server('apiGetWorkstationDetails', mainAssetNumber)
        .then((res) => {
          if (!res.found) {
            this.openDrawer(this.emptyMarkup('Workstation not found', res.message || 'No deployment record matched this Main Asset Number.'));
            return;
          }

          const tpl = document.getElementById('workstationDetailsTemplate').content.cloneNode(true);
          document.getElementById('drawerContent').innerHTML = '';
          document.getElementById('drawerContent').appendChild(tpl);

          const dep = res.deployment || {};
          const maintenanceSummary = res.maintenanceSummary || res.repairSummary || { total: 0, open: 0, closed: 0, items: [] };
          const assignedAssets = res.assignedAssets || [];
          this.state.activeWorkstationDetail = res;
          this.setText('wsMainAssetNumber', dep.mainAssetNumber || 'Workstation');
          this.setText('wsAssignedUser', dep.user || '—');
          this.setText('wsBranch', dep.reportingBranch || '—');
          this.setText('wsOffice', dep.office || '—');
          this.setHtml('wsStatus', this.badge(dep.deploymentStatus, dep.badgeTone));
          this.setText('wsDateDeployed', dep.dateDeployed || '—');
          this.setText('wsDateReturned', dep.dateReturned || '—');
          this.setText('wsEncodedBy', dep.encodedBy || '—');
          this.setText('wsRemarks', dep.remarks || '—');

          this.setText('wsRepairTotal', maintenanceSummary.total || 0);
          this.setText('wsRepairOpen', maintenanceSummary.open || 0);
          this.setText('wsRepairClosed', maintenanceSummary.closed || 0);

          const assetsWrap = document.getElementById('wsAssignedAssets');
          if (!assignedAssets.length) {
            assetsWrap.innerHTML = this.emptyMarkup('No assigned assets', 'This workstation currently has no linked asset rows.');
          } else {
            assetsWrap.innerHTML = assignedAssets.map(asset => `
              <div class="mini-card">
                <div class="mini-title">
                  <a class="table-link" data-asset-code="${this.escapeHtml(asset.assetCode)}">${this.escapeHtml(asset.assetCode)}</a>
                  · ${this.escapeHtml(asset.assetCategory || 'Asset')}
                </div>
                <div class="mini-meta">${this.escapeHtml(asset.displayName || 'No display name')}</div>
                <div class="mini-note">
                  Status: ${this.escapeHtml(asset.assetStatus || '—')}<br>
                  User: ${this.escapeHtml(asset.currentUser || '—')}<br>
                  Office: ${this.escapeHtml(asset.office || '—')}
                </div>
              </div>
            `).join('');
          }

          const maint = document.getElementById('wsMaintenanceItems');
          maint.innerHTML = (maintenanceSummary.items || []).length
            ? maintenanceSummary.items.slice(0, 6).map(item => `
              <div class="mini-card">
                <div class="mini-title">${this.escapeHtml(item.assetCode || 'Asset')}</div>
                <div class="mini-meta">${this.escapeHtml(item.dateReported || 'No date')}</div>
                <div class="mini-note">${this.escapeHtml(item.issue || 'No issue details')}<br>${this.escapeHtml(item.repairStatus || 'No status')}</div>
              </div>
            `).join('')
            : this.emptyMarkup('No maintenance records', 'No repair logs matched the assets under this workstation.');

          const history = document.getElementById('wsHistoryList');
          history.innerHTML = (res.history || []).length
            ? res.history.map(item => `
              <div class="mini-card">
                <div class="mini-title">${this.escapeHtml(item.action || 'History item')}</div>
                <div class="mini-meta">${this.escapeHtml(item.transactionDate || 'No date')}</div>
                <div class="mini-note">
                  Asset: ${this.escapeHtml(item.assetCode || '—')}<br>
                  From: ${this.escapeHtml(item.fromUser || '—')} → To: ${this.escapeHtml(item.toUser || '—')}<br>
                  ${this.escapeHtml(item.remarks || '')}
                </div>
              </div>
            `).join('')
            : this.emptyMarkup('No history yet', 'No history records matched this Main Asset Number.');

          document.getElementById('detailsDrawer').classList.add('open');
        })
        .catch((error) => {
          this.openDrawer(this.emptyMarkup('Load failed', error.message || 'Unable to load workstation details.'));
        });
    },

    openAssetDetails(assetCode) {
      this.openDrawer(this.loadingMarkup());

      this.server('apiGetAssetDetails', assetCode)
        .then((res) => {
          if (!res.found) {
            this.openDrawer(this.emptyMarkup('Asset not found', res.message || 'No asset record matched this asset code.'));
            return;
          }

          const tpl = document.getElementById('assetDetailsTemplate').content.cloneNode(true);
          document.getElementById('drawerContent').innerHTML = '';
          document.getElementById('drawerContent').appendChild(tpl);
          this.state.activeAssetDetail = res;

          const asset = res.asset;
          this.setText('assetDrawerCode', asset.assetCode || 'Asset');
          this.setText('assetHeroTitle', asset.assetCode || 'Asset');
          this.setText('assetHeroSub', `${asset.assetCategory || 'Asset'} — ${[asset.brand, asset.model].filter(Boolean).join(' ') || 'Unspecified model'}`);

          const icon = this.iconForCategory(asset.assetCategory);
          this.setText('assetHeroIcon', icon);
          this.setText('assetOverviewMedia', icon);

          const dep = Math.round(Number(asset.depreciationProgress || 0));
          this.setProgress('assetDepProgressBar', dep);
          this.setProgress('assetDepProgressBar2', dep);
          this.setText('assetDepProgressText', dep + '%');
          this.setText('assetDepProgressText2', dep + '%');
          this.setText('assetHeroSub', `${asset.assetCategory || 'Asset'} - ${[asset.brand, asset.model].filter(Boolean).join(' ') || 'Unspecified model'}`);
          this.setText('assetHeroMeta', [asset.office, asset.reportingBranch].filter(Boolean).join(' | '));
          this.setText('assetTagCode', asset.assetCode || 'Asset');
          this.setHtml('assetHeroStatus', this.badge(asset.assetStatus, asset.badgeTone));
          this.setText('assetPhotoIcon', icon);
          this.renderAssetTag(asset);
          this.bindAssetPhotoUpload(asset.assetCode);
          this.setAssetMetrics(res);

          const overviewList = document.getElementById('assetOverviewList');
          const assignedTo = [
            res.overview.currentUser,
            res.overview.employeeId ? `ID: ${res.overview.employeeId}` : ''
          ].filter(Boolean).join(' - ');
          overviewList.innerHTML = this.kvRows([
            ['Asset Tag', res.overview.assetCode],
            ['Category', res.overview.assetCategory],
            ['Brand', res.overview.brand],
            ['Model', res.overview.model],
            ['Serial Number', res.overview.serialNumber],
            ['Main Asset Number', res.overview.assignedMainAssetNo],
            ['Assigned To', assignedTo],
            ['Date Deployment', this.formatDisplayDate((res.record && res.record.dateDeployment) || res.overview.dateDeployment)],
            ['Reporting Branch', res.overview.reportingBranch],
            ['Office', res.overview.office],
            ['Asset Status', this.badge(res.overview.assetStatus, res.overview.badgeTone)]
          ], true);

          const specsList = document.getElementById('assetSpecsList');
          const specs = Array.isArray(res.specs) ? res.specs : [];
          specsList.innerHTML = specs.length
            ? this.kvRows(specs.map(item => [item[0], item[1]]), true)
            : this.emptyMarkup('No specs available', 'This category has no populated specification fields yet.');

          const fin = res.financials || {};
          document.getElementById('assetFinancialsList').innerHTML = this.kvRows([
            ['Purchase Date', this.formatDisplayDate(fin.purchaseDate)],
            ['Purchase Price', this.money(fin.purchasePrice)],
            ['Expected Life', fin.expectedLifeYears ? fin.expectedLifeYears + ' years' : '—'],
            ['Residual Value', this.money(fin.residualValue)],
            ['Current Value', this.money(fin.currentValue)],
            ['Device EOL', this.formatDisplayDate(fin.deviceEol)],
            ['Fully Depreciated Date', this.formatDisplayDate(fin.fullyDepreciatedDate)]
          ], true);

          const assign = res.assignment || {};
          const assignmentUser = [
            assign.currentUser,
            assign.employeeId ? `ID: ${assign.employeeId}` : ''
          ].filter(Boolean).join(' - ');
          document.getElementById('assetAssignmentList').innerHTML = this.kvRows([
            ['Current User', assignmentUser],
            ['Assigned Main Asset No.', assign.assignedMainAssetNo],
            ['Deployment Date', this.formatDisplayDate(assign.dateDeployment || (res.record && res.record.dateDeployment))],
            ['Reporting Branch', assign.reportingBranch],
            ['Office', assign.office],
            ['Asset Status', assign.assetStatus]
          ], true);

          const repairList = document.getElementById('assetRepairList');
          repairList.innerHTML = (res.repairLogs || []).length
            ? res.repairLogs.map(item => `
              <div class="mini-card">
                <div class="mini-title">${this.escapeHtml(item.issue || 'Repair item')}</div>
                <div class="mini-meta">${this.escapeHtml(item.dateReported || 'No date')} · ${this.escapeHtml(item.sentTo || 'No vendor')}</div>
                <div class="mini-note">
                  Status: ${this.escapeHtml(item.repairStatus || '—')}<br>
                  Cost: ${this.money(item.cost)}<br>
                  ${this.escapeHtml(item.remarks || '')}
                </div>
              </div>
            `).join('')
            : this.emptyMarkup('No maintenance logs', 'No repair records matched this asset.');

          const historyList = document.getElementById('assetHistoryList');
          historyList.innerHTML = (res.history || []).length
            ? res.history.map(item => `
              <div class="mini-card">
                <div class="mini-title">${this.escapeHtml(item.action || 'History item')}</div>
                <div class="mini-meta">${this.escapeHtml(item.transactionDate || 'No date')}</div>
                <div class="mini-note">
                  Asset: ${this.escapeHtml(item.assetCode || '—')}<br>
                  Main Asset Number: ${this.escapeHtml(item.mainAssetNumber || '—')}<br>
                  ${this.escapeHtml(item.remarks || '')}
                </div>
              </div>
            `).join('')
            : this.emptyMarkup('No history records', 'No asset history matched this asset.');
          this.renderAssetTimeline(res);

          const editBtn = document.getElementById('editAssetBtn');
          if (editBtn) {
            editBtn.onclick = () => this.openEditAssetModal();
          }

          const transferBtn = document.getElementById('transferAssetBtn');
          if (transferBtn) transferBtn.onclick = () => this.transferActiveAsset();

          const printBtn = document.getElementById('printAssetTagBtn');
          if (printBtn) printBtn.onclick = () => this.printAssetTag();

          const downloadBtn = document.getElementById('downloadAssetTagBtn');
          if (downloadBtn) downloadBtn.onclick = () => this.downloadAssetTag(asset.assetCode);

          this.switchAssetTab('overview');
          document.getElementById('detailsDrawer').classList.add('open');
        })
        .catch((error) => {
          this.openDrawer(this.emptyMarkup('Load failed', error.message || 'Unable to load asset details.'));
        });
    },

    renderAssetTag(asset) {
      const qr = document.getElementById('assetTagQr');
      const codeNode = document.getElementById('assetTagCode');
      const tagCard = document.getElementById('assetTagCard');
      if (!qr) return;

      const code = asset.assetCode || 'ASSET';
      const url = this.assetTagLookupUrl(code);
      const localQr = this.assetQrImageUrl(code);
      const fallbackQr = this.qrCodeUrl(url, 220);
      qr.innerHTML = `<img crossorigin="anonymous" src="${localQr}" data-fallback-src="${this.escapeHtml(fallbackQr)}" alt="QR for ${this.escapeHtml(code)}" onerror="if(!this.dataset.fallbackUsed){this.dataset.fallbackUsed='1';this.src=this.dataset.fallbackSrc}else{this.parentElement.classList.add('qr-failed')}">`;
      qr.title = url;
      if (codeNode) codeNode.textContent = code;
      if (tagCard) tagCard.dataset.assetCode = code;
      this.ensureAssetQrFile(code).then((res) => {
        const img = qr.querySelector('img');
        if (img && res && res.qrUrl) {
          img.dataset.fallbackUsed = '';
          img.src = res.qrUrl;
        }
      }).catch(() => {});
    },

    assetTagLookupUrl(assetCode) {
      return `${location.origin}${location.pathname}?asset=${encodeURIComponent(assetCode || '')}`;
    },

    assetTagLogoUrl() {
      return `${location.origin}${location.pathname.replace(/[^/]*$/, '')}assets/images/logos/bspi-wordmark.png`;
    },

    qrCodeUrl(data, size = 220) {
      return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=8&data=${encodeURIComponent(data || '')}`;
    },

    assetQrImageUrl(assetCode) {
      const safe = String(assetCode || 'ASSET').trim().toUpperCase().replace(/[^A-Z0-9_-]/g, '_');
      return `uploads/qr/${encodeURIComponent(safe)}.png`;
    },

    ensureAssetQrFile(assetCode) {
      if (!assetCode) return Promise.resolve(null);
      const localUrl = this.assetQrImageUrl(assetCode);
      return fetch(localUrl, { cache: 'no-store' })
        .then((response) => {
          if (response.ok) return { qrUrl: localUrl, created: false };
          throw new Error('QR file is not stored yet.');
        })
        .catch(() => this.saveAssetQrFromBrowser(assetCode));
    },

    saveAssetQrFromBrowser(assetCode) {
      const url = this.qrCodeUrl(this.assetTagLookupUrl(assetCode), 260);
      return fetch(url, { mode: 'cors' })
        .then((response) => {
          if (!response.ok) throw new Error('Unable to download QR image.');
          return response.blob();
        })
        .then((blob) => new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => resolve(String(reader.result || ''));
          reader.onerror = () => reject(new Error('Unable to read QR image.'));
          reader.readAsDataURL(blob);
        }))
        .then((dataUrl) => this.server('apiSaveAssetQr', { assetCode, dataUrl }));
    },

    ensureAssetQrFiles(assets) {
      const codes = Array.from(new Set((assets || []).map(asset => asset && asset.assetCode).filter(Boolean)));
      if (!codes.length) return Promise.resolve([]);
      return Promise.all(codes.map(code => this.ensureAssetQrFile(code).catch((error) => ({ assetCode: code, error }))));
    },

    assetTagPrintMarkup(asset) {
      const code = this.escapeHtml(asset.assetCode || 'ASSET');
      const qr = this.assetQrImageUrl(asset.assetCode || code);
      const fallbackQr = this.qrCodeUrl(this.assetTagLookupUrl(asset.assetCode || code), 260);
      const logo = this.assetTagLogoUrl();
      return `
        <article class="print-tag">
          <div class="print-tag-qr" data-fallback="QR"><img crossorigin="anonymous" src="${qr}" data-fallback-src="${this.escapeHtml(fallbackQr)}" alt="QR for ${code}" onerror="if(!this.dataset.fallbackUsed){this.dataset.fallbackUsed='1';this.src=this.dataset.fallbackSrc}else{this.parentElement.classList.add('qr-failed')}"></div>
          <div class="print-tag-copy">
            <img class="print-tag-logo" src="${logo}" alt="BSPI">
            <div class="print-tag-code">${code}</div>
          </div>
        </article>`;
    },

    assetTagPrintDocument(assets, title = 'Asset Tags') {
      const list = (assets || []).filter(Boolean);
      const css = `
        @page{size:A4;margin:8mm}
        *{box-sizing:border-box}
        html,body{margin:0;min-height:100%;font-family:Arial,Helvetica,sans-serif;color:#111;background:#fff}
        .sheet-title{margin:0 0 5mm;font-size:12pt;font-weight:900;color:#111}
        .print-page{display:grid;grid-template-columns:repeat(4,1fr);gap:4mm;align-items:start;width:100%}
        .print-tag{width:100%;height:27mm;border:.55mm solid #111;border-radius:1.5mm;background:#fff;padding:1.8mm;display:grid;grid-template-columns:17mm minmax(0,1fr);gap:1.8mm;align-items:center;break-inside:avoid;page-break-inside:avoid;overflow:hidden}
        .print-tag-qr{width:17mm;height:17mm;display:flex;align-items:center;justify-content:center;background:#fff;color:#111;font-size:6pt;font-weight:800;text-align:center}
        .print-tag-qr img{width:17mm;height:17mm;object-fit:contain;image-rendering:pixelated}
        .print-tag-qr.qr-failed img{display:none}
        .print-tag-qr.qr-failed::before{content:attr(data-fallback)}
        .print-tag-copy{min-width:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
        .print-tag-logo{width:18mm;max-height:7mm;object-fit:contain;margin-bottom:2.5mm}
        .print-tag-code{font-size:9pt;font-weight:900;letter-spacing:0;line-height:1;color:#111;text-align:center;white-space:nowrap;max-width:100%;overflow:visible}
        @media print{.sheet-title{display:none}.print-page{gap:4mm}.print-tag{box-shadow:none}}
      `;
      return `<!doctype html><html><head><meta charset="utf-8"><title>${this.escapeHtml(title)} - compact v2</title><style>${css}</style></head><body><h1 class="sheet-title">${this.escapeHtml(title)}</h1><main class="print-page">${list.map(asset => this.assetTagPrintMarkup(asset)).join('')}</main></body></html>`;
    },

    waitForPrintImages(win, timeout = 3500) {
      const images = Array.from(win.document.images || []);
      if (!images.length) return Promise.resolve();
      const pending = images.filter((img) => !img.complete);
      if (!pending.length) return Promise.resolve();
      return new Promise((resolve) => {
        let remaining = pending.length;
        const done = () => {
          remaining -= 1;
          if (remaining <= 0) resolve();
        };
        pending.forEach((img) => {
          img.addEventListener('load', done, { once: true });
          img.addEventListener('error', done, { once: true });
        });
        setTimeout(resolve, timeout);
      });
    },

    printAssetTags(assets, title = 'Asset Tags') {
      if (!assets || !assets.length) {
        alert('No assets selected to print.');
        return;
      }
      const win = window.open('', '_blank', 'width=1100,height=800');
      if (!win) {
        alert('Please allow pop-ups to print asset tags.');
        return;
      }
      win.document.open();
      win.document.write('<!doctype html><html><head><title>Preparing asset tags</title></head><body style="font-family:Arial,sans-serif;padding:24px">Preparing QR codes...</body></html>');
      win.document.close();
      win.focus();
      this.ensureAssetQrFiles(assets)
        .then(() => {
          win.document.open();
          win.document.write(this.assetTagPrintDocument(assets, title));
          win.document.close();
          win.focus();
          this.waitForPrintImages(win).then(() => setTimeout(() => win.print(), 100));
        })
        .catch((error) => {
          win.document.open();
          win.document.write(`<pre style="font-family:Arial,sans-serif;padding:24px;white-space:pre-wrap">Unable to prepare QR codes.\n${this.escapeHtml(error.message || 'Unknown error')}</pre>`);
          win.document.close();
        });
    },

    printAssetTag() {
      const asset = this.state.activeAssetDetail && this.state.activeAssetDetail.asset;
      if (!asset) return;
      this.printAssetTags([asset], `${asset.assetCode || 'asset'} tag`);
    },

    downloadAssetTag(assetCode) {
      const asset = this.state.activeAssetDetail && this.state.activeAssetDetail.asset;
      if (!asset) return;
      const html = this.assetTagPrintDocument([asset], `${assetCode || asset.assetCode || 'asset'} tag`);
      const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${assetCode || asset.assetCode || 'asset'}-tag.html`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    },

    getVisibleAssetsForTags() {
      return ((this.state.assetRegistry && this.state.assetRegistry.items) || []).filter(asset => asset && asset.assetCode);
    },

    getSelectedAssetsForTags() {
      const selected = Array.from(document.querySelectorAll('.asset-row-select:checked')).map(cb => cb.value);
      const lookup = new Map(this.getVisibleAssetsForTags().map(asset => [asset.assetCode, asset]));
      return selected.map(code => lookup.get(code)).filter(Boolean);
    },

    printVisibleAssetTags() {
      this.printAssetTags(this.getVisibleAssetsForTags(), 'Visible Asset Tags');
    },

    printSelectedAssetTags() {
      this.printAssetTags(this.getSelectedAssetsForTags(), 'Selected Asset Tags');
    },

    bindAssetPhotoUpload(assetCode) {
      const button = document.getElementById('assetPhotoButton');
      const input = document.getElementById('assetPhotoInput');
      const preview = document.getElementById('assetPhotoPreview');
      const icon = document.getElementById('assetPhotoIcon');
      if (!button || !input || !preview) return;

      const key = `asset-photo:${assetCode || ''}`;
      const serverPhoto = this.state.activeAssetDetail && this.state.activeAssetDetail.asset && this.state.activeAssetDetail.asset.photoUrl;
      const saved = serverPhoto || localStorage.getItem(key);
      if (saved) {
        preview.src = saved;
        preview.classList.add('has-image');
        if (icon) icon.classList.add('hidden');
      }

      button.onclick = () => input.click();
      input.onchange = () => {
        const file = input.files && input.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('method', 'apiUploadAssetPhoto');
        formData.append('assetCode', assetCode || '');
        formData.append('photo', file);
        fetch('api.php', { method: 'POST', body: formData })
          .then(async (response) => {
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload || payload.success === false) {
              throw new Error((payload && payload.message) || 'Upload failed');
            }
            return payload.data;
          })
          .then((res) => {
            if (res.photoUrl) {
              preview.src = res.photoUrl;
              preview.classList.add('has-image');
              if (icon) icon.classList.add('hidden');
            }
          })
          .catch((error) => {
            alert(error.message || 'Unable to upload asset photo.');
          });

        const reader = new FileReader();
        reader.onload = () => {
          const value = String(reader.result || '');
          localStorage.setItem(key, value);
          preview.src = value;
          preview.classList.add('has-image');
          if (icon) icon.classList.add('hidden');
        };
        reader.readAsDataURL(file);
      };
    },

    setAssetMetrics(res) {
      const record = res.record || {};
      const fin = res.financials || {};
      const purchaseDate = fin.purchaseDate || record.purchaseDate || '';
      const deployedDate = record.dateDeployment || '';
      const status = (res.asset && res.asset.assetStatus) || '';

      this.setText('assetWarrantyMetric', purchaseDate ? 'Tracked' : 'Not set');
      this.setText('assetWarrantySub', purchaseDate ? `Purchased ${this.formatDisplayDate(purchaseDate)}` : 'Add purchase date');
      this.setText('assetAgeMetric', deployedDate ? this.relativeAge(deployedDate) : 'Not set');
      this.setText('assetAgeSub', deployedDate ? `Since ${this.formatDisplayDate(deployedDate)}` : 'No deployment date');
      this.setText('assetConditionMetric', /repair|maintenance/i.test(status) ? 'Needs service' : 'Good');
      this.setText('assetConditionSub', /repair|maintenance/i.test(status) ? 'Review required' : 'Fully functional');
    },

    renderAssetTimeline(res) {
      const el = document.getElementById('assetTimelineList');
      if (!el) return;

      const record = res.record || {};
      const rows = [];
      (res.history || []).slice(0, 5).forEach(item => {
        rows.push({
          date: item.transactionDate || '',
          title: item.action || 'Asset activity',
          text: item.remarks || item.statusAfter || item.toUser || ''
        });
      });

      if (record.dateDeployment) {
        rows.push({ date: record.dateDeployment, title: 'Asset deployed', text: record.currentUser || record.office || '' });
      }
      if (record.createdAt || record.purchaseDate) {
        rows.push({ date: record.createdAt || record.purchaseDate, title: 'Asset registered', text: 'Asset added to inventory' });
      }

      el.innerHTML = rows.length
        ? rows.slice(0, 6).map(item => `
          <div class="timeline-item">
            <time>${this.escapeHtml(item.date || 'No date')}</time>
            <div><strong>${this.escapeHtml(item.title)}</strong><span>${this.escapeHtml(item.text || 'No details')}</span></div>
          </div>
        `).join('')
        : this.emptyMarkup('No timeline yet', 'Asset movement will appear here once history exists.');
    },

    relativeAge(dateValue) {
      const start = new Date(dateValue);
      if (Number.isNaN(start.getTime())) return 'Not set';
      const days = Math.max(0, Math.floor((Date.now() - start.getTime()) / 86400000));
      if (days < 30) return `${days} day${days === 1 ? '' : 's'}`;
      const months = Math.floor(days / 30);
      if (months < 12) return `${months} month${months === 1 ? '' : 's'}`;
      const years = Math.floor(months / 12);
      const remainingMonths = months % 12;
      if (!remainingMonths) return `${years} year${years === 1 ? '' : 's'}`;
      return `${years} year${years === 1 ? '' : 's'} ${remainingMonths} month${remainingMonths === 1 ? '' : 's'}`;
    },

    switchAssetTab(tab) {
      document.querySelectorAll('#assetTabNav .tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
      });

      document.querySelectorAll('[data-tab-panel]').forEach(panel => {
        panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab);
      });
    },

    openDrawer(markup) {
      const drawer = document.getElementById('detailsDrawer');
      const content = document.getElementById('drawerContent');
      if (!drawer || !content) return;
      content.innerHTML = markup;
      drawer.classList.add('open');
      window.requestAnimationFrame(() => drawer.classList.add('drawer-enter'));
    },

    closeDrawer() {
      const drawer = document.getElementById('detailsDrawer');
      if (!drawer) return;
      drawer.classList.remove('drawer-enter');
      drawer.classList.remove('open');
    },

    openAddAssetModal() {
      const modal = document.getElementById('addAssetModal');
      if (!modal) return;

      this.setAddAssetFormMode('create');
      this.populateAddAssetForm();
      this.resetAddAssetForm(false);
      modal.classList.add('open');
    },

    openEditAssetModal() {
      const detail = this.state.activeAssetDetail;
      if (!detail || !detail.found) return;

      const modal = document.getElementById('addAssetModal');
      if (!modal) return;

      this.populateAddAssetForm();
      this.resetAddAssetForm(false);
      this.setAddAssetFormMode('edit', detail);
      this.populateAddAssetFormFromRecord(detail.record || {});
      modal.classList.add('open');
    },

    closeAddAssetModal() {
      const modal = document.getElementById('addAssetModal');
      if (!modal) return;
      modal.classList.remove('open');
      this.setAddAssetFormMode('create');
    },

    openAddWorkstationModal() {
      const modal = document.getElementById('addWorkstationModal');
      if (!modal) return;

      this.populateAddWorkstationForm();
      this.resetAddWorkstationForm(false);
      modal.classList.add('open');
    },

    closeAddWorkstationModal() {
      const modal = document.getElementById('addWorkstationModal');
      if (!modal) return;
      modal.classList.remove('open');
    },

    resetAddAssetForm(resetCategory) {
      const form = document.getElementById('addAssetForm');
      if (!form) return;

      form.reset();

      if (resetCategory !== false) {
        const categoryEl = document.getElementById('addAssetCategory');
        if (categoryEl) categoryEl.value = '';
      }
      if (this.state.globalSite) this.setValue('addAssetBranch', this.state.globalSite);

      const encodedBy = document.getElementById('addAssetEncodedBy');
      if (encodedBy && !encodedBy.value) encodedBy.value = 'Admin';

      this.updateAddAssetDynamicFields();
      this.applyExpectedLifeFromSettings();
      this.calculateAddAssetDepreciation();
      this.previewNextAssetCode();
      this.clearAssetLookupResult();
      const importFileEl = document.getElementById('addAssetImportFile');
      if (importFileEl) importFileEl.value = '';
      this.setAddAssetImportStatus('No file selected.');
      this.setAddAssetSubmitting(false);
    },

    setAddAssetFormMode(mode, detail) {
      this.state.assetFormMode = mode === 'edit' ? 'edit' : 'create';
      this.state.activeAssetDetail = detail || this.state.activeAssetDetail;

      const eyebrow = document.getElementById('addAssetModalEyebrow');
      const title = document.getElementById('addAssetModalTitle');
      const subtitle = document.getElementById('addAssetModalSubtitle');
      const importSection = document.getElementById('assetBulkImportSection');
      const importTitle = document.getElementById('assetBulkImportTitle');
      const submitBtn = document.getElementById('addAssetSubmitBtn');
      const assetCodeField = document.getElementById('addAssetAssetCode');

      if (this.state.assetFormMode === 'edit') {
        if (eyebrow) eyebrow.textContent = 'Asset Editing';
        if (title) title.textContent = 'Edit Asset';
        if (subtitle) subtitle.textContent = 'Update missing details, specs, and financial values.';
        if (submitBtn) submitBtn.textContent = 'Save Changes';
        if (importSection) importSection.classList.add('hidden');
        if (importTitle) importTitle.classList.add('hidden');
        if (assetCodeField && detail && detail.asset) assetCodeField.value = detail.asset.assetCode || '';
      } else {
        if (eyebrow) eyebrow.textContent = 'Asset Creation';
        if (title) title.textContent = 'Add New Asset';
        if (subtitle) subtitle.textContent = 'Expected life and depreciation fields are auto-calculated from category, purchase date, and purchase price.';
        if (submitBtn) submitBtn.textContent = 'Save Asset';
        if (importSection) importSection.classList.remove('hidden');
        if (importTitle) importTitle.classList.remove('hidden');
        if (assetCodeField) assetCodeField.value = '';
      }

      const categoryEl = document.getElementById('addAssetCategory');
      if (categoryEl) categoryEl.disabled = this.state.assetFormMode === 'edit';
    },

    populateAddAssetFormFromRecord(record) {
      if (!record) return;

      this.setValue('addAssetAssetCode', record.assetCode || '');
      this.setValue('addAssetCategory', record.assetCategory || '');
      this.updateAddAssetDynamicFields();
      this.setValue('addAssetBranch', record.reportingBranch || '');
      this.setValue('addAssetOffice', record.office || '');
      this.setValue('addAssetStatus', record.assetStatus || '');
      this.setValue('addAssetBrand', record.brand || '');
      this.setValue('addAssetModel', record.model || '');
      this.setValue('addAssetSerial', record.serialNumber || '');
      this.setValue('addAssetCurrentUser', record.currentUser || '');
      this.setValue('addAssetMainAssetNo', String(record.assetCategory || '').toLowerCase() === 'laptop' ? '' : (record.assignedMainAssetNo || ''));
      this.setValue('addAssetDateDeployment', record.dateDeployment || '');
      this.setValue('addAssetPurchaseDate', record.purchaseDate || record.dateDeployment || '');
      this.setValue('addAssetPurchasePrice', record.purchasePrice != null ? record.purchasePrice : '');
      this.setValue('addAssetExpectedLife', record.expectedLifeYears != null && record.expectedLifeYears !== '' ? record.expectedLifeYears : '');
      this.setValue('addAssetResidualValue', record.residualValue != null ? record.residualValue : '');
      this.setValue('addAssetCurrentValue', record.currentValue != null ? record.currentValue : '');
      this.setValue('addAssetDeviceEol', record.deviceEol || '');
      this.setValue('addAssetFullyDepDate', record.fullyDepreciatedDate || '');
      this.setValue('addAssetEncodedBy', record.encodedBy || 'Admin');
      this.setValue('addAssetRemarks', record.remarks || '');
      this.setValue('addAssetProcessor', record.processor || '');
      this.setValue('addAssetRam', record.ram || '');
      this.setValue('addAssetStorage', record.storage || '');
      this.setValue('addAssetOS', record.operatingSystem || '');
      this.setValue('addAssetPcName', record.pcName || '');
      this.setValue('addAssetLaptopName', record.laptopName || '');
      this.setValue('addAssetMac', record.macAddress || '');
      this.setValue('addAssetIp', record.ipAddress || '');
      this.setValue('addAssetCapacityVa', record.capacityVa || '');
      this.setValue('addAssetPrinterType', record.printerType || '');
      if (!this.valueOf('addAssetExpectedLife')) {
        this.applyExpectedLifeFromSettings();
      }
      this.calculateAddAssetDepreciation();
      this.previewNextAssetCode();
    },

    resetAddWorkstationForm(resetBranch) {
      const form = document.getElementById('addWorkstationForm');
      if (!form) return;

      form.reset();

      if (resetBranch !== false) {
        const branchEl = document.getElementById('addWsBranch');
        if (branchEl) branchEl.value = '';
      }
      if (this.state.globalSite) this.setValue('addWsBranch', this.state.globalSite);

      const encodedBy = document.getElementById('addWsEncodedBy');
      if (encodedBy && !encodedBy.value) encodedBy.value = 'Admin';
      this.setValue('addWsEmployeeId', '');
      this.setWorkstationEmployeeStatus('Use Employee ID or exact name.');

      this.state.wsComponents.forEach((key) => this.updateWorkstationComponentVisibility(key));
      this.previewNextWorkstationCode();
      this.setAddWorkstationSubmitting(false);
    },

    updateAddAssetDynamicFields() {
      const category = (document.getElementById('addAssetCategory') || {}).value || '';
      const key = category.toLowerCase();

      const isSystemUnit = key === 'system unit';
      const isLaptop = key === 'laptop';
      const isUPS = key === 'ups';
      const isPrinter = key === 'printer';
      const isComputer = isSystemUnit || isLaptop;

      document.querySelectorAll('.spec-field').forEach(el => {
        el.classList.add('hidden');
        const input = el.querySelector('input');
        if (input) input.required = false;
      });

      const showGroup = (selector, required) => {
        document.querySelectorAll(selector).forEach(el => {
          el.classList.remove('hidden');
          const input = el.querySelector('input');
          if (input && required) input.required = true;
        });
      };

      if (isComputer) showGroup('.spec-computer', true);
      if (isSystemUnit) showGroup('.spec-system-unit', false);
      if (isLaptop) showGroup('.spec-laptop', false);
      if (isUPS) showGroup('.spec-ups', true);
      if (isPrinter) showGroup('.spec-printer', true);

      const mainAssetField = document.getElementById('addAssetMainAssetField');
      const mainAssetInput = document.getElementById('addAssetMainAssetNo');
      if (mainAssetField) mainAssetField.classList.toggle('hidden', isLaptop);
      if (isLaptop && mainAssetInput) mainAssetInput.value = '';
    },

    updateWorkstationComponentVisibility(key) {
      const mode = this.valueOf(this.wsModeId(key)).toLowerCase();
      const existingWrap = document.querySelector(`[data-ws-existing="${key}"]`);
      const newWrap = document.querySelector(`[data-ws-new="${key}"]`);

      if (existingWrap) existingWrap.classList.toggle('hidden', mode !== 'existing');
      if (newWrap) newWrap.classList.toggle('hidden', mode !== 'new');
    },

    applyExpectedLifeFromSettings() {
      const category = this.valueOf('addAssetCategory');
      const expectedLifeInput = document.getElementById('addAssetExpectedLife');
      if (!expectedLifeInput) return;

      const map = (this.state.settings && this.state.settings.defaultEolYearsByCategory) || {};
      expectedLifeInput.value = category && map[category] ? map[category] : '';
    },

    calculateAddAssetDepreciation() {
      const purchaseDate = this.valueOf('addAssetPurchaseDate');
      const purchasePriceRaw = this.valueOf('addAssetPurchasePrice');
      const expectedLifeRaw = this.valueOf('addAssetExpectedLife');

      const purchasePrice = Number(purchasePriceRaw);
      const expectedLifeYears = Number(expectedLifeRaw);

      const residualField = document.getElementById('addAssetResidualValue');
      const currentField = document.getElementById('addAssetCurrentValue');
      const eolField = document.getElementById('addAssetDeviceEol');
      const depDateField = document.getElementById('addAssetFullyDepDate');

      if (residualField) residualField.value = '';
      if (currentField) currentField.value = '';
      if (eolField) eolField.value = '';
      if (depDateField) depDateField.value = '';

      if (!isNaN(purchasePrice) && purchasePrice > 0) {
        const residual = this.round2(purchasePrice * 0.10);
        if (residualField) residualField.value = residual.toFixed(2);

        let currentValue = purchasePrice;

        if (purchaseDate && !isNaN(expectedLifeYears) && expectedLifeYears > 0) {
          const purchaseDateObj = this.parseISODate(purchaseDate);
          const fullyDepDateObj = this.addYearsToDate(purchaseDateObj, expectedLifeYears);

          if (eolField) eolField.value = this.toISODateString(fullyDepDateObj);
          if (depDateField) depDateField.value = this.toISODateString(fullyDepDateObj);

          const now = new Date();
          const totalMs = Math.max(1, fullyDepDateObj.getTime() - purchaseDateObj.getTime());
          const elapsedMs = Math.max(0, Math.min(now.getTime() - purchaseDateObj.getTime(), totalMs));
          const fraction = elapsedMs / totalMs;
          const depreciable = purchasePrice - residual;
          currentValue = Math.max(residual, purchasePrice - (depreciable * fraction));
        }

        if (currentField) currentField.value = this.round2(currentValue).toFixed(2);
      } else if (purchaseDate && !isNaN(expectedLifeYears) && expectedLifeYears > 0) {
        const purchaseDateObj = this.parseISODate(purchaseDate);
        const fullyDepDateObj = this.addYearsToDate(purchaseDateObj, expectedLifeYears);
        if (eolField) eolField.value = this.toISODateString(fullyDepDateObj);
        if (depDateField) depDateField.value = this.toISODateString(fullyDepDateObj);
      }
    },

    previewNextAssetCode() {
      const category = this.valueOf('addAssetCategory');
      const preview = document.getElementById('addAssetCodePreview');
      if (!preview) return;

      if (!category) {
        preview.textContent = 'Select a category to preview next asset code.';
        return;
      }

      preview.textContent = 'Loading next asset code...';

      this.server('apiGenerateNextAssetCode', category)
        .then((res) => {
          preview.textContent = res.assetCode || 'Unable to preview code';
        })
        .catch((error) => {
          preview.textContent = error.message || 'Unable to preview code';
        });
    },

    lookupCurrentUserAssets(silent) {
      const user = this.valueOf('addAssetCurrentUser');
      if (!user) {
        if (!silent) this.renderAssetLookupResult({ found: false, message: 'Enter a current user first.' });
        return;
      }

      this.setAddAssetImportStatus('Looking up current user...');
      this.server('apiLookupAssetsByUser', user)
        .then((res) => {
          this.renderAssetLookupResult(res);
          if (res && res.found) this.applyUserLookupToForm(res);
          this.setAddAssetImportStatus(res && res.found ? 'User lookup complete.' : 'No linked assets found for that user.');
        })
        .catch((error) => {
          this.renderAssetLookupResult({ found: false, message: error.message || 'Unable to look up current user.' });
          this.setAddAssetImportStatus('Lookup failed.');
        });
    },

    applyUserLookupToForm(res) {
      if (!res) return;
      if (res.user) this.setValue('addAssetCurrentUser', res.user);
      if (this.valueOf('addAssetCategory').toLowerCase() !== 'laptop' && res.mainAssetNumber && !this.valueOf('addAssetMainAssetNo')) {
        this.setValue('addAssetMainAssetNo', res.mainAssetNumber);
      }
      if (res.dateDeployed && !this.valueOf('addAssetDateDeployment')) this.setValue('addAssetDateDeployment', res.dateDeployed);
      if (res.reportingBranch && !this.valueOf('addAssetBranch')) this.setValue('addAssetBranch', res.reportingBranch);
      if (res.office && !this.valueOf('addAssetOffice')) this.setValue('addAssetOffice', res.office);
    },

    renderAssetLookupResult(res) {
      const el = document.getElementById('addAssetLookupResult');
      if (!el) return;

      if (!res || !res.found) {
        el.innerHTML = `<h4>User lookup</h4><div>${this.escapeHtml((res && res.message) || 'No linked deployment found.')}</div>`;
        el.classList.remove('hidden');
        return;
      }

      const assets = (res.linkedAssets || []).map(item => {
        const title = [item.brand, item.model].filter(Boolean).join(' ').trim();
        const suffix = title ? ` (${this.escapeHtml(title)})` : '';
        const extra = [item.serialNumber, item.assetStatus].filter(Boolean).join(' | ');
        const extraText = extra ? ` - ${this.escapeHtml(extra)}` : '';
        return `<li>${this.escapeHtml(item.assetCode || 'Unknown')} - ${this.escapeHtml(item.assetCategory || 'Asset')}${suffix}${extraText}</li>`;
      }).join('');
      const isLaptop = this.valueOf('addAssetCategory').toLowerCase() === 'laptop';
      el.innerHTML = `
        <h4>${this.escapeHtml(res.user || 'User lookup')}</h4>
        <div>${this.escapeHtml(res.message || 'Linked deployment found.')}</div>
        <ul>
          ${isLaptop ? '' : `<li>Main asset: ${this.escapeHtml(res.mainAssetNumber || 'N/A')}</li>`}
          <li>Branch: ${this.escapeHtml(res.reportingBranch || 'N/A')}</li>
          <li>Office: ${this.escapeHtml(res.office || 'N/A')}</li>
          <li>Date deployed: ${this.escapeHtml(res.dateDeployed || 'N/A')}</li>
        </ul>
        ${assets ? `<div style="margin-top:10px;font-weight:800;">Linked assets</div><ul>${assets}</ul>` : ''}
      `;
      el.classList.remove('hidden');
    },

    clearAssetLookupResult() {
      const el = document.getElementById('addAssetLookupResult');
      if (el) {
        el.classList.add('hidden');
        el.innerHTML = '';
      }
    },

    setAddAssetImportStatus(text) {
      const el = document.getElementById('addAssetImportStatus');
      if (el) el.textContent = text || 'No file selected.';
    },

    renderAddAssetImportResult(res) {
      const el = document.getElementById('addAssetImportStatus');
      if (!el) return;
      const created = Number(res.createdCount || 0);
      const skipped = Number(res.skippedCount || 0);
      const duplicates = res.duplicates || [];
      const warnings = res.warnings || [];
      const errors = res.errors || [];
      const duplicateRows = [...duplicates, ...warnings];
      const summary = `Imported ${created} row(s). Skipped ${skipped}. Duplicates ${duplicates.length}. Warnings ${warnings.length}.${errors.length ? ` Errors ${errors.length}.` : ''}`;
      const detailRows = duplicateRows.map(item => `
        <div class="import-detail-row tone-${item.action === 'Skipped' ? 'danger' : 'warning'}">
          <strong>Row ${this.escapeHtml(item.row || '-')} · ${this.escapeHtml(item.type || 'Duplicate')}</strong>
          <span>${this.escapeHtml(item.assetCode || 'No asset code')} ${item.serialNumber ? `· Serial ${this.escapeHtml(item.serialNumber)}` : ''}</span>
          <small>${this.escapeHtml(item.message || '')}${item.existingAssetCode ? ` Existing: ${this.escapeHtml(item.existingAssetCode)}` : ''}</small>
        </div>
      `).join('');
      const errorRows = errors.map(error => `<div class="import-detail-row tone-danger"><strong>Error</strong><small>${this.escapeHtml(error)}</small></div>`).join('');
      el.innerHTML = `
        <div class="import-summary">${this.escapeHtml(summary)}</div>
        ${duplicateRows.length || errors.length ? `
          <details class="import-details">
            <summary>View duplicate/import details</summary>
            <div class="import-detail-list">${detailRows}${errorRows}</div>
          </details>
        ` : ''}
      `;
    },

    importAssetsFromSpreadsheet() {
      const fileEl = document.getElementById('addAssetImportFile');
      if (!fileEl || !fileEl.files || !fileEl.files[0]) {
        this.setAddAssetImportStatus('Choose an .xlsx or .csv file first.');
        return;
      }

      const formData = new FormData();
      formData.append('method', 'apiImportAssetsFromSpreadsheet');
      formData.append('file', fileEl.files[0]);
      formData.append('defaultCategory', this.valueOf('addAssetCategory') || 'System Unit');
      formData.append('defaultBranch', this.valueOf('addAssetBranch'));
      formData.append('defaultOffice', this.valueOf('addAssetOffice'));
      formData.append('defaultStatus', this.valueOf('addAssetStatus') || 'Ready to Deploy');
      formData.append('encodedBy', this.valueOf('addAssetEncodedBy') || 'Admin');

      this.setAddAssetImportStatus('Uploading file...');

      fetch('api.php', {
        method: 'POST',
        body: formData
      })
      .then(async (response) => {
        const payload = await response.json().catch(() => null);
        if (!response.ok || !payload || payload.success === false) {
          throw new Error((payload && payload.message) || 'Import failed');
        }
        return payload.data;
      })
      .then((res) => {
        this.renderAddAssetImportResult(res || {});
        this.state.assetRegistry = null;
        this.state.dashboardData = null;
        if (this.state.currentView === 'assets') this.loadAssets();
        if (this.state.currentView === 'dashboard') this.loadDashboard();
        if (this.state.currentView === 'workstations') this.loadWorkstations();
      })
      .catch((error) => {
        this.setAddAssetImportStatus(error.message || 'Import failed.');
      });
    },

    previewNextWorkstationCode() {
      const site = this.valueOf('addWsBranch');
      const preview = document.getElementById('addWorkstationCodePreview');
      if (!preview) return;

      if (!site) {
        preview.textContent = 'Select a site to preview next workstation ID.';
        return;
      }

      preview.textContent = 'Loading next workstation ID...';

      this.server('apiGenerateNextMainAssetNumber', site)
        .then((res) => {
          preview.textContent = res.mainAssetNumber || 'Unable to preview ID';
        })
        .catch((error) => {
          preview.textContent = error.message || 'Unable to preview ID';
        });
    },

    setWorkstationEmployeeStatus(text) {
      const el = document.getElementById('addWsEmployeeStatus');
      if (el) el.textContent = text || 'Use Employee ID or exact name.';
    },

    lookupWorkstationEmployee(silent) {
      const query = this.valueOf('addWsUser');
      if (!query) {
        if (!silent) this.setWorkstationEmployeeStatus('Enter an Employee ID or name first.');
        return Promise.resolve(null);
      }

      this.setWorkstationEmployeeStatus('Looking up employee...');
      return this.server('apiLookupEmployee', query)
        .then((res) => {
          if (res.found && res.employee) {
            const employee = res.employee;
            this.setValue('addWsEmployeeId', employee.employeeId);
            this.setValue('addWsUser', employee.employeeName);
            if (employee.reportingBranch && !this.valueOf('addWsBranch')) {
              this.setValue('addWsBranch', employee.reportingBranch);
              this.previewNextWorkstationCode();
            }
            if (employee.office && !this.valueOf('addWsOffice')) this.setValue('addWsOffice', employee.office);
            this.setWorkstationEmployeeStatus(`Matched ${employee.employeeId}. Branch and office can be adjusted if needed.`);
            return employee;
          }

          this.setValue('addWsEmployeeId', '');
          const suggestions = (res.items || []).map(item => `${item.employeeId} - ${item.employeeName}`).join('; ');
          this.setWorkstationEmployeeStatus(suggestions ? `No exact match. Similar: ${suggestions}` : 'No employee found.');
          if (!silent && confirm(`Employee "${query}" was not found. Create this employee from the workstation form?`)) {
            return this.createEmployeeFromWorkstationForm();
          }
          return null;
        })
        .catch((error) => {
          this.setWorkstationEmployeeStatus(error.message || 'Employee lookup failed.');
          return null;
        });
    },

    createEmployeeFromWorkstationForm() {
      const employeeName = this.valueOf('addWsUser');
      if (!employeeName) return Promise.resolve(null);
      return this.server('apiCreateEmployee', {
        employeeName,
        reportingBranch: this.valueOf('addWsBranch'),
        office: this.valueOf('addWsOffice'),
        active: 'Yes',
        idSource: 'Workstation'
      })
        .then((employee) => {
          this.setValue('addWsEmployeeId', employee.employeeId);
          this.setValue('addWsUser', employee.employeeName);
          this.setWorkstationEmployeeStatus(`Created ${employee.employeeId} in Employees.`);
          this.state.employeeData = null;
          return employee;
        })
        .catch((error) => {
          this.setWorkstationEmployeeStatus(error.message || 'Unable to create employee.');
          throw error;
        });
    },

    collectAddAssetPayload() {
      const category = this.valueOf('addAssetCategory');
      const isLaptop = category.toLowerCase() === 'laptop';
      return {
        assetCode: this.valueOf('addAssetAssetCode'),
        assetCategory: category,
        reportingBranch: this.valueOf('addAssetBranch'),
        office: this.valueOf('addAssetOffice'),
        assetStatus: this.valueOf('addAssetStatus'),
        brand: this.valueOf('addAssetBrand'),
        model: this.valueOf('addAssetModel'),
        serialNumber: this.valueOf('addAssetSerial'),
        employeeId: '',
        currentUser: this.valueOf('addAssetCurrentUser'),
        assignedMainAssetNo: isLaptop ? '' : this.valueOf('addAssetMainAssetNo'),
        dateDeployment: this.valueOf('addAssetDateDeployment'),
        purchaseDate: this.valueOf('addAssetPurchaseDate'),
        purchasePrice: this.valueOf('addAssetPurchasePrice'),
        expectedLifeYears: this.valueOf('addAssetExpectedLife'),
        residualValue: this.valueOf('addAssetResidualValue'),
        currentValue: this.valueOf('addAssetCurrentValue'),
        deviceEol: this.valueOf('addAssetDeviceEol'),
        fullyDepreciatedDate: this.valueOf('addAssetFullyDepDate'),
        encodedBy: this.valueOf('addAssetEncodedBy'),
        remarks: this.valueOf('addAssetRemarks'),
        processor: this.valueOf('addAssetProcessor'),
        ram: this.valueOf('addAssetRam'),
        storage: this.valueOf('addAssetStorage'),
        operatingSystem: this.valueOf('addAssetOS'),
        pcName: this.valueOf('addAssetPcName'),
        laptopName: this.valueOf('addAssetLaptopName'),
        macAddress: this.valueOf('addAssetMac'),
        ipAddress: this.valueOf('addAssetIp'),
        capacityVa: this.valueOf('addAssetCapacityVa'),
        printerType: this.valueOf('addAssetPrinterType')
      };
    },

    collectAddWorkstationPayload() {
      const payload = {
        mainAssetNumber: this.valueOf('addWsMainAssetNumber'),
        employeeId: this.valueOf('addWsEmployeeId'),
        reportingBranch: this.valueOf('addWsBranch'),
        user: this.valueOf('addWsUser'),
        office: this.valueOf('addWsOffice'),
        dateDeployed: this.valueOf('addWsDateDeployed'),
        dateReturned: this.valueOf('addWsDateReturned'),
        active: this.valueOf('addWsActive'),
        deploymentStatus: this.valueOf('addWsDeploymentStatus'),
        remarks: this.valueOf('addWsRemarks'),
        encodedBy: this.valueOf('addWsEncodedBy'),
        allowCreateEmployee: false
      };

      this.state.wsComponents.forEach((key) => {
        const cap = this.capitalize(key);
        payload[key + 'Mode'] = this.valueOf('addWs' + cap + 'Mode');
        payload[key + 'Code'] = this.valueOf('addWs' + cap + 'Code');

        payload[key + 'Brand'] = this.valueOf('addWs' + cap + 'Brand');
        payload[key + 'Model'] = this.valueOf('addWs' + cap + 'Model');
        payload[key + 'SerialNumber'] = this.valueOf('addWs' + cap + 'SerialNumber');
        payload[key + 'PurchaseDate'] = this.valueOf('addWs' + cap + 'PurchaseDate');
        payload[key + 'PurchasePrice'] = this.valueOf('addWs' + cap + 'PurchasePrice');

        payload[key + 'Processor'] = this.valueOf('addWs' + cap + 'Processor');
        payload[key + 'Ram'] = this.valueOf('addWs' + cap + 'Ram');
        payload[key + 'Storage'] = this.valueOf('addWs' + cap + 'Storage');
        payload[key + 'OperatingSystem'] = this.valueOf('addWs' + cap + 'OperatingSystem');
        payload[key + 'PcName'] = this.valueOf('addWs' + cap + 'PcName');
        payload[key + 'LaptopName'] = this.valueOf('addWs' + cap + 'LaptopName');
        payload[key + 'MacAddress'] = this.valueOf('addWs' + cap + 'MacAddress');
        payload[key + 'IpAddress'] = this.valueOf('addWs' + cap + 'IpAddress');
        payload[key + 'CapacityVa'] = this.valueOf('addWs' + cap + 'CapacityVa');
        payload[key + 'PrinterType'] = this.valueOf('addWs' + cap + 'PrinterType');
      });

      return payload;
    },

    submitAddAssetForm() {
      const payload = this.collectAddAssetPayload();
      this.setAddAssetSubmitting(true);

      const method = this.state.assetFormMode === 'edit' ? 'apiUpdateAsset' : 'apiCreateAsset';
      this.server(method, payload)
        .then((res) => {
          alert(this.state.assetFormMode === 'edit'
            ? `Asset updated successfully.\n\nAsset Code: ${res.assetCode}`
            : `Asset created successfully.\n\nAsset Code: ${res.assetCode}`);
          this.closeAddAssetModal();
          this.resetAddAssetForm();

          this.state.assetRegistry = null;
          this.state.dashboardData = null;
          this.state.activeAssetDetail = null;

          if (this.state.currentView === 'assets') this.loadAssets();
          if (this.state.currentView === 'dashboard') this.loadDashboard();
          if (this.state.currentView === 'workstations') this.loadWorkstations();
        })
        .catch((error) => {
          alert(error.message || 'Unable to save asset.');
        })
        .finally(() => {
          this.setAddAssetSubmitting(false);
        });
    },

    submitAddWorkstationForm() {
      const payload = this.collectAddWorkstationPayload();
      this.setAddWorkstationSubmitting(true);

      const ensureEmployee = payload.employeeId
        ? Promise.resolve(payload)
        : this.lookupWorkstationEmployee(false).then((employee) => {
            if (employee) {
              payload.employeeId = employee.employeeId;
              payload.user = employee.employeeName;
              return payload;
            }
            if (!confirm(`Create workstation for "${payload.user}" without an existing employee record? Choose OK to create the employee now.`)) {
              throw new Error('Employee is required before creating a workstation.');
            }
            return this.createEmployeeFromWorkstationForm().then((created) => {
              payload.employeeId = created.employeeId;
              payload.user = created.employeeName;
              return payload;
            });
          });

      ensureEmployee.then((readyPayload) => this.server('apiCreateWorkstation', readyPayload))
        .then((res) => {
          alert(`Workstation created successfully.\n\nMain Asset Number: ${res.mainAssetNumber}`);
          this.closeAddWorkstationModal();
          this.resetAddWorkstationForm();

          this.state.assetRegistry = null;
          this.state.dashboardData = null;

          this.loadWorkstations();
          if (this.state.currentView === 'assets') this.loadAssets();
          if (this.state.currentView === 'dashboard') this.loadDashboard();
        })
        .catch((error) => {
          alert(error.message || 'Unable to save workstation.');
        })
        .finally(() => {
          this.setAddWorkstationSubmitting(false);
        });
    },

    setAddAssetSubmitting(isSubmitting) {
      const btn = document.getElementById('addAssetSubmitBtn');
      if (!btn) return;
      btn.disabled = !!isSubmitting;
      btn.textContent = isSubmitting ? 'Saving...' : 'Save Asset';
    },

    setAddWorkstationSubmitting(isSubmitting) {
      const btn = document.getElementById('addWsSubmitBtn');
      if (!btn) return;
      btn.disabled = !!isSubmitting;
      btn.textContent = isSubmitting ? 'Saving...' : 'Save Workstation';
    },

    setEmployeeSubmitting(isSubmitting) {
      const btn = document.getElementById('employeeSubmitBtn');
      if (!btn) return;
      btn.disabled = !!isSubmitting;
      btn.textContent = isSubmitting ? 'Saving...' : (this.state.employeeFormMode === 'edit' ? 'Update Employee' : 'Save Employee');
    },

    renderSimpleFeed(id, items, rowTemplate, emptyTitle) {
      const el = document.getElementById(id);
      if (!el) return;

      if (!(items || []).length) {
        el.innerHTML = this.emptyMarkup(emptyTitle || 'No data', 'No records are available yet.');
        return;
      }

      el.innerHTML = items.map(rowTemplate).join('');
    },

    kvRows(rows, allowHtml) {
      return rows.map(([label, value]) => `
        <div class="kv-row">
          <div class="kv-label">${this.escapeHtml(label || '')}</div>
          <div class="kv-value">${allowHtml ? (value || '—') : this.escapeHtml(value || '—')}</div>
        </div>
      `).join('');
    },

    badge(text, tone) {
      const value = String(text || '').toLowerCase();
      const normalizedTone = String(tone || '').toLowerCase();
      const allowedTone = ['success', 'info', 'warning', 'danger', 'neutral'].includes(normalizedTone) ? normalizedTone : '';
      const inferredTone = allowedTone || (
        /deployed|assigned|active|good|tracked|completed|done|available/.test(value) ? 'success' :
        /ready|open|pending|new/.test(value) ? 'info' :
        /maintenance|repair|warning|expir|check/.test(value) ? 'warning' :
        /retired|disposed|resigned|inactive|failed|missing/.test(value) ? 'danger' :
        'neutral'
      );
      return `<span class="status-badge tone-${this.escapeHtml(inferredTone)}">${this.escapeHtml(text || '—')}</span>`;
    },

    assetChip(code) {
      if (!code) return '<span class="muted">—</span>';
      return `<span class="asset-chip" data-asset-code="${this.escapeHtml(code)}">${this.escapeHtml(code)}</span>`;
    },

    setText(id, value) {
      const el = document.getElementById(id);
      if (el) el.textContent = value == null || value === '' ? '—' : value;
    },

    setHtml(id, value) {
      const el = document.getElementById(id);
      if (el) el.innerHTML = value || '—';
    },

    setProgress(id, value) {
      const el = document.getElementById(id);
      if (el) el.style.width = `${Number(value || 0)}%`;
    },

    setValue(id, value) {
      const el = document.getElementById(id);
      if (el) el.value = value == null ? '' : String(value);
    },

    loadingMarkup() {
      const template = document.getElementById('loadingTemplate');
      if (template) return template.innerHTML;
      return '<div class="loading-wrap"><div class="loading-card"><div class="spinner"></div><div style="font-weight:800; margin-bottom:6px;">Loading</div><div class="muted">Please wait while the system fetches records.</div></div></div>';
    },

    setBusy(isBusy) {
      document.body.classList.toggle('is-busy', !!isBusy);
    },

    emptyMarkup(title, message) {
      const template = document.getElementById('emptyStateTemplate');
      if (!template) {
        return `<div class="empty-state"><div class="empty-title">${this.escapeHtml(title || 'No data')}</div><div class="muted">${this.escapeHtml(message || 'There are no records to display.')}</div></div>`;
      }
      const wrapper = document.createElement('div');
      wrapper.innerHTML = template.innerHTML;
      const titleEl = wrapper.querySelector('#emptyStateTitle');
      const msgEl = wrapper.querySelector('#emptyStateMessage');
      if (titleEl) titleEl.textContent = title || 'No data';
      if (msgEl) msgEl.textContent = message || 'There are no records to display.';
      return wrapper.innerHTML;
    },

    wsModeId(key) {
      return 'addWs' + this.capitalize(key) + 'Mode';
    },

    capitalize(text) {
      return text.charAt(0).toUpperCase() + text.slice(1);
    },

    server(fnName, ...args) {
      return fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ method: fnName, args: args })
      })
      .then(async (response) => {
        const payload = await response.json().catch(() => null);
        if (!response.ok || !payload || payload.success === false) {
          throw new Error((payload && payload.message) || 'Server error');
        }
        return payload.data;
      });
    },

    iconImg(name, className = 'ui-svg-icon') {
      return `<img class="${className}" src="assets/images/icons/${name}.svg" alt="" aria-hidden="true">`;
    },

    categoryIconImg(category, className = 'ui-svg-icon') {
      const map = {
        'system unit': 'category-desktop',
        'desktop': 'category-desktop',
        'monitor': 'category-monitor',
        'mouse': 'category-mouse',
        'keyboard': 'category-keyboard',
        'ups': 'category-ups',
        'printer': 'category-printer',
        'laptop': 'category-laptop'
      };
      const icon = map[String(category || '').toLowerCase()] || 'category-default';
      return this.iconImg(icon, className);
    },

    valueOf(id) {
      const el = document.getElementById(id);
      return el ? el.value.trim() : '';
    },

    money(value) {
      const num = Number(value || 0);
      if (!num) return '₱0.00';
      return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(num);
    },

    iconForCategory(category) {
      const map = {
        'system unit': 'Desktop',
        'desktop': 'Desktop',
        'monitor': 'Monitor',
        'mouse': 'Mouse',
        'keyboard': 'Keyboard',
        'ups': 'UPS',
        'printer': 'Printer',
        'laptop': 'Laptop'
      };
      return map[String(category || '').toLowerCase()] || 'Asset';
    },

    parseISODate(value) {
      const parts = String(value || '').split('-');
      return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    },

    toISODateString(date) {
      const d = new Date(date);
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${yyyy}-${mm}-${dd}`;
    },

    formatDisplayDate(value) {
      const raw = String(value || '').trim();
      if (!raw) return '';
      const dateOnly = raw.includes('T') ? raw.slice(0, 10) : raw.split(' ')[0];
      const iso = dateOnly.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
      const slash = dateOnly.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
      if (iso) return `${iso[2].padStart(2, '0')}/${iso[3].padStart(2, '0')}/${iso[1]}`;
      if (slash) return `${slash[1].padStart(2, '0')}/${slash[2].padStart(2, '0')}/${slash[3]}`;
      return raw;
    },

    addYearsToDate(date, years) {
      const d = new Date(date);
      d.setFullYear(d.getFullYear() + Number(years || 0));
      return d;
    },

    round2(value) {
      return Math.round(Number(value || 0) * 100) / 100;
    },

    titleCase(text) {
      return String(text || '')
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, ch => ch.toUpperCase());
    },

    escapeHtml(value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    },

    handleError(error) {
      console.error(error);
      alert((error && error.message) || 'Something went wrong.');
    }
  };

  window.addEventListener('DOMContentLoaded', () => App.init());
