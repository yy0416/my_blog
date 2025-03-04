<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // 如果用户已经登录，重定向到首页
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // 获取上次登录错误（如果有）
        $error = $authenticationUtils->getLastAuthenticationError();
        // 获取上次输入的用户名
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // 控制器永远不会被执行，
        // 因为路由被 Symfony 的 Security 系统拦截
    }
}
