<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
            'articles' => $articleRepository->findByAuthor($this->getUser()),
        ]);
    }
}
