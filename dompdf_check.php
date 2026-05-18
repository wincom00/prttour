<?php
ini_set('display_errors',1); error_reporting(E_ALL);

$base = __DIR__ . '/vendor/dompdf';
require_once $base . '/src/Autoloader.php';
Dompdf\Autoloader::register();

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('chroot', __DIR__);

$dompdf = new Dompdf($options);
$html = '<html><meta charset="utf-8"><style>@font-face{font-family:NotoSansKR;src:url("fonts/NotoSansKR-Regular.otf") format("opentype");}*{font-family:NotoSansKR,DejaVu Sans,Arial}</style><h1>도움! dompdf 테스트</h1><p>한글 보이면 OK</p></html>';
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream('diag.pdf', ['Attachment'=>false]);
