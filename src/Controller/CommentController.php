<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    #[Route('/comment/{comment_id}/reply', name: 'app_comment_reply', methods: ['POST'])]
    public function reply(
        Request $request,
        int $comment_id,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // 查找评论
        $comment = $entityManager->getRepository(Comment::class)->find($comment_id);

        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        $article = $comment->getArticle();

        // 创建回复
        $reply = new Comment();
        $reply->setArticle($article);
        $reply->setParent($comment);
        $reply->setAuthor($this->getUser());
        $reply->setContent($request->request->get('content'));

        if (!empty($reply->getContent())) {
            $entityManager->persist($reply);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réponse a été publiée.');
        } else {
            $this->addFlash('error', 'Une erreur est survenue.');
        }

        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }
}
