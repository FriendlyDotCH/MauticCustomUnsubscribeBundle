<?php

namespace MauticPlugin\MauticUnsubscribeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HiddenLinkController extends AbstractController
{
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function trackRedirect(Request $request, $id)
    {
        if (!$id) {
            return new Response('Invalid contact ID.', Response::HTTP_BAD_REQUEST);
        }

        // Start session to store redirect tracking
        $session   = $request->getSession();
        $timestamp = time();

        // Store the timestamp in session
        $strId = (string) $id;
        $session->set("redirect_click_$strId", $timestamp);

        // ✅ Use relative link instead of hardcoded URL
        $trackedPage = $this->router->generate('tracked_page', [], UrlGeneratorInterface::ABSOLUTE_PATH);

        return new RedirectResponse($trackedPage);
    }
}
