<?php
ini_set('max_execution_time', '120');
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

$assetTables = [
  'System Unit' => ['table'=>'System Units','prefix'=>'SU','code'=>'systemUnitCode'],
  'Laptop' => ['table'=>'Laptops','prefix'=>'LAP','code'=>'laptopCode'],
  'Monitor' => ['table'=>'Monitors','prefix'=>'MON','code'=>'monitorCode'],
  'Mouse' => ['table'=>'Mouse','prefix'=>'MOU','code'=>'mouseCode'],
  'Keyboard' => ['table'=>'Keyboards','prefix'=>'KB','code'=>'keyboardCode'],
  'UPS' => ['table'=>'UPS','prefix'=>'UPS','code'=>'upsCode'],
  'Printer' => ['table'=>'Printers','prefix'=>'PRT','code'=>'printerCode'],
];

function table_exists($table){
  return (bool)scalar("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?", [$table]);
}
function column_exists($table, $column){
  return (bool)scalar("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? AND COLUMN_NAME=?", [$table, $column]);
}
function ensure_column($table, $column, $type){
  if(!column_exists($table, $column)){
    db()->exec('ALTER TABLE '.qname($table).' ADD '.qname($column).' '.$type);
  }
}
function employee_name_key($name){
  return strtoupper(preg_replace('/\s+/', ' ', trim((string)$name)));
}
function is_temp_employee_id($id){
  return preg_match('/^TEMP-\d+$/i', trim((string)$id)) === 1;
}
function next_temp_employee_id($existingIds){
  $max = 0;
  foreach($existingIds as $id){
    if(preg_match('/^TEMP-(\d+)$/i', trim((string)$id), $m)) $max = max($max, (int)$m[1]);
  }
  return 'TEMP-' . str_pad($max + 1, 5, '0', STR_PAD_LEFT);
}
function ensure_employee_schema(){
  static $checked = false;
  if ($checked) return;
  $checked = true;
  global $assetTables;
  if(!table_exists('Employees')){
    db()->exec("
      CREATE TABLE [dbo].[Employees] (
        [Employee ID] NVARCHAR(50) NOT NULL PRIMARY KEY,
        [Employee Name] NVARCHAR(255) NOT NULL,
        [Reporting Branch] NVARCHAR(100) NULL,
        [Office] NVARCHAR(100) NULL,
        [Active] NVARCHAR(20) NOT NULL DEFAULT 'Yes',
        [ID Source] NVARCHAR(30) NOT NULL DEFAULT 'Temporary',
        [Created At] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
        [Updated At] DATETIME2 NOT NULL DEFAULT SYSDATETIME()
      )
    ");
  }

  foreach($assetTables as $def){
    ensure_column($def['table'], 'Employee ID', 'NVARCHAR(50) NULL');
  }
  ensure_column('Deployments', 'Employee ID', 'NVARCHAR(50) NULL');
  ensure_column('Employees', 'Employee Name', 'NVARCHAR(255) NOT NULL DEFAULT \'\'' );
  ensure_column('Employees', 'Reporting Branch', 'NVARCHAR(100) NULL');
  ensure_column('Employees', 'Office', 'NVARCHAR(100) NULL');
  ensure_column('Employees', 'Active', "NVARCHAR(20) NOT NULL DEFAULT 'Yes'");
  ensure_column('Employees', 'ID Source', "NVARCHAR(30) NOT NULL DEFAULT 'Temporary'");
  ensure_column('Employees', 'Created At', 'DATETIME2 NOT NULL DEFAULT SYSDATETIME()');
  ensure_column('Employees', 'Updated At', 'DATETIME2 NOT NULL DEFAULT SYSDATETIME()');
}

function ensure_preventive_maintenance_schema(){
  static $checked = false;
  if ($checked) return;
  $checked = true;
  if(!table_exists('Preventive_Maintenance')){
    db()->exec("
      CREATE TABLE [dbo].[Preventive_Maintenance] (
        [PM ID] NVARCHAR(50) NOT NULL PRIMARY KEY,
        [Cycle] NVARCHAR(20) NOT NULL,
        [Main Asset Number] NVARCHAR(100) NOT NULL,
        [Reporting Branch] NVARCHAR(100) NULL,
        [Office] NVARCHAR(100) NULL,
        [User] NVARCHAR(255) NULL,
        [Checklist] NVARCHAR(MAX) NULL,
        [Remarks] NVARCHAR(MAX) NULL,
        [Technician] NVARCHAR(255) NULL,
        [Done] NVARCHAR(10) NOT NULL DEFAULT 'No',
        [Completed At] DATETIME2 NULL,
        [Created At] DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
        [Updated At] DATETIME2 NOT NULL DEFAULT SYSDATETIME()
      )
    ");
  }
  ensure_column('Preventive_Maintenance', 'PM ID', 'NVARCHAR(50) NOT NULL');
  ensure_column('Preventive_Maintenance', 'Cycle', 'NVARCHAR(20) NOT NULL DEFAULT \'\'' );
  ensure_column('Preventive_Maintenance', 'Main Asset Number', 'NVARCHAR(100) NOT NULL DEFAULT \'\'' );
  ensure_column('Preventive_Maintenance', 'Reporting Branch', 'NVARCHAR(100) NULL');
  ensure_column('Preventive_Maintenance', 'Office', 'NVARCHAR(100) NULL');
  ensure_column('Preventive_Maintenance', 'User', 'NVARCHAR(255) NULL');
  ensure_column('Preventive_Maintenance', 'Checklist', 'NVARCHAR(MAX) NULL');
  ensure_column('Preventive_Maintenance', 'Remarks', 'NVARCHAR(MAX) NULL');
  ensure_column('Preventive_Maintenance', 'Technician', 'NVARCHAR(255) NULL');
  ensure_column('Preventive_Maintenance', 'Done', "NVARCHAR(10) NOT NULL DEFAULT 'No'");
  ensure_column('Preventive_Maintenance', 'Completed At', 'DATETIME2 NULL');
  ensure_column('Preventive_Maintenance', 'Created At', 'DATETIME2 NOT NULL DEFAULT SYSDATETIME()');
  ensure_column('Preventive_Maintenance', 'Updated At', 'DATETIME2 NOT NULL DEFAULT SYSDATETIME()');
}

function current_pm_cycle(){
  return date('Y-m');
}

function next_pm_id(){
  $max = 0;
  foreach(all_rows('Preventive_Maintenance') as $row){
    if(preg_match('/PM-(\d+)/i', val($row, 'PM ID'), $m)) $max = max($max, (int)$m[1]);
  }
  return 'PM-' . str_pad($max + 1, 6, '0', STR_PAD_LEFT);
}
function sync_employee_ids(){
  global $assetTables;
  $employees = [];
  $existingIds = [];
  foreach(all_rows('Employees') as $employee){
    $key = employee_name_key($employee['Employee Name'] ?? '');
    $id = val($employee, 'Employee ID');
    if($id !== '') $existingIds[] = $id;
    if($key !== '') $employees[$key] = ['id'=>$id, 'source'=>val($employee, 'ID Source', is_temp_employee_id($id) ? 'Temporary' : 'Manual')];
  }

  $sources = [];
  foreach($assetTables as $def){
    foreach(all_rows($def['table']) as $row){
      $id = val($row, 'Employee ID');
      $name = val($row, 'Current User');
      if($name === '') continue;
      $key = employee_name_key($name);
      if(!isset($sources[$key])){
        $sources[$key] = ['id'=>$id, 'name'=>$name, 'branch'=>val($row, 'Reporting Branch'), 'office'=>val($row, 'Office')];
      } elseif($sources[$key]['id'] === '' && $id !== '') {
        $sources[$key]['id'] = $id;
      }
    }
  }
  foreach(all_rows('Deployments') as $row){
    $id = val($row, 'Employee ID');
    $name = val($row, 'User');
    if($name === '') continue;
    $key = employee_name_key($name);
    if(!isset($sources[$key])){
      $sources[$key] = ['id'=>$id, 'name'=>$name, 'branch'=>val($row, 'Reporting Branch'), 'office'=>val($row, 'Office')];
    } elseif($sources[$key]['id'] === '' && $id !== '') {
      $sources[$key]['id'] = $id;
    }
  }

  foreach($sources as $key => $source){
    $incomingId = trim((string)$source['id']);
    $incomingIsReal = $incomingId !== '' && !is_temp_employee_id($incomingId);

    if(isset($employees[$key])){
      $currentId = $employees[$key]['id'];
      if($incomingIsReal && is_temp_employee_id($currentId) && strcasecmp($incomingId, $currentId) !== 0){
        foreach($assetTables as $def){
          db()->prepare('UPDATE '.qname($def['table']).' SET [Employee ID]=? WHERE [Employee ID]=?')->execute([$incomingId, $currentId]);
        }
        db()->prepare('UPDATE [Deployments] SET [Employee ID]=? WHERE [Employee ID]=?')->execute([$incomingId, $currentId]);
        db()->prepare('UPDATE [Employees] SET [Employee ID]=?, [ID Source]=?, [Updated At]=SYSDATETIME() WHERE [Employee ID]=?')->execute([$incomingId, 'HR Upload', $currentId]);
        $employees[$key] = ['id'=>$incomingId, 'source'=>'HR Upload'];
        $existingIds[] = $incomingId;
      }
      continue;
    }

    $newId = $incomingId;
    $sourceLabel = $incomingIsReal ? 'HR Upload' : 'Temporary';
    if($newId === ''){
      $newId = next_temp_employee_id($existingIds);
      $existingIds[] = $newId;
    }
    $employees[$key] = ['id'=>$newId, 'source'=>$sourceLabel];
    $stmt = db()->prepare('
      IF NOT EXISTS (SELECT 1 FROM [Employees] WHERE [Employee ID]=?)
      INSERT INTO [Employees] ([Employee ID],[Employee Name],[Reporting Branch],[Office],[Active],[ID Source]) VALUES (?,?,?,?,?,?)
    ');
    $stmt->execute([$newId, $source['name'], $source['branch'], $source['office'], 'Yes', $sourceLabel]);
  }

  foreach($assetTables as $def){
    foreach(all_rows($def['table']) as $row){
      if(val($row, 'Employee ID') !== '') continue;
      $id = $employees[employee_name_key(val($row, 'Current User'))]['id'] ?? '';
      if($id === '') continue;
      db()->prepare('UPDATE '.qname($def['table']).' SET [Employee ID]=? WHERE [Asset Code]=?')->execute([$id, val($row, 'Asset Code')]);
    }
  }
  foreach(all_rows('Deployments') as $row){
    if(val($row, 'Employee ID') !== '') continue;
    $id = $employees[employee_name_key(val($row, 'User'))]['id'] ?? '';
    if($id === '') continue;
    db()->prepare('UPDATE [Deployments] SET [Employee ID]=? WHERE [Main Asset Number]=?')->execute([$id, val($row, 'Main Asset Number')]);
  }
}

function settings_data(){
  $rows = all_rows('Settings');
  $unique = function($col) use($rows){ $out=[]; foreach($rows as $r){ $v=val($r,$col); if($v!=='' && !in_array($v,$out,true)) $out[]=$v; } return $out; };
  $eol=[]; $prefixes=[];
  foreach($rows as $r){
    $cat = val($r,'ASSET CATEGORY');
    if($cat!==''){
      $eol[$cat] = (float)($r['DEFAULT EOL (YEARS)'] ?? 0);
      $pref = val($r,'PREFIX');
      if($pref!=='') $prefixes[$cat] = $pref;
    }
  }
  return [
    'sites'=>$unique('SITES'),'activeFlags'=>$unique('ACTIVE FLAGS'),'offices'=>$unique('DEPARTMENTS / OFFICES'),
    'assetCategories'=>$unique('ASSET CATEGORY'),'prefixes'=>$unique('PREFIX'),'defaultEolYearsByCategory'=>$eol,
    'categoryPrefixes'=>$prefixes,
    'assetStatuses'=>$unique('ASSET STATUSES'),'deploymentStatuses'=>$unique('DEPLOYMENT STATUSES'),
    'counters'=>all_rows('Asset_Counters')
  ];
}
function employee_list(){
  ensure_employee_schema();
  sync_employee_ids();
  $rows = all_rows('Employees');
  usort($rows, fn($a,$b)=>strcmp(val($a,'Employee ID'), val($b,'Employee ID')));
  return ['total'=>count($rows),'items'=>$rows];
}
function normalize_employee($r){
  return [
    'employeeId' => val($r, 'Employee ID'),
    'employeeName' => val($r, 'Employee Name'),
    'reportingBranch' => val($r, 'Reporting Branch'),
    'office' => val($r, 'Office'),
    'active' => val($r, 'Active', 'Yes'),
    'idSource' => val($r, 'ID Source'),
    'createdAt' => norm_dt($r['Created At'] ?? ''),
    'updatedAt' => norm_dt($r['Updated At'] ?? ''),
  ];
}
function employee_module_list($filters=[]){
  ensure_employee_schema();
  $q = trim((string)($filters['query'] ?? ''));
  $branch = trim((string)($filters['branch'] ?? ''));
  $office = trim((string)($filters['office'] ?? ''));
  $active = trim((string)($filters['active'] ?? ''));

  $sql = 'SELECT * FROM [Employees] WHERE 1=1';
  $params = [];
  
  if($branch !== ''){
    $sql .= ' AND [Reporting Branch] = ?';
    $params[] = $branch;
  }
  if($office !== ''){
    $sql .= ' AND [Office] = ?';
    $params[] = $office;
  }
  if($active !== ''){
    $sql .= ' AND [Active] = ?';
    $params[] = $active;
  }
  if($q !== ''){
    $sql .= ' AND ([Employee ID] LIKE ? OR [Employee Name] LIKE ? OR [Reporting Branch] LIKE ? OR [Office] LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
  }
  
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $items = array_map('normalize_employee', $stmt->fetchAll(PDO::FETCH_ASSOC));
  
  usort($items, fn($a,$b)=>strcmp($a['employeeId'], $b['employeeId']));
  return ['total'=>count($items),'items'=>$items];
}
function create_employee($p){
  ensure_employee_schema();
  $employeeId = trim((string)($p['employeeId'] ?? ''));
  $employeeName = trim((string)($p['employeeName'] ?? ''));
  if($employeeName === '') throw new Exception('Employee Name is required.');

  if($employeeId === ''){
    $ids = array_map(fn($r)=>val($r, 'Employee ID'), all_rows('Employees'));
    $employeeId = next_temp_employee_id($ids);
    $idSource = 'Temporary';
  } else {
    $idSource = trim((string)($p['idSource'] ?? 'Manual')) ?: 'Manual';
  }

  $existing = scalar('SELECT [Employee ID] FROM [Employees] WHERE UPPER([Employee ID])=?', [strtoupper($employeeId)]);
  if($existing) throw new Exception('Employee ID already exists.');

  $nameKey = employee_name_key($employeeName);
  if($nameKey !== ''){
    $nameExists = scalar('SELECT [Employee ID] FROM [Employees] WHERE UPPER(LTRIM(RTRIM([Employee Name])))=?', [$nameKey]);
    if($nameExists) throw new Exception('Employee name already exists with ID '.$nameExists.'.');
  }

  $stmt = db()->prepare('
    INSERT INTO [Employees] ([Employee ID],[Employee Name],[Reporting Branch],[Office],[Active],[ID Source],[Created At],[Updated At])
    VALUES (?,?,?,?,?,?,SYSDATETIME(),SYSDATETIME())
  ');
  $stmt->execute([
    $employeeId,
    $employeeName,
    trim((string)($p['reportingBranch'] ?? '')),
    trim((string)($p['office'] ?? '')),
    trim((string)($p['active'] ?? 'Yes')) ?: 'Yes',
    $idSource
  ]);

  $stmt = db()->prepare('SELECT * FROM [Employees] WHERE [Employee ID]=?');
  $stmt->execute([$employeeId]);
  return normalize_employee($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
}
function update_employee($p){
  ensure_employee_schema();
  $employeeId = trim((string)($p['employeeId'] ?? ''));
  if($employeeId === '') throw new Exception('Employee ID is required.');
  $employeeName = trim((string)($p['employeeName'] ?? ''));
  if($employeeName === '') throw new Exception('Employee Name is required.');

  $stmt = db()->prepare('SELECT * FROM [Employees] WHERE [Employee ID]=?');
  $stmt->execute([$employeeId]);
  $current = $stmt->fetch(PDO::FETCH_ASSOC);
  if(!$current) throw new Exception('Employee not found.');

  $active = trim((string)($p['active'] ?? val($current, 'Active', 'Yes'))) ?: 'Yes';
  $stmt = db()->prepare('
    UPDATE [Employees]
    SET [Employee Name]=?, [Reporting Branch]=?, [Office]=?, [Active]=?, [Updated At]=SYSDATETIME()
    WHERE [Employee ID]=?
  ');
  $stmt->execute([
    $employeeName,
    trim((string)($p['reportingBranch'] ?? '')),
    trim((string)($p['office'] ?? '')),
    $active,
    $employeeId
  ]);

  $stmt = db()->prepare('SELECT * FROM [Employees] WHERE [Employee ID]=?');
  $stmt->execute([$employeeId]);
  return normalize_employee($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
}
function employee_assets($employeeId){
  ensure_employee_schema();
  $employeeId = trim((string)$employeeId);
  if($employeeId === '') throw new Exception('Employee ID is required.');

  $stmt = db()->prepare('SELECT * FROM [Employees] WHERE [Employee ID]=?');
  $stmt->execute([$employeeId]);
  $employeeRow = $stmt->fetch(PDO::FETCH_ASSOC);
  if(!$employeeRow) return ['found'=>false,'message'=>'Employee not found.'];

  $employee = normalize_employee($employeeRow);
  $nameKey = employee_name_key($employee['employeeName']);
  $assets = [];
  foreach(all_assets([])['items'] as $asset){
    $assetEmployeeId = trim((string)($asset['employeeId'] ?? ''));
    $assetNameKey = employee_name_key($asset['currentUser'] ?? '');
    if(strcasecmp($assetEmployeeId, $employeeId) === 0 || ($nameKey !== '' && $assetNameKey === $nameKey)){
      $assets[] = $asset;
    }
  }

  $workstations = [];
  foreach(workstation_list([])['items'] as $workstation){
    $workstationNameKey = employee_name_key($workstation['user'] ?? '');
    if($nameKey !== '' && $workstationNameKey === $nameKey) $workstations[] = $workstation;
  }

  return ['found'=>true,'employee'=>$employee,'assets'=>$assets,'workstations'=>$workstations];
}
function normalize_deployment($r,$i=0){
  return [
    'rowNumber'=>$i+1,'mainAssetNumber'=>val($r,'Main Asset Number'),'reportingBranch'=>val($r,'Reporting Branch'),
    'employeeId'=>val($r,'Employee ID'),'user'=>val($r,'User'),'office'=>val($r,'Office'),'systemUnitCode'=>val($r,'System Unit Code'),
    'monitorCode'=>val($r,'Monitor Code'),'mouseCode'=>val($r,'Mouse Code'),'keyboardCode'=>val($r,'Keyboard Code'),
    'upsCode'=>val($r,'UPS Code'),'printerCode'=>val($r,'Printer Code'),'laptopCode'=>val($r,'Laptop Code'),
    'dateDeployed'=>norm_date($r['Date Deployed'] ?? ''),'dateReturned'=>norm_date($r['Date Returned'] ?? ''),
    'active'=>val($r,'Active'),'deploymentStatus'=>val($r,'Deployment Status'),'remarks'=>val($r,'Remarks'),
    'createdAt'=>norm_dt($r['Created At'] ?? ''),'updatedAt'=>norm_dt($r['Updated At'] ?? ''),'encodedBy'=>val($r,'Encoded By'),
    'badgeTone'=>badge_tone(val($r,'Deployment Status'))
  ];
}
function component_field_for_category($category){
  $key = strtolower(trim((string)$category));
  if($key === 'system unit') return 'systemUnitCode';
  if($key === 'monitor') return 'monitorCode';
  if($key === 'mouse') return 'mouseCode';
  if($key === 'keyboard') return 'keyboardCode';
  if($key === 'ups') return 'upsCode';
  if($key === 'printer') return 'printerCode';
  return '';
}
function workstation_from_asset_group($main, $assets, $i=0){
  $first = $assets[0] ?? [];
  $row = [
    'rowNumber'=>$i+1,
    'mainAssetNumber'=>$main,
    'reportingBranch'=>$first['reportingBranch'] ?? '',
    'user'=>$first['currentUser'] ?? '',
    'office'=>$first['office'] ?? '',
    'systemUnitCode'=>'',
    'monitorCode'=>'',
    'mouseCode'=>'',
    'keyboardCode'=>'',
    'upsCode'=>'',
    'printerCode'=>'',
    'laptopCode'=>'',
    'dateDeployed'=>$first['purchaseDate'] ?? '',
    'dateReturned'=>'',
    'active'=>'Yes',
    'deploymentStatus'=>'Deployed',
    'remarks'=>'Built from assigned asset rows',
    'createdAt'=>$first['createdAt'] ?? '',
    'updatedAt'=>$first['updatedAt'] ?? '',
    'encodedBy'=>$first['encodedBy'] ?? '',
    'badgeTone'=>badge_tone('Deployed')
  ];
  foreach($assets as $asset){
    $field = component_field_for_category($asset['assetCategory'] ?? '');
    if($field !== '' && $row[$field] === '') $row[$field] = $asset['assetCode'] ?? '';
    foreach(['reportingBranch','office'] as $fieldName){
      if($row[$fieldName] === '' && !empty($asset[$fieldName])) $row[$fieldName] = $asset[$fieldName];
    }
    if($row['user'] === '' && !empty($asset['currentUser'])) $row['user'] = $asset['currentUser'];
  }
  return $row;
}
function workstation_list($filters=[]){
  $items=[]; $seen=[];
  foreach(all_rows('Deployments') as $r){
    $item = normalize_deployment($r, count($items));
    $items[] = $item;
    if($item['mainAssetNumber'] !== '') $seen[strtoupper($item['mainAssetNumber'])] = true;
  }
  $assetGroups=[];
  $allAssetItems = all_assets([])['items'];
  $userMainLookup = [];
  $fallbackSeqByBranch = [];
  $existingMaxByBranch = [];
  foreach($items as $item){
    $branch = $item['reportingBranch'] ?: 'DVO';
    $key = branch_code($branch);
    $existingMaxByBranch[$key] = max($existingMaxByBranch[$key] ?? 0, main_no_sequence($item['mainAssetNumber'], $branch));
  }
  foreach($allAssetItems as $asset){
    $branch = $asset['reportingBranch'] ?: 'DVO';
    $key = branch_code($branch);
    $existingMaxByBranch[$key] = max($existingMaxByBranch[$key] ?? 0, main_no_sequence($asset['assignedMainAssetNo'] ?? '', $branch));
  }
  foreach($allAssetItems as $asset){
    if(strcasecmp($asset['assetCategory'] ?? '', 'System Unit') !== 0) continue;
    $userKey = trim((string)($asset['employeeId'] ?? '')) ?: employee_name_key($asset['currentUser'] ?? '');
    if($userKey === '') continue;
    if(isset($userMainLookup[$userKey])) continue;
    $main = trim((string)($asset['assignedMainAssetNo'] ?? ''));
    if($main === ''){
      $branch = $asset['reportingBranch'] ?: 'DVO';
      $branchKey = branch_code($branch);
      $fallbackSeqByBranch[$branchKey] = ($fallbackSeqByBranch[$branchKey] ?? ($existingMaxByBranch[$branchKey] ?? 0)) + 1;
      $main = format_main_no($branch, $fallbackSeqByBranch[$branchKey]);
    }
    if($main !== '' && !isset($userMainLookup[$userKey])) $userMainLookup[$userKey] = $main;
  }
  foreach($allAssetItems as $asset){
    $main = trim((string)($asset['assignedMainAssetNo'] ?? ''));
    if($main === '' && strcasecmp($asset['assetCategory'] ?? '', 'System Unit') === 0 && trim((string)($asset['currentUser'] ?? '')) !== ''){
      $userKey = trim((string)($asset['employeeId'] ?? '')) ?: employee_name_key($asset['currentUser'] ?? '');
      $main = $userMainLookup[$userKey] ?? '';
    } elseif($main === '') {
      $userKey = trim((string)($asset['employeeId'] ?? '')) ?: employee_name_key($asset['currentUser'] ?? '');
      $main = $userMainLookup[$userKey] ?? '';
    }
    if($main === '') continue;
    $key = strtoupper($main);
    if(isset($seen[$key])) continue;
    if(!isset($assetGroups[$key])) $assetGroups[$key] = ['main'=>$main, 'assets'=>[]];
    $assetGroups[$key]['assets'][] = $asset;
  }
  foreach($assetGroups as $group){
    $items[] = workstation_from_asset_group($group['main'], $group['assets'], count($items));
  }
  $q=strtolower($filters['query'] ?? '');
  $items=array_values(array_filter($items, function($row) use($filters,$q){
    if($q){ $hay=implode(' ', $row); if(stripos($hay,$q)===false) return false; }
    foreach(['office','status'=>'deploymentStatus','branch'=>'reportingBranch','active'] as $fk=>$rk){ if(is_int($fk)) $fk=$rk; $fv=$filters[$fk] ?? ''; if($fv!=='' && strcasecmp($row[$rk] ?? '',$fv)!==0) return false; }
    return true;
  }));
  usort($items, fn($a,$b)=>strcmp($a['mainAssetNumber'],$b['mainAssetNumber']));
  foreach($items as $i=>&$r) $r['rowNumber']=$i+1;
  unset($r);
  $sites=[]; $deployed=0; $ready=0; $repair=0;
  foreach($items as $r){ if($r['reportingBranch']) $sites[$r['reportingBranch']]=1; $s=strtolower($r['deploymentStatus']); if(strpos($s,'deploy')!==false) $deployed++; if(strpos($s,'ready')!==false) $ready++; if(strpos($s,'repair')!==false) $repair++; }
  return ['total'=>count($items),'kpis'=>['totalWorkstations'=>count($items),'readyToDeploy'=>$ready,'deployed'=>$deployed,'inRepair'=>$repair,'allSites'=>count($sites)],'items'=>$items];
}
function all_assets($filters=[]){
  global $assetTables; $items=[];
  $q = trim((string)($filters['query'] ?? ''));
  $categoryFilter = trim((string)($filters['category'] ?? ''));
  $officeFilter = trim((string)($filters['office'] ?? ''));
  $statusFilter = trim((string)($filters['status'] ?? ''));
  $branchFilter = trim((string)($filters['branch'] ?? ''));

  foreach($assetTables as $cat=>$def){
    if($categoryFilter !== '' && strcasecmp($cat, $categoryFilter) !== 0) continue;
    
    $sql = 'SELECT * FROM ' . qname($def['table']) . ' WHERE 1=1';
    $params = [];
    
    if($officeFilter !== ''){
      $sql .= ' AND [Office] = ?';
      $params[] = $officeFilter;
    }
    if($statusFilter !== ''){
      $sql .= ' AND [Asset Status] = ?';
      $params[] = $statusFilter;
    }
    if($branchFilter !== ''){
      $sql .= ' AND [Reporting Branch] = ?';
      $params[] = $branchFilter;
    }
    if($q !== ''){
      $sql .= ' AND ([Asset Code] LIKE ? OR [Brand] LIKE ? OR [Model] LIKE ? OR [Serial Number] LIKE ? OR [Current User] LIKE ? OR [Remarks] LIKE ?)';
      $likeParam = '%' . $q . '%';
      array_push($params, $likeParam, $likeParam, $likeParam, $likeParam, $likeParam, $likeParam);
    }
    
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){
      $row=[
        'assetCode'=>val($r,'Asset Code'),'assetCategory'=>val($r,'Asset Category',$cat),'reportingBranch'=>val($r,'Reporting Branch'),
        'office'=>val($r,'Office'),'employeeId'=>val($r,'Employee ID'),'assignedMainAssetNo'=>val($r,'Assigned Main Asset No.'),'currentUser'=>val($r,'Current User'),
        'assetStatus'=>val($r,'Asset Status'),'brand'=>val($r,'Brand'),'model'=>val($r,'Model'),'serialNumber'=>val($r,'Serial Number'),
        'purchaseDate'=>norm_date($r['Purchase Date'] ?? ''),'purchasePrice'=>(float)($r['Purchase Price'] ?? 0),
        'expectedLifeYears'=>(float)($r['Expected Life (Years)'] ?? 0),'residualValue'=>(float)($r['Residual Value'] ?? 0),
        'currentValue'=>(float)($r['Current Value'] ?? 0),'deviceEol'=>norm_date($r['Device EOL'] ?? ''),
        'fullyDepreciatedDate'=>norm_date($r['Fully Depreciated Date'] ?? ''),
        'remarks'=>val($r,'Remarks'),'createdAt'=>norm_dt($r['Created At'] ?? ''),'updatedAt'=>norm_dt($r['Updated At'] ?? ''),'encodedBy'=>val($r,'Encoded By'),
        'badgeTone'=>badge_tone(val($r,'Asset Status'))
      ];
      $row['displayName']=trim($row['brand'].' '.$row['model']);
      $items[]=$row;
    }
  }
  usort($items, fn($a,$b)=>strcmp($a['assetCode'],$b['assetCode']));
  return ['total'=>count($items),'items'=>$items];
}
function find_asset($assetCode){
  global $assetTables; $code=strtoupper(trim($assetCode));
  foreach($assetTables as $cat=>$def){
    $stmt=db()->prepare('SELECT * FROM '.qname($def['table']).' WHERE UPPER([Asset Code])=?');
    $stmt->execute([$code]); $r=$stmt->fetch(PDO::FETCH_ASSOC);
    if($r) return [$cat,$def,$r];
  }
  return null;
}

function asset_photo_url($assetCode){
  $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper(trim((string)$assetCode)));
  if($safe === '') return '';
  foreach(['asset_images','asset_photos'] as $folder){
    foreach(['jpg','jpeg','png','gif','webp'] as $ext){
      $path = __DIR__ . '/uploads/' . $folder . '/' . $safe . '.' . $ext;
      if(is_file($path)) return 'uploads/' . $folder . '/' . rawurlencode($safe . '.' . $ext) . '?v=' . filemtime($path);
    }
  }
  return '';
}
function asset_qr_url($assetCode){
  $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper(trim((string)$assetCode)));
  if($safe === '') return '';
  $path = __DIR__ . '/uploads/qr/' . $safe . '.png';
  if(is_file($path)) return 'uploads/qr/' . rawurlencode($safe . '.png') . '?v=' . filemtime($path);
  return '';
}
function app_asset_lookup_url($assetCode){
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
  $scheme = $https ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
  $path = preg_replace('/api\.php$/i', 'index.php', $script);
  return $scheme . '://' . $host . $path . '?asset=' . rawurlencode($assetCode);
}
function fetch_url_bytes($url){
  if(function_exists('curl_init')){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CONNECTTIMEOUT => 8,
      CURLOPT_TIMEOUT => 15,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_USERAGENT => 'BSPI-Asset-Manager/1.0'
    ]);
    $data = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if($data !== false && $status >= 200 && $status < 300) return $data;
  }
  $context = stream_context_create(['http'=>['timeout'=>12, 'header'=>"User-Agent: BSPI-Asset-Manager/1.0\r\n"]]);
  return @file_get_contents($url, false, $context);
}
function ensure_asset_qr($p){
  $code = is_array($p) ? trim((string)($p['assetCode'] ?? '')) : trim((string)$p);
  if($code === '') throw new Exception('Asset Code is required for QR generation.');
  if(!find_asset($code)) throw new Exception('Asset not found.');

  $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper($code));
  $dir = __DIR__ . '/uploads/qr';
  if(!is_dir($dir) && !mkdir($dir, 0775, true)) throw new Exception('Unable to create QR folder.');

  $dest = $dir . '/' . $safe . '.png';
  if(is_file($dest)) return ['qrUrl'=>asset_qr_url($code), 'created'=>false];

  $lookupUrl = app_asset_lookup_url($code);
  $remoteQr = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=8&data=' . rawurlencode($lookupUrl);
  $image = fetch_url_bytes($remoteQr);
  if($image === false || strlen($image) < 100) throw new Exception('Unable to generate QR image. Check internet connection or add the PNG manually.');

  $imageInfo = @getimagesizefromstring($image);
  if(!$imageInfo || strtolower($imageInfo['mime'] ?? '') !== 'image/png') throw new Exception('QR generator returned an invalid image.');
  if(file_put_contents($dest, $image, LOCK_EX) === false) throw new Exception('Unable to save QR image.');

  return ['qrUrl'=>asset_qr_url($code), 'created'=>true];
}
function save_asset_qr($p){
  $code = is_array($p) ? trim((string)($p['assetCode'] ?? '')) : '';
  $dataUrl = is_array($p) ? trim((string)($p['dataUrl'] ?? '')) : '';
  if($code === '') throw new Exception('Asset Code is required for QR saving.');
  if(!find_asset($code)) throw new Exception('Asset not found.');
  if($dataUrl === '' || !preg_match('/^data:image\/png;base64,/', $dataUrl)) throw new Exception('QR image must be a PNG data URL.');

  $raw = substr($dataUrl, strpos($dataUrl, ',') + 1);
  $image = base64_decode($raw, true);
  if($image === false || strlen($image) < 100 || strlen($image) > 1024 * 1024) throw new Exception('QR image data is invalid.');

  $imageInfo = @getimagesizefromstring($image);
  if(!$imageInfo || strtolower($imageInfo['mime'] ?? '') !== 'image/png') throw new Exception('QR image data is not a valid PNG.');

  $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper($code));
  $dir = __DIR__ . '/uploads/qr';
  if(!is_dir($dir) && !mkdir($dir, 0775, true)) throw new Exception('Unable to create QR folder.');

  $dest = $dir . '/' . $safe . '.png';
  if(file_put_contents($dest, $image, LOCK_EX) === false) throw new Exception('Unable to save QR image.');
  return ['qrUrl'=>asset_qr_url($code), 'created'=>true];
}

