<?php
// includes/PdfGenerator.php
require_once __DIR__ . '/../config.php';

// Suppress errors for development in case mPDF isn't installed
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class PdfGenerator {
    
    public static function generateCertificate($participant, $background_img_path) {
        // If mPDF is not installed yet, just output a simple HTML mock
        if (!class_exists('\Mpdf\Mpdf')) {
            echo "<h2>[MOCK CERTIFICATE]</h2>";
            echo "<p>mPDF library is not installed. Please run <code>composer require mpdf/mpdf</code> in the project root.</p>";
            echo "<p><strong>Callsign:</strong> " . htmlspecialchars($participant['callsign']) . "</p>";
            echo "<p><strong>Category:</strong> " . htmlspecialchars($participant['category_op']) . " / " . htmlspecialchars($participant['category_band']) . "</p>";
            exit;
        }

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8', 
                'format' => 'A4-L', // Landscape
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0
            ]);

            // Assuming $background_img_path is an absolute path or relative to project root
            $bg_html = '';
            if (file_exists($background_img_path)) {
                $bg_html = 'background-image: url("' . $background_img_path . '"); background-image-resize: 6;';
            }

            // Design HTML template
            $html = '
            <style>
                body {
                    ' . $bg_html . '
                    font-family: sans-serif;
                }
                .cert-container {
                    text-align: center;
                    padding-top: 150px;
                    color: #333;
                }
                .title {
                    font-size: 48px;
                    font-weight: bold;
                    color: #1e1b4b;
                    margin-bottom: 20px;
                }
                .subtitle {
                    font-size: 24px;
                    margin-bottom: 50px;
                }
                .callsign {
                    font-size: 64px;
                    font-weight: bold;
                    color: #3b82f6;
                    margin-bottom: 30px;
                }
                .category {
                    font-size: 20px;
                    color: #555;
                }
            </style>
            <div class="cert-container">
                <div class="title">CERTIFICATE OF ACHIEVEMENT</div>
                <div class="subtitle">YB DX Contest ' . date('Y') . '</div>
                <div class="subtitle" style="font-size:18px; margin-bottom: 20px;">This certifies that</div>
                <div class="callsign">' . htmlspecialchars($participant['callsign']) . '</div>
                <div class="category">
                    has successfully participated in the ' . htmlspecialchars($participant['category_op']) . ' - ' . htmlspecialchars($participant['category_band']) . ' category<br>
                    with a final score of ' . number_format($participant['raw_score']) . ' points.
                </div>
            </div>';

            $mpdf->WriteHTML($html);
            $mpdf->Output($participant['callsign'] . '_YBDXContest_' . date('Y') . '.pdf', \Mpdf\Output\Destination::INLINE);
            
        } catch (\Mpdf\MpdfException $e) {
            echo "PDF Generation Error: " . $e->getMessage();
        }
    }
}
?>
