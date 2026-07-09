<?php
function db() {
  static $pdo = null;
  if ($pdo) return $pdo;
  $cfg = require __DIR__ . '/config.php';
  $server = $cfg['server'];
  $database = $cfg['database'];
  $loginTimeout = isset($cfg['login_timeout']) ? (int)$cfg['login_timeout'] : 5;
  $dsn = "sqlsrv:Server=$server;Database=$database;LoginTimeout=$loginTimeout";
  if (!empty($cfg['encrypt'])) {
    $dsn .= ';Encrypt=yes';
    if (!empty($cfg['trust_server_certificate'])) {
      $dsn .= ';TrustServerCertificate=yes';
    }
  } else {
    $dsn .= ';Encrypt=no';
  }
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ];
  if (defined('PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE')) {
    $options[PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE] = true;
  }
  $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
  return $pdo;
}
function fail_json($e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>$e instanceof Throwable ? $e->getMessage() : strval($e)]); exit; }
function qname($name){ return '[' . str_replace(']', ']]', $name) . ']'; }
function all_rows($table){
  static $cache = [];
  if (isset($cache[$table])) return $cache[$table];
  $cache[$table] = db()->query('SELECT * FROM '.qname($table))->fetchAll(PDO::FETCH_ASSOC);
  return $cache[$table];
}
function scalar($sql,$params=[]){ $s=db()->prepare($sql); $s->execute($params); return $s->fetchColumn(); }
function val($row,$key,$default=''){ return isset($row[$key]) && $row[$key]!==null ? trim((string)$row[$key]) : $default; }
function norm_date($v){ if(!$v) return ''; try { return (new DateTime((string)$v))->format('Y-m-d'); } catch(Exception $e){ return (string)$v; } }
function norm_dt($v){ if(!$v) return ''; try { return (new DateTime((string)$v))->format('Y-m-d H:i:s'); } catch(Exception $e){ return (string)$v; } }
function badge_tone($status){ $s=strtolower((string)$status); if(strpos($s,'ready')!==false) return 'success'; if(strpos($s,'deploy')!==false || strpos($s,'use')!==false) return 'primary'; if(strpos($s,'repair')!==false) return 'warning'; if(strpos($s,'dispose')!==false || strpos($s,'return')!==false) return 'danger'; return 'neutral';}
function like_match($text,$q){ return $q==='' || stripos((string)$text,$q)!==false; }
