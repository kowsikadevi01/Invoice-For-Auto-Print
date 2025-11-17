<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hotelpavieshpark_gifts');
define('DB_PASS', 'Developer@2025');
define('DB_NAME', 'hotelpavieshpark_modern');

// Company details
define('COMPANY_NAME', 'V-Fran');
define('COMPANY_ADDRESS', 'Karuveppampatti,Tiruchengode,<br>Namakkal-637201');
define('COMPANY_GST', '29AAAAA0000A1Z5');
define('COMPANY_STATE', 'Tamil Nadu');
define('COMPANY_STATE_CODE', '33');
define('COMPANY_PHONE', '+91 9876543210');
define('COMPANY_PAN', 'AAAAA0000A');

// Bank Details
define('BANK_NAME', 'State Bank of India');
define('BANK_ACCOUNT', '1234567890123456');
define('BANK_BRANCH', 'Tiruchengode Branch');
define('BANK_IFSC', 'SBIN0001234');

// Terms & Conditions
define('TERMS_CONDITIONS', 'Payment due within 30 days. Interest @24% p.a. will be charged on overdue amounts. Goods once sold will not be taken back or exchanged. Subject to Tiruchengode Jurisdiction only.');

// Printer Configuration
define('DEFAULT_PRINTER_TYPE', 'thermal');
define('PRINTER_SETTINGS', [
    'thermal' => [
        'width' => '80mm',
        'font_size' => '8px',
        'margin' => '2mm 1mm',
        'table_font_size' => '7px',
        'line_height' => '1.1',
        'max_chars' => 42,
        'page_size' => 'A4',
        'padding' => '1mm'
    ],
    'inkjet' => [
        'width' => '210mm',
        'font_size' => '12px',
        'margin' => '10mm',
        'table_font_size' => '10px',
        'line_height' => '1.4',
        'max_chars' => 100,
        'page_size' => 'A4',
        'padding' => '3mm'
    ],
    'laser' => [
        'width' => '210mm',
        'font_size' => '11px',
        'margin' => '10mm',
        'table_font_size' => '9px',
        'line_height' => '1.3',
        'max_chars' => 100,
        'page_size' => 'A4',
        'padding' => '2mm'
    ]
]);

// Database connection
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    $db->set_charset("utf8");
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Initialize database tables
function initDatabase($db) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(50) UNIQUE,
            order_number VARCHAR(50),
            customer_first_name VARCHAR(100),
            customer_last_name VARCHAR(100),
            customer_email VARCHAR(100),
            phone_number VARCHAR(20),
            shipping_address TEXT,
            billing_address TEXT,
            product_id VARCHAR(50),
            variant_id VARCHAR(50),
            product_name VARCHAR(255),
            product_quantity INT DEFAULT 1,
            product_price DECIMAL(10,2) DEFAULT 0,
            total_price DECIMAL(10,2),
            order_date DATETIME,
            order_notes TEXT,
            printed BOOLEAN DEFAULT FALSE,
            printer_type VARCHAR(20) DEFAULT 'thermal',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    foreach ($queries as $query) {
        if (!$db->query($query)) {
            error_log("Database error: " . $db->error);
        }
    }
}

// Security functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Number to words function (Indian format)
function numberToWords($number) {
    $hyphen      = '-';
    $hundred     = ' Hundred ';
    $digits     = array('Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine');
    $levels     = array('', 'Thousand', 'Lakh', 'Crore');

    if ($number == 0) {
        return 'Zero';
    }

    $result = '';
    $numStr = (string) $number;
    $numLength = strlen($numStr);

    if ($numLength <= 3) {
        $hundreds = (int) ($number / 100);
        $tens_units = $number % 100;

        if ($hundreds) {
            $result .= $digits[$hundreds] . ' Hundred ';
        }
        if ($tens_units) {
            if ($tens_units < 10) {
                $result .= $digits[$tens_units] . ' ';
            } elseif ($tens_units < 20) {
                $teens = array('Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen');
                $result .= $teens[$tens_units - 10] . ' ';
            } else {
                $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
                $unit = $tens_units % 10;
                $result .= $tens[(int)($tens_units / 10)] . ' ';
                if ($unit) {
                    $result .= $digits[$unit] . ' ';
                }
            }
        }
    } else {
        $crores = (int) ($number / 10000000);
        $lakhs = (int) (($number % 10000000) / 100000);
        $thousands = (int) (($number % 100000) / 1000);
        $hundreds = (int) (($number % 1000) / 100);
        $tens_units = $number % 100;

        if ($crores) {
            $result .= numberToWords($crores) . ' Crore ';
        }
        if ($lakhs) {
            $result .= numberToWords($lakhs) . ' Lakh ';
        }
        if ($thousands) {
            $result .= numberToWords($thousands) . ' Thousand ';
        }
        if ($hundreds) {
            $result .= $digits[$hundreds] . ' Hundred ';
        }
        if ($tens_units) {
            if ($tens_units < 10) {
                $result .= $digits[$tens_units] . ' ';
            } elseif ($tens_units < 20) {
                $teens = array('Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen');
                $result .= $teens[$tens_units - 10] . ' ';
            } else {
                $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
                $unit = $tens_units % 10;
                $result .= $tens[(int)($tens_units / 10)] . ' ';
                if ($unit) {
                    $result .= $digits[$unit] . ' ';
                }
            }
        }
    }
    return trim($result);
}

