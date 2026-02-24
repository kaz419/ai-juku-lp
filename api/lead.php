<?php
// AI-juku Lead API (for mixhost + Airtable)
// Required env vars:
// - AIRTABLE_TOKEN
// - AIRTABLE_BASE_ID
// - AIRTABLE_TABLE

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
  exit;
}

// same-origin only (simple CSRF mitigation)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($origin && stripos($origin, $host) === false) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Forbidden origin']);
  exit;
}

// basic rate-limit by IP (5 req / 10 min)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$key = '/tmp/aijuku_rate_' . md5($ip);
$now = time();
$window = 600;
$maxReq = 5;
$hits = [];
if (file_exists($key)) {
  $raw = @file_get_contents($key);
  $hits = json_decode($raw, true) ?: [];
}
$hits = array_values(array_filter($hits, fn($t) => ($now - (int)$t) < $window));
if (count($hits) >= $maxReq) {
  http_response_code(429);
  echo json_encode(['ok' => false, 'error' => 'Too many requests']);
  exit;
}
$hits[] = $now;
@file_put_contents($key, json_encode($hits));

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
  exit;
}

// Honeypot
if (!empty($data['website'])) {
  echo json_encode(['ok' => true]);
  exit;
}

$required = ['company','name','email','role','industry','inquiryType','teamSize','message'];
foreach ($required as $field) {
  if (empty(trim((string)($data[$field] ?? '')))) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => "Missing field: {$field}"]);
    exit;
  }
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'Invalid email']);
  exit;
}

$token = getenv('AIRTABLE_TOKEN');
$baseId = getenv('AIRTABLE_BASE_ID');
$table = getenv('AIRTABLE_TABLE') ?: 'Leads';
if (!$token || !$baseId) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Airtable env not set']);
  exit;
}

$payload = [
  'fields' => [
    'created_at'   => gmdate('c'),
    'company'      => trim($data['company']),
    'name'         => trim($data['name']),
    'email'        => trim($data['email']),
    'role'         => trim($data['role']),
    'industry'     => trim($data['industry']),
    'inquiry_type' => trim($data['inquiryType']),
    'team_size'    => trim($data['teamSize']),
    'phone'        => trim((string)($data['phone'] ?? '')),
    'message'      => trim($data['message']),
    'source_url'   => trim((string)($data['sourceUrl'] ?? '')),
    'utm_source'   => trim((string)($data['utm_source'] ?? '')),
    'utm_medium'   => trim((string)($data['utm_medium'] ?? '')),
    'utm_campaign' => trim((string)($data['utm_campaign'] ?? '')),
  ]
];

$url = "https://api.airtable.com/v0/{$baseId}/" . rawurlencode($table);
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
  ],
  CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
  CURLOPT_TIMEOUT => 20,
]);

$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err || $code >= 300) {
  http_response_code(502);
  echo json_encode(['ok' => false, 'error' => 'Airtable request failed', 'status' => $code]);
  exit;
}

echo json_encode(['ok' => true]);
