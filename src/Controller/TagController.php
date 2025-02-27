<?php

namespace App\Controller;

use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class TagController extends AbstractController
{
    #[Route('/tag/new-ajax', name: 'app_tag_new_ajax', methods: ['POST'])]
    public function newAjax(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;

        if (!$name) {
            return $this->json(['success' => false, 'error' => 'Nom du tag requis'], Response::HTTP_BAD_REQUEST);
        }

        // 检查标签是否已存在
        $existingTag = $entityManager->getRepository(Tag::class)->findOneBy(['name' => $name]);
        if ($existingTag) {
            return $this->json([
                'success' => true,
                'tag' => [
                    'id' => $existingTag->getId(),
                    'name' => $existingTag->getName()
                ],
                'message' => 'Tag existant utilisé'
            ]);
        }

        // 创建新标签
        $tag = new Tag();
        $tag->setName($name);

        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'tag' => [
                'id' => $tag->getId(),
                'name' => $tag->getName()
            ]
        ]);
    }

    #[Route('/tag/{id}', name: 'app_tag_show', methods: ['GET'])]
    public function show(Tag $tag): Response
    {
        return $this->render('tag/show.html.twig', [
            'tag' => $tag,
            'articles' => $tag->getArticles()
        ]);
    }
}
