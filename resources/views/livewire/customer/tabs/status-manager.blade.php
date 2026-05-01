<div>
    @include('backend.pages.customers.partials.status-manager', [
        'managerCompanies' => $managerCompanies,
        'selectedCompany' => $selectedCompany,
        'companyManager' => $companyManager,
        'canViewReport' => $canViewReport,
    ])
</div>