function asset_details($assetCode){
  $found=find_asset($assetCode); if(!$found) return ['found'=>false,'message'=>'Asset not found'];
  [$cat,$def,$r]=$found;
  $asset=[
    'assetCode'=>val($r,'Asset Code'),'assetCategory'=>val($r,'Asset Category',$cat),'displayName'=>trim(val($r,'Brand').' '.val($r,'Model')),
    'brand'=>val($r,'Brand'),'model'=>val($r,'Model'),'serialNumber'=>val($r,'Serial Number'),'reportingBranch'=>val($r,'Reporting Branch'),
    'office'=>val($r,'Office'),'employeeId'=>val($r,'Employee ID'),'assignedMainAssetNo'=>val($r,'Assigned Main Asset No.'),'currentUser'=>val($r,'Current User'),
    'assetStatus'=>val($r,'Asset Status'),'remarks'=>val($r,'Remarks'),'encodedBy'=>val($r,'Encoded By'),'badgeTone'=>badge_tone(val($r,'Asset Status'))
  ];
  $asset['photoUrl'] = asset_photo_url($asset['assetCode']);
  $asset['qrUrl'] = asset_qr_url($asset['assetCode']);
  $record = [
    'assetCode' => val($r, 'Asset Code'),
    'assetCategory' => val($r, 'Asset Category', $cat),
    'reportingBranch' => val($r, 'Reporting Branch'),
    'office' => val($r, 'Office'),
    'assetStatus' => val($r, 'Asset Status'),
    'brand' => val($r, 'Brand'),
    'model' => val($r, 'Model'),
    'serialNumber' => val($r, 'Serial Number'),
    'employeeId' => val($r, 'Employee ID'),
    'currentUser' => val($r, 'Current User'),
    'assignedMainAssetNo' => val($r, 'Assigned Main Asset No.'),
    'dateDeployment' => norm_date($r['Date Deployed'] ?? ''),
    'purchaseDate' => norm_date($r['Purchase Date'] ?? ''),
    'purchasePrice' => (float)val($r, 'Purchase Price'),
    'expectedLifeYears' => val($r, 'Expected Life (Years)'),
    'residualValue' => (float)val($r, 'Residual Value'),
    'currentValue' => (float)val($r, 'Current Value'),
    'deviceEol' => norm_date($r['Device EOL'] ?? ''),
    'fullyDepreciatedDate' => norm_date($r['Fully Depreciated Date'] ?? ''),
    'encodedBy' => val($r, 'Encoded By'),
    'remarks' => val($r, 'Remarks'),
    'createdAt' => norm_dt($r['Created At'] ?? ''),
    'updatedAt' => norm_dt($r['Updated At'] ?? ''),
    'processor' => val($r, 'Processor'),
    'ram' => val($r, 'RAM'),
    'storage' => val($r, 'Storage'),
    'operatingSystem' => val($r, 'Operating System'),
    'pcName' => val($r, 'PC Name'),
    'laptopName' => val($r, 'Laptop Name'),
    'macAddress' => val($r, 'MAC Address'),
    'ipAddress' => val($r, 'IP Address'),
    'capacityVa' => val($r, 'Capacity (VA)'),
    'printerType' => val($r, 'Printer Type'),
  ];
  if($record['dateDeployment'] === ''){
    $stmt = db()->prepare('
      SELECT TOP 1 *
      FROM [Deployments]
      WHERE UPPER([Main Asset Number]) = ?
         OR UPPER([System Unit Code]) = ?
         OR UPPER([Monitor Code]) = ?
         OR UPPER([Mouse Code]) = ?
         OR UPPER([Keyboard Code]) = ?
         OR UPPER([UPS Code]) = ?
         OR UPPER([Printer Code]) = ?
         OR UPPER([Laptop Code]) = ?
      ORDER BY [Date Deployed] DESC, [Updated At] DESC, [Created At] DESC
    ');
    $assetCodeUpper = strtoupper($record['assetCode']);
    $mainAssetUpper = strtoupper($record['assignedMainAssetNo']);
    $stmt->execute([
      $mainAssetUpper,
      $assetCodeUpper,
      $assetCodeUpper,
      $assetCodeUpper,
      $assetCodeUpper,
      $assetCodeUpper,
      $assetCodeUpper,
      $assetCodeUpper,
    ]);
    $deploymentRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if($deploymentRow){
      $deployment = normalize_deployment($deploymentRow, 0);
      $record['dateDeployment'] = $deployment['dateDeployed'];
      $record['currentUser'] = $record['currentUser'] ?: $deployment['user'];
      $record['assignedMainAssetNo'] = $record['assignedMainAssetNo'] ?: $deployment['mainAssetNumber'];
      $record['reportingBranch'] = $record['reportingBranch'] ?: $deployment['reportingBranch'];
      $record['office'] = $record['office'] ?: $deployment['office'];
    }
  }
  $asset['dateDeployment'] = $record['dateDeployment'];
  $asset['currentUser'] = $asset['currentUser'] ?: $record['currentUser'];
  $asset['assignedMainAssetNo'] = $asset['assignedMainAssetNo'] ?: $record['assignedMainAssetNo'];
  $asset['reportingBranch'] = $asset['reportingBranch'] ?: $record['reportingBranch'];
  $asset['office'] = $asset['office'] ?: $record['office'];
  $specMap = [
    'System Unit' => [
      ['Processor', val($r, 'Processor')],
      ['RAM', val($r, 'RAM')],
      ['Storage', val($r, 'Storage')],
      ['Operating System', val($r, 'Operating System')],
      ['PC Name', val($r, 'PC Name')],
      ['MAC Address', val($r, 'MAC Address')],
      ['IP Address', val($r, 'IP Address')],
    ],
    'Laptop' => [
      ['Processor', val($r, 'Processor')],
      ['RAM', val($r, 'RAM')],
      ['Storage', val($r, 'Storage')],
      ['Operating System', val($r, 'Operating System')],
      ['Laptop Name', val($r, 'Laptop Name')],
      ['MAC Address', val($r, 'MAC Address')],
    ],
    'UPS' => [
      ['Capacity (VA)', val($r, 'Capacity (VA)')],
    ],
    'Printer' => [
      ['Printer Type', val($r, 'Printer Type')],
    ],
  ];
  $specs = array_values(array_filter($specMap[$cat] ?? [], function($item){
    return trim((string)($item[1] ?? '')) !== '';
  }));
  if(!$specs){
    $specs = [
      ['Brand', val($r, 'Brand')],
      ['Model', val($r, 'Model')],
      ['Serial Number', val($r, 'Serial Number')],
    ];
  }
  $financials = [
    'purchaseDate' => val($r, 'Purchase Date'),
    'purchasePrice' => (float)val($r, 'Purchase Price'),
    'expectedLifeYears' => val($r, 'Expected Life (Years)'),
    'residualValue' => (float)val($r, 'Residual Value'),
    'currentValue' => (float)val($r, 'Current Value'),
    'deviceEol' => val($r, 'Device EOL'),
    'fullyDepreciatedDate' => val($r, 'Fully Depreciated Date'),
  ];
  $progress = 0;
  if($financials['purchaseDate'] && (float)$financials['expectedLifeYears'] > 0){
    try {
      $purchase = new DateTimeImmutable($financials['purchaseDate']);
      $end = $purchase->modify('+' . (int)$financials['expectedLifeYears'] . ' years');
      $now = new DateTimeImmutable('now');
      $total = max(1, $end->getTimestamp() - $purchase->getTimestamp());
      $elapsed = max(0, min($now->getTimestamp() - $purchase->getTimestamp(), $total));
      $progress = round(($elapsed / $total) * 100, 2);
    } catch(Throwable $e) {}
  }
  $asset['depreciationProgress'] = $progress;
  return [
    'found' => true,
    'asset' => $asset,
    'overview' => $asset,
    'record' => $record,
    'specs' => $specs,
    'financials' => $financials,
    'assignment' => $asset,
    'history' => asset_history($assetCode),
    'repairLogs' => repair_logs($assetCode),
  ];
}
function workstation_details($main){
  $stmt=db()->prepare('SELECT * FROM [Deployments] WHERE UPPER([Main Asset Number])=?'); $stmt->execute([strtoupper(trim($main))]); $r=$stmt->fetch(PDO::FETCH_ASSOC);
  if($r){
    $dep=normalize_deployment($r,0);
  } else {
    $assetsForMain = [];
    $allAssetItems = all_assets([])['items'];
    $userMainLookup = [];
    $fallbackSeqByBranch = [];
    $existingMaxByBranch = [];
    foreach(all_rows('Deployments') as $row){
      $deployment = normalize_deployment($row, 0);
      $branch = $deployment['reportingBranch'] ?: 'DVO';
      $key = branch_code($branch);
      $existingMaxByBranch[$key] = max($existingMaxByBranch[$key] ?? 0, main_no_sequence($deployment['mainAssetNumber'], $branch));
    }
    foreach($allAssetItems as $asset){
      $branch = $asset['reportingBranch'] ?: 'DVO';
      $key = branch_code($branch);
      $existingMaxByBranch[$key] = max($existingMaxByBranch[$key] ?? 0, main_no_sequence($asset['assignedMainAssetNo'] ?? '', $branch));
    }
    foreach($allAssetItems as $asset){
      if(strcasecmp($asset['assetCategory'] ?? '', 'System Unit') !== 0) continue;
      $userKey = trim((string)($asset['employeeId'] ?? '')) ?: employee_name_key($asset['currentUser'] ?? '');
      if($userKey === '') continue;
      if(isset($userMainLookup[$userKey])) continue;
      $assetMain = trim((string)($asset['assignedMainAssetNo'] ?? ''));
      if($assetMain === ''){
        $branch = $asset['reportingBranch'] ?: 'DVO';
        $branchKey = branch_code($branch);
        $fallbackSeqByBranch[$branchKey] = ($fallbackSeqByBranch[$branchKey] ?? ($existingMaxByBranch[$branchKey] ?? 0)) + 1;
        $assetMain = format_main_no($branch, $fallbackSeqByBranch[$branchKey]);
      }
      if($assetMain !== '' && !isset($userMainLookup[$userKey])) $userMainLookup[$userKey] = $assetMain;
    }
    $targetUser = '';
    foreach($allAssetItems as $asset){
      $assetMain = trim((string)($asset['assignedMainAssetNo'] ?? ''));
      if($assetMain === ''){
        $userKey = trim((string)($asset['employeeId'] ?? '')) ?: employee_name_key($asset['currentUser'] ?? '');
        $assetMain = $userMainLookup[$userKey] ?? '';
      }
      if(strcasecmp($assetMain, $main) === 0){
        $targetUser = trim((string)($asset['currentUser'] ?? ''));
        break;
      }
    }
    foreach($allAssetItems as $asset){
      $assetMain = trim((string)($asset['assignedMainAssetNo'] ?? ''));
      if($assetMain === ''){
        $userKey = trim((string)($asset['employeeId'] ?? '')) ?: employee_name_key($asset['currentUser'] ?? '');
        $assetMain = $userMainLookup[$userKey] ?? '';
      }
      if(strcasecmp($assetMain, $main) === 0 || ($assetMain === '' && $targetUser !== '' && strcasecmp($asset['currentUser'] ?? '', $targetUser) === 0)) $assetsForMain[] = $asset;
    }
    if(!$assetsForMain) return ['found'=>false,'message'=>'No deployment record matched'];
    $dep = workstation_from_asset_group($main, $assetsForMain, 0);
  }
  $codes=array_filter([$dep['systemUnitCode'],$dep['monitorCode'],$dep['mouseCode'],$dep['keyboardCode'],$dep['upsCode'],$dep['printerCode']]);
  $assets=[]; foreach($codes as $c){ $d=asset_details($c); if($d['found']) $assets[]=$d['asset']; }
  $maintenanceSummary = ['total'=>0,'open'=>0,'closed'=>0,'items'=>[]];
  $repairSummary = repair_summary_for_assets($assets);
  return [
    'found'=>true,
    'mainAssetNumber'=>$dep['mainAssetNumber'],
    'deployment'=>$dep,
    'assignedAssets'=>$assets,
    'history'=>asset_history('', $dep['mainAssetNumber']),
    'maintenanceSummary'=>$maintenanceSummary,
    'repairSummary'=>$repairSummary
  ];
}
function asset_history($assetCode='', $main=''){
  $rows=all_rows('Asset_History'); $out=[]; foreach($rows as $r){ if(($assetCode==='' || strcasecmp(val($r,'Asset Code'),$assetCode)==0) && ($main==='' || strcasecmp(val($r,'Main Asset Number'),$main)==0)) $out[]=[
    'historyId'=>val($r,'History ID'),'transactionDate'=>norm_dt($r['Transaction Date'] ?? ''),'action'=>val($r,'Action'),'assetCode'=>val($r,'Asset Code'),'mainAssetNumber'=>val($r,'Main Asset Number'),'fromUser'=>val($r,'From User'),'toUser'=>val($r,'To User'),'reportingBranch'=>val($r,'Reporting Branch'),'office'=>val($r,'Office'),'statusAfter'=>val($r,'Status After'),'remarks'=>val($r,'Remarks'),'encodedBy'=>val($r,'Encoded By')
  ]; }
  return $out;
}
function normalize_repair_log($r){
  return [
    'repairId'=>val($r,'Repair ID'),
    'dateReported'=>norm_date($r['Date Reported'] ?? ''),
    'assetCode'=>val($r,'Asset Code'),
    'assetCategory'=>val($r,'Asset Category'),
    'issue'=>val($r,'Issue'),
    'sentTo'=>val($r,'Sent To'),
    'dateSent'=>norm_date($r['Date Sent'] ?? ''),
    'dateReturned'=>norm_date($r['Date Returned'] ?? ''),
    'repairStatus'=>val($r,'Repair Status'),
    'cost'=>val($r,'Cost'),
    'remarks'=>val($r,'Remarks'),
    'createdAt'=>norm_dt($r['Created At'] ?? ''),
    'updatedAt'=>norm_dt($r['Updated At'] ?? ''),
    'encodedBy'=>val($r,'Encoded By'),
    'badgeTone'=>badge_tone(val($r,'Repair Status'))
  ];
}
function repair_logs($assetCode){
  if(!table_exists('Repair_Log')) return [];
  $assetCode = strtoupper(trim((string)$assetCode));
  $rows = all_rows('Repair_Log');
  $out = [];
  foreach($rows as $r){
    if($assetCode !== '' && strtoupper(val($r,'Asset Code')) !== $assetCode) continue;
    $out[] = normalize_repair_log($r);
  }
  usort($out, fn($a,$b)=>strcmp($b['dateReported'] ?: $b['createdAt'], $a['dateReported'] ?: $a['createdAt']));
  return $out;
}
function repair_summary_for_assets($assets){
  $codes = [];
  foreach($assets as $asset){
    $code = strtoupper(trim((string)($asset['assetCode'] ?? '')));
    if($code !== '') $codes[$code] = true;
  }
  if(!$codes) return ['total'=>0,'open'=>0,'closed'=>0,'items'=>[]];

  $items = [];
  foreach(repair_logs('') as $row){
    if(isset($codes[strtoupper($row['assetCode'] ?? '')])) $items[] = $row;
  }

  $closed = 0;
  foreach($items as $item){
    $status = strtolower(trim((string)($item['repairStatus'] ?? '')));
    if($status !== '' && (strpos($status,'closed') !== false || strpos($status,'complete') !== false || strpos($status,'done') !== false || strpos($status,'return') !== false || strpos($status,'resolved') !== false)) $closed++;
  }
  return ['total'=>count($items),'open'=>max(0, count($items) - $closed),'closed'=>$closed,'items'=>$items];
}
function find_employee_by_identifier($identifier){
  $identifier = trim((string)$identifier);
  if($identifier === '') return null;
  $stmt = db()->prepare('SELECT TOP 1 * FROM [Employees] WHERE UPPER([Employee ID])=? OR UPPER(LTRIM(RTRIM([Employee Name])))=?');
  $stmt->execute([strtoupper($identifier), strtoupper($identifier)]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ? normalize_employee($row) : null;
}
function lookup_employee($query){
  ensure_employee_schema();
  $query = trim((string)$query);
  if($query === '') throw new Exception('Employee ID or name is required.');
  $exact = find_employee_by_identifier($query);
  if($exact) return ['found'=>true,'employee'=>$exact,'items'=>[$exact]];

  $needle = '%' . strtoupper($query) . '%';
  $stmt = db()->prepare('
    SELECT TOP 8 *
    FROM [Employees]
    WHERE UPPER([Employee ID]) LIKE ? OR UPPER([Employee Name]) LIKE ?
    ORDER BY [Employee Name]
  ');
  $stmt->execute([$needle, $needle]);
  $items = array_map('normalize_employee', $stmt->fetchAll(PDO::FETCH_ASSOC));
  return ['found'=>false,'items'=>$items,'message'=>$items ? 'No exact match. Choose a matching employee or create a new one.' : 'No employee matched.'];
}
function next_history_id(){
  $max = 0;
  foreach(all_rows('Asset_History') as $row){
    if(preg_match('/HIST-(\d+)/i', val($row, 'History ID'), $m)) $max = max($max, (int)$m[1]);
  }
  return 'HIST-' . str_pad($max + 1, 6, '0', STR_PAD_LEFT);
}
function write_asset_history($action, $assetCode, $mainAssetNumber, $fromUser, $toUser, $branch, $office, $statusAfter, $remarks, $encodedBy='Admin'){
  $cols = ['History ID','Transaction Date','Action','Asset Code','Main Asset Number','From User','To User','Reporting Branch','Office','Status After','Remarks','Encoded By'];
  $vals = [next_history_id(), date('Y-m-d H:i:s'), $action, $assetCode, $mainAssetNumber, $fromUser, $toUser, $branch, $office, $statusAfter, $remarks, $encodedBy];
  db()->prepare('INSERT INTO [Asset_History] ('.implode(',',array_map('qname',$cols)).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute($vals);
}
function transfer_workstation($p){
  global $assetTables;
  ensure_employee_schema();
  $main = trim((string)($p['mainAssetNumber'] ?? ''));
  $targetText = trim((string)($p['toEmployee'] ?? $p['toEmployeeId'] ?? ''));
  if($main === '') throw new Exception('Main Asset Number is required.');
  if($targetText === '') throw new Exception('Target employee is required.');

  $target = find_employee_by_identifier($targetText);
  if(!$target) throw new Exception('Target employee not found. Enter an exact Employee ID or Employee Name.');

  $details = workstation_details($main);
  if(empty($details['found'])) throw new Exception('Workstation not found.');
  $dep = $details['deployment'];
  $fromUser = $dep['user'] ?? '';
  $toUser = $target['employeeName'];
  $targetOffice = $target['office'] ?: ($dep['office'] ?? '');
  $targetBranch = $target['reportingBranch'] ?: ($dep['reportingBranch'] ?? '');
  $remarks = trim((string)($p['remarks'] ?? 'Transferred from workstation details.'));
  $encodedBy = trim((string)($p['encodedBy'] ?? 'Admin')) ?: 'Admin';
  $now = date('Y-m-d H:i:s');

  $stmt = db()->prepare('SELECT COUNT(*) FROM [Deployments] WHERE UPPER([Main Asset Number])=?');
  $stmt->execute([strtoupper($main)]);
  if((int)$stmt->fetchColumn() > 0){
    db()->prepare('UPDATE [Deployments] SET [Employee ID]=?, [User]=?, [Reporting Branch]=?, [Office]=?, [Updated At]=?, [Encoded By]=? WHERE UPPER([Main Asset Number])=?')
      ->execute([$target['employeeId'], $toUser, $targetBranch, $targetOffice, $now, $encodedBy, strtoupper($main)]);
  }

  $transferred = [];
  foreach($assetTables as $def){
    $stmt = db()->prepare('SELECT * FROM '.qname($def['table']).' WHERE UPPER([Assigned Main Asset No.])=?');
    $stmt->execute([strtoupper($main)]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $asset){
      $assetCode = val($asset, 'Asset Code');
      $oldUser = val($asset, 'Current User', $fromUser);
      db()->prepare('UPDATE '.qname($def['table']).' SET [Employee ID]=?, [Current User]=?, [Reporting Branch]=?, [Office]=?, [Asset Status]=?, [Updated At]=?, [Encoded By]=? WHERE [Asset Code]=?')
        ->execute([$target['employeeId'], $toUser, $targetBranch, $targetOffice, 'Deployed', $now, $encodedBy, $assetCode]);
      write_asset_history('Transfer', $assetCode, $main, $oldUser, $toUser, $targetBranch, $targetOffice, 'Deployed', $remarks, $encodedBy);
      $transferred[] = $assetCode;
    }
  }

  if(!empty($p['markOldResigned']) && $fromUser !== ''){
    $old = find_employee_by_identifier($fromUser);
    if($old){
      db()->prepare("UPDATE [Employees] SET [Active]='Resigned', [Updated At]=SYSDATETIME() WHERE [Employee ID]=?")->execute([$old['employeeId']]);
    }
  }

  return ['message'=>'Transfer completed.','transferredAssets'=>$transferred,'workstation'=>workstation_details($main)];
}
function transfer_asset($p){
  $assetCode = trim((string)($p['assetCode'] ?? ''));
  $targetText = trim((string)($p['toEmployee'] ?? $p['toEmployeeId'] ?? ''));
  if($assetCode === '') throw new Exception('Asset Code is required.');
  if($targetText === '') throw new Exception('Target employee is required.');

  $target = find_employee_by_identifier($targetText);
  if(!$target) throw new Exception('Target employee not found. Enter an exact Employee ID or Employee Name.');

  $found = find_asset($assetCode);
  if(!$found) throw new Exception('Asset not found.');
  [$cat, $def, $row] = $found;

  $fromUser = val($row, 'Current User');
  $main = val($row, 'Assigned Main Asset No.');
  $branch = $target['reportingBranch'] ?: val($row, 'Reporting Branch');
  $office = $target['office'] ?: val($row, 'Office');
  $toUser = $target['employeeName'];
  $remarks = trim((string)($p['remarks'] ?? 'Transferred from asset details.'));
  $encodedBy = trim((string)($p['encodedBy'] ?? 'Admin')) ?: 'Admin';
  $now = date('Y-m-d H:i:s');

  db()->prepare('UPDATE '.qname($def['table']).' SET [Employee ID]=?, [Current User]=?, [Reporting Branch]=?, [Office]=?, [Asset Status]=?, [Updated At]=?, [Encoded By]=? WHERE [Asset Code]=?')
    ->execute([$target['employeeId'], $toUser, $branch, $office, 'Deployed', $now, $encodedBy, $assetCode]);
  write_asset_history('Transfer', $assetCode, $main, $fromUser, $toUser, $branch, $office, 'Deployed', $remarks, $encodedBy);

  return ['message'=>'Asset transfer completed.','asset'=>asset_details($assetCode)];
}

function update_asset_operation_status($assetCode, $status, $remarks, $action, $clearAssignment=false, $encodedBy='Admin'){
  $assetCode = trim((string)$assetCode);
  if($assetCode === '') throw new Exception('Asset Code is required.');
  $found = find_asset($assetCode);
  if(!$found) throw new Exception('Asset not found.');
  [$cat, $def, $row] = $found;

  $status = trim((string)$status);
  if($status === '') $status = 'Ready to Deploy';
  $remarks = trim((string)$remarks);
  $encodedBy = trim((string)$encodedBy) ?: 'Admin';
  $now = date('Y-m-d H:i:s');
  $fromUser = val($row, 'Current User');
  $main = val($row, 'Assigned Main Asset No.');
  $branch = val($row, 'Reporting Branch');
  $office = val($row, 'Office');

  if($clearAssignment){
    db()->prepare('UPDATE '.qname($def['table']).' SET [Employee ID]=?, [Current User]=?, [Assigned Main Asset No.]=?, [Asset Status]=?, [Remarks]=?, [Updated At]=?, [Encoded By]=? WHERE [Asset Code]=?')
      ->execute(['', '', '', $status, $remarks, $now, $encodedBy, val($row, 'Asset Code')]);
    if($main !== ''){
      db()->prepare("UPDATE [Deployments] SET [Deployment Status]=?, [Active]=?, [Updated At]=?, [Encoded By]=? WHERE UPPER([Main Asset Number])=?")
        ->execute(['Checked In', 'No', $now, $encodedBy, strtoupper($main)]);
    }
    write_asset_history($action, val($row, 'Asset Code'), $main, $fromUser, '', $branch, $office, $status, $remarks, $encodedBy);
  } else {
    db()->prepare('UPDATE '.qname($def['table']).' SET [Asset Status]=?, [Remarks]=?, [Updated At]=?, [Encoded By]=? WHERE [Asset Code]=?')
      ->execute([$status, $remarks, $now, $encodedBy, val($row, 'Asset Code')]);
    write_asset_history($action, val($row, 'Asset Code'), $main, $fromUser, $fromUser, $branch, $office, $status, $remarks, $encodedBy);
  }

  return ['message'=>$action.' completed.','asset'=>asset_details($assetCode)];
}

function checkin_asset($p){
  return update_asset_operation_status(
    $p['assetCode'] ?? '',
    $p['status'] ?? 'Ready to Deploy',
    $p['remarks'] ?? 'Asset checked in.',
    'Check-In',
    true,
    $p['encodedBy'] ?? 'Admin'
  );
}

function mark_asset_maintenance($p){
  $priority = trim((string)($p['priority'] ?? 'Medium'));
  $issue = trim((string)($p['issue'] ?? 'Requires maintenance review.'));
  $remarks = trim($priority . ' priority - ' . $issue);
  return update_asset_operation_status(
    $p['assetCode'] ?? '',
    'In Repair',
    $remarks,
    'Maintenance',
    false,
    $p['encodedBy'] ?? 'Admin'
  );
}

function preventive_maintenance_list($filters=[]){
  ensure_preventive_maintenance_schema();
  $cycle = trim((string)($filters['cycle'] ?? current_pm_cycle())) ?: current_pm_cycle();
  $branch = trim((string)($filters['branch'] ?? ''));
  $query = trim((string)($filters['query'] ?? ''));
  $workFilters = [
    'branch' => $branch,
    'office' => $filters['office'] ?? '',
    'query' => $query
  ];
  $workstations = workstation_list($workFilters)['items'];

  $stmt = db()->prepare('SELECT * FROM [Preventive_Maintenance] WHERE [Cycle]=?');
  $stmt->execute([$cycle]);
  $records = [];
  foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
    $records[strtoupper(val($row, 'Main Asset Number'))] = $row;
  }

  $items = [];
  $done = 0;
  foreach($workstations as $row){
    $main = $row['mainAssetNumber'] ?? '';
    $record = $records[strtoupper($main)] ?? null;
    $isDone = $record && strcasecmp(val($record, 'Done'), 'Yes') === 0;
    if($isDone) $done++;
    $items[] = [
      'mainAssetNumber' => $main,
      'user' => $row['user'] ?? '',
      'reportingBranch' => $row['reportingBranch'] ?? '',
      'office' => $row['office'] ?? '',
      'deploymentStatus' => $row['deploymentStatus'] ?? '',
      'cycle' => $cycle,
      'pmStatus' => $isDone ? 'Done' : 'Pending',
      'done' => $isDone ? 'Yes' : 'No',
      'completedAt' => $record ? norm_dt($record['Completed At'] ?? '') : '',
      'technician' => $record ? val($record, 'Technician') : '',
      'remarks' => $record ? val($record, 'Remarks') : '',
      'checklist' => $record ? json_decode(val($record, 'Checklist', '{}'), true) : new stdClass()
    ];
  }

  $total = count($items);
  $remaining = max(0, $total - $done);
  $overdue = ((int)date('j') >= 25) ? $remaining : 0;
  $sites = [];
  foreach($items as $item){
    $site = $item['reportingBranch'] ?: 'No Site';
    if(!isset($sites[$site])) $sites[$site] = ['site'=>$site,'total'=>0,'done'=>0,'remaining'=>0];
    $sites[$site]['total']++;
    if($item['done'] === 'Yes') $sites[$site]['done']++;
  }
  foreach($sites as &$siteRow){
    $siteRow['remaining'] = max(0, $siteRow['total'] - $siteRow['done']);
  }
  unset($siteRow);

  return [
    'cycle' => $cycle,
    'summary' => [
      'total' => $total,
      'done' => $done,
      'remaining' => $remaining,
      'overdue' => $overdue
    ],
    'siteProgress' => array_values($sites),
    'items' => $items
  ];
}

function complete_preventive_maintenance($p){
  ensure_preventive_maintenance_schema();
  $main = trim((string)($p['mainAssetNumber'] ?? ''));
  if($main === '') throw new Exception('Workstation ID is required.');
  $details = workstation_details($main);
  if(empty($details['found'])) throw new Exception('Workstation not found.');
  $dep = $details['deployment'];
  $cycle = trim((string)($p['cycle'] ?? current_pm_cycle())) ?: current_pm_cycle();
  $checklist = json_encode($p['checklist'] ?? [], JSON_UNESCAPED_SLASHES);
  $remarks = trim((string)($p['remarks'] ?? ''));
  $technician = trim((string)($p['technician'] ?? 'Admin')) ?: 'Admin';
  $done = !empty($p['done']) && strcasecmp((string)$p['done'], 'No') !== 0 ? 'Yes' : 'No';
  $completedAt = $done === 'Yes' ? date('Y-m-d H:i:s') : null;
  $now = date('Y-m-d H:i:s');

  $stmt = db()->prepare('SELECT [PM ID] FROM [Preventive_Maintenance] WHERE [Cycle]=? AND UPPER([Main Asset Number])=?');
  $stmt->execute([$cycle, strtoupper($main)]);
  $existingId = $stmt->fetchColumn();
  if($existingId){
    db()->prepare('
      UPDATE [Preventive_Maintenance]
      SET [Reporting Branch]=?, [Office]=?, [User]=?, [Checklist]=?, [Remarks]=?, [Technician]=?, [Done]=?, [Completed At]=?, [Updated At]=?
      WHERE [PM ID]=?
    ')->execute([
      $dep['reportingBranch'] ?? '',
      $dep['office'] ?? '',
      $dep['user'] ?? '',
      $checklist,
      $remarks,
      $technician,
      $done,
      $completedAt,
      $now,
      $existingId
    ]);
  } else {
    db()->prepare('
      INSERT INTO [Preventive_Maintenance]
      ([PM ID],[Cycle],[Main Asset Number],[Reporting Branch],[Office],[User],[Checklist],[Remarks],[Technician],[Done],[Completed At],[Created At],[Updated At])
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
    ')->execute([
      next_pm_id(),
      $cycle,
      $dep['mainAssetNumber'] ?? $main,
      $dep['reportingBranch'] ?? '',
      $dep['office'] ?? '',
      $dep['user'] ?? '',
      $checklist,
      $remarks,
      $technician,
      $done,
      $completedAt,
      $now,
      $now
    ]);
  }

  foreach(($details['assignedAssets'] ?? []) as $asset){
    if(empty($asset['assetCode'])) continue;
    write_asset_history('Preventive Maintenance', $asset['assetCode'], $main, $dep['user'] ?? '', $dep['user'] ?? '', $dep['reportingBranch'] ?? '', $dep['office'] ?? '', $done === 'Yes' ? 'PM Done' : 'PM Pending', $remarks ?: 'Preventive maintenance checklist updated.', $technician);
  }
  return preventive_maintenance_list(['cycle'=>$cycle, 'branch'=>$p['branch'] ?? '']);
}
function dashboard_asset_bucket($asset){
  $status = strtolower(trim((string)($asset['assetStatus'] ?? '')));
  $user = trim((string)($asset['currentUser'] ?? ''));
  $main = trim((string)($asset['assignedMainAssetNo'] ?? ''));

  if(strpos($status,'repair')!==false || strpos($status,'maintenance')!==false || strpos($status,'service')!==false) return 'maintenance';
  if(strpos($status,'dispose')!==false || strpos($status,'retire')!==false || strpos($status,'scrap')!==false) return 'retired';
  if($user !== '' || $main !== '' || strpos($status,'deploy')!==false || strpos($status,'assign')!==false || strpos($status,'in use')!==false) return 'assigned';
  if(strpos($status,'ready')!==false || strpos($status,'available')!==false || strpos($status,'stock')!==false) return 'available';
  return 'unclassified';
}
function dashboard_data($filters=[]){
  $branch = trim((string)($filters['branch'] ?? ''));
  $assetFilters = $branch !== '' ? ['branch'=>$branch] : [];
  $workFilters = $branch !== '' ? ['branch'=>$branch] : [];
  $assets=all_assets($assetFilters)['items']; $work=workstation_list($workFilters)['items'];
  $deployedCodes=[];
  foreach($work as $w){
    foreach(['systemUnitCode','monitorCode','mouseCode','keyboardCode','upsCode','printerCode'] as $field){
      $code = strtoupper(trim((string)($w[$field] ?? '')));
      if($code !== '') $deployedCodes[$code] = true;
    }
  }
  $byCat=[]; $bySite=[]; $matrix=[]; $assigned=0; $ready=0; $repair=0; $disposed=0; $unclassified=0; $repairItems=[];
  $totalVal = 0;
  $currentVal = 0;
  foreach($assets as $a){
    $totalVal += (float)($a['purchasePrice'] ?? 0);
    $currentVal += (float)($a['currentValue'] ?? 0);
    $category = $a['assetCategory'] ?: 'Uncategorized';
    $site = $a['reportingBranch'] ?: 'Unassigned';
    if(!isset($byCat[$category])){
      $byCat[$category] = ['label'=>$category,'value'=>0,'assigned'=>0,'available'=>0,'maintenance'=>0,'retired'=>0,'unclassified'=>0];
    }
    $byCat[$category]['value']++;
    $bySite[$site] = ($bySite[$site] ?? 0)+1;
    $matrix[$site][$category] = ($matrix[$site][$category] ?? 0) + 1;
    $bucket = isset($deployedCodes[strtoupper($a['assetCode'])]) ? 'assigned' : dashboard_asset_bucket($a);
    if($bucket === 'assigned') $assigned++;
    if($bucket === 'available') $ready++;
    if($bucket === 'maintenance') $repair++;
    if($bucket === 'retired') $disposed++;
    if($bucket === 'unclassified') $unclassified++;
    $byCat[$category][$bucket]++;
    if($bucket === 'maintenance'){
      $repairItems[]=['assetCode'=>$a['assetCode'],'issue'=>$a['remarks'] ?: 'Requires maintenance review','repairStatus'=>'Open','dateReported'=>$a['updatedAt'] ?? ''];
    }
  }
  $pairs=function($m){ $o=[]; foreach($m as $k=>$v)$o[]=['label'=>$k,'value'=>$v]; return $o; };
  $history = asset_history();
  if($branch !== ''){
    $history = array_values(array_filter($history, fn($row)=>strcasecmp($row['reportingBranch'] ?? '', $branch) === 0));
  }
  $matrixCategories = array_keys($byCat);
  sort($matrixCategories);
  $matrixRows = [];
  foreach($matrix as $site=>$counts){
    $row = ['site'=>$site,'total'=>array_sum($counts),'counts'=>[]];
    foreach($matrixCategories as $category) $row['counts'][$category] = $counts[$category] ?? 0;
    $matrixRows[] = $row;
  }
  usort($matrixRows, fn($a,$b)=>strcmp($a['site'],$b['site']));
  return ['scope'=>['branch'=>$branch],'kpis'=>['totalAssets'=>count($assets),'totalDeployed'=>$assigned,'activeDeployments'=>count($work),'readyToDeploy'=>$ready,'inRepair'=>$repair,'disposed'=>$disposed,'unclassified'=>$unclassified,'totalValue'=>$totalVal,'totalCurrentValue'=>$currentVal],
    'statusCounts'=>['assigned'=>$assigned,'available'=>$ready,'maintenance'=>$repair,'retired'=>$disposed,'unclassified'=>$unclassified],
    'assetsByCategory'=>array_values($byCat),'assetsBySite'=>$pairs($bySite),
    'equipmentMatrix'=>['categories'=>$matrixCategories,'rows'=>$matrixRows],
    'recentDeploymentActivity'=>array_slice($history,0,10),'recentRepairActivity'=>array_slice($repairItems,0,10)];
}
function next_asset_code($category){
  global $assetTables; $prefix='AST'; $table='';
  foreach($assetTables as $cat=>$def){
    if(strcasecmp($cat,$category)==0){
      $prefix=$def['prefix'];
      $table=$def['table'];
      break;
    }
  }
  if ($table === '') {
    $stmt = db()->prepare("SELECT TOP 1 [PREFIX] FROM [Settings] WHERE UPPER([ASSET CATEGORY]) = ?");
    $stmt->execute([strtoupper($category)]);
    $customPrefix = $stmt->fetchColumn();
    if ($customPrefix) {
      $prefix = $customPrefix;
    }
    $table = 'System Units';
  }
  $stmt = db()->prepare('SELECT MAX([Asset Code]) FROM ' . qname($table) . ' WHERE [Asset Code] LIKE ?');
  $stmt->execute([$prefix . '-%']);
  $maxCode = $stmt->fetchColumn();
  $max = 0;
  if ($maxCode) {
    $suffix = substr($maxCode, strlen($prefix) + 1);
    if (preg_match('/^\d+$/', $suffix)) {
      $max = (int)$suffix;
    }
  }
  return $prefix.'-'.str_pad($max+1,4,'0',STR_PAD_LEFT);
}
function branch_code($site){
  $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim((string)$site)));
  if($code === 'DVO' || $code === 'DAVAO') return 'DV';
  return $code !== '' ? $code : 'DVO';
}
function main_no_prefix($site){
  return 'BSPI' . branch_code($site);
}
function format_main_no($site, $number){
  return main_no_prefix($site) . str_pad((int)$number, 5, '0', STR_PAD_LEFT);
}
function main_no_sequence($main, $site){
  $prefix = main_no_prefix($site);
  $value = strtoupper(trim((string)$main));
  if(!str_starts_with($value, $prefix)) return 0;
  $suffix = substr($value, strlen($prefix));
  return preg_match('/^\d+$/', $suffix) ? (int)$suffix : 0;
}
function identity_token($value){
  return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', trim((string)$value)));
}
function workstation_identity_key($asset){
  $person = trim((string)($asset['employeeId'] ?? '')) ?: employee_name_key($asset['currentUser'] ?? '');
  $serial = identity_token($asset['serialNumber'] ?? '');
  $code = identity_token($asset['assetCode'] ?? '');
  if($person !== '' && $serial !== '') return $person . '|SERIAL:' . $serial;
  if($person !== '' && $code !== '') return $person . '|ASSET:' . $code;
  return $code !== '' ? 'ASSET:' . $code : '';
}
function next_main_for_branch($branch, &$existingMaxByBranch){
  $branchKey = branch_code($branch);
  $existingMaxByBranch[$branchKey] = ($existingMaxByBranch[$branchKey] ?? 0) + 1;
  return format_main_no($branch, $existingMaxByBranch[$branchKey]);
}
function next_main_no($site){
  $prefix = main_no_prefix($site);
  $max = 0;
  
  $stmt = db()->prepare('SELECT MAX([Main Asset Number]) FROM [Deployments] WHERE [Main Asset Number] LIKE ?');
  $stmt->execute([$prefix . '%']);
  $maxDep = $stmt->fetchColumn();
  if ($maxDep) {
    $seq = main_no_sequence($maxDep, $site);
    if ($seq > $max) $max = $seq;
  }
  
  global $assetTables;
  foreach ($assetTables as $def) {
    $stmt = db()->prepare('SELECT MAX([Assigned Main Asset No.]) FROM ' . qname($def['table']) . ' WHERE [Assigned Main Asset No.] LIKE ?');
    $stmt->execute([$prefix . '%']);
    $maxAsset = $stmt->fetchColumn();
    if ($maxAsset) {
      $seq = main_no_sequence($maxAsset, $site);
      if ($seq > $max) $max = $seq;
    }
  }
  
  return format_main_no($site, $max + 1);
}
function persist_generated_main_asset_numbers(){
  global $assetTables;
  $all = all_assets([])['items'];
  $identityMainLookup = [];
  $existingMaxByBranch = [];
  $seenSystemUnitMain = [];

  foreach(all_rows('Deployments') as $row){
    $branch = val($row, 'Reporting Branch') ?: 'DVO';
    $key = branch_code($branch);
    $existingMaxByBranch[$key] = max($existingMaxByBranch[$key] ?? 0, main_no_sequence(val($row, 'Main Asset Number'), $branch));
  }
  foreach($all as $asset){
    $branch = $asset['reportingBranch'] ?: 'DVO';
    $key = branch_code($branch);
    $existingMaxByBranch[$key] = max($existingMaxByBranch[$key] ?? 0, main_no_sequence($asset['assignedMainAssetNo'] ?? '', $branch));
    $main = trim((string)($asset['assignedMainAssetNo'] ?? ''));
    $identityKey = workstation_identity_key($asset);
    if($main !== '' && $identityKey !== '' && !isset($identityMainLookup[$identityKey])) $identityMainLookup[$identityKey] = $main;
  }

  foreach($all as $asset){
    if(strcasecmp($asset['assetCategory'] ?? '', 'System Unit') !== 0) continue;
    $identityKey = workstation_identity_key($asset);
    if($identityKey === '' || isset($identityMainLookup[$identityKey])) continue;
    $branch = $asset['reportingBranch'] ?: 'DVO';
    $identityMainLookup[$identityKey] = next_main_for_branch($branch, $existingMaxByBranch);
  }

  foreach($all as $asset){
    $table = $assetTables[$asset['assetCategory']]['table'] ?? '';
    if($table === '') continue;

    $main = trim((string)($asset['assignedMainAssetNo'] ?? ''));
    $identityKey = workstation_identity_key($asset);
    if($main === ''){
      $main = $identityMainLookup[$identityKey] ?? '';
      if($main === '') continue;
    }

    if(strcasecmp($asset['assetCategory'] ?? '', 'System Unit') === 0){
      $mainKey = strtoupper($main);
      if($mainKey !== '' && isset($seenSystemUnitMain[$mainKey])){
        $main = next_main_for_branch($asset['reportingBranch'] ?: 'DVO', $existingMaxByBranch);
        if($identityKey !== '') $identityMainLookup[$identityKey] = $main;
      }
      if($mainKey !== '') $seenSystemUnitMain[strtoupper($main)] = true;
    }

    if(strcasecmp(trim((string)($asset['assignedMainAssetNo'] ?? '')), $main) !== 0){
      db()->prepare('UPDATE '.qname($table).' SET [Assigned Main Asset No.]=? WHERE [Asset Code]=?')->execute([$main, $asset['assetCode']]);
    }
  }
}

