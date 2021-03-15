<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile", name="profile")
     */
    public function index(UserRepository $userRepository): Response
    {
        $user = $userRepository->find($this->getUser());
        $test = $this->getUser();
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'user'=>$test
        ]);
    }
}
