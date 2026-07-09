<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HiddenLinkController extends AbstractController
{
    public function trackRedirectAction(Request $request, int $id): Response
    {
        if (!$id) {
            return new Response('Invalid contact ID.', Response::HTTP_BAD_REQUEST);
        }

        // Start session to store redirect tracking
        $session   = $request->getSession();
        $timestamp = time();

        // Store the timestamp in session
        $session->set("redirect_click_$id", $timestamp);

        return new Response('ok', Response::HTTP_OK);
    }
}
