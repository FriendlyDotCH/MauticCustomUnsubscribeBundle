<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Service;

use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\TagModel;

class UnsubscrribeTagService
{
    public function __construct(
        private TagModel $tagModel,
        private LeadModel $leadModel
    ) {
    }

    public function addTag(int $leadId, string $origin): void
    {
        $lead = $this->leadModel->getEntity($leadId);
        if (!$lead) {
            return;
        }

        $tagName = 'body' === $origin ? 'unsubscribe-link' : 'unsubscribe-header-link';
        $tag     = $this->tagModel->getRepository()->getTagByNameOrCreateNewOne($tagName);
        $lead->addTag($tag);
        $this->leadModel->saveEntity($lead);
    }
}