function normalize_header_key($value){
  return preg_replace('/[^a-z0-9]+/', '', strtolower(trim((string)$value)));
}

function row_value_from_aliases($row, $aliases, $default = ''){
  $lookup = [];
  foreach($row as $key => $value){
    $lookup[normalize_header_key($key)] = $value;
  }
  foreach((array)$aliases as $alias){
    $key = normalize_header_key($alias);
    if(array_key_exists($key, $lookup)){
      $value = trim((string)$lookup[$key]);
      if($value !== '') return $value;
    }
  }
  return $default;
}

function serial_identity_token($value){
  $raw = strtolower(trim((string)$value));
  $emptyTokens = ['','n/a','na','none','no serial','noserial','no serial number','-','--'];
  if(in_array($raw, $emptyTokens, true)) return '';
  return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $raw));
}

function serial_duplicate_is_warning($category){
  return in_array(strtolower(trim((string)$category)), ['keyboard','mouse'], true);
}

function find_asset_by_serial($serial, $category=''){
  $needle = serial_identity_token($serial);
  if($needle === '') return null;
  foreach(all_assets([])['items'] as $asset){
    if($category !== '' && strcasecmp($asset['assetCategory'] ?? '', $category) !== 0) continue;
    if(serial_identity_token($asset['serialNumber'] ?? '') === $needle) return $asset;
  }
  return null;
}

