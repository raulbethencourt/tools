<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PageController extends AbstractController
{
    #[Route('/hello', name: 'app_page')]
    public function index(): Response
    {
        return $this->render('page/index.html.twig');
    }

    #[Route('/auth', name: 'auth')]
    #[IsGranted('ROLE_USER')]
    public function auth(): Response
    {
        return $this->render('page/index.html.twig');
    }

    #[Route('/admin', name: 'admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->render('page/index.html.twig');
    }

    #[Route('/mail', name: 'mail')]
    public function mail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->subject('Hello')
            ->text('Hello')
            ->from('noreplay@domain.fr')
            ->to('contact@domain.fr');

        $mailer->send($email);
        return new Response('Hello');
    }
}
