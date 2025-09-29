<?php
require_once __DIR__ . '/includes/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(40,10,'Hello iRoks!');
$pdf->Output();