function asset_category_name($value, $fallback = 'System Unit'){
  global $assetTables;
  $candidate = trim((string)$value);
  $aliases = [
    'System Units' => 'System Unit',
    'Laptops' => 'Laptop',
    'Monitors' => 'Monitor',
    'Mouse' => 'Mouse',
    'Mice' => 'Mouse',
    'Keyboards' => 'Keyboard',
    'UPS' => 'UPS',
    'Printers' => 'Printer',
  ];
  foreach($aliases as $alias => $category){
    if(strcasecmp($alias, $candidate) === 0) return $category;
  }
  foreach(array_keys($assetTables) as $category){
    if(strcasecmp($category, $candidate) === 0) return $category;
  }
  return $fallback;
}

function asset_depreciation_meta($purchasePrice, $purchaseDate, $expectedLifeYears){
  $price = (float)$purchasePrice;
  $years = (float)$expectedLifeYears;
  $residual = $price > 0 ? round($price * 0.10, 2) : '';
  $current = $price;
  $deviceEol = '';
  $fullyDepreciatedDate = '';

  if($purchaseDate && $years > 0){
    try {
      $purchase = new DateTimeImmutable((string)$purchaseDate);
      $end = $purchase->modify('+' . (int)$years . ' years');
      $deviceEol = $end->format('Y-m-d');
      $fullyDepreciatedDate = $deviceEol;

      if($price > 0){
        $now = new DateTimeImmutable('now');
        $total = max(1, $end->getTimestamp() - $purchase->getTimestamp());
        $elapsed = max(0, min($now->getTimestamp() - $purchase->getTimestamp(), $total));
        $fraction = $elapsed / $total;
        $depreciable = $price - $residual;
        $current = max((float)$residual, $price - ($depreciable * $fraction));
      }
    } catch(Throwable $e) {
      // Ignore malformed dates and keep the basic values.
    }
  }

  return [
    'residualValue' => $residual,
    'currentValue' => round((float)$current, 2),
    'deviceEol' => $deviceEol,
    'fullyDepreciatedDate' => $fullyDepreciatedDate,
  ];
}

