<?php


namespace App\Tests\Unit;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait NeedLogin
{

    public function login(KernelBrowser $client, User $user)
    {
        $client->request('GET', '/');
        $request = $client->getRequest();
        assert($request instanceof Request);
        $session = $request->getSession();

        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        $session->set('_security_main', serialize($token));
        $session->save();
        
        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }
}

