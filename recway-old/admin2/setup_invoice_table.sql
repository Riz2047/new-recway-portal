-- Create customer_invoices table
CREATE TABLE IF NOT EXISTS `customer_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `period` enum('day','week','month') NOT NULL,
  `invoice_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('to_be_invoiced','sent','paid') NOT NULL DEFAULT 'to_be_invoiced',
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `status` (`status`),
  KEY `period` (`period`),
  KEY `created_date` (`created_date`),
  CONSTRAINT `customer_invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clear existing data
DELETE FROM customer_invoices;

-- Insert comprehensive dummy data
INSERT INTO `customer_invoices` (`customer_id`, `period`, `invoice_amount`, `status`, `due_date`, `notes`, `created_date`) VALUES
-- Day invoices
(1, 'day', 150.00, 'to_be_invoiced', '2024-02-20', 'Daily service for Customer 1', '2024-01-15 10:30:00'),
(2, 'day', 200.00, 'sent', '2024-02-18', 'Daily service for Customer 2', '2024-01-20 14:15:00'),
(3, 'day', 175.00, 'paid', '2024-01-25', 'Daily service for Customer 3', '2024-01-10 09:45:00'),

-- Week invoices
(1, 'week', 800.00, 'to_be_invoiced', '2024-02-25', 'Weekly service for Customer 1', '2024-01-18 11:20:00'),
(2, 'week', 650.00, 'sent', '2024-02-22', 'Weekly service for Customer 2', '2024-01-22 16:30:00'),
(4, 'week', 750.00, 'paid', '2024-01-30', 'Weekly service for Customer 4', '2024-01-12 13:10:00'),

-- Month invoices
(1, 'month', 2500.00, 'to_be_invoiced', '2024-03-15', 'Monthly service for Customer 1', '2024-01-25 08:00:00'),
(3, 'month', 3200.00, 'sent', '2024-03-10', 'Monthly service for Customer 3', '2024-01-28 15:45:00'),
(5, 'month', 1800.00, 'paid', '2024-02-05', 'Monthly service for Customer 5', '2024-01-05 12:30:00'),

-- Additional mixed invoices
(2, 'month', 2800.00, 'to_be_invoiced', '2024-03-20', 'Monthly premium service for Customer 2', '2024-01-30 10:15:00'),
(4, 'day', 120.00, 'sent', '2024-02-16', 'Daily consultation for Customer 4', '2024-02-01 14:20:00'),
(5, 'week', 900.00, 'to_be_invoiced', '2024-02-28', 'Weekly maintenance for Customer 5', '2024-02-05 09:30:00'),
(3, 'week', 550.00, 'paid', '2024-01-28', 'Weekly support for Customer 3', '2024-01-08 16:45:00'),
(1, 'day', 180.00, 'sent', '2024-02-19', 'Daily support for Customer 1', '2024-02-02 11:00:00'),
(2, 'month', 2200.00, 'paid', '2024-01-20', 'Monthly service for Customer 2', '2024-01-01 08:30:00');