function excel_serial_to_date($value){
  $serial = (float)$value;
  if(!$serial) return '';
  $base = new DateTimeImmutable('1899-12-30');
  $days = (int)floor($serial);
  $fraction = $serial - $days;
  $seconds = (int)round($fraction * 86400);
  return $base->modify("+{$days} days")->modify("+{$seconds} seconds")->format('Y-m-d');
}

function xlsx_text($node){
  if(!$node) return '';
  $text = '';
  foreach($node->xpath('.//t') ?: [] as $part){
    $text .= (string)$part;
  }
  return $text !== '' ? $text : trim((string)$node);
}

function xlsx_column_index($ref){
  if(!preg_match('/^([A-Z]+)(\d+)$/i', (string)$ref, $m)) return 0;
  $letters = strtoupper($m[1]);
  $index = 0;
  for($i = 0; $i < strlen($letters); $i++){
    $index = ($index * 26) + (ord($letters[$i]) - 64);
  }
  return $index;
}

function spreadsheetml_column_index($ref){
  if(!preg_match('/^([A-Z]+)(\d+)$/i', (string)$ref, $m)) return 0;
  return xlsx_column_index($m[1] . $m[2]);
}

function spreadsheetml_sheet_rows($sheetNode, $sheetName = ''){
  $rows = [];
  $headers = [];
  $rowIndex = 0;

  foreach($sheetNode->Table->Row as $rowNode){
    $cells = [];
    $colIndex = 0;
    foreach($rowNode->Cell as $cellNode){
      $ref = (string)($cellNode['ss:Index'] ?? $cellNode->attributes('ss', true)['Index'] ?? '');
      if($ref !== ''){
        $colIndex = max(0, ((int)$ref) - 1);
      }
      $dataNode = $cellNode->Data;
      $value = $dataNode !== null ? (string)$dataNode : '';
      $cells[$colIndex] = trim($value);
      $colIndex++;
    }
    if(!empty($cells)){
      ksort($cells);
    }
    $maxIndex = empty($cells) ? -1 : max(array_keys($cells));
    $values = [];
    for($i = 0; $i <= $maxIndex; $i++){
      $values[$i] = $cells[$i] ?? '';
    }

    if($rowIndex === 0){
      $headers = $values;
      $rowIndex++;
      continue;
    }

    $mapped = [];
    foreach($headers as $idx => $header){
      $header = trim((string)$header);
      if($header === '') continue;
      $mapped[$header] = $values[$idx] ?? '';
    }
    if(array_filter($mapped, fn($value) => trim((string)$value) !== '')){
      $mapped['__sheetName'] = $sheetName;
      $rows[] = $mapped;
    }
    $rowIndex++;
  }

  return $rows;
}

