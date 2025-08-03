<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Controller;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\MauticUnsubscribeBundle\Exception\FieldNotAllowedException;
use MauticPlugin\MauticUnsubscribeBundle\Exception\PluginNotPublishedException;
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

    public function __construct(
        Connection $db,
        LoggerInterface $mauticLogger,
        AuditLogModel $auditLog,
        UrlGeneratorInterface $router,
        IntegrationsHelper $integrationsHelper
    ) {
        $this->db                = $db;
        $this->logger            = $mauticLogger;
        $this->auditLog          = $auditLog;
        $this->router            = $router;
        $this->unsubSubscribeInt = $integrationsHelper->getIntegration(FriendlyUnsubscribeIntegration::NAME);
    }

    public function unsubscribeAction(Request $request, int $id, string $field): Response
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

            if (!in_array($field, $allowedFields)) {
                throw new FieldNotAllowedException('Field not allowed to be used as unsubscribe.');
            }

            // Validate lead existence
            $lead = $this->db->fetchAssociative('SELECT id FROM leads WHERE id = ?', [$id]);
            if (!$lead) {
                return new Response('Lead ID not found.', Response::HTTP_NOT_FOUND);
            }

            // Capture request details
            $session   = $request->getSession();
            $timestamp = time();

            // Store the unsubscribe request temporarily
            $session->set("pending_unsubscribe_$id", $timestamp);
            $this->logger->info("Unsubscribe request stored temporarily for Lead ID: $id.");

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

            // ✅ Redirect to the corresponding landing page
            $landingPageUrl = '/'.$field;

            return new Response("<script>window.location.href = '$landingPageUrl';</script>");
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
