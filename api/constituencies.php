<?php
// Public list of constituencies, used to fill the MP and citizen forms.
require_once __DIR__ . '/../includes/bootstrap.php';

$rows = db()->query('SELECT code, name, district FROM constituencies ORDER BY district, name')->fetchAll();

json_out(['ok' => true, 'constituencies' => $rows]);
