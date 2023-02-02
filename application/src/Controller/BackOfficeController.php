<?php

namespace App\Controller;

use App\Entity\Passwords;
use App\Entity\Tokens;
use App\Entity\Users;
use App\Traits\GeneralTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{serverID}/backoffice")
 */
class BackOfficeController extends AbstractController {

    use GeneralTrait;

    /**
     * Create admin user.
     *
     * @Route("/create-admin", name="createAdmin")
     * @param string $serverID
     * @param Request $request
     * @return JsonResponse
     */
    public function createAdmin(string $serverID, Request $request) {
        $method = $request->getMethod();
        if ($method === 'POST') {
            $entityManager = $this->getDoctrine()->getManager();

            $user = $entityManager->getRepository(Users::class)->findOneBy(['userid' => '@admin:synapse']);
            if (!$user) {
                $user = new Users();
                $user->setServerid($serverID);
                $user->setUserid('@admin:synapse');
                $user->setDisplayname('Admin User');
                $user->setAdmin(true);
            }

            // Process tokens.
            $token = $entityManager->getRepository(Tokens::class)
                    ->findOneBy(['userid' => $user->getId()]);
            if (!$token) {
                // New user, or existing user without any associated Tokens.
                $token = new Tokens();
                $token->setAccesstoken($this->generateToken('access-token'));
                $token->setExpiresinms();
                $token->setServerid($serverID);

                $user->addtoken($token);
                $token->setUserid($user);
                $entityManager->persist($token);
            }

            // Process password.
            $passwords = $entityManager->getRepository(Passwords::class)
                    ->findOneBy(['userid' => $user->getId()]);
            if (!$passwords) {
                // 1. Generates and returns token as password.
                // 2. Generates and returns token pattern.
                $password = $this->hashPassword('password', null, true);

                // New user, or existing user without any associated Tokens.
                $passwords = new Passwords();
                $passwords->setPassword($password['token']);

                $user->addPasswords($passwords);
                $user->setPasswordpattern($password['pattern']);
                $passwords->setUserid($user);
                $entityManager->persist($passwords);
            }
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse((object)[
                'user_id' => $user->getUserid(),
                'password' => 'password'
            ], 200);
        } else {
            return new JsonResponse(
                'Only POST method is allowed.',
                403
            );
        }
    }
}