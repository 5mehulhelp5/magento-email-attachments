<?php
namespace BredaBeds\EmailAttachments\Helper;

class SendBasicEmail extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        private \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        protected \Magento\Store\Model\StoreManagerInterface $storeManager,
        protected \Magento\Contact\Model\ConfigInterface $contactsConfig,
    ) {
        parent::__construct($context);
    }

    public function sendEmail(string $to, string $subject, string $body, string|array|null $attachments = [])
    {
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('simple_text_email')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId()
                ])
                ->setTemplateVars([
                    'email_subject' => $subject,
                    'message' => $body,
                    'attachments' => $attachments
                ])
                ->setFromByScope($this->contactsConfig->emailSender())
                ->addTo($to);
    
                $transport->getTransport()->sendMessage();
        } catch (\Exception $e) {
            // Handle or log error
            throw $e;
        }
    }
}