function safe_simplexml_load_string($xml) {
  $oldEntityLoader = false;
  if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
    $oldEntityLoader = libxml_disable_entity_loader(true);
  }
  libxml_use_internal_errors(true);
  $doc = simplexml_load_string($xml);
  if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
    libxml_disable_entity_loader($oldEntityLoader);
  }
  return $doc;
}

function spreadsheet_rows_from_sheet_xml($sheetXml, $sharedStrings, $sheetName = ''){
  $sheet = safe_simplexml_load_string($sheetXml);
  if(!$sheet || !isset($sheet->sheetData->row)) return [];

  $rows = [];
  $headers = [];
  $rowIndex = 0;

  foreach($sheet->sheetData->row as $row){
    $cells = [];
    foreach($row->c as $cell){
      $ref = (string)($cell['r'] ?? '');
      $colIndex = xlsx_column_index($ref);
      if($colIndex < 1) continue;
      $type = (string)($cell['t'] ?? '');
      $value = '';
      if($type === 's'){
        $sharedIndex = (int)((string)($cell->v ?? '0'));
        $value = $sharedStrings[$sharedIndex] ?? '';
      } elseif($type === 'inlineStr'){
        $value = xlsx_text($cell->is);
      } elseif(isset($cell->v)) {
        $value = (string)$cell->v;
      }
      $cells[$colIndex - 1] = trim((string)$value);
    }
    if(!empty($cells)){
      ksort($cells);
    }
    $maxIndex = empty($cells) ? -1 : max(array_keys($cells));
    $values = [];
    for($i = 0; $i <= $maxIndex; $i++){
      $values[$i] = $cells[$i] ?? '';
    }

    if($rowIndex === 0){
      $headers = $values;
      $rowIndex++;
      continue;
    }

    $mapped = [];
    foreach($headers as $idx => $header){
      $header = trim((string)$header);
      if($header === '') continue;
      $mapped[$header] = $values[$idx] ?? '';
    }
    if(array_filter($mapped, fn($value) => trim((string)$value) !== '')){
      $mapped['__sheetName'] = $sheetName;
      $rows[] = $mapped;
    }
    $rowIndex++;
  }

  return $rows;
}

function spreadsheet_rows_csv($path){
  $handle = fopen($path, 'rb');
  if(!$handle) throw new Exception('Unable to read CSV file.');

  $headers = [];
  $rows = [];
  $rowIndex = 0;

  while(($data = fgetcsv($handle)) !== false){
    if($rowIndex === 0){
      $headers = array_map(function($value){
        return preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value));
      }, $data);
      $rowIndex++;
      continue;
    }

    $row = [];
    foreach($headers as $idx => $header){
      if($header === '') continue;
      $row[$header] = isset($data[$idx]) ? trim((string)$data[$idx]) : '';
    }
    if(array_filter($row, fn($value) => trim((string)$value) !== '')){
      $rows[] = $row;
    }
  }

  fclose($handle);
  return $rows;
}

function spreadsheet_rows_xlsx($path){
  if(!class_exists('ZipArchive')) throw new Exception('XLSX import requires the ZipArchive extension.');
  $zip = new ZipArchive();
  $open = $zip->open($path);
  if($open !== true) throw new Exception('Unable to open XLSX file.');

  $sharedStrings = [];
  $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
  if($sharedXml !== false){
    $sharedDoc = safe_simplexml_load_string($sharedXml);
    if($sharedDoc && isset($sharedDoc->si)){
      foreach($sharedDoc->si as $si){
        $sharedStrings[] = xlsx_text($si);
      }
    }
  }

  $rows = [];
  $skipSheets = ['examples', 'example', 'readme', 'notes', 'instructions'];

  $workbookXml = $zip->getFromName('xl/workbook.xml');
  $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
  if($workbookXml === false || $relsXml === false){
    $zip->close();
    throw new Exception('Unable to read workbook metadata.');
  }

  $workbook = safe_simplexml_load_string($workbookXml);
  $relsDoc = safe_simplexml_load_string($relsXml);
  if(!$workbook || !$relsDoc){
    $zip->close();
    throw new Exception('Unable to parse workbook metadata.');
  }

  $rels = [];
  foreach($relsDoc->Relationship as $rel){
    $rels[(string)$rel['Id']] = (string)$rel['Target'];
  }

  $sheets = $workbook->sheets && $workbook->sheets->sheet ? $workbook->sheets->sheet : [];
  foreach($sheets as $sheetNode){
    $sheetName = strtolower(trim((string)$sheetNode['name']));
    if(in_array($sheetName, $skipSheets, true)) continue;

    $rid = (string)$sheetNode->attributes('r', true)['id'];
    if(!$rid || empty($rels[$rid])) continue;

    $target = ltrim($rels[$rid], '/');
    if(str_starts_with($target, 'worksheets/')) {
      $target = 'xl/' . $target;
    } elseif(!str_starts_with($target, 'xl/')) {
      $target = 'xl/' . $target;
    }

    $sheetXml = $zip->getFromName($target);
    if($sheetXml === false) continue;

    $rows = array_merge($rows, spreadsheet_rows_from_sheet_xml($sheetXml, $sharedStrings, (string)$sheetNode['name']));
  }

  $zip->close();
  return $rows;
}

function spreadsheet_rows($path, $originalName){
  $ext = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));
  if($ext === 'csv') return spreadsheet_rows_csv($path);
  if($ext === 'xlsx') return spreadsheet_rows_xlsx($path);
  if($ext === 'xls') return spreadsheet_rows_spreadsheetml($path);
  throw new Exception('Only .xlsx, .xls, and .csv files are supported.');
}

function spreadsheet_rows_spreadsheetml($path){
  $xml = file_get_contents($path);
  if($xml === false) throw new Exception('Unable to read XLS file.');
  $doc = safe_simplexml_load_string($xml);
  if(!$doc) throw new Exception('Unable to parse XLS file.');

  $doc->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
  $skipSheets = ['examples', 'example', 'readme', 'notes', 'instructions'];
  $rows = [];

  foreach($doc->Worksheet as $sheetNode){
    $sheetName = strtolower(trim((string)$sheetNode['ss:Name'] ?? $sheetNode->attributes('ss', true)['Name'] ?? ''));
    if(in_array($sheetName, $skipSheets, true)) continue;
    $rows = array_merge($rows, spreadsheetml_sheet_rows($sheetNode, (string)($sheetNode['ss:Name'] ?? $sheetNode->attributes('ss', true)['Name'] ?? '')));
  }

  return $rows;
}

