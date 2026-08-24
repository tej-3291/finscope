<?php
// scripts/generate_sample_csv.php
// Creates a robust 140+ transaction CSV file for testing imports

$filename = __DIR__ . '/../data/sample_statement.csv';
$fh = fopen($filename, 'w');

// Header
fputcsv($fh, ['Amount', 'Category', 'Subcategory', 'Description', 'Method', 'Date']);

$txns = [];
$currentDate = new DateTime();
$currentDate->modify('-5 months'); // Generate 5 months of data

// Patterns for average Indian middle-class guy
// Monthly fixed
$monthly = [
    ['amount' => 55000, 'cat' => 'Income', 'sub' => 'Salary', 'desc' => 'TCS Salary Credit', 'method' => 'UPI'],
    ['amount' => -18000, 'cat' => 'Bills', 'sub' => 'Rent', 'desc' => 'Flat Rent', 'method' => 'UPI'],
    ['amount' => -1500, 'cat' => 'Bills', 'sub' => 'Utilities', 'desc' => 'Electricity Bill', 'method' => 'UPI'],
    ['amount' => -800, 'cat' => 'Bills', 'sub' => 'Utilities', 'desc' => 'Jio Fiber', 'method' => 'UPI'],
    ['amount' => -10000, 'cat' => 'Others', 'sub' => 'Savings', 'desc' => 'Zerodha SIP', 'method' => 'UPI'],
];

// Variables
$variables = [
    ['amount' => [-150, -300], 'cat' => 'Food', 'sub' => 'Delivery', 'desc' => ['Zomato Lunch', 'Swiggy Dinner', 'Foodpanda Snack'], 'method' => 'UPI', 'freq' => 6],
    ['amount' => [-30, -60], 'cat' => 'Food', 'sub' => 'Snacks', 'desc' => ['Tapri Chai', 'Samosa', 'Bakery'], 'method' => 'UPI', 'freq' => 15],
    ['amount' => [-1500, -3000], 'cat' => 'Food', 'sub' => 'Groceries', 'desc' => ['Dmart Shopping', 'BigBasket', 'Blinkit'], 'method' => 'Card', 'freq' => 2],
    ['amount' => [-100, -250], 'cat' => 'Transport', 'sub' => 'Commute', 'desc' => ['Uber Ride', 'Ola Auto', 'Rapido'], 'method' => 'UPI', 'freq' => 8],
    ['amount' => [-50, -100], 'cat' => 'Transport', 'sub' => 'Commute', 'desc' => ['Metro Recharge', 'Bus Ticket'], 'method' => 'UPI', 'freq' => 4],
    ['amount' => [-1500, -2500], 'cat' => 'Shopping', 'sub' => 'Online', 'desc' => ['Amazon Order', 'Myntra Fashion', 'Flipkart Sale'], 'method' => 'Card', 'freq' => 2],
    ['amount' => [-400, -800], 'cat' => 'Entertainment', 'sub' => 'Media', 'desc' => ['Netflix', 'Amazon Prime', 'BookMyShow Movie'], 'method' => 'Card', 'freq' => 1],
    ['amount' => [-200, -600], 'cat' => 'Health', 'sub' => 'Medical', 'desc' => ['Apollo Pharmacy', 'Practo Consult'], 'method' => 'UPI', 'freq' => 1],
];

for ($m = 0; $m < 5; $m++) {
    // Add monthly fixed
    foreach ($monthly as $fixed) {
        $txDate = clone $currentDate;
        $txDate->modify('+' . rand(1, 5) . ' days');
        $txns[] = [$fixed['amount'], $fixed['cat'], $fixed['sub'], $fixed['desc'], $fixed['method'], $txDate->format('Y-m-d')];
    }
    
    // Add variables
    foreach ($variables as $var) {
        $count = rand(max(1, $var['freq'] - 2), $var['freq'] + 2);
        for ($i = 0; $i < $count; $i++) {
            $amt = rand($var['amount'][0], $var['amount'][1]);
            $desc = $var['desc'][array_rand($var['desc'])];
            $txDate = clone $currentDate;
            $txDate->modify('+' . rand(1, 28) . ' days');
            $txns[] = [$amt, $var['cat'], $var['sub'], $desc, $var['method'], $txDate->format('Y-m-d')];
        }
    }
    
    $currentDate->modify('+1 month');
}

// Sort by date descending
usort($txns, function($a, $b) { return strtotime($b[5]) - strtotime($a[5]); });

foreach ($txns as $t) {
    fputcsv($fh, $t);
}
fclose($fh);
echo "Generated ~180 transactions to C:\\xampp\\htdocs\\finscope\\data\\sample_statement.csv\n";
?>
