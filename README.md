
# magento-email-attachments

This module adds the ability to programatically send attachments with Magento 2 emails. 

## Template Variables
Method 1 (template variables):
```php
->setTemplateVars([
    'attachments' => '/path/to/document.pdf'
])
```

Method 2 (template variables):
```php
->setTemplateVars([
    'attachments' => [
        'path' => '/path/to/document.xlsx',
        'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]
])
```

Method 3 (template variables):
```php
->setTemplateVars([
    'attachments' => [
        '/path/to/document1.pdf',
        [
            'path' => '/path/to/document2.xlsx',
            'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
        '/path/to/document3.docx'
    ]
])
```

Method 4 (addAttachment function):
```php
$absolutePath = '/path/to/document1.pdf';
$transport = $this->transportBuilder
    ->setTemplateIdentifier('simple_text_email')
    ->setTemplateOptions([
        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
        'store' => $this->storeManager->getStore()->getId()
    ])
    ->setTemplateVars([
        'email_subject' => $subject,
        'message' => $body,
        'attachments' => $absolutePath // Can be added here via template variables
    ])
    ->setFromByScope($this->contactsConfig->emailSender())
    ->addTo($to)
    ->addAttachment(file_get_contents($absolutePath), basename($absolutePath), 'application/pdf'); // Or here

$transport->getTransport()->sendMessage();
```

## Browserless PDF generation and basic email template
This module also has the ability to generate a PDF via Browserless from a URL. This also has a very basic template called "simple_text_email" that allows the body and subject to be set via template variables (see above example). This module has a wrapper helper for that template:
```php
public function __construct(
    protected \BredaBeds\EmailAttachments\Helper\SendBasicEmail $sendBasicEmail,
    protected \BredaBeds\EmailAttachments\Helper\Browserless $browserless
) { }

protected function sendTestEmail()
{
    $absolutePath = $this->browserless->generatePdfFromUrl(
        'https://google.com',   // URL 
        'google.pdf',           // Filename to use
        'pdf/files'             // Location in the "var" path
    ); // Saves a PDF to: "/path/to/magento/var/pdf/files/google.pdf"

    $this->sendBasicEmail->sendEmail(
        'test@example.com',
        'Your PDF Document',
        'Your PDF document is attached. Please contact us if you have any questions or concerns.',
        [$absolutePath] // Sets attachments via template variables, see methods 1-3 above.
    ); // Sends an email with the previously saved PDF
}
```
For Browserless to work, add to the ./app/etc/env.php file:
```json
'bredabeds' => [
    'browserless' => [
        'endpoint' => '127.0.0.1:3000',
        'token' => 'secret-token'
    ]
]
```