function lookup_assets_by_user($user){
  $target = trim((string)$user);
  if($target === '') throw new Exception('Current user is required.');

  $normalized = strtoupper($target);
  $stmt = db()->prepare('SELECT TOP 1 * FROM [Deployments] WHERE UPPER(LTRIM(RTRIM([User]))) = ? ORDER BY [Updated At] DESC, [Created At] DESC');
  $stmt->execute([$normalized]);
  $deployment = $stmt->fetch(PDO::FETCH_ASSOC);

  $linkedAssets = [];
  $mainAssetNumber = '';
  $reportingBranch = '';
  $office = '';
  $dateDeployed = '';
  $displayUser = $target;

  if($deployment){
    $dep = normalize_deployment($deployment, 0);
    $displayUser = $dep['user'] ?: $target;
    $mainAssetNumber = $dep['mainAssetNumber'];
    $reportingBranch = $dep['reportingBranch'];
    $office = $dep['office'];
    $dateDeployed = $dep['dateDeployed'];

    foreach(array_filter([
      $dep['systemUnitCode'],
      $dep['monitorCode'],
      $dep['mouseCode'],
      $dep['keyboardCode'],
      $dep['upsCode'],
      $dep['printerCode'],
    ]) as $code){
      $found = find_asset($code);
      if($found){
        [$cat, $def, $row] = $found;
        $linkedAssets[] = [
          'assetCode' => val($row, 'Asset Code'),
          'assetCategory' => val($row, 'Asset Category', $cat),
          'brand' => val($row, 'Brand'),
          'model' => val($row, 'Model'),
          'serialNumber' => val($row, 'Serial Number'),
          'assignedMainAssetNo' => val($row, 'Assigned Main Asset No.'),
          'currentUser' => val($row, 'Current User'),
          'assetStatus' => val($row, 'Asset Status'),
          'reportingBranch' => val($row, 'Reporting Branch'),
          'office' => val($row, 'Office'),
        ];
      }
    }
  }

  foreach(all_assets([])['items'] as $asset){
    if(strcasecmp($asset['currentUser'] ?? '', $target) !== 0) continue;
    $exists = false;
    foreach($linkedAssets as $item){
      if(strcasecmp($item['assetCode'] ?? '', $asset['assetCode'] ?? '') === 0){
        $exists = true;
        break;
      }
    }
    if(!$exists) $linkedAssets[] = $asset;
  }

  if(!$mainAssetNumber && !empty($linkedAssets)){
    $mainAssetNumber = $linkedAssets[0]['assignedMainAssetNo'] ?? '';
    $reportingBranch = $reportingBranch ?: ($linkedAssets[0]['reportingBranch'] ?? '');
    $office = $office ?: ($linkedAssets[0]['office'] ?? '');
  }
  if(!$dateDeployed && !empty($linkedAssets)){
    $dateDeployed = $linkedAssets[0]['purchaseDate'] ?? '';
  }

  return [
    'found' => $deployment || !empty($linkedAssets),
    'user' => $displayUser,
    'mainAssetNumber' => $mainAssetNumber,
    'reportingBranch' => $reportingBranch,
    'office' => $office,
    'dateDeployed' => $dateDeployed,
    'linkedAssets' => $linkedAssets,
    'message' => $deployment
      ? 'Deployment record found.'
      : (!empty($linkedAssets) ? 'Matching assets found for this user.' : 'No deployment or asset rows matched that user.'),
  ];
}

function import_assets_from_spreadsheet($payload){
  global $assetTables;

  if(empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])){
    throw new Exception('Please choose an Excel or CSV file first.');
  }

  $file = $_FILES['file'];
  $rows = spreadsheet_rows($file['tmp_name'], $file['name']);
  if(!$rows) throw new Exception('The spreadsheet does not contain any data rows.');

  $defaults = settings_data();
  $defaultCategory = asset_category_name($payload['defaultCategory'] ?? 'System Unit');
  $defaultBranch = trim((string)($payload['defaultBranch'] ?? ''));
  $defaultOffice = trim((string)($payload['defaultOffice'] ?? ''));
  $defaultStatus = trim((string)($payload['defaultStatus'] ?? 'Ready to Deploy')) ?: 'Ready to Deploy';
  $encodedBy = trim((string)($payload['encodedBy'] ?? 'Admin')) ?: 'Admin';
  $eolMap = $defaults['defaultEolYearsByCategory'] ?? [];

  $created = 0;
  $skipped = 0;
  $errors = [];
  $duplicates = [];
  $warnings = [];
  $seenCodes = [];
  $seenSerials = [];

  foreach($rows as $idx => $row){
    $rowNumber = $idx + 2;
    $sheetCategory = asset_category_name($row['__sheetName'] ?? '', $defaultCategory);
    $category = asset_category_name(row_value_from_aliases($row, ['Asset Category', 'Category'], $sheetCategory), $sheetCategory);
    $assetCode = trim((string)row_value_from_aliases($row, ['Asset Code', 'Code'], ''));
    if($assetCode === '') {
      $assetCode = next_asset_code($category);
    }

    $assetCodeKey = strtoupper($assetCode);
    if($assetCodeKey !== '' && isset($seenCodes[$assetCodeKey])){
      $duplicates[] = [
        'row' => $rowNumber,
        'type' => 'Asset Code',
        'assetCode' => $assetCode,
        'category' => $category,
        'existingAssetCode' => $seenCodes[$assetCodeKey],
        'action' => 'Skipped',
        'message' => 'Duplicate asset code already appeared in this upload.'
      ];
      $skipped++;
      continue;
    }

    if($assetCode !== '' && find_asset($assetCode)){
      $duplicates[] = [
        'row' => $rowNumber,
        'type' => 'Asset Code',
        'assetCode' => $assetCode,
        'category' => $category,
        'existingAssetCode' => $assetCode,
        'action' => 'Skipped',
        'message' => 'Asset code already exists in the registry.'
      ];
      $skipped++;
      continue;
    }
    if($assetCodeKey !== '') $seenCodes[$assetCodeKey] = $assetCode;

    $purchaseDate = row_value_from_aliases($row, ['Purchase Date'], '');
    if(is_numeric($purchaseDate) && (int)$purchaseDate > 0){
      $purchaseDate = excel_serial_to_date($purchaseDate);
    }
    if($purchaseDate !== ''){
      $purchaseDate = norm_date($purchaseDate);
    }

    $dateDeployment = row_value_from_aliases($row, ['Date Deployment', 'Date Deployed'], '');
    if(is_numeric($dateDeployment) && (int)$dateDeployment > 0){
      $dateDeployment = excel_serial_to_date($dateDeployment);
    }
    if($dateDeployment !== ''){
      $dateDeployment = norm_date($dateDeployment);
    }
    if($purchaseDate === '' && $dateDeployment !== ''){
      $purchaseDate = $dateDeployment;
    }

    $purchasePrice = row_value_from_aliases($row, ['Purchase Price', 'Price'], '0');
    $expectedLifeYears = row_value_from_aliases($row, ['Expected Life (Years)', 'Expected Life', 'EOL Years'], $eolMap[$category] ?? '');
    $depreciation = asset_depreciation_meta($purchasePrice, $purchaseDate, $expectedLifeYears);

    $serialNumber = row_value_from_aliases($row, ['Serial Number', 'Serial'], '');
    $serialKey = serial_identity_token($serialNumber);
    if($serialKey !== ''){
      $existingSerialAsset = find_asset_by_serial($serialNumber, $category);
      $seenSerialAsset = $seenSerials[$category][$serialKey] ?? null;
      $serialMatch = $existingSerialAsset ?: ($seenSerialAsset ? ['assetCode'=>$seenSerialAsset] : null);
      if($serialMatch){
        $detail = [
          'row' => $rowNumber,
          'type' => 'Serial Number',
          'assetCode' => $assetCode,
          'category' => $category,
          'serialNumber' => $serialNumber,
          'existingAssetCode' => $serialMatch['assetCode'] ?? '',
          'action' => serial_duplicate_is_warning($category) ? 'Imported with warning' : 'Skipped',
          'message' => serial_duplicate_is_warning($category)
            ? 'Duplicate serial allowed for this peripheral category.'
            : 'Serial number already exists for this category.'
        ];
        if(serial_duplicate_is_warning($category)){
          $warnings[] = $detail;
        } else {
          $duplicates[] = $detail;
          $skipped++;
          continue;
        }
      }
      $seenSerials[$category][$serialKey] = $assetCode;
    }

    $payloadRow = [
      'assetCode' => $assetCode,
      'assetCategory' => $category,
      'reportingBranch' => row_value_from_aliases($row, ['Reporting Branch', 'Branch'], $defaultBranch),
      'office' => row_value_from_aliases($row, ['Office', 'Department', 'Department / Office'], $defaultOffice),
      'assetStatus' => row_value_from_aliases($row, ['Asset Status', 'Status'], $defaultStatus),
      'brand' => row_value_from_aliases($row, ['Brand'], ''),
      'model' => row_value_from_aliases($row, ['Model'], ''),
      'serialNumber' => $serialNumber,
      'employeeId' => row_value_from_aliases($row, ['Employee ID', 'Employee No.', 'Employee Number', 'Emp ID'], ''),
      'currentUser' => row_value_from_aliases($row, ['Current User', 'User', 'Assigned To'], ''),
      'assignedMainAssetNo' => row_value_from_aliases($row, ['Assigned Main Asset No.', 'Assigned Main Asset No', 'Main Asset No.', 'Main Asset Number'], ''),
      'dateDeployment' => $dateDeployment,
      'purchaseDate' => $purchaseDate,
      'purchasePrice' => $purchasePrice,
      'expectedLifeYears' => $expectedLifeYears,
      'residualValue' => row_value_from_aliases($row, ['Residual Value', 'Residual Value (10%)'], $depreciation['residualValue']),
      'currentValue' => row_value_from_aliases($row, ['Current Value'], $depreciation['currentValue']),
      'deviceEol' => row_value_from_aliases($row, ['Device EOL'], $depreciation['deviceEol']),
      'fullyDepreciatedDate' => row_value_from_aliases($row, ['Fully Depreciated Date'], $depreciation['fullyDepreciatedDate']),
      'encodedBy' => row_value_from_aliases($row, ['Encoded By', 'EncodedBy'], $encodedBy),
      'remarks' => row_value_from_aliases($row, ['Remarks', 'Notes'], ''),
      'processor' => row_value_from_aliases($row, ['Processor'], ''),
      'ram' => row_value_from_aliases($row, ['RAM'], ''),
      'storage' => row_value_from_aliases($row, ['Storage'], ''),
      'operatingSystem' => row_value_from_aliases($row, ['Operating System', 'OS'], ''),
      'pcName' => row_value_from_aliases($row, ['PC Name'], ''),
      'laptopName' => row_value_from_aliases($row, ['Laptop Name'], ''),
      'macAddress' => row_value_from_aliases($row, ['MAC Address'], ''),
      'ipAddress' => row_value_from_aliases($row, ['IP Address'], ''),
      'capacityVa' => row_value_from_aliases($row, ['Capacity (VA)', 'Capacity VA'], ''),
      'printerType' => row_value_from_aliases($row, ['Printer Type'], ''),
    ];

    try {
      create_asset($payloadRow);
      $created++;
    } catch(Throwable $e){
      $errors[] = 'Row ' . ($idx + 2) . ': ' . $e->getMessage();
    }
  }

  return [
    'createdCount' => $created,
    'skippedCount' => $skipped,
    'duplicateCount' => count($duplicates),
    'warningCount' => count($warnings),
    'duplicates' => $duplicates,
    'warnings' => $warnings,
    'errors' => $errors,
    'message' => $errors ? ('Imported with ' . count($errors) . ' row error(s).') : 'Import completed.',
  ];
}

function asset_write_package($cat, $p, $now, $createdAt = null){
  if(strcasecmp($cat, 'Laptop') === 0) $p['assignedMainAssetNo'] = '';
  $meta = asset_depreciation_meta($p['purchasePrice'] ?? 0, $p['purchaseDate'] ?? '', $p['expectedLifeYears'] ?? 0);
  $baseCols = ['Asset Code','Asset Category','Reporting Branch','Office','Assigned Main Asset No.','Employee ID','Current User','Asset Status','Brand','Model','Serial Number'];
  $baseVals = [
    $p['assetCode'] ?? '',
    $cat,
    $p['reportingBranch'] ?? '',
    $p['office'] ?? '',
    $p['assignedMainAssetNo'] ?? '',
    $p['employeeId'] ?? '',
    $p['currentUser'] ?? '',
    $p['assetStatus'] ?? 'Ready to Deploy',
    $p['brand'] ?? '',
    $p['model'] ?? '',
    $p['serialNumber'] ?? ''
  ];
  $extraCols = ['Date Deployed','Purchase Date','Purchase Price','Expected Life (Years)','Residual Value','Current Value','Device EOL','Fully Depreciated Date','Remarks','Created At','Updated At','Encoded By'];
  $extraVals = [
    $p['dateDeployment'] ?? '',
    $p['purchaseDate'] ?? '',
    $p['purchasePrice'] ?? 0,
    $p['expectedLifeYears'] ?? 0,
    $p['residualValue'] ?? $meta['residualValue'],
    $p['currentValue'] ?? $meta['currentValue'],
    $p['deviceEol'] ?? $meta['deviceEol'],
    $p['fullyDepreciatedDate'] ?? $meta['fullyDepreciatedDate'],
    $p['remarks'] ?? '',
    $createdAt ?? $now,
    $now,
    $p['encodedBy'] ?? 'Admin'
  ];

  $cols = array_merge($baseCols, $extraCols);
  $vals = array_merge($baseVals, $extraVals);

  switch($cat){
    case 'System Unit':
      array_splice($cols, 10, 0, ['Processor','RAM','Storage','Operating System','PC Name','MAC Address','IP Address']);
      array_splice($vals, 10, 0, [$p['processor'] ?? '', $p['ram'] ?? '', $p['storage'] ?? '', $p['operatingSystem'] ?? '', $p['pcName'] ?? '', $p['macAddress'] ?? '', $p['ipAddress'] ?? '']);
      break;
    case 'Laptop':
      array_splice($cols, 10, 0, ['Processor','RAM','Storage','Operating System','Laptop Name','MAC Address']);
      array_splice($vals, 10, 0, [$p['processor'] ?? '', $p['ram'] ?? '', $p['storage'] ?? '', $p['operatingSystem'] ?? '', $p['laptopName'] ?? '', $p['macAddress'] ?? '']);
      break;
    case 'UPS':
      array_splice($cols, 10, 0, ['Capacity (VA)']);
      array_splice($vals, 10, 0, [$p['capacityVa'] ?? '']);
      break;
    case 'Printer':
      array_splice($cols, 10, 0, ['Printer Type']);
      array_splice($vals, 10, 0, [$p['printerType'] ?? '']);
      break;
  }

  return [$cols, $vals];
}

function create_asset($p){
  global $assetTables; $cat=$p['assetCategory'] ?? $p['category'] ?? 'System Unit'; $def=$assetTables[$cat] ?? $assetTables['System Unit'];
  $code=trim((string)($p['assetCode'] ?? ''));
  if($code === '') $code = next_asset_code($cat);
  $now=date('Y-m-d H:i:s');
  [$cols, $vals] = asset_write_package($cat, array_merge($p, ['assetCode' => $code]), $now, $now);
  $sql='INSERT INTO '.qname($def['table']).' ('.implode(',',array_map('qname',$cols)).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
  db()->prepare($sql)->execute($vals);
  return asset_details($code);
}

function update_asset($p){
  $code = trim((string)($p['assetCode'] ?? ''));
  if($code === '') throw new Exception('Asset Code is required for updates.');

  $found = find_asset($code);
  if(!$found) throw new Exception('Asset not found.');

  [$cat, $def, $r] = $found;
  $targetCat = asset_category_name($p['assetCategory'] ?? $cat, $cat);
  $now = date('Y-m-d H:i:s');
  [$cols, $vals] = asset_write_package($cat, array_merge($p, ['assetCode' => $code, 'assetCategory' => $targetCat]), $now, val($r, 'Created At') ?: $now);

  $assignments = [];
  $params = [];
  foreach($cols as $i => $col){
    if($col === 'Asset Code') continue;
    $assignments[] = qname($col) . '=?';
    $params[] = $vals[$i];
  }
  $params[] = $code;

  $sql = 'UPDATE ' . qname($def['table']) . ' SET ' . implode(',', $assignments) . ' WHERE UPPER([Asset Code]) = ?';
  db()->prepare($sql)->execute($params);
  return asset_details($code);
}

