<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Tag;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/article')]
class ArticleController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        $category = $request->query->get('category');
        $articles = $category
            ? $articleRepository->findByCategory($category)
            : $articleRepository->findAll();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $article->setAuthor($this->getUser());
        $article->setCreatedAt(new \DateTime());
        $article->setViews(0);

        // 清除会话中的旧表单数据
        $request->getSession()->remove('_csrf/form');

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 确保 slug 已设置
            if (!$article->getSlug()) {
                $article->setSlug($this->slugify($article->getTitle()));
            }

            // 确保标签被正确处理
            $tagInput = $form->get('tagInput')->getData();
            if ($tagInput) {
                $tags = explode(',', $tagInput);
                foreach ($tags as $tagName) {
                    $tagName = trim($tagName);
                    if (!empty($tagName)) {
                        $tag = $entityManager->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
                        if (!$tag) {
                            $tag = new Tag();
                            $tag->setName($tagName);
                            $entityManager->persist($tag);
                        }
                        $article->addTag($tag);
                    }
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article créé avec succès.');
            return $this->redirectToRoute('app_home');
        }

        // 如果表单提交但无效，显示详细错误
        if ($form->isSubmitted() && !$form->isValid()) {
            // 显示 CSRF 错误
            if (!$this->isCsrfTokenValid('article_form', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');
            }

            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    private function slugify(string $text): string
    {
        // 替换非字母数字字符为连字符
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // 转为小写
        $text = strtolower($text);
        // 移除不需要的字符
        $text = preg_replace('~[^-\w]+~', '', $text);
        // 移除重复的连字符
        $text = preg_replace('~-+~', '-', $text);
        // 移除开头和结尾的连字符
        $text = trim($text, '-');

        return $text ?: 'n-a'; // 如果为空，返回'n-a'
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        // 增加浏览量
        $article->incrementViews();
        $entityManager->flush();

        // 确保标签被加载
        $tags = $article->getTags()->toArray(); // 强制加载标签

        // 创建评论表单
        $comment = new Comment();
        $comment->setArticle($article);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($this->getUser());
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        // 创建回复评论的表单工厂函数
        $controller = $this;
        $replyFormFactory = function () use ($article, $controller) {
            $replyComment = new Comment();
            $replyComment->setArticle($article);
            return $controller->createForm(CommentType::class, $replyComment, [
                'attr' => ['class' => 'reply-form']
            ])->createView();
        };

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'comment_form' => $form->createView(),
            'reply_form_factory' => $replyFormFactory
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index');
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        // 检查当前用户是否是文章作者或管理员
        if (!$this->isGranted('ROLE_ADMIN') && $article->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet article.');
        }

        // 调试信息
        $token = $request->request->get('_token');
        $expectedToken = $this->container->get('security.csrf.token_manager')->getToken('delete' . $article->getId())->getValue();
        $this->logger->info('Delete article attempt', [
            'article_id' => $article->getId(),
            'received_token' => $token,
            'expected_token' => $expectedToken,
            'is_valid' => $this->isCsrfTokenValid('delete' . $article->getId(), $token)
        ]);

        // 检查 CSRF 令牌
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
            // 删除文章
            $entityManager->remove($article);
            $entityManager->flush();

            // 添加成功消息
            $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_article_index');
    }

    #[Route('/article/{article_id}/comment/{parent_id}/reply', name: 'app_comment_reply')]
    public function replyToComment(
        Request $request,
        int $article_id,
        int $parent_id,
        EntityManagerInterface $entityManager
    ): Response {
        $article = $entityManager->getRepository(Article::class)->find($article_id);
        $parentComment = $entityManager->getRepository(Comment::class)->find($parent_id);

        if (!$article || !$parentComment) {
            throw $this->createNotFoundException('Article or parent comment not found');
        }

        $reply = new Comment();
        $reply->setArticle($article);
        $reply->setParent($parentComment);
        $reply->setAuthor($this->getUser());

        $form = $this->createForm(CommentType::class, $reply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reply);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_show', ['id' => $article_id]);
    }
}
