<?php

namespace Mailery\Channel\Smtp\Handler;

use Mailery\Campaign\Entity\Sendout;
use Mailery\Campaign\Entity\Recipient;
use Mailery\Channel\Handler\HandlerInterface;
use Cycle\ORM\ORMInterface;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;
use Yiisoft\Mailer\MailerInterface;
use Mailery\Sender\Email\Entity\EmailSender;
use Mailery\Template\Email\Entity\EmailTemplate;

class ChannelHandler implements HandlerInterface
{

    /**
     * @param ORMInterface $orm
     */
    public function __construct(
        private ORMInterface $orm,
        private MailerInterface $mailer
    ) {}

    /**
     * @inheritdoc
     */
    public function handle(Sendout $sendout, Recipient $recipient): bool
    {
        $recipient->setSendout($sendout);
        (new EntityWriter($this->orm))->write([$recipient]);

        /** @var EmailSender $sender */
        $sender = $sendout->getCampaign()->getSender();
        /** @var EmailTemplate $template */
        $template = $sendout->getCampaign()->getTemplate();

        $message = $this->mailer->compose()
            ->withTo([$recipient->getIdentifier() => $recipient->getName()])
            ->withFrom([$sender->getEmail() => $sender->getName()])
            ->withReplyTo([$sender->getReplyEmail() => $sender->getReplyName()])
            ->withSubject($sendout->getCampaign()->getName())
            ->withTextBody($template->getTextContent())
            ->withHtmlBody($template->getHtmlContent())
        ;

        $this->mailer->send($message);

        return true;
    }

}
