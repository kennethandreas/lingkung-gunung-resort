# Ekspor Presentasi_Website_Resort.pptx ke PDF memakai Microsoft PowerPoint (hasil paling setia).
#   powershell -ExecutionPolicy Bypass -File docs\presentasi\scripts\ekspor-pdf.ps1 [-GambarDir <folder>]
# -GambarDir (opsional): simpan juga setiap slide sebagai PNG untuk pemeriksaan visual.
param([string]$GambarDir = '')

$ErrorActionPreference = 'Stop'
$dir = Split-Path -Parent $PSScriptRoot
$pptx = Join-Path $dir 'Presentasi_Website_Resort.pptx'
$pdf = Join-Path $dir 'Presentasi_Website_Resort.pdf'

$app = New-Object -ComObject PowerPoint.Application
try {
    # Open(FileName, ReadOnly, Untitled, WithWindow)
    $deck = $app.Presentations.Open($pptx, -1, 0, 0)
    $deck.SaveAs($pdf, 32)  # 32 = ppSaveAsPDF
    Write-Output ("PDF: {0} ({1} slide)" -f $pdf, $deck.Slides.Count)
    if ($GambarDir -ne '') {
        New-Item -ItemType Directory -Force $GambarDir | Out-Null
        foreach ($slide in $deck.Slides) {
            $file = Join-Path $GambarDir ('slide-{0:D2}.png' -f $slide.SlideIndex)
            $slide.Export($file, 'PNG', 1600, 900)
        }
        Write-Output "Gambar slide: $GambarDir"
    }
    $deck.Close()
}
finally {
    $app.Quit()
    [System.Runtime.InteropServices.Marshal]::ReleaseComObject($app) | Out-Null
}
