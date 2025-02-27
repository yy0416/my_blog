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
        // 获取请求和会话
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $session = $request->getSession();

        // 调试信息
        dump([
            'current_user' => $this->getUser(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'session' => $session->all(),
            'request' => $request->request->all()
        ]);

        if ($error = $authenticationUtils->getLastAuthenticationError()) {
            dump('Authentication error:', [
                'message' => $error->getMessage(),
                'trace' => $error->getTraceAsString()
            ]);
        }

        return $this->render('security/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // 控制器永远不会被执行，
        // 因为路由被 Symfony 的 Security 系统拦截
    }
}