function upload_asset_photo($p){
  $code = trim((string)($p['assetCode'] ?? ''));
  if($code === '') throw new Exception('Asset Code is required for photo upload.');
  if(!find_asset($code)) throw new Exception('Asset not found.');
  if(empty($_FILES['photo']) || !is_uploaded_file($_FILES['photo']['tmp_name'])) throw new Exception('Please choose an image first.');

  $file = $_FILES['photo'];
  if(($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new Exception('Image upload failed. Please try again.');
  if(($file['size'] ?? 0) <= 0) throw new Exception('Uploaded image is empty.');
  if(($file['size'] ?? 0) > 5 * 1024 * 1024) throw new Exception('Image must be 5 MB or smaller.');

  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','gif','webp'];
  if(!in_array($ext, $allowed, true)) throw new Exception('Only JPG, PNG, GIF, or WEBP images are allowed.');

  $imageInfo = @getimagesize($file['tmp_name']);
  if(!$imageInfo || empty($imageInfo['mime'])) throw new Exception('Uploaded file is not a valid image.');
  $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp'];
  if(!in_array(strtolower($imageInfo['mime']), $allowedMimes, true)) throw new Exception('Only JPG, PNG, GIF, or WEBP images are allowed.');
  if(($imageInfo[0] ?? 0) < 1 || ($imageInfo[1] ?? 0) < 1) throw new Exception('Uploaded image dimensions are invalid.');
  if(($imageInfo[0] ?? 0) > 6000 || ($imageInfo[1] ?? 0) > 6000) throw new Exception('Image dimensions must be 6000px or smaller.');

  $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper($code));
  $dir = __DIR__ . '/uploads/asset_images';
  if(!is_dir($dir) && !mkdir($dir, 0775, true)) throw new Exception('Unable to create upload folder.');

  foreach($allowed as $oldExt){
    $old = $dir . '/' . $safe . '.' . $oldExt;
    if(is_file($old)) @unlink($old);
  }

  $dest = $dir . '/' . $safe . '.' . $ext;
  if(!move_uploaded_file($file['tmp_name'], $dest)) throw new Exception('Unable to save asset photo.');

  return ['photoUrl' => asset_photo_url($code)];
}

function create_workstation($p){
  $employee = null;
  $employeeInput = trim((string)($p['employeeId'] ?? '')) ?: trim((string)($p['user'] ?? ''));
  if($employeeInput !== '') $employee = find_employee_by_identifier($employeeInput);
  if(!$employee && !empty($p['allowCreateEmployee']) && trim((string)($p['user'] ?? '')) !== ''){
    $employee = create_employee([
      'employeeName' => $p['user'],
      'reportingBranch' => $p['reportingBranch'] ?? '',
      'office' => $p['office'] ?? '',
      'active' => 'Yes',
      'idSource' => 'Workstation'
    ]);
  }
  if($employee){
    $p['employeeId'] = $employee['employeeId'];
    $p['user'] = $employee['employeeName'];
    if(empty($p['reportingBranch'])) $p['reportingBranch'] = $employee['reportingBranch'];
    if(empty($p['office'])) $p['office'] = $employee['office'];
  }
  $main=trim((string)($p['mainAssetNumber'] ?? ''));
  if($main === '') $main=next_main_no($p['reportingBranch'] ?? 'DVO');
  $now=date('Y-m-d H:i:s');
  $cols=['Main Asset Number','Reporting Branch','Employee ID','User','Office','System Unit Code','Monitor Code','Mouse Code','Keyboard Code','UPS Code','Printer Code','Date Deployed','Active','Deployment Status','Remarks','Created At','Updated At','Encoded By'];
  $vals=[$main,$p['reportingBranch'] ?? '',$p['employeeId'] ?? '',$p['user'] ?? '',$p['office'] ?? '',$p['systemUnitCode'] ?? '',$p['monitorCode'] ?? '',$p['mouseCode'] ?? '',$p['keyboardCode'] ?? '',$p['upsCode'] ?? '',$p['printerCode'] ?? '',$p['dateDeployed'] ?? date('Y-m-d'),$p['active'] ?? 'Yes',$p['deploymentStatus'] ?? 'Deployed',$p['remarks'] ?? '',$now,$now,$p['encodedBy'] ?? 'Admin'];
  db()->prepare('INSERT INTO [Deployments] ('.implode(',',array_map('qname',$cols)).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute($vals);
  return workstation_details($main);
}

function add_setting_value($group, $payload) {
  $mapping = [
    'sites' => 'SITES',
    'offices' => 'DEPARTMENTS / OFFICES',
    'statuses' => 'ASSET STATUSES',
    'deploymentStatuses' => 'DEPLOYMENT STATUSES',
    'prefixes' => 'PREFIX'
  ];
  
  if ($group === 'categories') {
    $category = trim((string)($payload['category'] ?? ''));
    $prefix = trim((string)($payload['prefix'] ?? ''));
    $eol = trim((string)($payload['eol'] ?? '5'));
    
    if ($category === '') throw new Exception("Category name is required.");
    
    $stmt = db()->prepare("SELECT COUNT(*) FROM [Settings] WHERE UPPER([ASSET CATEGORY]) = ?");
    $stmt->execute([strtoupper($category)]);
    if ($stmt->fetchColumn() > 0) throw new Exception("Category already exists.");
    
    $stmt = db()->prepare("SELECT COUNT(*) FROM [Settings] WHERE [ASSET CATEGORY] IS NULL");
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
      $updateSql = "UPDATE TOP (1) [Settings] SET [ASSET CATEGORY] = ?, [PREFIX] = ?, [DEFAULT EOL (YEARS)] = ? WHERE [ASSET CATEGORY] IS NULL";
      db()->prepare($updateSql)->execute([$category, $prefix, $eol]);
    } else {
      $insertSql = "INSERT INTO [Settings] ([ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)]) VALUES (?, ?, ?)";
      db()->prepare($insertSql)->execute([$category, $prefix, $eol]);
    }
    return settings_data();
  }
  
  $column = $mapping[$group] ?? null;
  if (!$column) throw new Exception("Invalid setting group: " . $group);
  
  $value = trim((string)($payload['value'] ?? ''));
  if ($value === '') throw new Exception("Value is required.");
  
  $stmt = db()->prepare("SELECT COUNT(*) FROM [Settings] WHERE UPPER(" . qname($column) . ") = ?");
  $stmt->execute([strtoupper($value)]);
  if ($stmt->fetchColumn() > 0) throw new Exception("Value already exists.");
  
  $stmt = db()->prepare("SELECT COUNT(*) FROM [Settings] WHERE " . qname($column) . " IS NULL");
  $stmt->execute();
  if ($stmt->fetchColumn() > 0) {
    $updateSql = "UPDATE TOP (1) [Settings] SET " . qname($column) . " = ? WHERE " . qname($column) . " IS NULL";
    db()->prepare($updateSql)->execute([$value]);
  } else {
    $insertSql = "INSERT INTO [Settings] (" . qname($column) . ") VALUES (?)";
    db()->prepare($insertSql)->execute([$value]);
  }
  return settings_data();
}

function update_setting_value($group, $payload) {
  $mapping = [
    'sites' => 'SITES',
    'offices' => 'DEPARTMENTS / OFFICES',
    'statuses' => 'ASSET STATUSES',
    'deploymentStatuses' => 'DEPLOYMENT STATUSES',
    'prefixes' => 'PREFIX'
  ];
  
  if ($group === 'categories') {
    $oldCategory = trim((string)($payload['oldCategory'] ?? ''));
    $category = trim((string)($payload['category'] ?? ''));
    $prefix = trim((string)($payload['prefix'] ?? ''));
    $eol = trim((string)($payload['eol'] ?? '5'));
    
    if ($oldCategory === '' || $category === '') throw new Exception("Category name is required.");
    
    if (strcasecmp($oldCategory, $category) !== 0) {
      $stmt = db()->prepare("SELECT COUNT(*) FROM [Settings] WHERE UPPER([ASSET CATEGORY]) = ?");
      $stmt->execute([strtoupper($category)]);
      if ($stmt->fetchColumn() > 0) throw new Exception("New category name already exists.");
    }
    
    $updateSql = "UPDATE [Settings] SET [ASSET CATEGORY] = ?, [PREFIX] = ?, [DEFAULT EOL (YEARS)] = ? WHERE UPPER([ASSET CATEGORY]) = ?";
    db()->prepare($updateSql)->execute([$category, $prefix, $eol, strtoupper($oldCategory)]);
    return settings_data();
  }
  
  $column = $mapping[$group] ?? null;
  if (!$column) throw new Exception("Invalid setting group: " . $group);
  
  $oldValue = trim((string)($payload['oldValue'] ?? ''));
  $value = trim((string)($payload['value'] ?? ''));
  if ($oldValue === '' || $value === '') throw new Exception("Value is required.");
  
  if (strcasecmp($oldValue, $value) !== 0) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM [Settings] WHERE UPPER(" . qname($column) . ") = ?");
    $stmt->execute([strtoupper($value)]);
    if ($stmt->fetchColumn() > 0) throw new Exception("Value already exists.");
  }
  
  $updateSql = "UPDATE [Settings] SET " . qname($column) . " = ? WHERE UPPER(" . qname($column) . ") = ?";
  db()->prepare($updateSql)->execute([$value, strtoupper($oldValue)]);
  return settings_data();
}

function delete_setting_value($group, $payload) {
  $mapping = [
    'sites' => 'SITES',
    'offices' => 'DEPARTMENTS / OFFICES',
    'statuses' => 'ASSET STATUSES',
    'deploymentStatuses' => 'DEPLOYMENT STATUSES',
    'prefixes' => 'PREFIX'
  ];
  
  if ($group === 'categories') {
    $category = trim((string)($payload['category'] ?? ''));
    if ($category === '') throw new Exception("Category name is required.");
    
    $updateSql = "UPDATE [Settings] SET [ASSET CATEGORY] = NULL, [PREFIX] = NULL, [DEFAULT EOL (YEARS)] = NULL WHERE UPPER([ASSET CATEGORY]) = ?";
    db()->prepare($updateSql)->execute([strtoupper($category)]);
    return settings_data();
  }
  
  $column = $mapping[$group] ?? null;
  if (!$column) throw new Exception("Invalid setting group: " . $group);
  
  $value = trim((string)($payload['value'] ?? ''));
  if ($value === '') throw new Exception("Value is required.");
  
  $updateSql = "UPDATE [Settings] SET " . qname($column) . " = NULL WHERE UPPER(" . qname($column) . ") = ?";
  db()->prepare($updateSql)->execute([strtoupper($value)]);
  return settings_data();
}

try {
  $raw = [];
  if(!empty($_POST['method'])){
    $raw = ['method' => $_POST['method'], 'args' => [array_merge($_POST, ['__files' => $_FILES])]];
  } else {
    $raw = json_decode(file_get_contents('php://input'), true) ?: [];
  }
  $m=$raw['method'] ?? ''; $a=$raw['args'] ?? [];
  ensure_employee_schema();
  ensure_preventive_maintenance_schema();
  
  $isWrite = in_array($m, [
    'apiCreateEmployee', 'apiUpdateEmployee', 'apiTransferWorkstation', 'apiTransferAsset',
    'apiCheckInAsset', 'apiMarkAssetMaintenance', 'apiCreateAsset', 'apiUpdateAsset',
    'apiImportAssetsFromSpreadsheet', 'apiCreateWorkstation', 'apiCompletePreventiveMaintenance'
  ], true);
  
  if ($isWrite) {
    sync_employee_ids();
    persist_generated_main_asset_numbers();
  }
  switch($m){
    case 'getInitialAppData': ok(['settings'=>settings_data(),'workstations'=>workstation_list([]),'dashboard'=>dashboard_data([])]); break;
    case 'apiGetSettingsData': ok(settings_data()); break;
    case 'apiAddSetting': ok(add_setting_value($a[0]['group'] ?? '', $a[0] ?? [])); break;
    case 'apiUpdateSetting': ok(update_setting_value($a[0]['group'] ?? '', $a[0] ?? [])); break;
    case 'apiDeleteSetting': ok(delete_setting_value($a[0]['group'] ?? '', $a[0] ?? [])); break;
    case 'apiGetEmployeeList': ok(employee_module_list($a[0] ?? [])); break;
    case 'apiCreateEmployee': ok(create_employee($a[0] ?? [])); break;
    case 'apiUpdateEmployee': ok(update_employee($a[0] ?? [])); break;
    case 'apiGetEmployeeAssets': ok(employee_assets($a[0] ?? '')); break;
    case 'apiLookupEmployee': ok(lookup_employee($a[0] ?? '')); break;
    case 'apiTransferWorkstation': ok(transfer_workstation($a[0] ?? [])); break;
    case 'apiTransferAsset': ok(transfer_asset($a[0] ?? [])); break;
    case 'apiCheckInAsset': ok(checkin_asset($a[0] ?? [])); break;
    case 'apiMarkAssetMaintenance': ok(mark_asset_maintenance($a[0] ?? [])); break;
    case 'apiGetPreventiveMaintenance': ok(preventive_maintenance_list($a[0] ?? [])); break;
    case 'apiCompletePreventiveMaintenance': ok(complete_preventive_maintenance($a[0] ?? [])); break;
    case 'apiGetWorkstationList': ok(workstation_list($a[0] ?? [])); break;
    case 'apiSearchWorkstations': ok(workstation_list(['query'=>$a[0] ?? ''])); break;
    case 'apiGetWorkstationDetails': ok(workstation_details($a[0] ?? '')); break;
    case 'apiGetDashboardData': ok(dashboard_data($a[0] ?? [])); break;
    case 'apiGetUnifiedAssetRegistry': ok(all_assets($a[0] ?? [])); break;
    case 'apiSearchAssets': ok(all_assets(['query'=>$a[0] ?? ''])); break;
    case 'apiGetAssetDetails': ok(asset_details($a[0] ?? '')); break;
    case 'apiGenerateNextAssetCode': ok(['assetCode' => next_asset_code($a[0] ?? 'System Unit')]); break;
    case 'apiGenerateNextMainAssetNumber': ok(['mainAssetNumber' => next_main_no($a[0] ?? 'DVO')]); break;
    case 'apiLookupAssetsByUser': ok(lookup_assets_by_user($a[0] ?? '')); break;
    case 'apiCreateAsset': ok(create_asset($a[0] ?? [])); break;
    case 'apiUpdateAsset': ok(update_asset($a[0] ?? [])); break;
    case 'apiEnsureAssetQr': ok(ensure_asset_qr($a[0] ?? [])); break;
    case 'apiSaveAssetQr': ok(save_asset_qr($a[0] ?? [])); break;
    case 'apiUploadAssetPhoto': ok(upload_asset_photo($a[0] ?? [])); break;
    case 'apiImportAssetsFromSpreadsheet': ok(import_assets_from_spreadsheet($a[0] ?? [])); break;
    case 'apiCreateWorkstation': ok(create_workstation($a[0] ?? [])); break;
    case 'apiGetAssetHistory': ok(asset_history($a[0] ?? '')); break;
    case 'apiGetRepairLogs': ok(repair_logs($a[0] ?? '')); break;
    default: throw new Exception('Unknown API method: '.$m);
  }
} catch(Throwable $e) { fail_json($e); }
