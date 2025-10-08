<?php
require_once __DIR__ . '/../includes/config.php';

// Set your country code (ISO 3166-1 alpha-2)
$countryCode = 'SR'; // Suriname
$year = date('Y');

// Fetch from API
$url = "https://date.nager.at/api/v3/PublicHolidays/$year/$countryCode";
$json = file_get_contents($url);
if ($json === false) {
    die("❌ Failed to fetch holiday data.");
}

$holidays = json_decode($json, true);
if (!is_array($holidays)) {
    die("❌ Invalid JSON received from API.");
}

$inserted = 0;
$updated = 0;

foreach ($holidays as $h) {
    $date = $h['date'];
    $name = $h['localName'] ?? $h['name'];
    $desc = $h['name'] ?? '';

    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM holidays WHERE holiday_date = ?");
    $stmt->execute([$date]);
    if ($stmt->fetch()) {
        // Update existing
        $upd = $pdo->prepare("UPDATE holidays SET holiday_name = ?, description = ? WHERE holiday_date = ?");
        $upd->execute([$name, $desc, $date]);
        $updated++;
    } else {
        // Insert new
        $ins = $pdo->prepare("INSERT INTO holidays (holiday_date, holiday_name, description) VALUES (?, ?, ?)");
        $ins->execute([$date, $name, $desc]);
        $inserted++;
    }
}

echo "✅ Done! Inserted: $inserted | Updated: $updated holidays for $year.";