// Initialize database
initDatabase($db);

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fetch_shopify_data'])) {
        handleShopifyImport($db);
    }
} else {
    if (isset($_GET['action'])) {
        handleAction($db);
    } else {
        showMainInterface($db);
    }
}

function handleAction($db) {
    $action = sanitizeInput($_GET['action']);
    $id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
    $printerType = isset($_GET['printer']) ? sanitizeInput($_GET['printer']) : DEFAULT_PRINTER_TYPE;
    
    switch ($action) {
        case 'view_shopify_data':
            showShopifyData($db);
            break;
            
        case 'test_shopify_data':
            header('Content-Type: application/json');
            echo json_encode(getShopifyData($db), JSON_PRETTY_PRINT);
            exit;
            
        case 'print':
            if ($id) {
                printInvoice($db, $id, $printerType);
            }
            break;

        case 'download_pdf':
            if ($id) {
                downloadOrderPDF($db, $id, $printerType);
            }
            break;
            
        case 'mark_printed':
            if ($id) {
                $stmt = $db->prepare("UPDATE orders SET printed = 1 WHERE order_id = ?");
                $stmt->bind_param('s', $id);
                $stmt->execute();
                $stmt->close();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
            break;
            
        case 'delete':
            if ($id) {
                $stmt = $db->prepare("DELETE FROM orders WHERE order_id = ?");
                $stmt->bind_param('s', $id);
                $stmt->execute();
                $stmt->close();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
            break;
            
        default:
            showMainInterface($db);
            break;
    }
}

function handleShopifyImport($db) {
    $shopifyData = getShopifyData($db);
    $importCount = 0;
    
    foreach ($shopifyData as $row) {
        // Check if order already exists
        $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ?");
        $orderNumber = $row['order_number'] ?? 'SHOP-' . substr(uniqid(), -6);
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $orderId = uniqid();
            $firstName = isset($row['customer_first_name']) ? substr($row['customer_first_name'], 0, 100) : '';
            $lastName = isset($row['customer_last_name']) ? substr($row['customer_last_name'], 0, 100) : '';
            $email = isset($row['customer_email']) ? substr($row['customer_email'], 0, 100) : '';
            $phone = isset($row['phone_number']) ? substr($row['phone_number'], 0, 20) : '';
            
            $shippingAddr = isset($row['shipping_address']) ? $row['shipping_address'] : '';
            $billingAddr = isset($row['billing_address']) ? $row['billing_address'] : $shippingAddr;
            $totalPrice = isset($row['total_price']) ? floatval($row['total_price']) : 0;
            $orderDate = isset($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
            
            $productName = isset($row['product_name']) ? $row['product_name'] : 'Imported Product';
            $productPrice = isset($row['product_price']) ? floatval($row['product_price']) : $totalPrice;
            $productQty = isset($row['product_quantity']) ? intval($row['product_quantity']) : 1;
            $productId = isset($row['product_id']) ? $row['product_id'] : '';
            $variantId = isset($row['variant_id']) ? $row['variant_id'] : '';
            
            $insertStmt = $db->prepare("INSERT INTO orders (
                order_id, order_number, customer_first_name, customer_last_name, 
                customer_email, phone_number, shipping_address, billing_address,
                product_id, variant_id, product_name, product_quantity, product_price,
                total_price, order_date, order_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($insertStmt) {
                $orderNotes = 'Imported from Shopify - ' . date('Y-m-d H:i:s');
                $insertStmt->bind_param('ssssssssssiddsss', 
                    $orderId, $orderNumber, $firstName, $lastName,
                    $email, $phone, $shippingAddr, $billingAddr,
                    $productId, $variantId, $productName, $productQty, $productPrice,
                    $totalPrice, $orderDate, $orderNotes
                );
                
                if ($insertStmt->execute()) {
                    $importCount++;
                }
                $insertStmt->close();
            }
        }
        $stmt->close();
    }
    
    $_SESSION['success'] = "Imported $importCount new orders from Shopify";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

function getShopifyData($db) {
    $data = [];
    $query = "SELECT * FROM custom_orders ORDER BY created_at DESC LIMIT 100";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

function showMainInterface($db) {
    $orders = [];
    $query = "SELECT * FROM orders ORDER BY printed ASC, order_date DESC LIMIT 100";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo COMPANY_NAME; ?> - Invoice Management</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .table-container { overflow-x: auto; }
            .action-btn { margin-right: 5px; }
            .actions-cell {
                text-align: center;
                white-space: nowrap;
                min-width: 200px;
            }
            .action-buttons {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 5px;
            }
            .printer-dropdown {
                min-width: 140px;
            }
            .dropdown-menu {
                z-index: 1050;
            }
            .order-row.unprinted {
                background-color: #fff3cd;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <header class="py-4 mb-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><i class="fas fa-receipt"></i> <?php echo COMPANY_NAME; ?> Invoice Management</h1>
                    <div class="d-flex">
                        <a href="?action=view_shopify_data" class="btn btn-info me-2">
                            <i class="fas fa-database"></i> View Shopify Data
                        </a>
                        <a href="?action=test_shopify_data" class="btn btn-secondary me-2" target="_blank">
                            <i class="fas fa-code"></i> Test Data
                        </a>
                        <form method="post" class="d-inline">
                            <button type="submit" name="fetch_shopify_data" class="btn btn-warning">
                                <i class="fas fa-download"></i> Import from Shopify
                            </button>
                        </form>
                    </div>
                </div>
            </header>
            
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            
            
            <div class="table-container">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Order Number</th>
                            <th><i class="fas fa-user"></i> Customer</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-rupee-sign"></i> Total</th>
                            <th><i class="fas fa-calendar"></i> Date</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                            <th class="text-center"><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="lead">No orders found</p>
                            <p>Import orders from Shopify to get started.</p>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr class="order-row <?php echo !$order['printed'] ? 'unprinted' : ''; ?>">
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars(trim($order['customer_first_name'] . ' ' . $order['customer_last_name'])); ?></td>
                            <td><small><?php echo htmlspecialchars($order['customer_email']); ?></small></td>
                            <td><strong>₹<?php echo number_format($order['total_price'], 2); ?></strong></td>
                            <td><small><?php echo date('M j, Y<\b\r>g:i A', strtotime($order['order_date'])); ?></small></td>
                            <td>
                                <?php if ($order['printed']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Printed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <div class="action-buttons">
                                    <!-- Print Invoice Dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false" title="Print Invoice">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                        <ul class="dropdown-menu printer-dropdown">
                                            <li><h6 class="dropdown-header"><i class="fas fa-printer"></i> Select Printer</h6></li>
                                            <li><a class="dropdown-item" href="?action=print&id=<?php echo urlencode($order['order_id']); ?>&printer=thermal" target="_blank">
                                                <i class="fas fa-receipt"></i> Thermal Printer
                                            </a></li>
                                            <li><a class="dropdown-item" href="?action=print&id=<?php echo urlencode($order['order_id']); ?>&printer=inkjet" target="_blank">
                                                <i class="fas fa-print"></i> Inkjet Printer
                                            </a></li>
                                            <li><a class="dropdown-item" href="?action=print&id=<?php echo urlencode($order['order_id']); ?>&printer=laser" target="_blank">
                                                <i class="fas fa-print"></i> Laser Printer
                                            </a></li>
                                        </ul>
                                    </div>
                                    
                                    <!-- Download PDF Dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false" title="Download PDF">
                                            <i class="fas fa-download"></i> PDF
                                        </button>
                                        <ul class="dropdown-menu printer-dropdown">
                                            <li><h6 class="dropdown-header"><i class="fas fa-file-pdf"></i> PDF Format</h6></li>
                                            <li><a class="dropdown-item" href="?action=download_pdf&id=<?php echo urlencode($order['order_id']); ?>&printer=thermal">
                                                <i class="fas fa-file-pdf"></i> Thermal Format
                                            </a></li>
                                            <li><a class="dropdown-item" href="?action=download_pdf&id=<?php echo urlencode($order['order_id']); ?>&printer=inkjet">
                                                <i class="fas fa-file-pdf"></i> Inkjet Format
                                            </a></li>
                                            <li><a class="dropdown-item" href="?action=download_pdf&id=<?php echo urlencode($order['order_id']); ?>&printer=laser">
                                                <i class="fas fa-file-pdf"></i> Laser Format
                                            </a></li>
                                        </ul>
                                    </div>
                                    
                                    <?php if (!$order['printed']): ?>
                                    <a href="?action=mark_printed&id=<?php echo urlencode($order['order_id']); ?>" 
                                       class="btn btn-sm btn-outline-info" title="Mark as Printed">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo urlencode($order['order_id']); ?>" 
                                       class="btn btn-sm btn-outline-danger" title="Delete Order"
                                       onclick="return confirm('Are you sure you want to delete this order?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

function showShopifyData($db) {
    $data = getShopifyData($db);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Shopify Data - <?php echo COMPANY_NAME; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between mb-4">
                <h1><i class="fas fa-database"></i> Shopify Webhook Data</h1>
                <a href="?" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
            </div>
            
            <?php if (empty($data)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No Shopify data found in custom_orders table
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Found <?php echo count($data); ?> records in Shopify data
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Order Number</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Total Price</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['order_number'] ?? ''); ?></strong></td>
                            <td><?php echo htmlspecialchars(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')); ?></td>
                            <td><small><?php echo htmlspecialchars($row['customer_email'] ?? ''); ?></small></td>
                            <td><?php echo htmlspecialchars($row['phone_number'] ?? ''); ?></td>
                            <td><strong>₹<?php echo number_format($row['total_price'] ?? 0, 2); ?></strong></td>
                            <td><small><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}

// Enhanced Print Invoice Function
function printInvoice($db, $order_id, $printerType = 'thermal') {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows == 0) {
        die("Order not found");
    }
    $order = $result->fetch_assoc();
    $stmt->close();

    // Mark as printed and update printer type
    $updateStmt = $db->prepare("UPDATE orders SET printed = 1, printer_type = ? WHERE order_id = ?");
    $updateStmt->bind_param('ss', $printerType, $order_id);
    $updateStmt->execute();
    $updateStmt->close();

    // Parse products from individual fields
    $items = [];
    if (!empty($order['product_name'])) {
        $items[] = [
            'product_id' => $order['product_id'] ?? '',
            'variant_id' => $order['variant_id'] ?? '',
            'product_name' => $order['product_name'],
            'quantity' => $order['product_quantity'] ?? 1,
            'price' => $order['product_price'] ?? 0,
            'hsn_code' => '',
            'gst_rate' => 18 // Default GST rate
        ];
    }

    if (empty($items)) {
        $items[] = [
            'product_id' => '',
            'variant_id' => '',
            'product_name' => 'Order Item',
            'quantity' => 1,
            'price' => $order['total_price'],
            'hsn_code' => '',
            'gst_rate' => 18
        ];
    }

    // Determine GST rules
    $gst_enabled = true; // Default enabled for India
    $is_india = true; // Assuming India orders
    $same_state = true; // Assuming same state
    $apply_gst = $gst_enabled && $is_india;
    
    $currency_symbol = '₹'; // Default INR
    $order_in_words = numberToWords(floor($order['total_price'] ?? 0));

    // Render invoice based on printer type
    renderInvoice($order, $items, PRINTER_SETTINGS[$printerType], $apply_gst, $same_state, $order_in_words, $currency_symbol, $printerType);
}

// Enhanced invoice rendering function
function renderInvoice($order, $items, $settings, $apply_gst, $same_state, $order_in_words, $currency_symbol, $printerType) {
    $customer_name = trim(($order['customer_first_name'] ?? '') . ' ' . ($order['customer_last_name'] ?? ''));
    if (empty($customer_name)) {
        $customer_name = 'Customer';
    }

    // Parse addresses - Enhanced to handle both Bill To and Ship To
    $shipping_address = parseAddress($order['shipping_address'] ?? '');
    $billing_address = parseAddress($order['billing_address'] ?? '');
    
    // If billing address is empty, use shipping address
    if (empty($billing_address) && !empty($shipping_address)) {
        $billing_address = $shipping_address;
    }

    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>Invoice <?= htmlspecialchars($order['order_number'] ?? $order['order_id'] ?? 'N/A') ?></title>
<style>
<?php echo generateInvoiceCSS($printerType, $settings); ?>
</style>
</head>
<body>
<div class="invoice-container">
    <h1 class="document-title">INVOICE</h1>
    
    <!-- Company Details -->
    <div class="company-header">
        <div class="company-name"><?= COMPANY_NAME ?></div>
        <div class="company-details">
            <?= COMPANY_ADDRESS ?><br>
            GSTIN: <?= COMPANY_GST ?> | PAN: <?= COMPANY_PAN ?><br>
            Phone: <?= COMPANY_PHONE ?>
        </div>
    </div>
    
    <!-- Invoice Info -->
    <div class="invoice-info">
        <div class="info-row">
            <span class="label">Invoice No:</span>
            <span class="value"><?= htmlspecialchars($order['order_number'] ?? $order['order_id'] ?? 'N/A') ?></span>
        </div>
        <div class="info-row">
            <span class="label">Date:</span>
            <span class="value"><?= date('d-M-Y', strtotime($order['order_date'] ?? 'now')) ?></span>
        </div>
        <?php if ($printerType !== 'thermal'): ?>
        <div class="info-row">
            <span class="label">Due Date:</span>
            <span class="value"><?= date('d-M-Y', strtotime('+30 days', strtotime($order['order_date'] ?? 'now'))) ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Customer Details with separate Bill To and Ship To -->
    <div class="customer-section">
        <div class="bill-to">
            <div class="section-title">BILL TO:</div>
            <div class="customer-details">
                <div class="customer-name"><?= htmlspecialchars($customer_name) ?></div>
                <?php if (!empty($billing_address)): ?>
                    <div class="customer-address"><?= nl2br(htmlspecialchars($billing_address)) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['customer_email'])): ?>
                    <div class="customer-contact">Email: <?= htmlspecialchars($order['customer_email']) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['phone_number'])): ?>
                    <div class="customer-contact">Phone: <?= htmlspecialchars($order['phone_number']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Always show Ship To if different from Bill To or for non-thermal printers -->
        <?php if ($printerType !== 'thermal' || (!empty($shipping_address) && $shipping_address !== $billing_address)): ?>
        <div class="ship-to">
            <div class="section-title">SHIP TO:</div>
            <div class="customer-details">
                <div class="customer-name"><?= htmlspecialchars($customer_name) ?></div>
                <?php if (!empty($shipping_address)): ?>
                    <div class="customer-address"><?= nl2br(htmlspecialchars($shipping_address)) ?></div>
                <?php else: ?>
                    <div class="customer-address">Same as billing address</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Items Table -->
    <?php if ($printerType === 'thermal'): ?>
        <div class="items-section">
            <div class="section-title">Items:</div>
            <?php
            $sr_no = 1;
            $total_taxable = 0;
            $total_gst = 0;
            
            foreach ($items as $item):
                $qty = $item['quantity'] ?? 1;
                $price = $item['price'] ?? 0;
                $taxable_value = $qty * $price;
                $gst_rate = $apply_gst ? ($item['gst_rate'] ?? 18) : 0;
                $gst_amount = $apply_gst ? round($taxable_value * $gst_rate / 100, 2) : 0;
                
                $total_taxable += $taxable_value;
                $total_gst += $gst_amount;
            ?>
            <div class="thermal-item">
                <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="item-details">
                    Qty: <?= $qty ?> × <?= $currency_symbol ?><?= number_format($price, 2) ?>
                    <?php if ($apply_gst && $gst_rate > 0): ?>
                        <br>GST (<?= $gst_rate ?>%): <?= $currency_symbol ?><?= number_format($gst_amount, 2) ?>
                    <?php endif; ?>
                </div>
                <div class="item-total"><?= $currency_symbol ?><?= number_format($taxable_value + $gst_amount, 2) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">S.No.</th>
                    <th style="width: 35%">Description</th>
                    <th style="width: 10%">HSN/SAC</th>
                    <th style="width: 8%">Qty</th>
                    <th style="width: 12%">Rate</th>
                    <th style="width: 12%">Amount</th>
                    <?php if ($apply_gst): ?>
                        <th style="width: 8%">GST%</th>
                        <th style="width: 12%">GST Amt</th>
                    <?php endif; ?>
                    <th style="width: 12%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sr_no = 1;
                $total_taxable = 0;
                $total_gst = 0;
                
                foreach ($items as $item):
                    $qty = $item['quantity'] ?? 1;
                    $price = $item['price'] ?? 0;
                    $taxable_value = $qty * $price;
                    $gst_rate = $apply_gst ? ($item['gst_rate'] ?? 18) : 0;
                    $gst_amount = $apply_gst ? round($taxable_value * $gst_rate / 100, 2) : 0;
                    $line_total = $taxable_value + $gst_amount;
                    
                    $total_taxable += $taxable_value;
                    $total_gst += $gst_amount;
                ?>
                <tr>
                    <td class="text-center"><?= $sr_no++ ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['hsn_code'] ?: 'N/A') ?></td>
                    <td class="text-center"><?= number_format($qty, 0) ?></td>
                    <td class="text-right"><?= $currency_symbol ?><?= number_format($price, 2) ?></td>
                    <td class="text-right"><?= $currency_symbol ?><?= number_format($taxable_value, 2) ?></td>
                    <?php if ($apply_gst): ?>
                        <td class="text-center"><?= number_format($gst_rate, 0) ?>%</td>
                        <td class="text-right"><?= $currency_symbol ?><?= number_format($gst_amount, 2) ?></td>
                    <?php endif; ?>
                    <td class="text-right"><strong><?= $currency_symbol ?><?= number_format($line_total, 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Totals Section -->
    <?php
    $final_total = $order['total_price'] ?? ($total_taxable + $total_gst);
    ?>
    
    <div class="totals-section">
        <?php if ($printerType !== 'thermal'): ?>
        <div class="terms-section">
            <div class="section-title">Terms & Conditions:</div>
            <div class="terms-content"><?= TERMS_CONDITIONS ?></div>
        </div>
        <?php endif; ?>
        
        <div class="totals-table-section">
            <table class="totals-table">
                <tr>
                    <td class="total-label">Subtotal</td>
                    <td class="text-right"><?= $currency_symbol ?><?= number_format($total_taxable, 2) ?></td>
                </tr>
                <?php if ($apply_gst && $total_gst > 0): ?>
                <tr>
                    <td class="total-label">GST</td>
                    <td class="text-right"><?= $currency_symbol ?><?= number_format($total_gst, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="total-label"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong><?= $currency_symbol ?><?= number_format($final_total, 2) ?></strong></td>
                </tr>
            </table>

            <?php if ($printerType !== 'thermal'): ?>
            <div class="amount-words">
                <strong>Amount in Words:</strong><br>
                <?= $order_in_words ?> Rupees Only
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($printerType !== 'thermal'): ?>
    <!-- Bank Details and Signature -->
    <div class="footer-section">
        <div class="bank-section">
            <div class="section-title">Bank Details:</div>
            <div class="bank-content">
                <strong>Account Name:</strong> <?= COMPANY_NAME ?><br>
                <strong>Bank:</strong> <?= BANK_NAME ?><br>
                <strong>Account No:</strong> <?= BANK_ACCOUNT ?><br>
                <strong>Branch:</strong> <?= BANK_BRANCH ?><br>
                <strong>IFSC Code:</strong> <?= BANK_IFSC ?>
            </div>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div>For <?= COMPANY_NAME ?></div>
                <div class="signature-line">Authorized Signatory</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="thank-you">Thank You for Your Business!</div>
</div>

<div class="no-print controls">
    <button onclick="window.print()" class="print-btn">
        <i class="fas fa-print"></i> Print Invoice
    </button>
    <button onclick="window.close()" class="close-btn">
        <i class="fas fa-times"></i> Close
    </button>
</div>

<script>
window.onload = function() {
    setTimeout(function() {
        window.print();
    }, 500);
};
</script>
</body>
</html>
<?php
    exit;
}

// Generate CSS based on printer type
function generateInvoiceCSS($printerType, $settings) {
    $baseCSS = "
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: {$settings['font_size']}; 
            line-height: {$settings['line_height']};
            margin: {$settings['margin']};
            width: {$settings['width']};
            max-width: {$settings['width']};
            color: #000;
            background: #fff;
        }
        
        @page { 
            size: {$settings['page_size']}; 
            margin: {$settings['margin']};
        }
        
        .invoice-container { 
            width: 100%; 
            padding: {$settings['padding']};
            border: 2px solid #000;
        }
        
        .document-title {
            text-align: center;
            font-size: 1.5em;
            font-weight: bold;
            background: #000;
            color: #fff;
            padding: 8px;
            margin-bottom: 15px;
        }
        
        .company-header { 
            text-align: center; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
        }
        
        .company-name { 
            font-weight: bold; 
            font-size: 1.3em;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 0.9em;
            line-height: 1.3;
        }
        
        .invoice-info {
            margin: 15px 0;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }
        
        .info-row { 
            display: flex;
            justify-content: space-between;
            margin: 3px 0; 
        }
        
        .label { 
            font-weight: bold; 
        }
        
        .section-title { 
            font-weight: bold; 
            margin: 15px 0 8px 0; 
            border-bottom: 1px solid #333; 
            padding-bottom: 3px;
        }
        
        .customer-section { 
            margin: 15px 0; 
            border-bottom: 1px solid #000;
            padding-bottom: 15px;
        }
        
        .customer-name { 
            font-weight: bold; 
            margin: 5px 0; 
        }
        
        .customer-address, .customer-contact {
            font-size: 0.9em;
            margin: 3px 0;
        }
        
        .totals-section { 
            margin: 15px 0; 
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .totals-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 0.95em;
        }
        
        .totals-table .total-label {
            background: #f0f0f0;
            font-weight: bold;
            width: 60%;
        }
        
        .totals-table .total-row {
            background: #e0e0e0;
            font-weight: bold;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .thank-you { 
            text-align: center; 
            font-weight: bold; 
            font-size: 1.1em;
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #000;
        }
        
        .no-print { 
            display: block; 
            text-align: center; 
            margin: 20px 0; 
        }
        
        .controls button { 
            padding: 10px 20px; 
            margin: 0 10px; 
            border: 1px solid #ccc; 
            background: #f8f9fa; 
            cursor: pointer; 
            border-radius: 4px; 
        }
        
        .controls button:hover {
            background: #e9ecef;
        }
        
        @media print { 
            .no-print { display: none !important; }
            body { margin: 0; }
            .invoice-container { border: none; }
        }
    ";

    // Printer-specific styles
    if ($printerType === 'thermal') {
        $baseCSS .= "
            body { 
                font-family: 'Courier New', monospace; 
                font-size: 9px;
            }
            
            .document-title { font-size: 1.2em; }
            .company-name { font-size: 1.1em; }
            
            .customer-section {
                display: block;
            }
            
            .bill-to, .ship-to {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .items-section {
                margin: 15px 0;
            }
            
            .thermal-item {
                margin: 8px 0;
                padding: 5px 0;
                border-bottom: 1px dashed #666;
            }
            
            .item-name {
                font-weight: bold;
                margin-bottom: 3px;
            }
            
            .item-details {
                font-size: 0.9em;
                margin: 2px 0;
            }
            
            .item-total {
                text-align: right;
                font-weight: bold;
                margin-top: 3px;
            }
            
            .totals-table-section {
                width: 100%;
            }
            
            @page { 
                size: 80mm auto; 
                margin: 2mm; 
            }
        ";
    } else {
        $baseCSS .= "
            .customer-section { 
                display: flex; 
                justify-content: space-between;
            }
            
            .bill-to, .ship-to {
                width: 48%;
            }
            
            .items-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 10px 0; 
            }
            
            .items-table th, .items-table td { 
                border: 1px solid #000; 
                padding: 6px 4px; 
                text-align: left; 
                font-size: {$settings['table_font_size']}; 
                vertical-align: middle;
            }
            
            .items-table th { 
                background: #f0f0f0; 
                font-weight: bold; 
                text-align: center;
            }
            
            .totals-section {
                display: flex;
                justify-content: space-between;
            }
            
            .terms-section {
                width: 50%;
                padding-right: 20px;
            }
            
            .totals-table-section {
                width: 45%;
            }
            
            .terms-content {
                font-size: 0.85em;
                line-height: 1.3;
            }
            
            .amount-words {
                font-size: 0.9em;
                font-weight: bold;
                margin-top: 10px;
                padding: 8px;
                border: 1px solid #000;
                background: #f9f9f9;
            }
            
            .footer-section {
                display: flex;
                justify-content: space-between;
                margin-top: 20px;
                border-top: 1px solid #000;
                padding-top: 15px;
            }
            
            .bank-section {
                width: 60%;
            }
            
            .signature-section {
                width: 35%;
                text-align: center;
            }
            
            .bank-content {
                font-size: 0.9em;
                line-height: 1.4;
            }
            
            .signature-box {
                margin-top: 30px;
            }
            
            .signature-line {
                border-top: 1px solid #000;
                margin-top: 50px;
                padding-top: 5px;
                font-size: 0.9em;
            }
        ";
    }

    return $baseCSS;
}

// Address parsing function
function parseAddress($addressData) {
    if (empty($addressData)) return '';
    
    $decoded = json_decode($addressData, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $address = '';
        if (isset($decoded['address1'])) $address .= $decoded['address1'] . "\n";
        if (isset($decoded['address2']) && !empty($decoded['address2'])) $address .= $decoded['address2'] . "\n";
        if (isset($decoded['city'])) $address .= $decoded['city'];
        if (isset($decoded['province']) && !empty($decoded['province'])) $address .= ', ' . $decoded['province'];
        if (isset($decoded['zip'])) $address .= ' - ' . $decoded['zip'] . "\n";
        if (isset($decoded['country'])) $address .= $decoded['country'];
        return trim($address);
    }
    
    return $addressData;
}

// Enhanced PDF Download function with direct download
function downloadOrderPDF($db, $order_id, $printerType = 'thermal') {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows == 0) {
        die("Order not found");
    }
    $order = $result->fetch_assoc();
    $stmt->close();

    // Mark as printed
    $updateStmt = $db->prepare("UPDATE orders SET printed = 1, printer_type = ? WHERE order_id = ?");
    $updateStmt->bind_param('ss', $printerType, $order_id);
    $updateStmt->execute();
    $updateStmt->close();

    // Generate HTML content
    ob_start();
    
    // Prepare data same as print function
    $items = [];
    if (!empty($order['product_name'])) {
        $items[] = [
            'product_id' => $order['product_id'] ?? '',
            'variant_id' => $order['variant_id'] ?? '',
            'product_name' => $order['product_name'],
            'quantity' => $order['product_quantity'] ?? 1,
            'price' => $order['product_price'] ?? 0,
            'hsn_code' => '',
            'gst_rate' => 18
        ];
    }

    if (empty($items)) {
        $items[] = [
            'product_id' => '',
            'variant_id' => '',
            'product_name' => 'Order Item',
            'quantity' => 1,
            'price' => $order['total_price'],
            'hsn_code' => '',
            'gst_rate' => 18
        ];
    }

    $gst_enabled = true;
    $is_india = true;
    $same_state = true;
    $apply_gst = $gst_enabled && $is_india;
    $currency_symbol = '₹';
    $order_in_words = numberToWords(floor($order['total_price'] ?? 0));

    // Render invoice
    renderInvoice($order, $items, PRINTER_SETTINGS[$printerType], $apply_gst, $same_state, $order_in_words, $currency_symbol, $printerType);
    
    $html = ob_get_clean();
    
    // Enhanced HTML for better PDF conversion with print styles
    $enhancedHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice ' . htmlspecialchars($order['order_number'] ?? $order['order_id']) . '</title>
        <style>
            @media print {
                .no-print { display: none !important; }
                body { margin: 0; font-size: 12px; }
                .invoice-container { border: none; }
                @page { margin: 10mm; }
            }
            @media screen {
                body { margin: 20px; }
            }
        </style>
    </head>
    <body onload="window.print(); setTimeout(function(){ window.close(); }, 1000);">
    ' . $html . '
    </body>
    </html>';
    
    // Set headers for direct HTML download that opens and prints
    $filename = "invoice-" . ($order['order_number'] ?? $order['order_id']) . "-" . $printerType . ".html";
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    echo $enhancedHtml;
    exit;
}

// Close database connection
if (isset($db)) {
    $db->close();
}
?>