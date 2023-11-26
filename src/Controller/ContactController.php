<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/profile/contact', name: 'app_contact')]
    public function index(MailerInterface $mailer, Request $request): Response
    {
        // Get user data
        $userName = ucfirst($this->getUser()->getName());
        $userFirstname = ucfirst($this->getUser()->getFirstname());
        $userEmail = $this->getUser()->getEmail();

        // Get admin email
        //$adminEmail = $_ENV["ADMIN_EMAIL"];

        $contactForm = $this->createForm(ContactType::class);
        $contactForm->handleRequest($request);

        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $this->addFlash('send', 'Votre message a bien été envoyé.');
            $data = $contactForm->getData();
            $message = $data["message"];
            $mailer->send((new TemplatedEmail())
                ->from("samuel.lasquellec@bbox.fr")
                ->to("samuel.lasquellec@bbox.fr")
                ->replyTo($userEmail)
                ->subject("Message provenant d'un utilisateur du site test")
                ->htmlTemplate("contact/email.html.twig")
                ->context([
                    "message" => $message,
                    "name" => $userName,
                    "firstname" => $userFirstname,
                    "userEmail" => $userEmail
                ]));
        }

        return $this->render('contact/index.html.twig', [
            'contactform' => $contactForm->createView()
        ]);
    }
}
