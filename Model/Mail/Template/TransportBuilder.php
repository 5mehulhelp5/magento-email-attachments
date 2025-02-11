<?php
declare(strict_types=1);
namespace BredaBeds\EmailAttachments\Model\Mail\Template;

// https://magento.stackexchange.com/questions/252506/magento-2-3-custom-email-attachment-not-working/297997#297997

use Magento\Framework\HTTP\Mime; // As an alternative to Laminas\Mime\Mime or Magento\Framework\Mail\MimeInterface
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\Message as MimeMessage;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{

    /**
     * Array to hold extra attachment MIME parts.
     *
     * @var array
     */
    protected $attachments = [];

    /**
     * Override prepareMessage to merge attachments.
     *
     * This method calls parent's prepareMessage() to build the main email body,
     * then checks if a template variable 'attachmentPdfPath' exists (and is valid).
     * If so, it adds that PDF as an attachment. It also merges any attachments
     * explicitly added via the public addAttachment() method.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareMessage()
    {
        parent::prepareMessage(); // Build the base message using parent's logic.

        // Handle attachments via templateVars START
        $templateVars = $this->templateVars;
        // Support both single path and array of attachments
        $attachments = $templateVars['attachments'] ?? [];
        if (!is_array($attachments)) $attachments = [$attachments];

        foreach ($attachments as $attachment) {
            // Support both string path and array format
            if (is_string($attachment)) {
                $path = $attachment;
                $type = mime_content_type($path) ?: Mime::TYPE_OCTETSTREAM;
                $filename = basename($path);
            } else {
                $path = $attachment['path'] ?? '';
                $type = $attachment['type'] ?? (mime_content_type($path) ?: Mime::TYPE_OCTETSTREAM);
                $filename = $attachment['filename'] ?? basename($path);
            }

            if (!empty($path) && file_exists($path)) $this->addAttachment(file_get_contents($path), $filename, $type);
        }
        // Handle attachments via templateVars END

        if (empty($this->attachments)) return $this; // Early return for no attachments

        // Merge any attachment parts with the existing main content.
        $existingBody = $this->message->getBody();
        $existingParts = $existingBody->getParts();
        $parts = array_merge($existingParts, $this->attachments);
        $newBody = (new MimeMessage())->setParts($parts);

        // Determine original email type by checking the first MIME part.
        $firstPart = reset($existingParts);
        $this->message->{$firstPart && $firstPart->getType() === Mime::TYPE_HTML ? 'setBodyHtml' : 'setBodyText'}($newBody);

        return $this;
    }

    /**
     * Public method to add an attachment.
     *
     * This method can be used explicitly, for example:
     *
     *     $this->transportBuilder
     *         ->setTemplateIdentifier('1')
     *         ->setTemplateOptions([...])
     *         ->setTemplateVars([...])
     *         ->setFromByScope([...], $storeId)
     *         ->addTo('email@example.com', 'Example')
     *         ->addAttachment($absolutePathContent, 'File.pdf', 'application/pdf');
     *
     * @param string $content Raw attachment content.
     * @param string $fileName The desired file name.
     * @param string $fileType The MIME type (e.g. 'application/pdf').
     * @return $this
     */
    public function addAttachment($content, $fileName, $fileType)
    {
        $attachmentPart = new MimePart($content);
        $attachmentPart->setType($fileType)
            ->setFileName($fileName)
            ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
            ->setEncoding(Mime::ENCODING_BASE64);

        $this->attachments[] = $attachmentPart;

        return $this;
    }

}
