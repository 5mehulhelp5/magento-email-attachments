<?php
declare(strict_types=1);

namespace BredaBeds\EmailAttachments\Helper;

class Browserless
{
    const PDF_STORAGE_PATH = 'pdf';
    const TIMEOUT = 300;

    public function __construct(
        protected \Magento\Framework\HTTP\Client\Curl $curl,
        protected \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        protected \Magento\Framework\Filesystem $filesystem
    ) { }

    public function generatePdfFromUrl(string $url, string $filename, ?string $savePath = null)
    {
        try {
            return $this->savePdf($this->generatePdf($url), $savePath, $filename); // Return absolute path
        } catch (\Exception $e) {
            return false; // Log error
        }
    }

    private function generatePdf(string $url): string
    {
        $browserlessEndpoint = $this->deploymentConfig->get('bredabeds/browserless/endpoint');
        $browserlessToken = $this->deploymentConfig->get('bredabeds/browserless/token');
        
        $apiUrl = "http://{$browserlessEndpoint}/pdf?token={$browserlessToken}";

        //\BredaBeds\Core\Helper\Notify::printLog('URL: ' . $url);
        
        $payload = [
            'url' => $url,
            'options' => [
                'displayHeaderFooter' => false,
                'printBackground' => false,
                'format' => 'Letter',
                // 'margin' => [
                //     'top' => '10mm',
                    // 'bottom' => '20mm',
                    // 'left' => '20mm',
                    // 'right' => '20mm'
                // ]
            ],
            // 'gotoOptions' => [
            //     'waitUntil' => 'networkidle2'
            // ],
            // 'waitForFunction' => [
            //     'fn' => '() => window.Magewire !== undefined',
            //     'timeout' => 10000
            // ],
            // 'bestAttempt' => true,
            // 'addStyleTag' => [
            //     [
            //         'content' => 'body { -webkit-print-color-adjust: exact; }'
            //     ]
            // ],
            'addStyleTag' => [
                [
                    'content' => '
                        .min-h-screen { 
                            min-height: auto !important; 
                            height: auto !important;
                        }
                    '
                ]
            ],
            // 'rejectResourceTypes' => ['image', 'font', 'stylesheet'],
            // 'timeout' => self::TIMEOUT
        ];

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/pdf'
        ]);
        
        $this->curl->post($apiUrl, json_encode($payload));
        
        if ($this->curl->getStatus() !== 200) {
            throw new \Exception("Browserless error: " . $this->curl->getBody());
        }

        return $this->curl->getBody();
    }

    private function savePdf($content, $path, $filename, )
    {
        $varDir = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR); // eg "/var/www/dev/var"
        $path = (!empty($path)) ? $path . '/' . $filename : self::PDF_STORAGE_PATH . '/' . $filename;
        $varDir->writeFile($path, $content);
        return $varDir->getAbsolutePath($path);
    }
}