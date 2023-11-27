<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    #[Route('/webhook', name: 'app_webhook')]
    public function webhook(EntityManagerInterface $em, RequestStack $requestStack): Response
    {
        if ($_ENV['APP_ENV'] === 'dev')
        {
            $stripeSecretKey = $_ENV["STRIPE_SECRET_KEY_DEV"];
            $endpoint_secret = $_ENV['STRIPE_SECRET_WHSEC_DEV'];
        } else if ($_ENV['APP_ENV'] === 'prod') {
            $stripeSecretKey = $_ENV["STRIPE_SECRET_KEY_PROD"];
            $endpoint_secret = $_ENV['STRIPE_SECRET_WHSEC_PROD'];
        }

        $stripe = new \Stripe\StripeClient($stripeSecretKey);

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        $user = $this->getUser();

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
        exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        // Handle the event
        switch ($event->type) {
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $user->setBill([$invoice->invoice_pdf]);
                $data = $invoice->lines->data;
                foreach ($data as $line) {
                    $products[] = $line->description;
                }
                if (in_array('Formation', $products))
                {
                    $user->setRoles(['ROLE_CUSTOMER_FORMATION']);
                } else {
                    $user->setRoles(['ROLE_CUSTOMER']);
                }
                $em->persist($user);
                $em->flush();
                $requestStack->getSession()->set('cart', []);
            default:
                echo 'Received unknown event type ' . $event->type;
        }

        http_response_code(200);
        return new Response(200);
    }
}