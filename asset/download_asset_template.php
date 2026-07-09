<?php
require __DIR__ . '/db.php';

function xml_escape($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function table_columns($table) {
  $stmt = db()->prepare("
    SELECT COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = ?
    ORDER BY ORDINAL_POSITION
  ");
  $stmt->execute([$table]);
  return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function col_letter($index) {
  $index = (int)$index;
  $out = '';
  while ($index > 0) {
    $index--;
    $out = chr(65 + ($index % 26)) . $out;
    $index = (int) floor($index / 26);
  }
  return $out;
}

function sheet_xml($name, array $rows) {
  $xml = '<Worksheet ss:Name="' . xml_escape($name) . '"><Table>';
  foreach ($rows as $row) {
    $xml .= '<Row>';
    foreach ($row as $value) {
      $xml .= '<Cell><Data ss:Type="String">' . xml_escape($value) . '</Data></Cell>';
    }
    $xml .= '</Row>';
  }
  $xml .= '</Table></Worksheet>';
  return $xml;
}

$sheetDefinitions = [
  [
    'name' => 'System Units',
    'columns' => [
      'Reporting Branch', 'Office', 'Date Deployed', 'Remarks', 'Created At', 'Updated At', 'Encoded By',
      'Asset Code', 'Asset Category', 'Assigned Main Asset No.', 'Current User', 'Asset Status', 'Brand', 'Model', 'Serial Number',
      'Processor', 'RAM', 'Storage', 'Operating System', 'PC Name', 'MAC Address', 'IP Address',
      'Purchase Date', 'Purchase Price', 'Expected Life (Years)', 'Residual Value', 'Current Value', 'Device EOL', 'Fully Depreciated Date',
    ],
  ],
  [
    'name' => 'Laptops',
    'columns' => [
      'Reporting Branch', 'Office', 'Date Deployed', 'Remarks', 'Created At', 'Updated At', 'Encoded By',
      'Asset Code', 'Asset Category', 'Assigned Main Asset No.', 'Current User', 'Asset Status', 'Brand', 'Model', 'Serial Number',
      'Processor', 'RAM', 'Storage', 'Operating System', 'Laptop Name', 'MAC Address',
      'Purchase Date', 'Purchase Price', 'Expected Life (Years)', 'Residual Value', 'Current Value', 'Device EOL', 'Fully Depreciated Date',
    ],
  ],
  [
    'name' => 'Monitors',
    'columns' => [
      'Reporting Branch', 'Office', 'Date Deployed', 'Remarks', 'Created At', 'Updated At', 'Encoded By',
      'Asset Code', 'Asset Category', 'Assigned Main Asset No.', 'Current User', 'Asset Status', 'Brand', 'Model', 'Serial Number',
      'Purchase Date', 'Purchase Price', 'Expected Life (Years)', 'Residual Value', 'Current Value', 'Device EOL', 'Fully Depreciated Date',
    ],
  ],
  [
    'name' => 'Mouse',
    'columns' => [
      'Reporting Branch', 'Office', 'Date Deployed', 'Remarks', 'Created At', 'Updated At', 'Encoded By',
      'Asset Code', 'Asset Category', 'Assigned Main Asset No.', 'Current User', 'Asset Status', 'Brand', 'Model', 'Serial Number',
      'Purchase Date', 'Purchase Price', 'Expected Life (Years)', 'Residual Value', 'Current Value', 'Device EOL', 'Fully Depreciated Date',
    ],
  ],
  [
    'name' => 'Keyboards',
    'columns' => [
      'Reporting Branch', 'Office', 'Date Deployed', 'Remarks', 'Created At', 'Updated At', 'Encoded By',
      'Asset Code', 'Asset Category', 'Assigned Main Asset No.', 'Current User', 'Asset Status', 'Brand', 'Model', 'Serial Number',
      'Purchase Date', 'Purchase Price', 'Expected Life (Years)', 'Residual Value', 'Current Value', 'Device EOL', 'Fully Depreciated Date',
    ],
  ],
  [
    'name' => 'UPS',
    'columns' => [
      'Reporting Branch', 'Office', 'Date Deployed', 'Remarks', 'Created At', 'Updated At', 'Encoded By',
      'Asset Code', 'Asset Category', 'Assigned Main Asset No.', 'Current User', 'Asset Status', 'Brand', 'Model', 'Serial Number',
      'Capacity (VA)', 'Purchase Date', 'Purchase Price', 'Expected Life (Years)', 'Residual Value', 'Current Value', 'Device EOL', 'Fully Depreciated Date',
    ],
  ],
  [
    'name' => 'Printers',
    'columns' => [
      'Reporting Branch', 'Office', 'Date Deployed', 'Remarks', 'Created At', 'Updated At', 'Encoded By',
      'Asset Code', 'Asset Category', 'Assigned Main Asset No.', 'Current User', 'Asset Status', 'Brand', 'Model', 'Serial Number',
      'Printer Type', 'Purchase Date', 'Purchase Price', 'Expected Life (Years)', 'Residual Value', 'Current Value', 'Device EOL', 'Fully Depreciated Date',
    ],
  ],
];

$worksheets = [];
foreach ($sheetDefinitions as $sheet) {
  $worksheets[] = sheet_xml($sheet['name'], [$sheet['columns']]);
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>';
$xml .= '<?mso-application progid="Excel.Sheet"?>';
$xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
$xml .= 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
$xml .= 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
$xml .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
$xml .= '<Styles><Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/><Font ss:FontName="Calibri" ss:Size="11"/></Style></Styles>';
$xml .= implode('', $worksheets);
$xml .= '</Workbook>';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="asset-import-template.xls"');
echo $xml;
