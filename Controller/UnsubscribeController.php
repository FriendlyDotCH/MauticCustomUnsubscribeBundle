<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Controller;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticUnsubscribeBundle\Exception\FieldNotAllowedException;
use MauticPlugin\MauticUnsubscribeBundle\Exception\PluginNotPublishedException;
use MauticPlugin\MauticUnsubscribeBundle\Helper\HashHelper;
use MauticPlugin\MauticUnsubscribeBundle\Integration\FriendlyUnsubscribeIntegration;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UnsubscribeController extends AbstractController
{
    private $db;

    private $logger;

    private $auditLog;

    private $router;

    private $unsubSubscribeInt;

    private $leadModel;

    private $hashHelper;

    public function __construct(
        Connection $db,
        LoggerInterface $mauticLogger,
        AuditLogModel $auditLog,
        UrlGeneratorInterface $router,
        IntegrationsHelper $integrationsHelper,
        LeadModel $leadModel,
        HashHelper $hashHelper
    ) {
        $this->db                = $db;
        $this->logger            = $mauticLogger;
        $this->auditLog          = $auditLog;
        $this->router            = $router;
        $this->unsubSubscribeInt = $integrationsHelper->getIntegration(FriendlyUnsubscribeIntegration::NAME);
        $this->leadModel         = $leadModel;
        $this->hashHelper        = $hashHelper;
    }

    /**
     * Handle secure unsubscribe with hashed URL.
     */
    public function unsubscribeSecureAction(Request $request, string $email, string $hash, string $field): Response
    {
        $config              = $this->unsubSubscribeInt?->getIntegrationConfiguration();
        if (!$config || !$config->isPublished()) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $hashValues = $this->unsubSubscribeInt->isSupported('hashLeadId');
        $this->logger->info('hashValues: '.$hashValues);

        if (!$hashValues) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $lead = $this->db->fetchAssociative(
            'SELECT id, email FROM leads WHERE email = :email',
            ['email' => $email]
        );

        if (!$lead || !$this->hashHelper->validateUnsubscribeHash($hash, (int) $lead['id'], $field, $lead['email'])) {
            return new Response('Invalid unsubscribe link.', Response::HTTP_BAD_REQUEST);
        }

        $id = (int) $lead['id'];

        return $this->processUnsubscribe($request, $id, $field);
    }

    /**
     * Handle legacy unsubscribe with direct ID (less secure).
     */
    public function unsubscribeAction(Request $request, int $id, string $field): Response
    {
        $config              = $this->unsubSubscribeInt?->getIntegrationConfiguration();
        if (!$config || !$config->isPublished()) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $hashValues = $this->unsubSubscribeInt->isSupported('hashLeadId');
        $this->logger->info('hashValues: '.$hashValues);

        if ($hashValues) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $lead = $this->db->fetchAssociative('SELECT id FROM leads WHERE id = ?', [$id]);
        if (!$lead) {
            return new Response('Lead not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->processUnsubscribe($request, $id, $field);
    }

    /**
     * Common unsubscribe processing logic.
     */
    private function processUnsubscribe(Request $request, int $id, string $field): Response
    {
        try {
            $this->logger->info('Friendly unsubscribeAction');
            $configuration = $this->unsubSubscribeInt?->getIntegrationConfiguration();

            $isPublished = $configuration?->isPublished();
            if (!$isPublished) {
                throw new PluginNotPublishedException('Plugin is not published.');
            }

            $decryptedApiKeys   = $configuration->getApiKeys();
            $expireTime         = $decryptedApiKeys['nhi'];
            $allowedFields      = explode(',', $decryptedApiKeys['fields']);

            if (count($allowedFields) > 0 && !in_array($field, $allowedFields)) {
                throw new FieldNotAllowedException('Field not allowed to be used as unsubscribe.');
            }

            // Capture request details
            $session   = $request->getSession();
            $timestamp = time();

            // Store the unsubscribe request temporarily
            $session->set("pending_unsubscribe_$id", $timestamp);
            $this->logger->debug("Unsubscribe request stored temporarily for Lead ID: $id.");

            // Delay processing by 3 seconds
            sleep(3);

            // Check if {nhi} tracking link was clicked within expiry time (10 sec)
            $lastRedirectClick = $session->get("redirect_click_$id");

            if ($lastRedirectClick && (time() - $lastRedirectClick <= $expireTime)) {
                $this->logger->warning("Unsubscribe request ignored for Lead ID $id (NHI Clicked in last $expireTime sec).");
                $session->remove("redirect_click_$id");

                return new Response('Unsubscribe request ignored.', Response::HTTP_OK);
            }

            // ✅ Remove {nhi} session after expiry
            $session->remove("redirect_click_$id");

            // ✅ Dynamically update the custom field
            $this->db->executeUpdate("UPDATE leads SET $field = 'DNC' WHERE id = ?", [$id]);

            // Log event
            $this->auditLog->writeToLog([
                'bundle'    => 'lead',
                'object'    => 'contact',
                'objectId'  => $id,
                'action'    => 'update',
                'details'   => ['Updated '.$field => 'DNC'],
            ]);

            $this->logger->info("Successfully unsubscribed Lead ID: $id (Field: $field)");

            // Redirect to the corresponding landing page
            return $this->redirect('/'.$field);
        } catch (PluginNotPublishedException $e) {
            return new Response('Error: '.$e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (FieldNotAllowedException $e) {
            return new Response('Error: '.$e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error("Error updating lead ID $id: ".$e->getMessage());

            return new Response('Error: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
