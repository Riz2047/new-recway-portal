# Customer Invoice Dashboard

## Overview
The Customer Invoice dashboard provides Admin and Manager users with a comprehensive view of customer invoices, allowing them to track invoice status, filter by various criteria, and manage invoice workflows.

## Features

### 📊 Dashboard View
- **Clear Overview**: Easy-to-read table showing all customer invoices
- **Real-time Data**: Server-side processing with DataTables for optimal performance
- **Responsive Design**: Works on desktop, tablet, and mobile devices

### 🔍 Advanced Filtering
- **Period Filter**: Filter by day, week, or month
- **Status Filter**: Filter by invoice status (To be invoiced, Sent, Paid)
- **Date Range**: Filter by date range (from/to dates)
- **Search**: Global search across customer names, amounts, and statuses

### 📋 Invoice Management
- **Status Updates**: Update invoice status with notes
- **Export Functionality**: Export filtered data to CSV
- **Pagination**: Efficient pagination for large datasets
- **Sorting**: Sort by any column

### 📈 Invoice Fields
- **Customer Name**: Name of the customer
- **Period**: Day/Week/Month billing period
- **Invoice Amount**: Amount to be invoiced (if available)
- **Status**: Current status (To be invoiced, Sent, Paid)
- **Due Date**: When the invoice is due
- **Created Date**: When the invoice was created
- **Actions**: Update status functionality

## Setup Instructions

### 1. Database Setup
Run the setup script to create the necessary database table:
```
http://your-domain/admin2/setup_invoice_system.php
```

This will:
- Create the `customer_invoices` table
- Insert sample data for testing
- Set up proper foreign key relationships

### 2. Navigation
The "Customer Invoice" tab has been added to the admin2 navigation menu, positioned after the "Customers" tab.

### 3. Access Control
- Only Admin and Manager users can access this feature
- Session-based authentication is enforced
- Unauthorized access attempts are blocked

## Usage Guide

### Viewing Invoices
1. Navigate to the Customer Invoice tab
2. Use the filter options to narrow down results
3. Click "Apply Filters" to update the table
4. Use the search box for quick text searches

### Updating Invoice Status
1. Click "Update Status" button for any invoice
2. Select the new status from the dropdown
3. Add optional notes
4. Click "Update Status" to save changes

### Exporting Data
1. Apply desired filters
2. Click the "Export Data" button
3. A CSV file will be downloaded with the filtered results

### Filtering Options

#### Period Filter
- **Day**: Daily invoices
- **Week**: Weekly invoices  
- **Month**: Monthly invoices
- **All Periods**: Show all periods

#### Status Filter
- **To be invoiced**: Invoices that need to be created/sent
- **Sent**: Invoices that have been sent to customers
- **Paid**: Invoices that have been paid
- **All Statuses**: Show all statuses

#### Date Range
- **Date From**: Start date for filtering
- **Date To**: End date for filtering
- Leave empty to include all dates

## Technical Details

### Database Schema
```sql
CREATE TABLE customer_invoices (
  id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  period enum('day','week','month') NOT NULL,
  invoice_amount decimal(10,2) DEFAULT NULL,
  status enum('to_be_invoiced','sent','paid') NOT NULL DEFAULT 'to_be_invoiced',
  due_date date DEFAULT NULL,
  notes text DEFAULT NULL,
  created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

### File Structure
- `customer-invoice.php` - Main dashboard page
- `includes/invoice_ajax.php` - AJAX handler for data processing
- `setup_invoice_system.php` - Database setup script
- `setup_invoice_table.sql` - SQL schema file

### AJAX Endpoints
- **Data Loading**: POST to `includes/invoice_ajax.php` with DataTables parameters
- **Status Update**: POST with `action=update_status`
- **Export**: GET with `action=export` and filter parameters

## Security Features
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping
- CSRF protection through session validation
- Input validation and sanitization
- Role-based access control

## Performance Optimizations
- Server-side processing for large datasets
- Indexed database columns for fast queries
- Efficient pagination to reduce memory usage
- AJAX-based updates to avoid page reloads
- Responsive design for optimal user experience

## Troubleshooting

### Common Issues
1. **No data showing**: Run the setup script to create sample data
2. **Permission denied**: Ensure you're logged in as Admin or Manager
3. **Export not working**: Check browser popup blockers
4. **Filters not applying**: Clear browser cache and try again

### Support
For technical support or feature requests, contact the development team.
