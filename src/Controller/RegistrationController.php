<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class RegistrationController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator
    ): Response {
        // 如果用户已登录，重定向到主页
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // 加密密码
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();

                // 手动登录用户
                $token = $userAuthenticator->authenticateUser($user, $authenticator, $request);

                // 添加成功消息
                $this->addFlash('success', 'Votre compte a été créé avec succès! Vous êtes maintenant connecté.');

                // 明确重定向到主页
                return $this->redirectToRoute('app_home');
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // 处理唯一性约束错误
                if (strpos($e->getMessage(), 'UNIQ_8D93D649E7927C74') !== false) {
                    $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                } elseif (strpos($e->getMessage(), 'UNIQ_8D93D649F85E0677') !== false) {
                    $this->addFlash('error', 'Ce nom d\'utilisateur est déjà utilisé.');
                } else {
                    $this->addFlash('error', 'Un compte avec ces informations existe déjà.');
                }

                $this->logger->error('Registration unique constraint error: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription: ' . $e->getMessage());
                // 记录详细错误信息到日志
                $this->logger->error('Registration error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
