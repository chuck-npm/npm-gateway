const initializeRmAuditReportPrint = (root = document) => {
  root.querySelectorAll('[data-print-report]').forEach((button) => {
    if (button.dataset.printReady === 'true') return;
    button.dataset.printReady = 'true';
    button.addEventListener('click', () => window.print());
  });
};

if (typeof document !== 'undefined') initializeRmAuditReportPrint();

export { initializeRmAuditReportPrint };
